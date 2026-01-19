<?php

namespace scripts;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_13_0Installer extends ReleaseInstaller
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
			$this->tasks[] = \EmundusHelperUpdate::createTable(
				'jos_emundus_connector_mapping',
				[
					new \EmundusTableColumn('label', \EmundusColumnTypeEnum::VARCHAR, 255, false, ''),
					new \EmundusTableColumn('synchronizer_id', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('target_object', \EmundusColumnTypeEnum::VARCHAR, 100, false, ''),
				],
				[
					new \EmundusTableForeignKey('#__emundus_connector_mapping_synchronizer_id___fk', 'synchronizer_id', 'jos_emundus_setup_sync', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
				]
			)['status'];

			$tableMappingRow = \EmundusHelperUpdate::createTable(
				'jos_emundus_connector_mapping_row',
				[
					new \EmundusTableColumn('mapping_id', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('order', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('source_type', \EmundusColumnTypeEnum::VARCHAR, 50, false, ''),
					new \EmundusTableColumn('source_field', \EmundusColumnTypeEnum::VARCHAR, 255, false, ''),
					new \EmundusTableColumn('target_field', \EmundusColumnTypeEnum::VARCHAR, 255, false, ''),
				],
				[
					new \EmundusTableForeignKey('#__emundus_connector_mapping_id___fk', 'mapping_id', 'jos_emundus_connector_mapping', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
				]
			);
			$this->tasks[] = $tableMappingRow['status'];
			if (!$tableMappingRow['status'])
			{
				$result['message'] .= "\n" . $tableMappingRow['message'];
			}


			$tableRowTransformations = \EmundusHelperUpdate::createTable(
				'jos_emundus_connector_mapping_row_transformation',
				[
					new \EmundusTableColumn('mapping_row_id', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('order', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('type', \EmundusColumnTypeEnum::VARCHAR, 50, false, ''),
					new \EmundusTableColumn('parameters', \EmundusColumnTypeEnum::TEXT, null, true, null),
				],
				[
					new \EmundusTableForeignKey('#__emundus_connector_mapping_row_id___fk', 'mapping_row_id', 'jos_emundus_connector_mapping_row', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
				]
			);
			$this->tasks[] = $tableRowTransformations['status'];
			if (!$tableRowTransformations['status'])
			{
				$result['message'] .= "\n" . $tableRowTransformations['message'];
			}


			$hubspotSynchronizer = $synchronizerRepository->getByType('hubspot');
			if (empty($hubspotSynchronizer))
			{
				$hubspotSynchronizer = new SynchronizerEntity(
					0,
					'hubspot',
					'HubSpot',
					'Synchronisation avec HubSpot CRM',
					[],
					[
						'authentication' => [
							'token'     => '',
						],
						'configuration' => [
							'base_url' => 'https://api.hubapi.com',
						]
					],
					false,
					false,
					'hubspot.svg'
				);

				$this->tasks[] = $synchronizerRepository->flush($hubspotSynchronizer);
			}

			$menuResult = \EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'onboardingmenu',
				'title' => 'Mappings',
				'alias' => 'mappings-list',
				'link' => 'index.php?option=com_emundus&view=mapping',
				'published' => 0,
				'type' => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'access' => 8,
				'params' => [
					'menu_show' => 1,
					'menu_image_css' => 'integration_instructions'
				]
			], 1, 0);
			$this->tasks[] = $menuResult['status'];

			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_external_reference', 'sync_id', 'INT', 11)['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_external_reference', 'reference_object', 'VARCHAR', 100)['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_external_reference', 'reference_attribute', 'VARCHAR', 100)['status'];

			// add event onUserActivation
			$this->tasks[] = \EmundusHelperUpdate::addCustomEvents([['label' => 'onAfterUserActivation', 'published' => 1, 'available' => 1, 'category' => 'User']])['status'];

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