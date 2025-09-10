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
use Joomla\CMS\Component\ComponentHelper;

class Release2_9_3Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			// Set 2FA mandatory for Super Users and Admins
			$query->clear()
				->select('extension_id,params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('emundus'));
			$this->db->setQuery($query);
			$plugin = $this->db->loadObject();

			if(!empty($plugin->extension_id))
			{
				$params = json_decode($plugin->params, true);

				if(empty($params['2faForceForProfiles']) || (count($params['2faForceForProfiles']) === 1 && empty($params['2faForceForProfiles'][0])))
				{
					$menus = ['adminmenu', 'coordinatormenu'];
					$query->clear()
						->select('id')
						->from($this->db->quoteName('#__emundus_setup_profiles'))
						->where($this->db->quoteName('menutype') . ' IN (' . implode(',', $this->db->quote($menus)) . ')');
					$this->db->setQuery($query);
					$profile_ids = $this->db->loadColumn();

					$params['2faForceForProfiles'] = $profile_ids;

					$plugin->params = json_encode($params);
					$tasks[]        = $this->db->updateObject('#__extensions', $plugin, ['extension_id']);
				}
			}
			//

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}