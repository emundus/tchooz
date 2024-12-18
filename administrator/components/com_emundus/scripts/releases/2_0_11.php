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

class Release2_0_11Installer extends ReleaseInstaller
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
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_LOGIN_WITH', 'Se connecter avec');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_LOGIN_WITH', 'Sign in with', 'override', 0, null, null, 'en-GB');

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