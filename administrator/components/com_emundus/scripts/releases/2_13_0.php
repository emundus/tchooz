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
use EmundusHelperUpdate;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Synchronizer\SynchronizerContextEnum;
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

			$eventNames = ['onAfterSaveEmundusUser'];
			$query->clear()
				->update($this->db->quoteName('jos_emundus_plugin_events'))
				->set($this->db->quoteName('available') . ' = 1')
				->set($this->db->quoteName('description') . ' = ' . $this->db->quote(''))
				->where($this->db->quoteName('label') . ' IN (' . implode(',', $this->db->quote($eventNames)) . ')');

			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();

			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_exports', 'result', 'LONGTEXT')['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_fabrik_elements', 'alias', 'VARCHAR', 255)['status'];

			// Move all aliases from params to the new column
			$query->clear()
				->select($this->db->quoteName(['id', 'params', 'alias']))
				->from($this->db->quoteName('jos_fabrik_elements'));
			$this->db->setQuery($query);
			$elements = $this->db->loadObjectList();

			foreach($elements as $element)
			{
				$params = json_decode($element->params);
				if (!empty($params->alias) && empty($element->alias))
				{
					$element->alias = $params->alias;
					$this->tasks[] = $this->db->updateObject('jos_fabrik_elements', $element, 'id');
				}
			}

			// Prefill new params
			$query->clear()
				->select('params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('com_emundus'));
			$this->db->setQuery($query);
			$emundusParams = $this->db->loadResult();

			$params = json_decode($emundusParams);
			$pdfDefaultSynthesis = $params->default_synthesis_pdf ?? [];
			if(empty($pdfDefaultSynthesis))
			{
				$pdfDefaultSynthesis = [
					'default_synthesis_pdf0' => (object)[
						'element' => 'id'
					],
					'default_synthesis_pdf1' => (object)[
						'element' => 'fnum'
					],
					'default_synthesis_pdf2' => (object)[
						'element' => 'email'
					],
					'default_synthesis_pdf3' => (object)[
						'element' => 'submitted_date'
					],
					'default_synthesis_pdf4' => (object)[
						'element' => 'printed_date'
					],
					'default_synthesis_pdf5' => (object)[
						'element' => 'status'
					],
					'default_synthesis_pdf6' => (object)[
						'element' => 'stickers'
					],
				];

				\EmundusHelperUpdate::updateComponentParameter('com_emundus', 'default_synthesis_pdf', $pdfDefaultSynthesis);
			}

			$pdfDefaultHeader = $params->default_header_pdf ?? [];
			if(empty($pdfDefaultHeader))
			{
				$pdfDefaultHeader = [
					'default_header_pdf0' => (object)[
						'element' => 'fullname'
					],
					'default_header_pdf1' => (object)[
						'element' => 'email'
					],
					'default_header_pdf2' => (object)[
						'element' => 'fnum'
					],
				];

				\EmundusHelperUpdate::updateComponentParameter('com_emundus', 'default_header_pdf', $pdfDefaultHeader);
			}

			$excelDefaultSynthesis = $params->default_synthesis_excel ?? [];
			if(empty($excelDefaultSynthesis))
			{
				$excelDefaultSynthesis = [
					'default_synthesis_excel0' => (object)[
						'element' => 'fnum'
					],
					'default_synthesis_excel1' => (object)[
						'element' => 'status'
					],
					'default_synthesis_excel2' => (object)[
						'element' => 'lastname'
					],
					'default_synthesis_excel3' => (object)[
						'element' => 'firstname'
					],
					'default_synthesis_excel4' => (object)[
						'element' => 'email'
					],
				];

				\EmundusHelperUpdate::updateComponentParameter('com_emundus', 'default_synthesis_excel', $excelDefaultSynthesis);
			}

			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_task_synctranslations', 'synctranslations', null, 'plugin', 1, 'task');

			// Create scheduler task for gantry mode
			$execution_rules = [
				'rule-type'     => 'interval-days',
				'interval-days' => '1',
				'exec-day'      => date('d'),
				'exec-time'     => '23:30',
			];
			$cron_rules      = [
				'type' => 'interval',
				'exp'  => 'P1D',
			];

			$this->tasks[] = \EmundusHelperUpdate::createSchedulerTask('Sync translations between database and files', 'plg_task_synctranslations', $execution_rules, $cron_rules);

			// Check if indexes exist and create them if not
			$queryStr = 'show index from jos_emundus_setup_languages where key_name = "jos_emundus_setup_languages_reference_id_index";';
			$this->db->setQuery($queryStr);
			$index1 = $this->db->loadObject();
			if(empty($index1))
			{
				$queryStr = 'create index jos_emundus_setup_languages_reference_id_index on jos_emundus_setup_languages (reference_id);';
				$this->db->setQuery($queryStr);
				$this->tasks[] = $this->db->execute();
			}

			$queryStr = 'show index from jos_emundus_setup_languages where key_name = "jos_emundus_setup_languages_created_by_index";';
			$this->db->setQuery($queryStr);
			$index2 = $this->db->loadObject();
			if(empty($index2))
			{
				$queryStr = 'create index jos_emundus_setup_languages_created_by_index on jos_emundus_setup_languages (created_by);';
				$this->db->setQuery($queryStr);
				$this->tasks[] = $this->db->execute();
			}

			$this->installDocaposte();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function installDocaposte(): void
	{
		$query = $this->db->createQuery();

		$synchronizerRepository = new SynchronizerRepository();
		$docaposteSynchronizer  = $synchronizerRepository->getByType('docaposte');
		if (empty($docaposteSynchronizer))
		{
			$docaposteSynchronizer = new SynchronizerEntity(
				0,
				'docaposte',
				'Docaposte',
				'Signature électronique via Docaposte',
				[],
				[
					'authentication' => [],
					'configuration'  => []
				],
				false,
				false,
				'docaposte.svg',
				null,
				SynchronizerContextEnum::NUMERIC_SIGN
			);
		}

		$this->tasks[] = $synchronizerRepository->flush($docaposteSynchronizer);

		// Emails
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('docaposte_transaction_initiation'));
		$this->db->setQuery($query);
		$docaposte_transaction_initiation_email = $this->db->loadResult();

		if (empty($docaposte_transaction_initiation_email))
		{
			$docaposte_transaction_initiation_email = [
				'lbl'        => 'docaposte_transaction_initiation',
				'subject'    => 'Demande de signature électronique sur la plateforme [SITE_NAME] / Electronic signature request on the [SITE_NAME] platform',
				'emailfrom'  => '',
				'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/docaposte/transaction_initiation_template.html'),
				'type'       => 1,
				'published'  => 0,
				'email_tmpl' => 1,
				'category'   => 'Système',
				'button'     => ''
			];
			$docaposte_transaction_initiation_email = (object) $docaposte_transaction_initiation_email;
			$this->db->insertObject('#__emundus_setup_emails', $docaposte_transaction_initiation_email);
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('docaposte_transaction_reminder'));
		$this->db->setQuery($query);
		$docaposte_transaction_reminder_email = $this->db->loadResult();

		if (empty($docaposte_transaction_reminder_email))
		{
			$docaposte_transaction_reminder_email = [
				'lbl'        => 'docaposte_transaction_reminder',
				'subject'    => 'Rappel – Signature de document en attente sur la plateforme [SITE_NAME] / Reminder – Document signature pending on the [SITE_NAME] platform',
				'emailfrom'  => '',
				'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/docaposte/transaction_reminder_template.html'),
				'type'       => 1,
				'published'  => 0,
				'email_tmpl' => 1,
				'category'   => 'Système',
				'button'     => ''
			];
			$docaposte_transaction_reminder_email = (object) $docaposte_transaction_reminder_email;
			$this->tasks[]                        = $this->db->insertObject('#__emundus_setup_emails', $docaposte_transaction_reminder_email);
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('docaposte_transaction_cancellation'));
		$this->db->setQuery($query);
		$docaposte_transaction_cancellation_email = $this->db->loadResult();

		if (empty($docaposte_transaction_cancellation_email))
		{
			$docaposte_transaction_cancellation_email = [
				'lbl'        => 'docaposte_transaction_cancellation',
				'subject'    => 'Procédure de signature annulée sur la plateforme [SITE_NAME] / Signature process cancelled on the [SITE_NAME] platform',
				'emailfrom'  => '',
				'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/docaposte/transaction_cancellation_template.html'),
				'type'       => 1,
				'published'  => 0,
				'email_tmpl' => 1,
				'category'   => 'Système',
				'button'     => ''
			];
			$docaposte_transaction_cancellation_email = (object) $docaposte_transaction_cancellation_email;
			$this->tasks[]                            = $this->db->insertObject('#__emundus_setup_emails', $docaposte_transaction_cancellation_email);
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('docaposte_transaction_completion'));
		$this->db->setQuery($query);
		$docaposte_transaction_completion_email = $this->db->loadResult();

		if (empty($docaposte_transaction_completion_email))
		{
			$docaposte_transaction_completion_email = [
				'lbl'        => 'docaposte_transaction_completion',
				'subject'    => 'Procédure de signature finalisée sur la plateforme [SITE_NAME] / Signature process completed on the [SITE_NAME] platform',
				'emailfrom'  => '',
				'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/docaposte/transaction_completion_template.html'),
				'type'       => 1,
				'published'  => 0,
				'email_tmpl' => 1,
				'category'   => 'Système',
				'button'     => ''
			];
			$docaposte_transaction_completion_email = (object) $docaposte_transaction_completion_email;
			$this->tasks[]                          = $this->db->insertObject('#__emundus_setup_emails', $docaposte_transaction_completion_email);
		}

		$tags = [
			['tag' => 'DOCAPOSTE_URL_SIGN', 'request' => '[DOCAPOSTE_URL_SIGN]', 'description' => 'Lien Docaposte vers le document à signer', 'published' => 0],
			['tag' => 'DOCAPOSTE_DOCUMENT_NAME', 'request' => '[DOCAPOSTE_DOCUMENT_NAME]', 'description' => 'Nom du document Docaposte à signer', 'published' => 0]
		];

		// add new default tag
		foreach ($tags as $tag)
		{
			$query->clear()
				->select('COUNT(id)')
				->from($this->db->quoteName('#__emundus_setup_tags'))
				->where($this->db->quoteName('tag') . ' = ' . $this->db->quote($tag['tag']));

			$count = $this->db->setQuery($query)->loadResult();
			if ($count > 0)
			{
				continue;
			}

			$query->clear()
				->insert($this->db->quoteName('#__emundus_setup_tags'))
				->columns($this->db->quoteName(['tag', 'request', 'description', 'published']))
				->values(
					$this->db->quote($tag['tag']) . ', ' .
					$this->db->quote($tag['request']) . ', ' .
					$this->db->quote($tag['description']) . ', ' .
					$this->db->quote($tag['published'])
				);

			$this->tasks[] = $this->db->setQuery($query)->execute();
		}

		$event_added = EmundusHelperUpdate::addCustomEvents([
			['label' => 'onAfterSignRequestCreated', 'published' => 0, 'category' => 'Sign'],
			['label' => 'onAfterSignRequestCancelled', 'published' => 0, 'category' => 'Sign'],
		]);
		$this->tasks[] = $event_added['status'];
	}
}