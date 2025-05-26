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
use Tchooz\Entities\Payment\CartProductStatus;
use Tchooz\Entities\Payment\TransactionStatus;
use Joomla\CMS\Language\Text;

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

			$tasks[] = $this->installPayment();

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

	private function installPayment(): bool
	{
		$tasks = [];

		$query = $this->db->getQuery(true);

		if (!class_exists('CartProductStatus')) {
			require_once (JPATH_ROOT . '/components/com_emundus/classes/Entities/Payment/CartProductStatus.php');
		}
		if (!class_exists('TransactionStatus')) {
			require_once (JPATH_ROOT . '/components/com_emundus/classes/Entities/Payment/TransactionStatus.php');
		}

		$columns = [
			[
				'name' => 'name',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'label',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'description',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => true,
				'default' => '',
			],
			[
				'name' => 'published',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 1,
			]
		];
		$result = \EmundusHelperUpdate::createTable('jos_emundus_setup_payment_method', $columns);
		if ($result['status'])
		{
			// add default values CB, sepa, transfer etc.
			$query->clear()
				->select('*')
				->from('#__emundus_setup_payment_method');

			$this->db->setQuery($query);
			$methods = $this->db->loadObjectList();

			// find cb
			$cb_installed = false;
			$sepa_installed = false;
			$cheque_installed = false;
			$transfer_installed = false;

			foreach ($methods as $method) {
				switch($method->name) {
					case 'CB':
						$cb_installed = true;
						break;
					case 'sepa':
						$sepa_installed = true;
						break;
					case 'cheque':
						$cheque_installed = true;
						break;
					case 'transfer':
						$transfer_installed = true;
						break;
				}
			}

			if (!$cb_installed) {
				$query->clear()
					->insert('#__emundus_setup_payment_method')
					->columns(['name', 'label', 'description', 'published'])
					->values('"CB", "Carte bancaire", "", 1');

				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}

			if (!$sepa_installed) {
				$query->clear()
					->insert('#__emundus_setup_payment_method')
					->columns(['name', 'label', 'description', 'published'])
					->values('"sepa", "Prélèvement (SEPA)", "", 1');

				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}

			if (!$cheque_installed) {
				$query->clear()
					->insert('#__emundus_setup_payment_method')
					->columns(['name', 'label', 'description', 'published'])
					->values('"cheque", "Chèque", "", 1');

				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}

			if (!$transfer_installed) {
				$query->clear()
					->insert('#__emundus_setup_payment_method')
					->columns(['name', 'label', 'description', 'published'])
					->values('"transfer", "Virement", "", 1');

				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}
		}
		$tasks[] = $result['status'];

		$columns = [
			[
				'name' => 'label',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
			],
			[
				'name' => 'published',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 1,
			]
		];
		$foreign_keys = [];
		\EmundusHelperUpdate::createTable('jos_emundus_product_category', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'label',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'description',
				'type' => 'TEXT',
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'price',
				'type' => 'DECIMAL(10, 2)',
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'currency_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'category_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'quantity',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => 0,
			],
			[
				'name' => 'illimited',
				'type' => 'TINYINT',
				'null' => true,
				'default' => 1,
			],
			[
				'name' => 'available_from',
				'type' => 'DATETIME',
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'available_to',
				'type' => 'DATETIME',
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'published',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 1,
			]
		];
		$foreign_keys = [
			[
				'name' => 'jos_emundus_product_currency_fk',
				'from_column'    => 'currency_id',
				'ref_table'      => 'data_currency',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name' => 'jos_emundus_product_category_id_fk',
				'from_column'    => 'category_id',
				'ref_table'      => 'jos_emundus_product_category',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
		];
		\EmundusHelperUpdate::createTable('jos_emundus_product', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'product_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'campaign_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
		];
		$foreign_keys = [
			[
				'name' => 'jos_emundus_product_campaigns_pid_fk',
				'from_column'    => 'product_id',
				'ref_table'      => 'jos_emundus_product',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name' => 'jos_emundus_product_campaigns_cid_fk',
				'from_column'    => 'campaign_id',
				'ref_table'      => 'jos_emundus_setup_campaigns',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
		];
		EmundusHelperUpdate::createTable('jos_emundus_product_campaigns', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'label',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'description',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => true,
				'default' => '',
			],
			[
				'name' => 'value',
				'type' => 'DECIMAL(10, 2)',
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'type',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => 'fixed', // enum ('fixed', 'percentage')
			],
			[
				'name' => 'currency_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true
			],
			[
				'name' => 'available_from',
				'type' => 'DATETIME',
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'available_to',
				'type' => 'DATETIME',
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'quantity',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => 0,
			],
			[
				'name' => 'published',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 1,
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_discount', $columns);

		$columns = [
			[
				'name' => 'created_at',
				'type' => 'DATETIME',
				'null' => false,
				'default' => '0000-00-00 00:00:00',
			],
			[
				'name' => 'created_by',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'updated_at',
				'type' => 'DATETIME',
				'null' => false,
				'default' => '0000-00-00 00:00:00',
			],
			[
				'name' => 'updated_by',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'fnum',
				'type' => 'VARCHAR',
				'length' => 28,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true
			],
			[
				'name' => 'total',
				'type' => 'DECIMAL(10, 2)',
				'null' => false,
				'default' => 0.00,
			],
			[
				'name' => 'user_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'payment_method_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'pay_advance',
				'type' => 'TINYINT',
				'length' => 1,
				'null' => true,
				'default' => 0,
			],
			[
				'name' => 'number_installment_debit',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => 1,
			],
			[
				'name' => 'installment_monthday',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => 1,
			],
			[
				'name' => 'published',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 1,
			]
		];
		$foreign_keys = [
			[
				'name' => 'jos_emundus_cart_user_id_fk',
				'from_column' => 'user_id',
				'ref_table' => 'jos_emundus_users',
				'ref_column' => 'user_id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name' => 'jos_emundus_cart_payment_method_id_fk',
				'from_column' => 'payment_method_id',
				'ref_table' => 'jos_emundus_setup_payment_method',
				'ref_column' => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name' => 'jos_emundus_cart_fnum_fk',
				'from_column' => 'fnum',
				'ref_table' => 'jos_emundus_campaign_candidature',
				'ref_column' => 'fnum',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name' => 'jos_emundus_cart_step_fk',
				'from_column' => 'step_id',
				'ref_table' => 'jos_emundus_setup_workflows_steps',
				'ref_column' => 'id',
				'update_cascade' => true,
				'delete_cascade' => false
			]
		];
		$unique_keys = [
			[
				'name' => 'jos_emundus_cart_fnum_unique_fk',
				'columns' => ['fnum'],
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_cart', $columns, $foreign_keys, '', $unique_keys);

		$columns = [
			[
				'name' => 'created_at',
				'type' => 'DATETIME',
				'null' => false,
				'default' => '0000-00-00 00:00:00',
			],
			[
				'name' => 'created_by',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'updated_at',
				'type' => 'DATETIME',
				'null' => false,
				'default' => '0000-00-00 00:00:00',
			],
			[
				'name' => 'updated_by',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'cart_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'product_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true
			],
			[
				'name' => 'discount_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true
			],
			[
				'name' => 'description',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => true,
				'default' => '',
			],
			[
				'name' => 'amount',
				'type' => 'DECIMAL(10, 2)',
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'type',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => 'fixed', // enum ('fixed', 'percentage', 'adjust_balance')
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_price_alteration_cart_fk',
				'from_column'    => 'cart_id',
				'ref_table'      => 'jos_emundus_cart',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_price_alteration_product_fk',
				'from_column'    => 'product_id',
				'ref_table'      => 'jos_emundus_product',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_price_alteration_discount_fk',
				'from_column'    => 'discount_id',
				'ref_table'      => 'jos_emundus_discount',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_price_alteration', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'created_at',
				'type' => 'DATETIME',
				'null' => false,
				'default' => '0000-00-00 00:00:00',
			],
			[
				'name' => 'created_by',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'updated_at',
				'type' => 'DATETIME',
				'null' => true,
			],
			[
				'name' => 'updated_by',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => 0,
			],
			[
				'name' => 'cart_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'product_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'quantity',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 1,
			],
			[
				'name' => 'price',
				'type' => 'DECIMAL(10, 2)',
				'null' => true,
				'default' => null,
			],
			[
				'name' => 'status',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => CartProductStatus::PENDING->value,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_cart_product_cart_fk',
				'from_column'    => 'cart_id',
				'ref_table'      => 'jos_emundus_cart',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_cart_product_product_fk',
				'from_column'    => 'product_id',
				'ref_table'      => 'jos_emundus_product',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_cart_product', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'product_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'mandatory',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 1,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_setup_workflow_step_product_fk',
				'from_column'    => 'product_id',
				'ref_table'      => 'jos_emundus_product',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_setup_workflow_step_fk',
				'from_column'    => 'step_id',
				'ref_table'      => 'jos_emundus_setup_workflows_steps',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_setup_workflow_step_product', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'product_category',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'mandatory',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 1,
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_setup_workflow_step_product_category', $columns);

		$columns = [
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'discount_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_setup_workflow_step_discount', $columns);

		$columns = [
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'payment_method',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_setup_workflow_step_payment_method_fk',
				'from_column'    => 'payment_method',
				'ref_table'      => 'jos_emundus_setup_payment_method',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_setup_workflow_step_payment_method_step_fk',
				'from_column'    => 'step_id',
				'ref_table'      => 'jos_emundus_setup_workflows_steps',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_setup_workflow_step_payment_method', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'adjust_balance',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'adjust_balance_step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
			],
			[
				'name' => 'synchronizer_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'advance_type',
				'type' => 'INT',
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'is_advance_amount_editable_by_applicant',
				'type' => 'TINYINT',
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'advance_amount',
				'type' => 'DECIMAL(10, 2)',
				'null' => true,
				'default' => 0
			],
			[
				'name' => 'advance_amount_type',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => true,
				'default' => $this->db->quote('fixed')
			],
			[
				'name' => 'installment_monthday',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => 0,
			],
			[
				'name' => 'installment_effect_date',
				'type' => 'DATE',
				'null' => true,
			],
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_setup_workflow_step_payment_rules_sync_fk',
				'from_column'    => 'synchronizer_id',
				'ref_table'      => 'jos_emundus_setup_sync',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_setup_workflow_step_payment_rules_step_fk',
				'from_column'    => 'step_id',
				'ref_table'      => 'jos_emundus_setup_workflows_steps',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_setup_workflow_step_payment_rules_adjust_balance_fk',
				'from_column'    => 'adjust_balance_step_id',
				'ref_table'      => 'jos_emundus_setup_workflows_steps',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$result = \EmundusHelperUpdate::createTable('jos_emundus_setup_workflow_step_payment_rules', $columns, $foreign_keys);
		$tasks[] = $result['status'];

		$columns = [
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0
			],
			[
				'name' => 'from_amount',
				'type' => 'DECIMAL(10, 2)',
				'null' => true,
				'default' => 0
			],
			[
				'name' => 'to_amount',
				'type' => 'DECIMAL(10, 2)',
				'null' => true,
				'default' => 0
			],
			[
				'name' => 'min_installments',
				'type' => 'INT',
				'null' => false,
				'default' => 1
			],
			[
				'name' => 'max_installments',
				'type' => 'INT',
				'null' => false,
				'default' => 1
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_setup_workflow_step_installment_rule_step_fk',
				'from_column'    => 'step_id',
				'ref_table'      => 'jos_emundus_setup_workflows_steps',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
		];
		$result = \EmundusHelperUpdate::createTable('jos_emundus_setup_workflow_step_installment_rule', $columns, $foreign_keys);
		$tasks[] = $result['status'];

		$columns = [
			[
				'name' => 'created_at',
				'type' => 'DATETIME',
				'null' => false,
				'default' => '0000-00-00 00:00:00',
			],
			[
				'name' => 'created_by',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'updated_at',
				'type' => 'DATETIME',
				'null' => true,
			],
			[
				'name' => 'updated_by',
				'type' => 'INT',
				'length' => 11,
				'null' => true,
				'default' => 0,
			],
			[
				'name' => 'cart_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'fnum',
				'type' => 'VARCHAR',
				'length' => 28,
				'null' => false,
			],
			[
				'name' => 'step_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'status',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => TransactionStatus::INITIATED->value,
			],
			[
				'name' => 'amount',
				'type' => 'DECIMAL(10, 2)',
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'currency_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => null,
			],
			[
				'name' => 'synchronizer_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'payment_method_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'data',
				'type' => 'JSON',
				'null' => true,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_payment_transaction_cart_fk',
				'from_column'    => 'cart_id',
				'ref_table'      => 'jos_emundus_cart',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_payment_transaction_synchronizer_fk',
				'from_column'    => 'synchronizer_id',
				'ref_table'      => 'jos_emundus_setup_sync',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_payment_transaction_payment_method_fk',
				'from_column'    => 'payment_method_id',
				'ref_table'      => 'jos_emundus_setup_payment_method',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_payment_transaction_step_fk',
				'from_column'    => 'step_id',
				'ref_table'      => 'jos_emundus_setup_workflows_steps',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_payment_transaction_fnum_fk',
				'from_column'    => 'fnum',
				'ref_table'      => 'jos_emundus_campaign_candidature',
				'ref_column'     => 'fnum',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_payment_transaction', $columns, $foreign_keys);

		$columns = [
			[
				'name' => 'contact_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'address1',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'address2',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => true,
				'default' => '',
			],
			[
				'name' => 'zip',
				'type' => 'VARCHAR',
				'length' => 20,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'city',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'state',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => true,
				'default' => '',
			],
			[
				'name' => 'country',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_contacts_address_contact_fk',
				'from_column'    => 'contact_id',
				'ref_table'      => 'jos_emundus_contacts',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_contacts_address_country_fk',
				'from_column'    => 'country',
				'ref_table'      => 'data_country',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		\EmundusHelperUpdate::createTable('jos_emundus_contacts_address', $columns, $foreign_keys);


		$columns = [
			[
				'name' => 'column',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'intern_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'reference',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => '',
			]
		];
		EmundusHelperUpdate::createTable('jos_emundus_external_reference', $columns);

		$columns = [
			[
				'name' => 'created_at',
				'type' => 'DATETIME',
				'null' => false,
				'default' => '0000-00-00 00:00:00',
			],
			[
				'name' => 'transaction_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'data',
				'type' => 'JSON',
				'null' => false,
				'default' => '',
			],
			[
				'name' => 'sync_id',
				'type' => 'INT',
				'length' => 11,
				'null' => false,
				'default' => 0,
			],
			[
				'name' => 'status',
				'type' => 'VARCHAR',
				'length' => 255,
				'null' => false,
				'default' => 'pending',
			],
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_payment_queue_transaction_fk',
				'from_column'    => 'transaction_id',
				'ref_table'      => 'jos_emundus_payment_transaction',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_payment_queue_sync_fk',
				'from_column'    => 'sync_id',
				'ref_table'      => 'jos_emundus_setup_sync',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		EmundusHelperUpdate::createTable('jos_emundus_payment_queue', $columns, $foreign_keys);

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_actions'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote('payment'));

		$this->db->setQuery($query);
		$action_id = $this->db->loadResult();

		if (empty($action_id)) {
			$query->clear()
				->insert($this->db->quoteName('#__emundus_setup_actions'))
				->columns($this->db->quoteName(['name', 'label', 'multi', 'c', 'r', 'u', 'd', 'ordering', 'status', 'description']))
				->values($this->db->quote('payment') . ', ' . $this->db->quote('COM_EMUNDUS_ACCESS_PAYMENT') . ', ' . $this->db->quote(0) . ',' . $this->db->quote(1) . ',' . $this->db->quote(1) . ',' . $this->db->quote(1) . ',' . $this->db->quote(1) . ',' . $this->db->quote(40) . ',' . $this->db->quote(1) . ',' . $this->db->quote('COM_EMUNDUS_ACCESS_PAYMENT_DESC'));

			$this->db->setQuery($query);
			$inserted = $this->db->execute();
			$tasks[] = $inserted;

			if ($inserted) {
				$action_id = $this->db->insertid();
				$query->clear()
					->insert($this->db->quoteName('#__emundus_setup_step_types'))
					->columns($this->db->quoteName(['parent_id', 'label', 'action_id', 'published', 'system']))
					->values($this->db->quote(0) . ', ' . $this->db->quote('COM_EMUNDUS_WORKFLOW_STEP_TYPE_PAYMENT') . ', ' . $this->db->quote($action_id) . ',' . $this->db->quote(1) . ',' . $this->db->quote(1));
				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}
		}

		$component_id = ComponentHelper::getComponent('com_emundus')->id;
		EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'onboardingmenu',
				'title' => 'Produits',
				'link' => 'index.php?option=com_emundus&view=payment&layout=products',
				'path' => 'products',
				'alias' => 'products',
				'type' => 'component',
				'component_id' => $component_id,
				'access' => 7,
				'params'       => [
					'menu_image_css' => 'shopping_cart'
				]
			]
		);

		EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'onboardingmenu',
				'title' => 'Transactions',
				'link' => 'index.php?option=com_emundus&view=payment&layout=transactions',
				'path' => 'transactions',
				'alias' => 'transactions',
				'type' => 'component',
				'component_id' => $component_id,
				'access' => 7,
				'params'       => [
					'menu_image_css' => 'payments'
				]
			]
		);

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__modules'))
			->where('module = ' . $this->db->quote('mod_emundus_checklist') . ' OR module = ' . $this->db->quote('mod_emundusflow'));

		$this->db->setQuery($query);
		$module_ids = $this->db->loadColumn();

		$result = EmundusHelperUpdate::addJoomlaMenu([
			'menutype' => 'topmenu',
			'title' => 'Panier',
			'link' => 'index.php?option=com_emundus&view=payment&layout=cart',
			'path' => 'cart',
			'alias' => 'cart',
			'type' => 'component',
			'component_id' => $component_id,
			'menu_show' => 0,
			'params'    => ['menu_image_css' => 'shopping_cart']
		], 1, 1, 'last-child', $module_ids);
		$tasks[] = $result['status'];

		$query->clear()
			->select('value')
			->from($this->db->quoteName('#__emundus_setup_config'))
			->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('payment'));

		$this->db->setQuery($query);
		$payment_config = $this->db->loadResult();

		if (empty($payment_config)) {
			$query->clear()
				->insert($this->db->quoteName('#__emundus_setup_config'))
				->columns($this->db->quoteName(['namekey', 'value']))
				->values($this->db->quote('payment') . ', ' . $this->db->quote(json_encode(['enabled' => 0, 'displayed' => 0, 'params' => ['currency_id' => 1]])));

			$this->db->setQuery($query);
			$tasks[] = $this->db->execute();
		}

		$inserted_column = EmundusHelperUpdate::addColumn('data_currency', 'iso4217', 'VARCHAR', 3, 1);
		$tasks[] = $inserted_column['status'];

		if ($inserted_column['status']) {
			$query->clear()
				->update('data_currency')
				->set('iso4217 = ' . $this->db->quote('978'))
				->where('iso3 = ' . $this->db->quote('EUR'));

			$this->db->setQuery($query);
			$tasks[] = $this->db->execute();
		}

		$inserted_column = EmundusHelperUpdate::addColumn('jos_emundus_setup_step_types', 'class', 'VARCHAR', 255);
		$tasks[] = $inserted_column['status'];

		$event_added = EmundusHelperUpdate::addCustomEvents([
			['label' => 'onAfterEmundusCartUpdate', 'published' => 1, 'category' => 'Payment', 'description' => 'Après la mise à jour du panier eMundus'],
			['label' => 'onBeforeEmundusCartRender', 'published' => 1, 'category' => 'Payment', 'description' => 'Avant le rendu du panier eMundus'],
			['label' => 'onAfterEmundusTransactionUpdate', 'published' => 1, 'category' => 'Payment', 'description' => 'Après la mise à jour de la transaction eMundus']
		]);
		$tasks[] = $event_added['status'];

		// setup sogecommerce basic sync
		$query->clear()
			->select('id')
			->from('#__emundus_setup_sync')
			->where('type = ' . $this->db->quote('sogecommerce'));

		$this->db->setQuery($query);
		$sogecommerce_id = $this->db->loadResult();

		if (empty($sogecommerce_id)) {
			$config = [
				'authentication' => ['client_id' => '', 'client_secret' => ''],
				'endpoint' => 'https://sogecommerce.societegenerale.eu/vads-payment/',
				'mode' => 'TEST',
				'return_url' => ''
			];

			$query->clear()
				->insert('#__emundus_setup_sync')
				->columns(['type', 'params', 'config', 'published', 'name', 'description', 'enabled', 'icon'])
				->values($this->db->quote('sogecommerce') . ', ' . $this->db->quote('{}') . ', ' . $this->db->quote(json_encode($config)) . ', 0, "Sogecommerce", "Paiement via le service Sogecommerce", 0, "sg_payment.png"');

			$this->db->setQuery($query);
			$tasks[] = $this->db->execute();
		}

		$add_column = EmundusHelperUpdate::addColumn('jos_emundus_setup_workflows_steps', 'description', 'TEXT');
		$tasks[] = $add_column['status'];

		return !in_array(false, $tasks);
	}
}