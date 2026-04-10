<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Factories\Language\LanguageFactory;

class Release2_19_1Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$automated_user_id = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);
			LanguageFactory::translate('PROFILE_NAME', [
				'fr' => 'Nom du rôle',
				'en' => 'Role name'
			], '', 0, '', $automated_user_id);
			LanguageFactory::translate('COM_EMUNDUS_SETUP_PROFILE_DISPLAY_DESCRIPTION', [
				'fr' => 'Afficher cette description sur le tableau de bord des utilisateurs ayant ce rôle.',
				'en' => 'Display this description on the dashboard of users with this role.'
			], '', 0, '', $automated_user_id);
			LanguageFactory::translate('IS_APPLICANT', [
				'fr' => 'Rôle pour un déposant ?',
				'en' => 'Role for an applicant?'
			], '', 0, '', $automated_user_id);
			LanguageFactory::translate('SETUP_PROFILE', [
				'fr' => 'Paramétrage d\'un rôle',
				'en' => 'Role setup'
			], '', 0, '', $automated_user_id);
			LanguageFactory::translate('TABLE_SETUP_PROFILES', [
				'fr' => 'Liste des rôles',
				'en' => 'Role list'
			], '', 0, '', $automated_user_id);

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
