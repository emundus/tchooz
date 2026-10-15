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
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\Configurations\ChoicesAddonConfiguration;

class Release2_25_5Installer extends ReleaseInstaller
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
			$this->tasks[] = EmundusHelperUpdate::disableEmundusPlugins('emunduswaitingroom', 'system');

			$this->indexEvaluationTables();
			$this->updateChoicesConfiguration();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}



	/**
	 * Evaluation step tables only had their primary key: filtering and counting by file, step or
	 * evaluator scanned them entirely. Same indexes as the ones set when such a table is created.
	 */
	private function indexEvaluationTables(): void
	{
		$existing_tables = $this->db->getTableList();

		$query = $this->db->createQuery()
			->select('DISTINCT ' . $this->db->qn('fl.db_table_name'))
			->from($this->db->qn('#__emundus_setup_workflows_steps', 'esws'))
			->join('INNER', $this->db->qn('#__fabrik_lists', 'fl') . ' ON ' . $this->db->qn('fl.form_id') . ' = ' . $this->db->qn('esws.form_id'));
		$this->db->setQuery($query);

		foreach ($this->db->loadColumn() as $table)
		{
			// A list can outlive its table: skip it rather than fail the whole release.
			if (!in_array($table, $existing_tables, true))
			{
				continue;
			}

			$columns = $this->db->getTableColumns($table);

			foreach (['ccid', 'fnum', 'evaluator'] as $column)
			{
				// Some old tables store these columns as TEXT, which MySQL cannot index without a prefix length.
				if (isset($columns[$column]) && !preg_match('/text|blob/i', $columns[$column]))
				{
					$this->tasks[] = EmundusHelperUpdate::addColumnIndex($table, $column)['status'];
				}
			}
		}
	}

	private function updateChoicesConfiguration(): void
	{
		$addonRepository = new AddonRepository();
		$choicesAddon = $addonRepository->getByName(AddonEnum::CHOICES->value);
		if(!empty($choicesAddon))
		{
			$rejectedVisiblesParameter = $choicesAddon->getParam(ChoicesAddonConfiguration::REJECTED_VISIBLES, 'configuration');
			if(is_null($rejectedVisiblesParameter))
			{
				$choicesAddonParams = $choicesAddon->getParams();
				if(isset($choicesAddonParams['configuration']))
				{
					$choicesAddonParams['configuration'][ChoicesAddonConfiguration::REJECTED_VISIBLES] = 1;
					$choicesAddon->setParams($choicesAddonParams);

					$this->tasks[] = $addonRepository->flush($choicesAddon);
				}
			}
		}
	}
}
