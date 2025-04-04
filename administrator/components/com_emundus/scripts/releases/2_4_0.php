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
use Symfony\Component\Yaml\Yaml;

class Release2_4_0Installer extends ReleaseInstaller
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
			$tasks = [];

			// Voting feature
			$tasks['vote'] = false;

			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/src/VotingInstall.php';
			$vote_install   = new \scripts\src\VotingInstall();
			$vote_installed = $vote_install->install();
			if ($vote_installed['status'])
			{
				$tasks['vote'] = true;
				EmundusHelperUpdate::displayMessage('La fonctionnalité de vote a été installée avec succès', 'success');
			}
			else
			{
				EmundusHelperUpdate::displayMessage($vote_installed['message'], 'error');
			}
			//

			// Update js of password element
			$query->clear()
				->update($this->db->quoteName('#__fabrik_jsactions','fj'))
				->leftJoin($this->db->quoteName('#__fabrik_elements','fe').' ON '.$this->db->quoteName('fe.id').' = '.$this->db->quoteName('fj.element_id'))
				->set('fj.code = ' . $this->db->quote('togglePasswordVisibility();'))
				->where($this->db->quoteName('fe.name').' = '.$this->db->quote('password'))
				->where($this->db->quoteName('fj.action').' = '.$this->db->quote('load'));
			$this->db->setQuery($query);
			$this->db->execute();
			//

			$manifest_cache = '{"name":"plg_fabrik_element_average","type":"plugin","creationDate":"2024-10-18","author":"eMundus","copyright":"Copyright (C) 2005-2024 eMundus - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"https:\/\/www.emundus.fr","version":"2.3.0","description":"PLG_ELEMENT_AVERAGE_DESCRIPTION","group":"","changelogurl":"","filename":"average"}';
			EmundusHelperUpdate::installExtension('plg_fabrik_element_average', 'average', $manifest_cache, 'plugin', 1, 'fabrik_element');
			$tasks[] = EmundusHelperUpdate::enableEmundusPlugins('average', 'fabrik_element');

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return $result;
		}


		return $result;
	}
}