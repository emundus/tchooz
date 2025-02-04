<?php
/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts\src;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';

class SharingFilesInstall
{
	/**
	 * @var DatabaseDriver
	 */
	private $db;

	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function install()
	{
		$query = $this->db->getQuery(true);

		$result = ['status' => false, 'message' => ''];

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_users', 'password', 'varchar', 100);
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne password à la table jos_emundus_users<br>';

			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'ccid', 'int', 11);
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne ccid à la table jos_emundus_files_request<br>';

			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'user_id', 'int', 11);
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne user_id à la table jos_emundus_files_request<br>';

			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'r', 'tinyint', 3, 0, 0);
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne r à la table jos_emundus_files_request<br>';

			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'u', 'tinyint', 3, 0, 0);
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne u à la table jos_emundus_files_request<br>';

			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'show_history', 'tinyint', 3, 0, 0);
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne show_history à la table jos_emundus_files_request<br>';

			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'show_shared_users', 'tinyint', 3, 0, 0);
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne show_shared_users à la table jos_emundus_files_request<br>';

			return $result;
		}

		$this->db->setQuery('ALTER TABLE `jos_emundus_files_request` MODIFY `attachment_id` int(11) NULL;');
		$attachment_modified = $this->db->execute();
		if (!$attachment_modified) {
			$result['message'] .= 'Erreur lors de la modification de la colonne attachment_id de la table jos_emundus_files_request<br>';

			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_campaign_candidature', 'locked_elements', 'text');
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne locked_elements à la table jos_emundus_files_request<br>';

			return $result;
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__fabrik_forms'))
			->where($this->db->quoteName('label') . ' = ' . $this->db->quote('COM_EMUNDUS_COLLABORATION'));
		$this->db->setQuery($query);
		$fabrik_form = $this->db->loadAssoc();

		if (empty($fabrik_form)) {
			$datas       = [
				'label'               => 'COM_EMUNDUS_COLLABORATION',
				'error'               => 'FORM_ERROR',
				'submit_button_label' => 'COM_EMUNDUS_COLLABORATION_ACCEPT',
			];
			$params      = [
				'submit_button_label' => 'COM_EMUNDUS_COLLABORATION_ACCEPT',
				'save_button_class'   => 'btn-primary save-btn sauvegarder',
			];
			$fabrik_form = EmundusHelperUpdate::addFabrikForm($datas, $params);
			if (!$fabrik_form['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout du formulaire de collaboration<br>';

				return $result;
			}

			$datas       = [
				'label'         => 'COM_EMUNDUS_COLLABORATION',
				'form_id'       => $fabrik_form['id'],
				'db_table_name' => 'jos_emundus_users',
				'access'        => 10,
			];
			$params      = [
				'allow_view_details'  => 10,
				'allow_edit_details'  => 10,
				'allow_add'           => 1,
				'allow_delete'        => 10,
				'allow_drop'          => 10,
				'menu_access_only'    => 1,
				'csv_import_frontend' => 10,
				'csv_export_frontend' => 10,
				'distinct'            => 0,
			];
			$fabrik_list = EmundusHelperUpdate::addFabrikList($datas, $params);
			if (!$fabrik_list['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout de la liste de collaboration<br>';

				return $result;
			}

			$datas          = [
				'name' => 'COM_EMUNDUS_COLLABORATION',
			];
			$params = [
				'repeat_group_show_first' => 1
			];
			$fabrik_group_1 = EmundusHelperUpdate::addFabrikGroup($datas, $params, 1, true);
			$datas          = [
				'name' => 'Names',
			];
			$params         = [
				'group_columns' => 2,
				'repeat_group_show_first' => 1
			];
			$fabrik_group_2 = EmundusHelperUpdate::addFabrikGroup($datas, $params, 1, true);

			if (!$fabrik_group_1['status'] || !$fabrik_group_2['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout des groupes de collaboration<br>';

				return $result;
			}

			$joined = EmundusHelperUpdate::joinFormGroup($fabrik_form['id'], [$fabrik_group_2['id'], $fabrik_group_1['id']]);
			if (!$joined['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout des groupes de collaboration<br>';

				return $result;
			}

			$datas    = [
				'name'     => 'id',
				'group_id' => $fabrik_group_1['id'],
				'plugin'   => 'internalid',
			];
			$id_added = EmundusHelperUpdate::addFabrikElement($datas);
			if (!$id_added['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout de l\'élément id<br>';

				return $result;
			}

			$datas        = [
				'name'     => 'user_id',
				'group_id' => $fabrik_group_1['id'],
				'plugin'   => 'field',
				'hidden'   => 1
			];
			$userid_added = EmundusHelperUpdate::addFabrikElement($datas);
			if (!$userid_added['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout de l\'élément user_id<br>';

				return $result;
			}

			$datas           = [
				'name'     => 'firstname',
				'label'    => 'FIRSTNAME',
				'group_id' => $fabrik_group_2['id'],
				'plugin'   => 'field',
			];
			$firstname_added = EmundusHelperUpdate::addFabrikElement($datas);
			if (!$firstname_added['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout de l\'élément firstname<br>';

				return $result;
			}

			$datas          = [
				'name'     => 'lastname',
				'label'    => 'LASTNAME',
				'group_id' => $fabrik_group_2['id'],
				'plugin'   => 'field',
			];
			$lastname_added = EmundusHelperUpdate::addFabrikElement($datas);
			if (!$lastname_added['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout de l\'élément lastname<br>';

				return $result;
			}

			$datas       = [
				'name'     => 'email',
				'label'    => 'EMAIL',
				'group_id' => $fabrik_group_1['id'],
				'plugin'   => 'field',
			];
			$email_added = EmundusHelperUpdate::addFabrikElement($datas);
			if (!$email_added['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout de l\'élément email<br>';

				return $result;
			}

			$datas          = [
				'name'     => 'password',
				'label'    => 'PASSWORD',
				'group_id' => $fabrik_group_1['id'],
				'plugin'   => 'field',
			];
			$params         = [
				'password' => 1,
			];
			$password_added = EmundusHelperUpdate::addFabrikElement($datas, $params);
			if (!$password_added['status']) {
				$result['message'] .= 'Erreur lors de l\'ajout de l\'élément password<br>';

				return $result;
			}

			// Add plugins to form
			$query->clear()
				->select('id,params')
				->from('#__fabrik_forms')
				->where('id = ' . $fabrik_form['id']);
			$this->db->setQuery($query);
			$form = $this->db->loadObject();

			$params                               = json_decode($form->params);
			$params->plugins                      = ['juser', 'emunduscollaborate'];
			$params->plugin_locations             = ['both', 'both'];
			$params->plugin_events                = ['both', 'both'];
			$params->plugin_description           = ['Create user', 'Collaborate'];
			$params->plugin_state                 = ['1', '1'];
			$params->juser_field_name             = [$lastname_added['id']];
			$params->juser_field_username         = [$email_added['id']];
			$params->juser_field_password         = [$password_added['id']];
			$params->juser_field_email            = [$email_added['id']];
			$params->juser_field_block            = [""];
			$params->juser_field_userid           = [$userid_added['id']];
			$params->juser_field_usertype         = [""];
			$params->juser_field_requirereset     = [""];
			$params->juser_bypass_activation      = ["1"];
			$params->juser_bypass_registration    = ["1"];
			$params->juser_bypass_accountdetails  = ["1"];
			$params->juser_use_email_plugin       = ["1"];
			$params->juser_require_reset          = ["0"];
			$params->juser_field_default_group    = ["2"];
			$params->juser_group_preserve         = ["0"];
			$params->juser_conditon               = [""];
			$params->juser_sync_pk                = ["0"];
			$params->juser_delete_user            = ["0"];
			$params->juser_auto_login             = ["1"];
			$params->synchro_users                = ["0"];
			$params->juser_sync_on_edit           = ["0"];
			$params->juser_sync_load_current_user = ["0"];
			$params->juser_additional_bind_data   = [""];

			$query->clear()
				->update('#__fabrik_forms')
				->set('params = ' . $this->db->quote(json_encode($params)))
				->where('id = ' . $fabrik_form['id']);
			$this->db->setQuery($query);
			$form_updated = $this->db->execute();
			if (!$form_updated) {
				$result['message'] .= 'Erreur lors de l\'ajout des plugins au formulaire de collaboration<br>';

				return $result;
			}
		}

		$datas = [
			'title'        => 'Accepter l\'invitation',
			'menutype'     => 'mainmenu',
			'type'         => 'component',
			'link'         => 'index.php?option=com_fabrik&view=form&formid=' . $fabrik_form['id'],
			'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
		];
		$menu  = EmundusHelperUpdate::addJoomlaMenu($datas);
		if (!$menu['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout du menu de collaboration<br>';

			return $result;
		}

		$emundus_updated = EmundusHelperUpdate::updateExtensionParam('collaborate_link', $menu['id']);
		if (!$emundus_updated) {
			$result['message'] .= 'Erreur lors de l\'ajout du lien de collaboration<br>';

			return $result;
		}

		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COLLABORATION_ACCEPT', 'Valider et accepter la collaboration');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COLLABORATION', 'Collaboration - Renseignez vos informations');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_LOCK_FOR_OTHERS', 'Verrouiller cet élément pour vos collaborateurs');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_UNLOCK_FOR_OTHERS', 'Dévérouiller cet élément pour vos collaborateurs');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_EVENTS_APPLICATION_CURRENT_EDITING', 'Un utilisateur modifie actuellement cette page, vous êtes donc en lecture seule');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_SESSION_EXPIRED', 'Votre session sur la page a expiré');

		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COLLABORATION_ACCEPT', 'Validate and accept the collaboration', 'override', 0, 'fabrik_forms', '', 'en-GB');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_COLLABORATION', 'Collaboration - Fill in your details', 'override', 0, 'fabrik_forms', 'label', 'en-GB');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_LOCK_FOR_OTHERS', 'Lock this item for your collaborators', 'override', 0, '', '', 'en-GB');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_UNLOCK_FOR_OTHERS', 'Unlock this element for your collaborators', 'override', 0, '', '', 'en-GB');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_EVENTS_APPLICATION_CURRENT_EDITING', 'A user is currently editing this page, so you are read-only.', 'override', 0, '', '', 'en-GB');
		EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_SESSION_EXPIRED', 'Your session on the page has expired', 'override', 0, '', '', 'en-GB');

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_email_templates'))
			->where($this->db->quoteName('lbl') . ' = ' . $this->db->quote('collaborate'));
		$this->db->setQuery($query);
		$tmpl = $this->db->loadResult();

		if(empty($tmpl)) {
			$collaboration_email_tmpl = file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/collaborate_template.html');

			$columns = [
				'date_time',
				'lbl',
				'Template',
				'type',
				'published'
			];

			$values = [
				$this->db->quote(date('Y-m-d H:i:s')),
				$this->db->quote('collaborate'),
				$this->db->quote($collaboration_email_tmpl),
				1,
				1
			];

			$query->clear()
				->insert($this->db->quoteName('#__emundus_email_templates'))
				->columns($this->db->quoteName($columns))
				->values(implode(',', $values));
			$this->db->setQuery($query);
			$tmpl_email_created = $this->db->execute();

			if (!$tmpl_email_created) {
				$result['message'] .= 'Erreur lors de l\'ajout du template d\'email<br>';

				return $result;
			}

			$tmpl = $this->db->insertid();
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('lbl') . ' = ' . $this->db->quote('collaborate_invitation'));
		$this->db->setQuery($query);
		$email = $this->db->loadResult();

		if(empty($email)) {
			$columns = [
				'lbl',
				'subject',
				'emailfrom',
				'message',
				'name',
				'type',
				'published',
				'email_tmpl',
				'category'
			];

			$values = [
				$this->db->quote('collaborate_invitation'),
				$this->db->quote('Invitation à collaborer'),
				$this->db->quote(''),
				$this->db->quote('<p style="text-align: center;"><strong>Bonjour !</strong></p><p style="text-align: center;"><br></p><p style="text-align: center;"><strong>Je vous invite à collaborer avec moi pour compléter mon dossier de candidature sur la plateforme</strong><a href=" [SITE_URL]" target="_blank"><strong> <span class="mention" data-denotation-char="" data-id="17" data-value="[SITE_URL]">﻿<span contenteditable="false" class=""><span class="ql-mention-denotation-char"></span>[SITE_URL]</span>﻿</span></strong></a><strong>.</strong></p>'),
				$this->db->quote(''),
				1,
				1,
				$tmpl,
				$this->db->quote('Système')
			];

			$query->clear()
				->insert($this->db->quoteName('#__emundus_setup_emails'))
				->columns($this->db->quoteName($columns))
				->values(implode(',', $values));
			$this->db->setQuery($query);
			$email_created = $this->db->execute();

			if (!$email_created) {
				$result['message'] .= 'Erreur lors de l\'ajout de l\'email<br>';

				return $result;
			}
		}

		// Install collaborate plugin
		$installed = EmundusHelperUpdate::installExtension('PLG_FABRIK_FORM_EMUNDUSCOLLABORATE','emunduscollaborate','{"name":"PLG_FABRIK_FORM_EMUNDUSCOLLABORATE","type":"plugin","creationDate":"January 2024","author":"eMundus","copyright":"Copyright (C) 2017-2024 eMundus.fr - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"2.0.0","description":"PLG_FABRIK_FORM_EMUNDUSCOLLABORATE_DESCRIPTION","group":"","filename":"emunduscollaborate"}','plugin',1,'fabrik_form');
		if(!$installed) {
			$result['message'] .= 'Erreur lors de l\'ajout du plugin emunduscollaborate<br>';

			return $result;
		}

		// Install history menu
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__modules'))
			->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundusflow'))
			->where($this->db->quoteName('published') . ' = 1');
		$this->db->setQuery($query);
		$flow_module_id = $this->db->loadResult();

		$datas = [
			'title'        => 'Voir mon dossier',
			'menutype'     => 'applicantmenu',
			'link'         => 'index.php?option=com_emundus&view=application&layout=history',
			'type'         => 'component',
			'component_id' => ComponentHelper::getComponent('com_emundus')->id,
			'params' => [
				'tabs' => ["history","forms","attachments"],
				'menu_show' => 0
			]
		];
		$menu  = EmundusHelperUpdate::addJoomlaMenu($datas,1,1,'last-child',[$flow_module_id]);
		if (!$menu['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout du menu de collaboration<br>';

			return $result;
		}

		EmundusHelperUpdate::addCustomEvents([
			['label' => 'onAfterAcceptCollaboration', 'category' => 'Collaboration'],
            ['label' => 'onAfterShareFileWith', 'category' => 'Collaboration']
		]);

		$column_added = EmundusHelperUpdate::addColumn('jos_fabrik_form_sessions', 'last_update', 'varchar(50)');
		if (!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne last_update à la table jos_fabrik_form_sessions<br>';

			return $result;
		}

		$result['status'] = true;

		return $result;
	}
}