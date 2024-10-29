<?php
/**
 * Fabrik: Package Installer Manifest Class
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @author      Henk
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
            private $app;

            public function __construct(AdministratorApplication $app)
            {
                $this->app = $app;
            }
            public function install(InstallerAdapter $parent): bool
            {
                return true;
            }

            public function update(InstallerAdapter $parent): bool
            {
                return true;
            }

            public function uninstall(InstallerAdapter $parent): bool
            {
                return true;
            }
			/**
			 * Run before installation or upgrade run
			 *
			 * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
			 *                         or install (standard install)
			 *                         or update (update)
			 * @param   object $parent installer object
			 *
			 * @return  void
			 */
			public function preflight(string $type, InstallerAdapter $parent): bool
			{
				$jversion = new Version();

				if (version_compare($jversion->getShortVersion(), '4.2', '<')) {
					throw new RuntimeException('Fabrik can not be installed on versions of Joomla older than 4.2');
					return false;
				}
				if (version_compare($jversion->getShortVersion(), '6.0', '>')) {
					throw new RuntimeException('Fabrik can not yet be installed on Joomla 6');
					return false;
				}

				if (version_compare(phpversion(), '8.1', '<')) {
					throw new RuntimeException('Fabrik can not yet be installed on versions of PHP less than 8.1, your version is '.phpversion());
					return false;
				}

				/* If we are upgrading from F3 to F4 we want to do some cleanup */
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);
				$query->select('*')->from('#__extensions')->where('element="com_fabrik"');
				$row = $db->setQuery($query)->loadObject();

				if (!empty($row)) { 
					$manifest_cache = json_decode($row->manifest_cache);
					/* There never was a 3.11 so this will match all versions of 3 but no versions of 4 */
					if (!empty($manifest_cache)) {
						if (version_compare($manifest_cache->version, '3.11', '<')) {
							// Remove fabrik library if it exists, it is rebuilt during the build process
							$path = JPATH_LIBRARIES.'/fabrik';		
							if(Folder::exists($path)) Folder::delete($path);
							// Remove old J!3 FormField overrides if exist (new will be re-installed)
							$path = JPATH_ADMINISTRATOR.'/components/com_fabrik/classes';		
							if(Folder::exists($path)) Folder::delete($path);
							// Remove old J!3 helpers if exist, but keep legacy/aliases (will be re-installed)
							$path = JPATH_ROOT.'/components/com_fabrik/helpers';		
							if(Folder::exists($path)) Folder::delete($path);
							$query->clear()->select('version_id')->from("#__schemas")->where("extension_id=".$row->extension_id);
							$dbVersion = $db->setQuery($query)->loadResult();
							if (empty($dbVersion) || version_compare($dbVersion, '3.10', '<')) {
								$query->clear()->update("#__schemas")->set("version_id='3.10'")->where("extension_id=".$row->extension_id);
								$db->setQuery($query);
								$db->execute();
							}
							/* Remove all old F3 update sql files */
							/** NOTE: This is being done on all installations right now. 
							 * Once 4.0 is released this codeblock should be moved to the above codeblock 
							 * and only processed on an actual upgrade 
							**/
							/* Remove the old 2.0-3.0 update file if it exists */
							$file = JPATH_ADMINISTRATOR.'/components/com_fabrik/sql/2.x-3.0.sql';
							if (File::exists($file)) File::delete($file);
							$directory = JPATH_ROOT.'/administrator/components/com_fabrik/sql/updates/mysql/';
							$files = scandir($directory);
							if (!empty($files)) {
								$files = array_diff($files, ['..', '.']);
								foreach ($files as $file) {
								  	$version = pathinfo($file, PATHINFO_FILENAME);
								    File::delete($directory.$file);
								}
							}
							/* Remove the pre packages fabrik package */
							try {
								$query->clear()->delete()->from('#__extensions')->where("type='package'")->where("element='pkg_fabrik'");
								$db->setQuery($query);
								$db->execute();
							} catch (Exception $e) {
								Factory::getApplication()->enqueueMessage($e->getMessage());
							}
							// Remove F3 update site
							$where = "location LIKE '%update/component/com_fabrik%' OR location = 'http://fabrikar.com/update/fabrik/package_list.xml'";
							$query->clear()->delete('#__update_sites')->where($where);
							$db->setQuery($query)->execute();
							// Remove previous skurvish update sites
							$where = "location like('%http://skurvishenterprises.com/fabrik/update%')";
							$query->clear()->delete('#__update_sites')->where($where);
							$db->setQuery($query)->execute();
						} elseif (version_compare($manifest_cache->version, '4.2', '<')) {
							/* We have some cleanup to do if we are installing on anything less than 4.2 */
							$query->clear()->select('*')->from('#__extensions')->where('element="com_fabrik"');
							$component = $db->setQuery($query)->loadObject();

							if (!empty($component)) { 
								$manifest_cache = json_decode($component->manifest_cache);
								if (empty($manifest_cache) || version_compare($manifest_cache->version, '4.2', '>=')) {
									/* Our work is done */
									return true;
								}
							} else {
								/* No previous installation */
								return true;
							}

							/* Now check old update sites */
							$query
								->clear()
								->select('*')
								->from('#__update_sites')
								->where('type in ("package", "extension")')
								->where('name like "%Fabrik%"')
								->order('update_site_id');
							$updateSites = $db->setQuery($query)->loadObjectList();
							$purgeCnt = 0;

							/* Before we go any further, let's check for duplicates */
							$siteTypes = ["Fabrik Base" => [], "Fabrik" => []];
							foreach ($siteTypes as $type => $sites) { 
								$siteTypes[$type] = array_filter($updateSites, function ($v) use ($type) { 
									return $v->name == $type;
								});

								/* We only wish to keep one of each Package - assuming we have one */
								if (count($siteTypes[$type])) {
									/* Lets keep the most recent base package with a dlid key */
									$one2keep = null;
									$one2keep = array_filter($siteTypes[$type], function ($v) use ($one2keep) {
										if (!empty($one2keep)) return false;
										if (strlen($v->extra_query) > 4) {
											$one2keep = [$v];
											return true;
										}
										return false;
									});
									If (empty($one2keep)) {
										/* We did not find one with a dlid, so keep the first */
										$one2keep = [array_pop($siteTypes[$type])];
									}
									/* Purge any others  */ 
									if (!empty($one2keep)) {
										$one2keep = $one2keep[0];
										foreach ($siteTypes[$type] as $idx => $site) {
											if ($site->update_site_id == $one2keep->update_site_id) continue;
											$query->clear()->delete('#__update_sites')->where("update_site_id=".$site->update_site_id);
											$db->setQuery($query)->execute();
											$purgeCnt++;
											unset($siteTypes[$type][$idx]);
										}
									} 
									$siteTypes[$type] = [$one2keep];
								}
							}

							/* If at this point, if we do not have a Fabrik Base, check for an older Fabrik update site */
							if (empty($siteTypes["Fabrik Base"]) && !empty($siteTypes["Fabrik"])) {
								/* Let's change its name to Fabrik Base */
								$site = array_pop($siteTypes["Fabrik"]);
								$site->name = "Fabrik Base";
							} else {
								$site = array_pop($siteTypes["Fabrik Base"]);
								/* we have a Fabrik Base, so purge an others */
								foreach ($siteTypes[$type] as $site) {
									$query
										->clear()
										->delete('#__update_sites')
										->where('update_site_id = :update_site_id')
										->bind(':update_site_id', $site->update_site_id);
									$db->setQuery($query)->execute();
									$purgeCnt++;
								}
							}

							/* And make sure the location url isn't using &amp;'s */
							$site->location = str_replace("amp;", "", $site->location);
							/* Update the update site */
							$db->updateObject('#__update_sites', $site, 'update_site_id');

							/* Purge any older Fabrik3.1 sites */
							$query
								->clear()
								->delete('#__update_sites')
								->where('name = "Fabrik31"');
							$db->setQuery($query)->execute();

							/* Now let's get rid of any older Fabrik Packages from the extensions table */
							$query
								->clear()
								->select('*')
								->from('#__extensions')
								->where('type = "package"')
								->where('name like "%Fabrik%"')
								->order('extension_id desc');
							$db->setQuery($query);
							$extensions = $db->loadObjectList();

							/* We don't want anything that is not Fabrik one of the keepers */
							$allowedExtensions = ["Fabrik Base Package" => [], "Fabrik Libraries Package" => [], "Others" => []];
							foreach($extensions as $extension) { 
								if (array_key_exists($extension->name, $allowedExtensions)) {
									$allowedExtensions[$extension->name][] = $extension;
								} else {
									$allowedExtensions["Others"][] = $extension;
								}
							}

							/* If there are more than one of the primaries, delete the extra */
							foreach($allowedExtensions as $extType => $installedExtensions) {
								if ($extType != "Others" && count($installedExtensions)) {
									// Pop off the top one, we keep it */
									$extension = array_pop($installedExtensions); 
									/* Let's make sure the changelog url doesn't have any &amp;'s */
									if (strpos($extension->changelogurl, '&amp;') !== false) {
										/* Update it */
										$extension->changelogurl = str_replace('amp;', '', $extension->changelogurl);
										$db->updateObject('#__extensions', $extension, 'extension_id');
									}
								}
								foreach($installedExtensions as $installedExtension) {
									$query
										->clear()
										->delete('#__extensions')
										->where("extension_id = :extension_id")
										->bind(":extension_id", $installedExtension->extension_id);
									$db->setQuery($query)->execute();
									$purgeCnt++;
								}
							}

							if ($purgeCnt) {
								Factory::getApplication()->enqueueMessage("All old Fabrik update sites have been deleted.");
							}

						}
					}
				}


				// Remove some update sites that got left over
				$where = "name like '%Fabrik31%' or location like '%skurvishenterprises.com/fabrik%'";
				$query->clear()->delete('#__update_sites')->where($where);
				$db->setQuery($query)->execute();

				/* Also remove any manifests that the admin installer is keeping */
				$folders = Folder::Folders(JPATH_ADMINISTRATOR . "/manifests/packages/", 'fabrik', false, true, ['fabrikbase', 'fabriklibraries']);
				foreach ($folders as $folder) {
					Folder::delete($folder);
				}
				$files = FOLDER::files(JPATH_ADMINISTRATOR . "/manifests/packages/", 'fabrik', false, true, ['pkg_fabrikbase.xml', 'pkg_fabriklibraries.xml']);
				foreach ($files as $file) {
					File::delete($file);
				}

				if (count($folders) || count($files)) {
					Factory::getApplication()->enqueueMessage("Old installation manifest files removed.");

				}
				if ($type == 'uninstall') {
					/* Check if any of the other fabrik packages are installed, and if so advise that they must be uninstalled first */
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->getQuery(true);
					$query->clear()->select("count(*)")->from("#__extensions")->where("type='package'")->where("element like('pkg_fabrik_%')")->where("element != 'pkg_fabrikbase'");
					$db->setQuery($query);
					if ($db->loadResult() != 0) {
						throw new RuntimeException('Fabrik core cannot be uninstalled when other Fabrik packages are still installed.');
						return false;
					}
				}
				
				return true;
			}

			public function postFlight(string $type, InstallerAdapter $parent): bool
			{
				if ($type !== 'uninstall') {
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->getQuery(true);
					/* Run through all the installed plugins and enable them */
					foreach($parent->manifest->files->file as $file) {
						list($prefix, $fabrik, $type, $element) = array_pad(explode("_", $file), 4, '');
						switch ($prefix) {
							case 'plg':
								if ($type == 'system') {
									$query->clear()->update("#__extensions")->set("enabled=1")
											->where("type='plugin'")->where("folder='system'")->where("element='$fabrik'");
								} else {
									$query->clear()->update("#__extensions")->set("enabled=1")
											->where("type='plugin'")->where("folder='fabrik_$type'")->where("element='$element'");
								}
								break;
							case 'com':
								$query->clear()->update("#__extensions")->set("enabled=1")
										->where("type='component'")->where("name='com_fabrik'");
								break;
							case 'lib':
								$query->clear()->update("#__extensions")->set("enabled=1")
										->where("type='library'")->where("element='$fabrik/$type'");
								break;
							case 'mod':
								if ($type != 'admin') {
									$query->clear()->update("#__extensions")->set("enabled=1")
											->where("name='mod_fabrik_$type'");
								} else {
									$query->clear()->update("#__extensions")->set("enabled=1")
											->where("type='module'")->where("type='mod_fabrik_$element'");
								}
								break;
							default:
								continue 2;
						}
						$db->setQuery($query);
						$db->execute();
					}
					Factory::getApplication()->enqueueMessage("All Core plugins have been enabled");
				}
				return true;
			}
			private function purgePackageAnditsChilden($package) {
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);
				$query
					->clear()
					->select('*')
					->from('#__extensions')
					->where('package_id = :extension_id')
					->bind(':extension_id', $package->extension_id);
				$db->setQuery($query);
				$packageParts = $db->loadObjectList();
				/* Remove everything that points to it */
				foreach ($packageParts as $part)  {
					$query
						->clear()
						->delete()
						->from('#__extensions')
						->where('extension_id = :extension_id')
						->bind(':extension_id', $part->extension_id);
					$db->setQuery($query);
					$db->execute();
				}
				/* Now remove the package itself */
				$query
					->clear()
					->delete()
					->from('#__extensions')
					->where('extension_id = :extension_id')
					->bind(':extension_id', $package->extension_id);
				$db->setQuery($query);
				$db->execute();
			}
		});
	}
};
