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

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;

class Pkg_FabrikInstallerScript
{
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
	public function preflight($type, $parent)
	{
		$jversion = new Version();

		if (version_compare($jversion->getShortVersion(), '4.2', '<')) {
			throw new RuntimeException('Fabrik can not be installed on versions of Joomla older than 4.2');
			return false;
		}
		if (version_compare($jversion->getShortVersion(), '5.0', '>')) {
			throw new RuntimeException('Fabrik can not yet be installed on Joomla 5');
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
		$row = $db->loadObject();

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
				}
			}
		}

		if ($type == 'uninstall') {
			/* Check if any of the other fabrik packages are installed, and if so advise that they must be uninstalled first */
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);
			$query->clear()->select("count(*)")->from("#__extensions")->where("type='package'")->where("element like('pkg_fabrik_%')")->where("element != 'pkg_fabrik_core'");
			$db->setQuery($query);
			if ($db->loadResult() != 0) {
				throw new RuntimeException('Fabrik core cannot be uninstalled when other Fabrik packages are still installed.');
				return false;
			}
		}
		
		return true;
	}

	public function postFlight($type, $parent) {
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
	}
}
