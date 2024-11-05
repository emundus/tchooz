<?php
/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;

class Release2_0_3Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			// Update Gantry manifest_cache version
			$gantry_components = [
				'plg_system_gantry5',
				'plg_quickicon_gantry5',
				'plg_gantry5_preset',
				'mod_gantry5_particle',
				'gantry5_nucleus',
				'com_gantry5',
				'pkg_gantry5',
				'Gantry 5 Framework',
				'g5_helium'
			];

			$query->select('extension_id,manifest_cache')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('name') . ' IN (' . implode(',', $this->db->quote($gantry_components)) . ')');
			$this->db->setQuery($query);
			$extensions = $this->db->loadObjectList();

			foreach ($extensions as $extension)
			{
				$manifest_cache = json_decode($extension->manifest_cache, true);
				$manifest_cache['version'] = '5.5.19';
				$extension->manifest_cache = json_encode($manifest_cache);

				if(!$this->db->updateObject('#__extensions', $extension, 'extension_id')) {
					EmundusHelperUpdate::displayMessage('Error updating Gantry manifest_cache');
				}
			}
			//

			// Update French language pack
			$language_elements = ['fr-FR','pkg_fr-FR'];

			$query->clear()
				->select('extension_id,manifest_cache')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' IN (' . implode(',', $this->db->quote($language_elements)) . ')');
			$this->db->setQuery($query);
			$extensions = $this->db->loadObjectList();

			foreach ($extensions as $extension)
			{
				$manifest_cache = json_decode($extension->manifest_cache, true);
				$manifest_cache['version'] = '5.2.0.1';
				$extension->manifest_cache = json_encode($manifest_cache);

				if(!$this->db->updateObject('#__extensions', $extension, 'extension_id')) {
					EmundusHelperUpdate::displayMessage('Error updating French language pack');
				}
			}
			//

			// Update SCP manifest_cache version
			$scp_elements = [
				'com_securitycheckpro',
				'mod_scpadmin_quickicons',
				'securitycheckpro',
				'securitycheckpro_cron'
			];

			$query->clear()
				->select('extension_id,manifest_cache')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' IN (' . implode(',', $this->db->quote($scp_elements)) . ')');
			$this->db->setQuery($query);
			$extensions = $this->db->loadObjectList();

			foreach ($extensions as $extension)
			{
				$manifest_cache = json_decode($extension->manifest_cache, true);
				$manifest_cache['version'] = '4.2.1';
				$extension->manifest_cache = json_encode($manifest_cache);

				if(!$this->db->updateObject('#__extensions', $extension, 'extension_id')) {
					EmundusHelperUpdate::displayMessage('Error updating SCP manifest_cache');
				}
			}
			//

			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();

			return $result;
		}


		return $result;
	}
}