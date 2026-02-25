<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use scripts\ReleaseInstaller;
use Tchooz\Repositories\Language\LanguageRepository;

class Release2_15_0Installer extends ReleaseInstaller
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
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'campaign', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'program', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'email', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'form', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'workflow', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'event', published: 1);

			$createdByColumn = new \EmundusTableColumn('created_by', \EmundusColumnTypeEnum::INT, 11, true);
			$this->tasks[]   = \EmundusHelperUpdate::addColumn('#__emundus_setup_emails', $createdByColumn->getName(), $createdByColumn->getType()->value, $createdByColumn->getLength(), ($createdByColumn->isNullable() ? 1 : 0));


			$query->select('id')
				->from($this->db->quoteName('#__viewlevels'))
				->where($this->db->quoteName('title') . ' = ' . $this->db->quote('Partner'));
			$this->db->setQuery($query);
			$partnerLevel = $this->db->loadResult();

			if (!empty($partnerLevel))
			{
				$query->clear()
					->select('id, params, access')
					->from($this->db->quoteName('#__fabrik_lists'))
					->where($this->db->quoteName('label') . ' = ' . $this->db->quote('TABLE_SETUP_PROGRAMS'));
				$this->db->setQuery($query);
				$programsList = $this->db->loadObject();
				if (!empty($programsList))
				{
					$params                        = json_decode($programsList->params, true);
					$params['allow_view_details']  = $partnerLevel;
					$params['allow_edit_details']  = $partnerLevel;
					$params['allow_add']           = $partnerLevel;
					$params['allow_delete']        = $partnerLevel;
					$programsList->access          = $partnerLevel;

					$programsList->params = json_encode($params);
					$this->tasks[]        = $this->db->updateObject('#__fabrik_lists', $programsList, 'id');
				}
			}

			$addedColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_form_rules_js_conditions', 'params', 'JSON' );
			$this->tasks[] = $addedColumn['status'];
			if (!$addedColumn['status'])
			{
				$result['message'] .= $addedColumn['message'];
			}

			$installed = \EmundusHelperUpdate::installExtension('plg_fabrik_element_orderlist', 'orderlist', null, 'plugin', 1, 'fabrik_element');
			$this->tasks[] = $installed;
			if (!$installed)
			{
				$result['message'] .= 'Failed to install orderlist plugin. ';
			}

			$languageRepository = new LanguageRepository();
			$url          = 'https://emundus.atlassian.net/wiki/external/ZjQ2MDYzMzc3YjU3NDU1N2FhMWMxMzFkZWFhNTQ1Njc';
			$translations = [
				'en-GB' => 'Need help ?',
				'fr-FR' => "Besoin d'aide ?",
			];

			foreach ($translations as $langCode => $translation)
			{
				$html = "<p style='text-align: right;'><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$translation}</a></p>";
				$md5  = md5($html);

				$languageRepository->setLangCode($langCode);
				$tagLettersIntro = $languageRepository->getByTag('SETUP_LETTERS_INTRO');
				$tagLettersIntro->setOverride($html);
				$tagLettersIntro->setOriginalText($html);
				$tagLettersIntro->setOriginalMd5($md5);
				$tagLettersIntro->setOverrideMd5($md5);
				$tagLettersIntro->setModifiedDate(new \DateTime());

				$updated = $languageRepository->flush($tagLettersIntro);

				$this->tasks[] = $updated;
				if (!$updated)
				{
					$result['message'] .= "Failed to update SETUP_LETTERS_INTRO tage from jos_emundus_setup_languages table for {$langCode} language code. ";
				}
			}

			$query->clear()
				->select('path')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('liste-des-alias'));

			$this->db->setQuery($query);
			$menuLink = $this->db->loadResult();

			if ($menuLink)
			{
				$tagApplicant = '$APPLICANT_NAME';

				$htmlCodes = [
					'en-GB' => "<p>To make this mail dynamic, insert <a href='/en/{$menuLink}' target='_blank' rel='noopener noreferrer'>tags</a> into its construction to add custom information for each candidate. For example, the <em>{$tagApplicant}</em> tag will be replaced with your candidate's name. Hello <em>{$tagApplicant}</em> will become Hello Julian.</p><p>For wysiwyg fields and if the template is a Word file, you will need to insert the tag this way to preserve the content formatting: $&#123;textarea_&lt;ID_FIELD&gt;&#125;$&#123;ID_FIELD&#125;$&#123;textarea_&lt;ID_FIELD&gt;&#125;</p>",
					'fr-FR' => "<p>Pour rendre ce courrier dynamique, insérer des <a href='/{$menuLink}' target='_blank' rel='noopener noreferrer'>balises</a> dans sa construction afin d’ajouter des informations personnalisées pour chaque candidat. Par exemple, la balise <em>{$tagApplicant}</em> sera remplacée par le nom de votre candidat. Bonjour <em>{$tagApplicant}</em> deviendra Bonjour Julien.</p><p>Pour les champs de type wysiwyg et si le modèle est un fichier word, il faudra insérer la balise ainsi pour conserver la mise en forme du contenu : $&#123;textarea_&lt;ID_CHAMP&gt;&#125;$&#123;ID_CHAMP&#125;$&#123;textarea_&lt;ID_CHAMP&gt;&#125;</p>",
				];

				foreach ($htmlCodes as $langCode => $htmlCode)
				{
					$md5 = md5($htmlCode);

					$languageRepository->setLangCode($langCode);
					$tagLettersGroup179Intro = $languageRepository->getByTag('SETUP_LETTERS_GROUP_179_INTRO');
					$tagLettersGroup179Intro->setOverride($htmlCode);
					$tagLettersGroup179Intro->setOriginalText($htmlCode);
					$tagLettersGroup179Intro->setOriginalMd5($md5);
					$tagLettersGroup179Intro->setOverrideMd5($md5);
					$tagLettersGroup179Intro->setModifiedDate(new \DateTime());
					$updated       = $languageRepository->flush($tagLettersGroup179Intro);
					$this->tasks[] = $updated;

					if (!$updated)
					{
						$result['message'] .= "Failed to update tag SETUP_LETTERS_GROUP_179_INTRO from jos_emundus_setup_languages table for {$langCode} language code. ";
					}

					$translation = $languageRepository->getByTag('SETUP_LETTERS_GROUP_185_INTRO');

					if (!empty($translation))
					{
						$translation->setOverride($htmlCode);
						$translation->setOriginalText($htmlCode);
						$translation->setOriginalMd5($md5);
						$translation->setOverrideMd5($md5);
						$translation->setModifiedDate(new \DateTime());
						$updated       = $languageRepository->flush($translation);
						$this->tasks[] = $updated;

						if (!$updated)
						{
							$result['message'] .= "Failed to update tag SETUP_LETTERS_GROUP_185_INTRO from jos_emundus_languages table for {$langCode} language code. ";
						}
					}
				}
			}

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
