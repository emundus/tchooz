<?php

namespace scripts;

use EmundusHelperUpdate;

class Release2_5_3Installer extends ReleaseInstaller
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
			EmundusHelperUpdate::insertTranslationsTag('EXPERT_INFORMATIONS_DOSS', 'Informations du dossier');
			EmundusHelperUpdate::insertTranslationsTag('EXPERT_INFORMATIONS_DOSS', 'Application file information', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('ACCEPT_EXPERT_REQUEST', 'Acceptez-vous l\'expertise de ce dossier ?');
			EmundusHelperUpdate::insertTranslationsTag('ACCEPT_EXPERT_REQUEST', 'Do you accept the expert appraisal of this application file?', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('SETUP_FORM_EXPERTS', 'Configuration des experts');
			EmundusHelperUpdate::insertTranslationsTag('SETUP_FORM_EXPERTS', 'Experts setup', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('SETUP_FORM_EXPERTS_CAMPAIGN', 'Campagne');
			EmundusHelperUpdate::insertTranslationsTag('SETUP_FORM_EXPERTS_CAMPAIGN', 'Campaign', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('SETUP_FORM_EXPERTS_ELEMENTS', 'Informations du dossier à afficher');
			EmundusHelperUpdate::insertTranslationsTag('SETUP_FORM_EXPERTS_ELEMENTS', 'Application file information to be displayed', 'override', 0, null, null, 'en-GB');

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