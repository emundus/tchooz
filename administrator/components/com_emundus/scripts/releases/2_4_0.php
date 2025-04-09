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
use stdClass;

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

		$tasks = [];

		try
		{
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

			// Override translations
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_MFA_ADD_AUTHENTICATOR_OF_TYPE', 'Activer');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_MFA_ADD_AUTHENTICATOR_OF_TYPE', 'Enable', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('PLG_MULTIFACTORAUTH_TOTP_METHOD_TITLE', 'Code par application');
			EmundusHelperUpdate::insertTranslationsTag('PLG_MULTIFACTORAUTH_TOTP_METHOD_TITLE', 'Code by application', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_MFA_RESEND_EMAIL', 'Renvoyer le code');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_MFA_RESEND_EMAIL', 'Resend code', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_MFA_BACKUPCODES_ONLY_ONCE', 'Les codes de secours ne peuvent être affichés qu\'une seule fois. Veuillez les enregistrer dans un endroit sûr.');
			EmundusHelperUpdate::insertTranslationsTag('COM_USERS_MFA_BACKUPCODES_ONLY_ONCE', 'Backup codes can only be displayed once. Please save them in a safe place.', 'override', 0, null, null, 'en-GB');

			// By default enable email and TOTP methods
			$tasks[] = EmundusHelperUpdate::enableEmundusPlugins('totp', 'multifactorauth');
			$tasks[] = EmundusHelperUpdate::enableEmundusPlugins('email', 'multifactorauth');

			// Create menu to access to 2fa methods
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('mainmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('2fa-methods') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_users&view=methods'));
			$this->db->setQuery($query);
			$multifactor_methods_menu = $this->db->loadResult();

			if(empty($multifactor_methods_menu))
			{
				$data              = [
					'menutype'          => 'mainmenu',
					'title'             => 'Options d\'authentification multifacteurs',
					'alias'             => '2fa-methods',
					'path'              => '2fa-methods',
					'link'              => 'index.php?option=com_users&view=methods',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_users')->id,
					'template_style_id' => 0,
					'params'            => [],
				];
				$multifactor_methods_menu = EmundusHelperUpdate::addJoomlaMenu($data);
				EmundusHelperUpdate::insertFalangTranslation(1, $multifactor_methods_menu['id'], 'menu', 'title', 'Multi-factor authentication options');
			}

			// Create users action menu to disabled 2fa
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('actions-users'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('disable-mfa') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&controller=users&task=disablemfa&user_id={applicant_id}'));
			$this->db->setQuery($query);
			$multifactor_disable_action_menu = $this->db->loadResult();

			if(empty($multifactor_disable_action_menu))
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('actions-users'))
					->where($this->db->quoteName('path') . ' LIKE ' . $this->db->quote('action-users'))
					->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('heading'));
				$this->db->setQuery($query);
				$parent_menu_id = $this->db->loadResult();

				if(!empty($parent_menu_id))
				{
					$data                            = [
						'menutype'          => 'actions-users',
						'title'             => 'Désactiver l\'authentification multifacteurs',
						'alias'             => 'disable-mfa',
						'path'              => 'disable-mfa',
						'link'              => 'index.php?option=com_emundus&controller=users&task=disablemfa&user_id={applicant_id}',
						'type'              => 'url',
						'component_id'      => 0,
						'template_style_id' => 0,
						'params'            => [],
						'note'              => '12|u|1|disablemfa'
					];
					$multifactor_disable_action_menu = EmundusHelperUpdate::addJoomlaMenu($data, $parent_menu_id);
					EmundusHelperUpdate::insertFalangTranslation(1, $multifactor_disable_action_menu['id'], 'menu', 'title', 'Disable multi-factor authentication');
				}
			}

			$query->clear()
				->select('extension_id,params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('com_users'))
				->andWhere($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$component = $this->db->loadObject();

			if(!empty($component)) {
				$params = json_decode($component->params);
				$params->frontend_userparams = 0;
				$component->params = json_encode($params);
				$this->db->updateObject('#__extensions', $component, 'extension_id');
			}
			// Disable some joomla plugins
			$tasks[] = EmundusHelperUpdate::disableEmundusPlugins('token');

			// Update delay of email plugin
			$query->clear()
				->select('extension_id, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('email'))
				->where($this->db->quoteName('folder') . ' LIKE ' . $this->db->quote('multifactorauth'));
			$this->db->setQuery($query);
			$plugin = $this->db->loadObject();

			if(!empty($plugin->extension_id))
			{
				if (empty($plugin->params))
				{
					$params                 = [];
					$params['timestep']     = 300;
					$params['force_enable'] = 0;
				}
				else
				{
					$params           = json_decode($plugin->params);
					$params->timestep = 300;
				}

				$plugin->params = json_encode($params);
				$this->db->updateObject('#__extensions', $plugin, 'extension_id');
			}

			// Add already_seen column to #__user_mfa
			$tasks[] = EmundusHelperUpdate::addColumn('#__user_mfa', 'already_seen', 'TINYINT', 1,0,0)['status'];

			// Update parameters of com_mails
			$query->clear()
				->select('extension_id, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('com_mails'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$component = $this->db->loadObject();

			if(!empty($component->extension_id)) {
				if(!empty($component->params)) {
					$params = json_decode($component->params);
				} else {
					$params = new stdClass();
				}

				$params->mail_style = 'html';
				$params->enable_mails_2fa = 1;
				$params->disable_htmllayout = 1;
				$params->mail_htmllayout = 'g5_helium:mailtemplate';
				$params->mail_logofile = '';

				$component->params = json_encode($params);
				$this->db->updateObject('#__extensions', $component, 'extension_id');
			}

			// Update parameters of com_users
			$query->clear()
				->select('extension_id, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('com_users'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$component = $this->db->loadObject();

			if(!empty($component->extension_id)) {
				$params = json_decode($component->params);
				$params->allowed_positions_frontend = ['header-a','footer-a'];

				$component->params = json_encode($params);
				$this->db->updateObject('#__extensions', $component, 'extension_id');
			}

			$query->clear()
				->select('id')
				->from('#__emundus_setup_actions')
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('edit_user_role'));
			$this->db->setQuery($query);
			$action_id = $this->db->loadResult();

			if (empty($action_id))
			{
				$insert_acl = [
					'name'     => 'edit_user_role',
					'label'    => 'COM_EMUNDUS_EDIT_USER_ROLE',
					'multi'    => 0,
					'c'        => 0,
					'r'        => 1,
					'u'        => 1,
					'd'        => 1,
					'ordering' => 40,
					'status'   => 1,
				];
				$insert_acl = (object) $insert_acl;

				if ($this->db->insertObject('#__emundus_setup_actions', $insert_acl))
				{
					$action_id = $this->db->insertId();
				}
			}

			$tasks[] = !empty($action_id);

			// add right to all users that have the right to edit user, to avoid changing what was already set
			if (!empty($action_id))
			{
				$query->clear()
					->select('DISTINCT acl.group_id')
					->from($this->db->quoteName('#__emundus_setup_actions','esa'))
					->leftJoin($this->db->quoteName('#__emundus_acl','acl').' ON '.$this->db->quoteName('acl.action_id').' = '.$this->db->quoteName('esa.id') . ' AND ('.$this->db->quoteName('acl.c').' = 1 OR '.$this->db->quoteName('acl.u').' = 1)')
					->where($this->db->quoteName('acl.action_id') . ' IN (12, 24, 20)')
					->andWhere('(acl.c = 1 OR acl.u = 1)');

				$this->db->setQuery($query);
				$group_ids = $this->db->loadColumn();

				foreach ($group_ids as $group_id)
				{
					$query->clear()
						->select('COUNT(*)')
						->from($this->db->quoteName('#__emundus_acl'))
						->where($this->db->quoteName('action_id') . ' = ' . $this->db->quote($action_id))
						->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($group_id));
					$this->db->setQuery($query);
					$count = $this->db->loadResult();

					if(empty($count))
					{
						$insert_acl_group = [
							'group_id'  => $group_id,
							'action_id' => $action_id,
							'c'         => 0,
							'r'         => 1,
							'u'         => 1,
							'd'         => 1,
						];
						$insert_acl_group = (object) $insert_acl_group;
						$this->db->insertObject('#__emundus_acl', $insert_acl_group);
					}
				}
			}

			// Add lock column to jos_emundus_setup_workflow_steps
			EmundusHelperUpdate::addColumn('jos_emundus_setup_workflows_steps', 'lock', 'TINYINT(1) DEFAULT 0');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ONBOARD_EVALUATION_LOCK_TITLE','Soumettre l\'évaluation');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ONBOARD_EVALUATION_LOCK_TITLE','Submit evaluation', 'override',0,null,null,'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ONBOARD_EVALUATION_LOCK_TEXT','Une fois votre évaluation soumise, vous ne pourrez plus la modifier. Veuillez vérifier attentivement vos réponses avant de valider.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ONBOARD_EVALUATION_LOCK_TEXT','Once you have submitted your evaluation, you will not be able to change it. Please check your answers carefully before validating.', 'override',0,null,null,'en-GB');


			// if config sms not exist
			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('sms'));

			$this->db->setQuery($query);
			$config = $this->db->loadObject();

			if (empty($config->namekey))
			{
				$params = '{"enabled":0,"displayed":0,"params":{"encoding":"UCS-2", "service": "ovh"}}';
				$query->clear()
					->insert($this->db->quoteName('#__emundus_setup_config'))
					->columns($this->db->quoteName('namekey') . ', ' . $this->db->quoteName('value'))
					->values($this->db->quote('sms') . ', ' . $this->db->quote($params));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			EmundusHelperUpdate::addColumn('#__emundus_setup_emails_trigger', 'sms_id', 'INT(11) DEFAULT NULL');

			$alter_query = "ALTER TABLE jos_emundus_setup_emails_trigger MODIFY email_id INT null";
			$this->db->setQuery($alter_query);
			$this->db->execute();

			EmundusHelperUpdate::addColumn('#__emundus_setup_emails_trigger', 'sms_id', 'INT(11) DEFAULT NULL');

			// Check if the foreign key already exists
			$check_foreign_query = "SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'jos_emundus_setup_emails_trigger' AND CONSTRAINT_NAME = 'jos_emundus_setup_emails_trigger_sms_id__fk'";
			$this->db->setQuery($check_foreign_query);
			$exists = $this->db->loadResult();

			if(empty($exists))
			{
				$foreign_key_query = "alter table jos_emundus_setup_emails_trigger add constraint jos_emundus_setup_emails_trigger_sms_id__fk foreign key (sms_id) references jos_emundus_setup_sms (id) on update cascade on delete set null;";
				$this->db->setQuery($foreign_key_query);
				$this->db->execute();
			}

			// Create menu to edit trigger
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('edit-trigger') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=emails&layout=triggeredit'));
			$this->db->setQuery($query);
			$trigger_menu = $this->db->loadResult();

			if(empty($trigger_menu))
			{
				$data              = [
					'menutype'          => 'onboardingmenu',
					'title'             => 'Ajouter/Modifier un déclencheur',
					'alias'             => 'edit-trigger',
					'path'              => 'edit-trigger',
					'link'              => 'index.php?option=com_emundus&view=emails&layout=triggeredit',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [
						'menu_show' => 0,
					],
				];
				$trigger_menu = EmundusHelperUpdate::addJoomlaMenu($data);
				EmundusHelperUpdate::insertFalangTranslation(1, $trigger_menu['id'], 'menu', 'title', 'Add/Edit a trigger');
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