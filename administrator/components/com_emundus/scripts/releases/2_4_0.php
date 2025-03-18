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