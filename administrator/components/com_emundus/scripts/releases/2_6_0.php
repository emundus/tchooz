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

class Release2_6_0Installer extends ReleaseInstaller
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
			// Create table jos_emundus_sign_requests
			$columns      = [
				[
					'name'   => 'attachment_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'upload_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'signed_upload_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'status',
					'type'   => 'VARCHAR',
					'length' => 50,
					'null'   => 0
				],
				[
					'name'   => 'steps_count',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'user_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'ccid',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'fnum',
					'type'   => 'VARCHAR',
					'length' => 28,
					'null'   => 1
				],
				[
					'name'   => 'connector',
					'type'   => 'VARCHAR',
					'length' => 50,
					'null'   => 1
				],
				[
					'name' => 'cancel_reason',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'   => 'cancel_at',
					'type'   => 'DATETIME',
					'null'   => 1
				],
				[
					'name'   => 'send_reminder',
					'type'   => 'TINYINT',
					'length' => 3,
					'null'   => 1
				],
				[
					'name'   => 'last_reminder_at',
					'type'   => 'DATETIME',
					'null'   => 1
				],
				[
					'name' => 'created_at',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name'   => 'created_by',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_sign_requests_attachment_id_fk',
					'from_column'    => 'attachment_id',
					'ref_table'      => 'jos_emundus_setup_attachments',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_sign_requests_upload_id_fk',
					'from_column'    => 'upload_id',
					'ref_table'      => 'jos_emundus_uploads',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_sign_requests_ccid_fk',
					'from_column'    => 'ccid',
					'ref_table'      => 'jos_emundus_campaign_candidature',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_sign_requests_fnum_fk',
					'from_column'    => 'fnum',
					'ref_table'      => 'jos_emundus_campaign_candidature',
					'ref_column'     => 'fnum',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$tasks[]      = EmundusHelperUpdate::createTable('jos_emundus_sign_requests', $columns, $foreign_keys)['status'];

			// Create table jos_emundus_sign_requests_signers
			$columns      = [
				[
					'name'   => 'request_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'status',
					'type'   => 'VARCHAR',
					'length' => 50,
					'null'   => 0
				],
				[
					'name' => 'signed_at',
					'type' => 'DATETIME',
					'null' => 1
				],
				[
					'name'   => 'contact_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'step',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'page',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'position',
					'type'   => 'VARCHAR',
					'length' => 10,
					'null'   => 0
				],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_sign_requests_signers_request_id_fk',
					'from_column'    => 'request_id',
					'ref_table'      => 'jos_emundus_sign_requests',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_sign_requests_signers_contact_id_fk',
					'from_column'    => 'contact_id',
					'ref_table'      => 'jos_emundus_contacts',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$tasks[]      = EmundusHelperUpdate::createTable('jos_emundus_sign_requests_signers', $columns, $foreign_keys)['status'];

			// Create table jos_emundus_yousign_requests
			$columns      = [
				[
					'name'   => 'name',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'request_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'procedure_id',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name'   => 'document_id',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name' => 'signature_field',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'   => 'status',
					'type'   => 'VARCHAR',
					'length' => 50,
					'null'   => 0
				],
				[
					'name' => 'request_payload',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name' => 'response_payload',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name' => 'error_details',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name' => 'created_at',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name'   => 'created_by',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name' => 'expiration_date',
					'type' => 'DATETIME',
					'null' => 1
				],
				[
					'name'   => 'retry_count',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_yousign_requests_request_id_fk',
					'from_column'    => 'request_id',
					'ref_table'      => 'jos_emundus_sign_requests',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$tasks[]      = EmundusHelperUpdate::createTable('jos_emundus_yousign_requests', $columns, $foreign_keys)['status'];

			// Create table jos_emundus_yousign_requests_signers
			$columns      = [
				[
					'name'   => 'parent_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0
				],
				[
					'name'   => 'signer_id',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'   => 'request_signer_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 1
				],
				[
					'name'   => 'signature_url',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name' => 'signature_field',
					'type' => 'TEXT',
					'null' => 1
				],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_yousign_requests_signers_parent_id_fk',
					'from_column'    => 'parent_id',
					'ref_table'      => 'jos_emundus_yousign_requests',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_yousign_requests_signers_request_signer_id_fk',
					'from_column'    => 'request_signer_id',
					'ref_table'      => 'jos_emundus_sign_requests_signers',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$tasks[]      = EmundusHelperUpdate::createTable('jos_emundus_yousign_requests_signers', $columns, $foreign_keys)['status'];

			$query->clear()
				->select('id')
				->from('#__emundus_setup_actions')
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('sign_request'));
			$this->db->setQuery($query);
			$action_id = $this->db->loadResult();

			if (empty($action_id))
			{
				$query->clear()
					->select('MAX(ordering)')
					->from('#__emundus_setup_actions')
					->where('ordering <> 999');
				$this->db->setQuery($query);
				$ordering = $this->db->loadResult();

				$insert_acl = [
					'name'     => 'sign_request',
					'label'    => 'COM_EMUNDUS_ACL_SIGN_REQUEST',
					'multi'    => 0,
					'c'        => 1,
					'r'        => 1,
					'u'        => 1,
					'd'        => 1,
					'ordering' => $ordering + 1,
					'status'   => 1,
				];
				$insert_acl = (object) $insert_acl;

				if ($this->db->insertObject('#__emundus_setup_actions', $insert_acl))
				{
					$action_id = $this->db->insertId();

					// Give all rights to all rights group
					$all_rights_group = ComponentHelper::getParams('com_emundus')->get('all_rights_group', 1);
					$sign_acl_rights  = [
						'group_id'  => $all_rights_group,
						'action_id' => $action_id,
						'c'         => 1,
						'r'         => 1,
						'u'         => 1,
						'd'         => 1,
						'time_date' => date('Y-m-d H:i:s')
					];
					$sign_acl_rights  = (object) $sign_acl_rights;
					$this->db->insertObject('#__emundus_acl', $sign_acl_rights);
				}
			}
			$tasks[] = !empty($action_id);

			$tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_setup_sync', 'consumptions', 'TEXT')['status'];
			$tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_setup_sync', 'context', 'VARCHAR', 100)['status'];

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_sync'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('yousign'));
			$this->db->setQuery($query);
			$yousign = $this->db->loadResult();

			if (empty($yousign))
			{
				$yousign = [
					'type'        => 'yousign',
					'name'        => 'Yousign',
					'description' => 'Signature électronique via Yousign',
					'params'      => '{}',
					'config'      => '{}',
					'icon'        => 'yousign.png',
					'enabled'     => 0,
					'published'   => 0,
					'context'     => 'numeric_sign'
				];
				$yousign = (object) $yousign;
				$this->db->insertObject('jos_emundus_setup_sync', $yousign);
			}

			$tasks[] = EmundusHelperUpdate::installExtension('plg_task_yousign', 'yousign', null, 'plugin', 1, 'task');

			$tasks[] = EmundusHelperUpdate::installExtension('plg_emundus_yousign', 'yousign', null, 'plugin', 1, 'emundus');

			$tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_uploads', 'signed_file', 'TINYINT', 3)['status'];
			$tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_uploads', 'thumbnail', 'TEXT')['status'];

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('numeric_sign'));
			$this->db->setQuery($query);
			$config = $this->db->loadObject();

			if (empty($config->namekey))
			{
				$params = '{"enabled":0,"displayed":0,"params":{}}';
				$query->clear()
					->insert($this->db->quoteName('#__emundus_setup_config'))
					->columns($this->db->quoteName('namekey') . ', ' . $this->db->quoteName('value'))
					->values($this->db->quote('numeric_sign') . ', ' . $this->db->quote($params));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('signatures') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=sign'));
			$this->db->setQuery($query);
			$list_sign_requests = $this->db->loadResult();

			if (empty($list_sign_requests))
			{
				$data               = [
					'menutype'          => 'onboardingmenu',
					'title'             => 'Signatures',
					'alias'             => 'signature',
					'path'              => 'signature',
					'link'              => 'index.php?option=com_emundus&view=sign',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [
						'menu_image_css' => 'signature'
					],
				];
				$list_sign_requests = EmundusHelperUpdate::addJoomlaMenu($data, 1, 0)['id'];
				EmundusHelperUpdate::insertFalangTranslation(1, $list_sign_requests, 'menu', 'title', 'Signatures');
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('create-sign-request') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=sign&layout=add'));
			$this->db->setQuery($query);
			$add_sign_request = $this->db->loadResult();

			if (!empty($list_sign_requests) && empty($add_sign_request))
			{
				$data             = [
					'menutype'          => 'onboardingmenu',
					'title'             => 'Créer une demande de signature',
					'alias'             => 'create-sign-request',
					'path'              => 'create-sign-request',
					'link'              => 'index.php?option=com_emundus&view=sign&layout=add',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
				];
				$add_sign_request = EmundusHelperUpdate::addJoomlaMenu($data, $list_sign_requests, 0)['id'];
				EmundusHelperUpdate::insertFalangTranslation(1, $add_sign_request, 'menu', 'title', 'Create a signature request');
			}

			$event_result = \EmundusHelperUpdate::addCustomEvents([['label' => 'onAfterSignRequestCompleted', 'category' => 'Sign', 'published' => 1]]);
			$tasks[] = $event_result['status'];

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