<?php

namespace scripts;

class Release2_4_4Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/translations.php');
			$m_translations = new \EmundusModelTranslations();

			$tasks[] = !empty($m_translations->updateTranslation('COM_USERS_LOGIN_WITH', 'Se connecter avec %s', 'fr-FR', 'override', '', 0, '', 1));
			$tasks[] = !empty($m_translations->updateTranslation('COM_USERS_LOGIN_WITH', 'Sign in with %s', 'en-GB','override', '', 0, '', 1));

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}
}