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
use Joomla\CMS\Factory;
use stdClass;
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
				if(empty($plugin->params)) {
					$params = [];
					$params['timestep'] = 300;
					$params['force_enable'] = 0;
				} else
				{
					$params           = json_decode($plugin->params);
					$params->timestep = 300;
				}

				$plugin->params = json_encode($params);
				$this->db->updateObject('#__extensions', $plugin, 'extension_id');
			}

			// Add already_seen column to #__user_mfa
			$tasks[] = EmundusHelperUpdate::addColumn('#__user_mfa', 'already_seen', 'TINYINT', 1,0,0);

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