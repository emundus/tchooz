<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Synchronizer\SynchronizerContextEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

use EmundusTableColumn;
use EmundusTableForeignKey;
use EmundusTableForeignKeyOnEnum;
use EmundusColumnTypeEnum;

class Release2_12_0Installer extends ReleaseInstaller
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
			$synchronizerRepository = new SynchronizerRepository();
			$docusignSynchronizer = $synchronizerRepository->getByType('docusign');
			if (empty($docusignSynchronizer))
			{
				$docusignSynchronizer = new SynchronizerEntity(
					0,
					'docusign',
					'Docusign',
					'Signature électronique via Docusign',
					[],
					[
						'authentication' => [
							'user_guid'     => '',
							'account_id' => '',
							'secret_key' => '',
							'rsa_private_key' => '',
							'integration_key' => ''
						],
						'configuration' => [
							'mode' => 'TEST',
						]
					],
					false,
					false,
					'docusign.svg',
					null,
					SynchronizerContextEnum::NUMERIC_SIGN
				);
			}

			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_sign_requests', 'subject', 'VARCHAR', 255, 1, '')['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_sign_requests_signers', 'order', 'INT', 11, 1, null)['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_sign_requests_signers', 'anchor', 'VARCHAR', 255, 1, null)['status'];

			$this->tasks[] = $synchronizerRepository->flush($docusignSynchronizer);

			$manifest = '{"name":"plg_emundus_signature_requests","type":"plugin","creationDate":"April 2025","author":"eMundus","copyright":"","authorEmail":"","authorUrl":"","version":"1.0.0","description":"Plugin to integrate Signature Requests Management on some events","group":"","changelogurl":"","namespace":"Joomla\\Plugin\\Emundus\\SignatureRequests","filename":"signature_requests"}';
			$this->tasks[] = \EmundusHelperUpdate::installExtension(
				'plg_emundus_signature_requests',
				'signature_requests',
				$manifest,
				'plugin',
				1,
				'emundus'
			);

			$manifest = '{"name":"plg_task_signature_requests","type":"plugin","creationDate":"2025-04","author":"eMundus","copyright":"(C) 2025 Open Source Matters, Inc.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"2.12.0","description":"","group":"","changelogurl":"","namespace":"Joomla\\Plugin\\Task\\SignatureRequests","filename":"signature_requests"}';
			$this->tasks[] = \EmundusHelperUpdate::installExtension(
				'plg_task_signature_requests',
				'signature_requests',
				$manifest,
				'plugin',
				1,
				'task'
			);

			$this->tasks[] = \EmundusHelperUpdate::createNewAction('export', ['multi' => 1, 'c' => 1, 'r' => 0, 'u' => 0, 'd' => 0]);

			$this->tasks[] = $this->db->setQuery('ALTER TABLE `jos_emundus_task` MODIFY `action_id` INT(11) NULL;')->execute();

			// Create table jos_emundus_sign_requests
			$columns      = [
				new EmundusTableColumn('created_at', EmundusColumnTypeEnum::DATETIME),
				new EmundusTableColumn('created_by', EmundusColumnTypeEnum::INT, 11),
				new EmundusTableColumn('progress', EmundusColumnTypeEnum::INT, 11, false, 0),
				new EmundusTableColumn('filename', EmundusColumnTypeEnum::VARCHAR, 255, false),
				new EmundusTableColumn('expired_at', EmundusColumnTypeEnum::DATETIME, null, true),
				new EmundusTableColumn('task_id', EmundusColumnTypeEnum::INT, 11, true),
				new EmundusTableColumn('hits', EmundusColumnTypeEnum::INT, 11, false, 0),
				new EmundusTableColumn('format', EmundusColumnTypeEnum::VARCHAR, 10, true),
				new EmundusTableColumn('cancelled', EmundusColumnTypeEnum::TINYINT, 3, true),
				new EmundusTableColumn('failed', EmundusColumnTypeEnum::TINYINT, 3, true, 0),
			];
			$foreign_keys = [
				new EmundusTableForeignKey('fk_export_task', 'task_id', 'jos_emundus_task', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			];
			$this->tasks[]      = \EmundusHelperUpdate::createTable('jos_emundus_exports', $columns, $foreign_keys)['status'];

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=export_select_columns&layout=exports'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('topmenu'));
			$this->db->setQuery($query);
			$exists = $this->db->loadResult();

			if(!$exists)
			{
				$datas        = [
					'menutype'     => 'topmenu',
					'title'        => 'Exports',
					'alias'        => 'my-exports',
					'link'         => 'index.php?option=com_emundus&view=export_select_columns&layout=exports',
					'type'         => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'params'       => [
						'menu_image_css' => 'archive',
						'menu_show'      => 0
					]
				];
				$exports_menu = \EmundusHelperUpdate::addJoomlaMenu($datas);
				if($this->tasks[] = $exports_menu['status'])
				{
					$this->tasks[] = \EmundusHelperUpdate::insertFalangTranslation(1, $exports_menu['id'], 'menu', 'title', 'Exports');
				}
			}

			\EmundusHelperUpdate::installExtension('plg_task_purgeexports', 'purgeexports', null, 'plugin', 1, 'task');
			$execution_rules = [
				'rule-type'     => 'interval-days',
				'exec-day'      => date('d'),
				'interval-days'     => '1',
				'exec-time'     => '04:00',
			];
			$cron_rules      = [
				'type' => 'interval',
				'exp'  => 'P1D',
			];
			$this->tasks[] = \EmundusHelperUpdate::createSchedulerTask('Purge expired exports', 'plg_task_purgeexports_task_get', $execution_rules, $cron_rules);


			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=files&tmpl=component&layout=export&format=raw'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('actions'));
			$this->db->setQuery($query);
			$exists = $this->db->loadResult();

			if(!$exists)
			{
				$query->clear()
					->select('parent_id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&task=export_pdf&fnums={fnums}&user={applicant_id}'))
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('actions'));
				$this->db->setQuery($query);
				$parent_id = $this->db->loadResult();

				$datas        = [
					'menutype'     => 'actions',
					'title'        => 'Exporter',
					'alias'        => 'export',
					'link'         => 'index.php?option=com_emundus&view=files&tmpl=component&layout=export&format=raw',
					'type'         => 'url',
					'component_id' => 0
				];
				$export_action_menu = \EmundusHelperUpdate::addJoomlaMenu($datas, $parent_id, 0);
				if($this->tasks[] = $export_action_menu['status'])
				{
					$this->tasks[] = \EmundusHelperUpdate::insertFalangTranslation(1, $export_action_menu['id'], 'menu', 'title', 'Export');
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