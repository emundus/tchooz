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

class Release2_8_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name'   => 'token',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'ip',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'captcha',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'succeed',
					'type'   => 'TINYINT',
					'length' => 1,
					'null'   => 0,
					'default' => 0
				],
			];
			$created = EmundusHelperUpdate::createTable("jos_emundus_token_auth_attempts", $columns);
			$tasks[] = $created['status'];

			EmundusHelperUpdate::addColumn('#__emundus_users', 'token', 'VARCHAR', 255, 1, '');
			EmundusHelperUpdate::addColumn('#__emundus_users', 'is_anonym', 'TINYINT', 1, 1, '0');
			EmundusHelperUpdate::addColumn('#__emundus_users', 'token_expiration', 'DATETIME', 0, 1, '0000-00-00 00:00:00');

			$columns = [
				[
					'name'   => 'date_time',
					'type'   => 'DATETIME',
					'null'   => 0
				],
				[
					'name'   => 'token',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'email',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'captcha',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'campaign',
					'type'   => 'VARCHAR',
					'length' => 255,
					'default'=> '',
					'null'   => 1
				],
				[
					'name'   => 'ip',
					'type'   => 'VARCHAR',
					'length' => 255,
					'default'=> '',
					'null'   => 1
				]
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_anonym_registration', $columns);
			$tasks[] = $created['status'];

			// add anonymous in emundus_setup_config table if not exists
			$namekey = 'anonymous';
			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote($namekey));

			$this->db->setQuery($query);
			$anonymous = $this->db->loadObject();

			if (empty($anonymous) || empty($anonymous->namekey))
			{
				// add anonymous in emundus_setup_config table
				$default = ["enabled" => 0, "displayed" => 0, "params" => ['token_duration_validity' => 30, 'token_duration_validity_unit' => 'days']];
				$default = json_encode($default);

				$query->clear()
					->insert($this->db->quoteName('#__emundus_setup_config'))
					->columns($this->db->quoteName(['namekey', 'value', 'default']))
					->values($this->db->quote($namekey) . ', ' . $this->db->quote($default) . ', ' . $this->db->quote($default));

				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}

			$manifest = '{"name":"PLG_FABRIK_FORM_CONNECT_FROM_TOKEN","type":"plugin","creationDate":"April 2025","author":"eMundus","copyright":"Copyright (C) 2017-2025 eMundus.fr - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"2.8.0","description":"","group":"","changelogurl":"","filename":"connectfromtoken"}';
			EmundusHelperUpdate::installExtension('PLG_FABRIK_FORM_CONNECT_FROM_TOKEN', 'connectfromtoken', $manifest, 'plugins', 1, 'fabrik_form');
			EmundusHelperUpdate::enableEmundusPlugins('connectfromtoken', 'fabrik_form');

			$manifest = '{"name":"PLG_FABRIK_FORM_ANONYM_REGISTRATION","type":"plugin","creationDate":"April 2025","author":"eMundus","copyright":"Copyright (C) 2017-2025 eMundus.fr - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"2.8.0","description":"","group":"","changelogurl":"","filename":"anonymregistration"}';
			EmundusHelperUpdate::installExtension('PLG_FABRIK_FORM_ANONYM_REGISTRATION', 'anonymregistration', $manifest, 'plugins', 1, 'fabrik_form');
			EmundusHelperUpdate::enableEmundusPlugins('anonymregistration', 'fabrik_form');

			$tasks = $this->createTokenAuthLoginForm($tasks);
			$tasks = $this->createTokenRegistrationForm($tasks);

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN', 'Se connecter avec un jeton');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN', 'Login with Token', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_INTRO', 'Si vous avez un jeton d\'authentification, vous pouvez l\'utiliser pour vous connecter.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_INTRO', 'If you have an authentication token, you can use it to log in.', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_TOKEN_LABEL', 'Jeton d\'authentification');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_TOKEN_LABEL', 'Authentication Token', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_CAPTCHA_LABEL', 'Captcha');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_CAPTCHA_LABEL', 'Captcha', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION', 'Créer un dossier anonymement');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION', 'Create an anonymous file', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_INTRO', 'Vous pouvez créer un dossier anonymement en utilisant un jeton d\'enregistrement. Pensez à le conserver pour vous connecter plus tard.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_INTRO', 'You can create an anonymous file using a registration token. Remember to keep it for later login.', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_TOKEN_LABEL', 'Jeton d\'enregistrement');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_TOKEN_LABEL', 'Registration Token', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_EMAIL_LABEL', 'Email');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_EMAIL_LABEL', 'Email', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_CAPTCHA_LABEL', 'Captcha');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_CAPTCHA_LABEL', 'Captcha', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_SUBMIT', 'Créer un compte anonyme');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_REGISTRATION_SUBMIT', 'Create an anonymous account', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_SUBMIT', 'Se connecter');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_SUBMIT', 'Login', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ANONYM_REGISTRATION_TOKEN_FIELD_NOT_SET', 'Le champ jeton n\'est pas défini dans le formulaire d\'enregistrement anonyme.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ANONYM_REGISTRATION_TOKEN_FIELD_NOT_SET', 'The token field is not set in the anonymous registration form.', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ANONYM_USERS_ATTEMPTS', 'Clé erronée. Vous n\'avez droits qu\'a %s essai(s) désormais.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ANONYM_USERS_ATTEMPTS', 'Wrong key. You only have %s attempt(s) left.', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_ANONYM_UNEXISTING_KEY', 'Clé inexistante.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_ANONYM_UNEXISTING_KEY', 'Non-existing key.', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_ANONYM_OUTDATED_KEY', 'Clé expirée.');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_ANONYM_OUTDATED_KEY', 'Outdated key.', 'override', 0, null, null, 'en-GB');

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_ANONYM_USER_NOT_ACTIVATED', '<p>Vous avez reçu un lien d\'activation à votre adresse email. Il vous suffit de cliquer sur le lien pour finaliser la création de votre compte.</p>');
			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_ANONYM_USER_NOT_ACTIVATED', '<p>You have received an activation link at your address email. Just click on the link to finalize the creation of your account.</p>', 'override', 0, null, null, 'en-GB');

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_emails'))
				->where($this->db->quoteName('lbl') . ' = ' . $this->db->quote('anonym_token_email'));

			$this->db->setQuery($query);
			$exists = $this->db->loadResult();

			if (empty($exists))
			{
				$message = "<p>Bonjour,</p><p>Votre compte anonyme a été créé avec succès sur [SITE_URL].</p><p>Pour activer votre compte et accéder à votre espace, veuillez cliquer sur le lien suivant: <a href='[ACTIVATION_ANONYM_URL]'>Lien d'activation</a></p><p>Votre identifiant utilisateur: [USER_ID]</p><p>Votre jeton d'accès: [TOKEN]</p><p>Si vous n’êtes pas à l’origine de cette demande, veuillez en informer l’administrateur du site.</p><p>Cordialement,</p>";
				$query->clear()
					->insert('#__emundus_setup_emails')
					->columns(['lbl', 'subject', 'message', 'category'])
					->values(
						$this->db->quote('anonym_token_email') . ', ' .
						$this->db->quote('Activation de votre compte anonyme') . ', ' .
						$this->db->quote($message) . ', ' .
						$this->db->quote('Système')
					);

				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * @param   array  $tasks
	 *
	 * @return array
	 */
	private function createTokenAuthLoginForm(array $tasks): array
	{
		$datas = [
			'label' => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN',
			'intro' => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_INTRO',
			'submit_button_label' => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_SUBMIT',
		];
		$params = [
			'process-jplugins'   => '2',
			'plugins'            => array("connectfromtoken"),
			'plugin_state'       => array("1"),
			'plugin_locations'   => array("both"),
			'plugin_events'      => array("both"),
			'plugin_description' => array("COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_PLUGIN_DESCRIPTION"),
			'token_field' => '' // This will be set later when the form is created
		];

		$form = EmundusHelperUpdate::addFabrikForm($datas, $params);
		$tasks[] = $form['status'];

		if ($form['status']) {
			$group = EmundusHelperUpdate::addFabrikGroup(['name' => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_GROUP_TITLE'], ['repeat_group_show_first' => 1], 1, true);

			if ($group['status']) {
				EmundusHelperUpdate::joinFormGroup($form['id'], [$group['id']]);

				$elements = [
					[
						'name'                 => 'id',
						'group_id'             => $group['id'],
						'plugin'               => 'internalid',
						'label'                => 'id',
						'show_in_list_summary' => 0,
						'hidden'               => 1
					],
					[
						'name'                 => 'date_time',
						'group_id'             => $group['id'],
						'plugin'               => 'date',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_DATE_TIME_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 1,
					],
					[
						'name'                 => 'token',
						'group_id'             => $group['id'],
						'plugin'               => 'field',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_TOKEN_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 0
					],
					[
						'name'                 => 'captcha',
						'group_id'             => $group['id'],
						'plugin'               => 'captcha',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_CAPTCHA_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 0
					],
					[
						'name'                 => 'ip',
						'group_id'             => $group['id'],
						'plugin'               => 'field',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_IP_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 1,
						'default'              => '$input = JFactory::getApplication()->input;return $input->server->get(\'REMOTE_ADDR\');',
						'eval'                 => 1
					],
					[
						'name'                 => 'succeed',
						'group_id'             => $group['id'],
						'plugin'               => 'yesno',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_SUCCEED_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 1,
						'default'              => '0'
					]
				];

				foreach ($elements as $element) {
					$result = EmundusHelperUpdate::addFabrikElement($element);
					$tasks[] = $result['status'];

					if ($element['name'] === 'token') {
						$params['token_field'] = $result['id'];
					}
				}

				$query = $this->db->createQuery();
				$query->select('params')
					->from($this->db->quoteName('#__fabrik_forms'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form['id']));

				$this->db->setQuery($query);
				$existingParams = $this->db->loadResult();
				$existingParams = json_decode($existingParams, true);
				$existingParams['token_field'] = $params['token_field'];
				$existingParams = json_encode($existingParams);

				$query->clear()
					->update($this->db->quoteName('#__fabrik_forms'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote($existingParams))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form['id']));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			$result = EmundusHelperUpdate::addFabrikList([
				'label' => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_LIST',
				'db_table_name' => 'jos_emundus_token_auth_attempts',
				'form_id' => $form['id']
			], [
				"menu_access_only" => "1",
				// 10 = nobody, 1 = public
				"allow_view_details" => "10",
				"allow_edit_details" => "10",
				"allow_add" => "1",
				"allow_delete" => "10",
				"allow_drop" => "10"
			]);
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'topmenu',
				'title' => 'COM_EMUNDUS_FORM_TOKEN_AUTH_LOGIN_MENU',
				'alias' => 'connect-from-token',
				'link' => 'index.php?option=com_fabrik&view=form&formid=' . $form['id'],
				'published' => 0,
				'type' => 'component',
				'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
				'params' => [
					'menu_show' => 0
				]
			], 1, 0);
			$tasks[] = $result['status'];
		}

		return $tasks;
	}

	private function createTokenRegistrationForm(array $tasks): array
	{
		$datas = [
			'label' => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION',
			'intro' => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_INTRO',
			'submit_button_label' => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_SUBMIT',
		];
		$params = [
			'process-jplugins'   => '2',
			'plugins'            => array("anonymregistration"),
			'plugin_state'       => array("1"),
			'plugin_locations'   => array("both"),
			'plugin_events'      => array("both"),
			'plugin_description' => array("COM_EMUNDUS_FORM_TOKEN_REGISTRATION_PLUGIN_DESCRIPTION"),
			'token_field' => '', // This will be set later when the form is created
			'email_field' => '', // This will be set later when the form is created
			'campaign_field' => '', // none by default
		];
		$form = EmundusHelperUpdate::addFabrikForm($datas, $params);

		$tasks[] = $form['status'];
		if ($form['status']) {
			$group = EmundusHelperUpdate::addFabrikGroup(['name' => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_GROUP_TITLE'], ['repeat_group_show_first' => 1], 1, true);

			if ($group['status']) {
				EmundusHelperUpdate::joinFormGroup($form['id'], [$group['id']]);

				$elements = [
					[
						'name'                 => 'id',
						'group_id'             => $group['id'],
						'plugin'               => 'internalid',
						'label'                => 'id',
						'show_in_list_summary' => 0,
						'hidden'               => 1
					],
					[
						'name'                 => 'date_time',
						'group_id'             => $group['id'],
						'plugin'               => 'date',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_DATE_TIME_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 1,
					],
					[
						'name'                 => 'token',
						'group_id'             => $group['id'],
						'plugin'               => 'field',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_TOKEN_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 0,
						'readonly'             => 1
					],
					[
						'name'                 => 'email',
						'group_id'             => $group['id'],
						'plugin'               => 'field',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_EMAIL_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 0
					],
					[
						'name'                 => 'captcha',
						'group_id'             => $group['id'],
						'plugin'               => 'captcha',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_CAPTCHA_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 0
					],
					[
						'name'                 => 'campaign',
						'group_id'             => $group['id'],
						'plugin' 			   => 'databasejoin',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_CAMPAIGN_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 1
					],
					[
						'name'                 => 'ip',
						'group_id'             => $group['id'],
						'plugin'               => 'field',
						'label'                => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_IP_LABEL',
						'show_in_list_summary' => 0,
						'hidden'               => 1,
						'default'              => '$input = JFactory::getApplication()->input;return $input->server->get(\'REMOTE_ADDR\');',
						'eval'				 => 1
					],
				];

				foreach ($elements as $element) {
					$result = EmundusHelperUpdate::addFabrikElement($element);
					$tasks[] = $result['status'];

					if ($element['name'] === 'token') {
						$params['token_field'] = $result['id'];
					} elseif ($element['name'] === 'email') {
						$params['email_field'] = $result['id'];
					} elseif ($element['name'] === 'campaign') {
						$params['campaign_field'] = $result['id'];
					}
				}

				$query = $this->db->createQuery();
				$query->select('params')
					->from($this->db->quoteName('#__fabrik_forms'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form['id']));

				$this->db->setQuery($query);
				$existingParams = $this->db->loadResult();

				$existingParams = json_decode($existingParams, true);
				$existingParams['token_field'] = $params['token_field'];
				$existingParams['email_field'] = $params['email_field'];
				$existingParams['campaign_field'] = $params['campaign_field'];

				$existingParams = json_encode($existingParams);

				$query->clear()
					->update($this->db->quoteName('#__fabrik_forms'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote($existingParams))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form['id']));

				$this->db->setQuery($query);
				$this->db->execute();

				$result = EmundusHelperUpdate::addFabrikList([
					'label' => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_LIST',
					'db_table_name' => 'jos_emundus_anonym_registration',
					'form_id' => $form['id']
				], [
					"menu_access_only" => "1",
					// 10 = nobody, 1 = public
					"allow_view_details" => "10",
					"allow_edit_details" => "10",
					"allow_add" => "1",
					"allow_delete" => "10",
					"allow_drop" => "10"
				]);
				$tasks[] = $result['status'];

				$result = EmundusHelperUpdate::addJoomlaMenu([
					'menutype' => 'topmenu',
					'title' => 'COM_EMUNDUS_FORM_TOKEN_REGISTRATION_MENU',
					'alias' => 'anonym-registration',
					'link' => 'index.php?option=com_fabrik&view=form&formid=' . $form['id'],
					'published' => 0,
					'type' => 'component',
					'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
					'params' => [
						'menu_show' => 0
					]
				], 1, 0);
				$tasks[] = $result['status'];
			}
		}

		return $tasks;
	}
}