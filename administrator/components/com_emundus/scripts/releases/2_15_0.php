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
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Actions\ActionRepository;

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

			LanguageFactory::deleteTranslation('COM_EMUNDUS_ACCESS_ACCESS_FILE');
			$accessFileUsersAction = \EmundusHelperUpdate::createNewAction(
				'access_file_users',
				['multi' => true, 'c' => 1, 'r' => 1, 'u' => 1, 'd' => 1],
				'',
				'',
				1
			);
			$this->tasks[]     = !empty($accessFileUsersAction);

			$actionRepository = new ActionRepository();
			$accessFileAction = $actionRepository->getByName('access_file');
			$accessFileUsersAction = $actionRepository->getByName('access_file_users');

			// Update note of action menu with the new action name
			$query->clear()
				->select('id, note')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=files&format=raw&layout=access&users={fnums}'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('actions'));
			$this->db->setQuery($query);
			$menuItem = $this->db->loadObject();
			if(!empty($menuItem))
			{
				$menuItem->note = $accessFileAction->getName().'|u|1,'.$accessFileUsersAction->getName().'|u|1';

				$this->tasks[] = $this->db->updateObject('#__menu', $menuItem, 'id');
			}

			$query->clear()
				->select('id, note')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=application&format=raw&layout=share'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('application'));
			$this->db->setQuery($query);
			$menuItem = $this->db->loadObject();
			if(!empty($menuItem))
			{
				$menuItem->note = $accessFileAction->getName().'|r,'.$accessFileUsersAction->getName().'|r';

				$this->tasks[] = $this->db->updateObject('#__menu', $menuItem, 'id');
			}

			// We have to give this right to all groups and users that already have the "access_file" action, otherwise they will lose access to the file access management page after the update
			$this->giveAccessFileUsersActionToExistingUsers($accessFileAction, $accessFileUsersAction);

			\EmundusHelperUpdate::installExtension('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT', 'microsoftoutlook365mailconnect', null, 'plugin', 0, 'system');
			\EmundusHelperUpdate::installExtension('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT', 'microsoftoutlook365mailconnect', null, 'plugin', 1, 'installer');
			\EmundusHelperUpdate::installExtension('PLG_SYSTEM_WEB357FRAMEWORK', 'web357framework', null, 'plugin', 1, 'system');
			\EmundusHelperUpdate::installExtension('PLG_SYSTEM_WEB357FRAMEWORK', 'web357framework', null, 'plugin', 1, 'ajax');


			$query->clear()
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('ms365'));
			$this->db->setQuery($query);
			$ms365MenuId = $this->db->loadResult();

			if(empty($ms365MenuId))
			{
				$ms365Menu = \EmundusHelperUpdate::addJoomlaMenu([
					'menutype'     => 'topmenu',
					'title'        => 'MS365',
					'link'         => '#',
					'path'         => 'ms365',
					'alias'        => 'ms365',
					'type'         => 'url',
					'component_id' => 0,
					'access'       => 1,
					'params'       => [
						'menu_show' => 0
					]
				]);
				$ms365MenuId = $ms365Menu['id'];
				$this->tasks[] = $ms365Menu['status'];
			}

			if(!empty($ms365MenuId))
			{
				$query->clear()
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('microsoft-outlook-365-mail-connect-authorize'));
				$this->db->setQuery($query);
				$msConnectMenuId = $this->db->loadResult();

				if(empty($msConnectMenuId))
				{
					$this->tasks[] = \EmundusHelperUpdate::addJoomlaMenu([
						'menutype'     => 'topmenu',
						'title'        => 'Microsoft Outlook 365 Mail Connect',
						'link'         => '#',
						'path'         => 'microsoft-outlook-365-mail-connect-authorize',
						'alias'        => 'microsoft-outlook-365-mail-connect-authorize',
						'type'         => 'url',
						'component_id' => 0,
						'access'       => 1,
						'params'       => [
							'menu_show' => 0
						]
					], $ms365MenuId)['status'];
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

	private function giveAccessFileUsersActionToExistingUsers(ActionEntity $accessFileAction, ActionEntity $accessFileUsersAction): void
	{
		$query = $this->db->createQuery();

		// ACL -> Groups
		$query->select('*')
			->from($this->db->quoteName('#__emundus_acl'))
			->where($this->db->quoteName('action_id') . ' = ' . $accessFileAction->getId());

		$this->db->setQuery($query);
		$accessFileActions = $this->db->loadObjectList();

		foreach ($accessFileActions as $action)
		{
			// Check if the user/group already has the "access_file_users" action, if yes skip to avoid duplicates
			$query->clear()
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_acl'))
				->where($this->db->quoteName('action_id') . ' = ' . $accessFileUsersAction->getId())
				->where($this->db->quoteName('group_id') . ' = ' . $action->group_id);
			$this->db->setQuery($query);
			$existingAccessFileUsersActionId = $this->db->loadResult();

			if(!$existingAccessFileUsersActionId)
			{
				// Update action id with the new "access_file_users" action id and insert new record
				unset($action->id);
				$action->action_id = $accessFileUsersAction->getId();
				$this->tasks[] = $this->db->insertObject('#__emundus_acl', $action);
			}
		}
		//

		// ACL -> Users
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_users_assoc'))
			->where($this->db->quoteName('action_id') . ' = ' . $accessFileAction->getId());

		$this->db->setQuery($query);
		$accessFileActions = $this->db->loadObjectList();

		foreach ($accessFileActions as $action)
		{
			// Check if the user/group already has the "access_file_users" action, if yes skip to avoid duplicates
			$query->clear()
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_users_assoc'))
				->where($this->db->quoteName('action_id') . ' = ' . $accessFileUsersAction->getId())
				->where($this->db->quoteName('user_id') . ' = ' . $action->user_id)
				->where($this->db->quoteName('fnum') . ' = ' . $action->fnum);
			$this->db->setQuery($query);
			$existingAccessFileUsersActionId = $this->db->loadResult();

			if(!$existingAccessFileUsersActionId)
			{
				// Update action id with the new "access_file_users" action id and insert new record
				unset($action->id);
				$action->action_id = $accessFileUsersAction->getId();
				$this->tasks[] = $this->db->insertObject('#__emundus_users_assoc', $action);
			}
		}
	}
}
