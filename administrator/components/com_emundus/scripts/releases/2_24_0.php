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

class Release2_24_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$query = $this->db->createQuery();

			$importTable = \EmundusHelperUpdate::createTable(
				'jos_emundus_import',
				[
					new \EmundusTableColumn('created_at', \EmundusColumnTypeEnum::DATETIME, null, false, null),
					new \EmundusTableColumn('created_by', \EmundusColumnTypeEnum::INT, null, false, null),
					new \EmundusTableColumn('type', \EmundusColumnTypeEnum::VARCHAR, 50, false, ''),
					new \EmundusTableColumn('filename', \EmundusColumnTypeEnum::VARCHAR, 255, true, null),
					new \EmundusTableColumn('original_filename', \EmundusColumnTypeEnum::VARCHAR, 255, true, null),
					new \EmundusTableColumn('format', \EmundusColumnTypeEnum::VARCHAR, 10, false, ''),
					new \EmundusTableColumn('conflict_mode', \EmundusColumnTypeEnum::VARCHAR, 20, false, 'skip'),
					new \EmundusTableColumn('progress', \EmundusColumnTypeEnum::FLOAT, null, false, '0'),
					new \EmundusTableColumn('total_rows', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('last_processed_row', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('report', \EmundusColumnTypeEnum::JSON, null, true, null),
					new \EmundusTableColumn('task_id', \EmundusColumnTypeEnum::INT, null, true, null),
					new \EmundusTableColumn('cancelled', \EmundusColumnTypeEnum::TINYINT, 1, false, '0'),
					new \EmundusTableColumn('failed', \EmundusColumnTypeEnum::TINYINT, 1, false, '0'),
				]
			);
			$this->tasks[] = $importTable['status'];
			if (!$importTable['status'])
			{
				$result['message'] .= "\n" . $importTable['message'];
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=imports&layout=imports'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('topmenu'));
			$this->db->setQuery($query);
			$exists = $this->db->loadResult();

			if (!$exists)
			{
				$datas        = [
					'menutype'     => 'topmenu',
					'title'        => 'Imports',
					'alias'        => 'my-imports',
					'link'         => 'index.php?option=com_emundus&view=imports&layout=imports',
					'type'         => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'params'       => [
						'menu_image_css' => 'archive',
						'menu_show'      => 0
					]
				];
				$imports_menu = \EmundusHelperUpdate::addJoomlaMenu($datas);
				if($this->tasks[] = $imports_menu['status'])
				{
					$this->tasks[] = \EmundusHelperUpdate::insertFalangTranslation(1, $imports_menu['id'], 'menu', 'title', 'Mes imports');
				}
			}

			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_task_purgeimports', 'purgeimports', null, 'plugin', 1, 'task');

			$execution_rules = [
				'rule-type'     => 'interval-days',
				'interval-days' => '1',
				'exec-day'      => date('d'),
				'exec-time'     => '03:30',
			];
			$cron_rules = [
				'type' => 'interval',
				'exp'  => 'P1D',
			];
			$this->tasks[] = \EmundusHelperUpdate::createSchedulerTask('Purge finished imports', 'plg_task_purgeimports_task_get', $execution_rules, $cron_rules);

			$result['status'] = !in_array(false, $this->tasks, true);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function associatePaymentMethod(string $methodName, int $serviceId): bool
	{
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_setup_payment_method'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($methodName));
		$this->db->setQuery($query);
		$methodId = $this->db->loadResult();

		if (empty($methodId))
		{
			return true;
		}

		$query->clear()
			->select('payment_method_id')
			->from($this->db->quoteName('#__emundus_setup_payment_method_sync'))
			->where($this->db->quoteName('payment_method_id') . ' = ' . $this->db->quote($methodId))
			->andWhere($this->db->quoteName('service_id') . ' = ' . $this->db->quote($serviceId));
		$this->db->setQuery($query);

		if (!empty($this->db->loadResult()))
		{
			return true;
		}

		$association                    = new \stdClass();
		$association->payment_method_id = $methodId;
		$association->service_id        = $serviceId;

		return $this->db->insertObject('#__emundus_setup_payment_method_sync', $association);
	}

}
