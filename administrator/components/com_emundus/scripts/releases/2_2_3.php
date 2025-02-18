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

class Release2_2_3Installer extends ReleaseInstaller
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
			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES', 'Langues du programme');
			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES', 'Program languages', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES_INTRO', 'Définissez les langues de dépôt autorisées pour les déposants. Si ils ne sont pas dans la bonne langue de dépôt, ils verront un message apparaitre les invitants à basculer de langue. Ce paramètre sera hérité sur toutes les campagnes de ce programme.');
			EmundusHelperUpdate::insertTranslationsTag('PROGRAM_LANGUAGES_INTRO', 'Define the deposit languages allowed for depositors. If they\'re not in the right language, they\'ll see a message prompting them to switch languages. This setting will be inherited by all campaigns in this program.', 'override', 0, null, null, 'en-GB');

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