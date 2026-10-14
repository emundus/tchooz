<?php
/**
 * @package     Tchooz\Fixtures\Evaluations\Seeders
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\Evaluations\Seeders;

use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;

\defined('_JEXEC') or die;

/**
 * Writes evaluation rows into the steps' tables, over the published files that reached one of the
 * step's entry statuses.
 *
 * A single-evaluator step gets one evaluation per picked file; a multiple-evaluator step gets
 * 1..$maxPerFile evaluations by distinct evaluators, so a file can be evaluated by X and Y, by X
 * only, or by nobody. Files that already have an evaluation for the step get no new one, but their
 * empty criteria are filled, so criteria added to the form after a first run get data too.
 */
final class EvaluationSeeder
{
	private const CHUNK_SIZE = 500;
	private const MAX_AGE    = 60 * 86400;

	/**
	 * Distinct options drawn per databasejoin criterion, so the counts of the filter values
	 * concentrate on a few options instead of spreading 1 by 1 over the whole referential.
	 */
	private const JOIN_POOL_SIZE = 8;

	private const COMMENTS = [
		'Dossier solide, projet cohérent.',
		'Motivation claire mais expérience limitée.',
		'Pièces complètes, argumentaire à approfondir.',
		'Très bon profil, à retenir en priorité.',
		'Projet intéressant, budget peu détaillé.',
		'Candidature hors périmètre de l\'appel.',
	];

	private DatabaseInterface $db;

	/**
	 * Join pools drawn during this run, by column, so every step sharing the form draws from the
	 * same options and the filter counts add up across steps.
	 *
	 * @var array<string, string[]>
	 */
	private array $joinPools = [];

	public function __construct(DatabaseInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * @param   StepEntity[]  $steps
	 * @param   int[]         $evaluatorIds
	 * @param   int           $coverage     Percentage (0..100) of eligible files to evaluate per step.
	 * @param   int           $maxPerFile   Upper bound of evaluators per file on a multiple step.
	 *
	 * @return  array{created: int, backfilled: int}  Evaluation rows created, existing rows whose criteria were filled.
	 */
	public function seed(array $steps, array $evaluatorIds, int $coverage, int $maxPerFile): array
	{
		$stats = ['created' => 0, 'backfilled' => 0];

		if (empty($evaluatorIds))
		{
			return $stats;
		}

		foreach ($steps as $step)
		{
			if (empty($step->getTable()))
			{
				throw new \RuntimeException('Step ' . $step->getId() . ' has no evaluation table.');
			}

			$criteria = $this->loadCriteria($step);
			$stats['backfilled'] += $this->fillEmptyCriteria($step, $criteria);

			$files = $this->loadEligibleFiles($step);
			shuffle($files);
			$files = array_slice($files, 0, (int) round(count($files) * $coverage / 100));

			$columns  = array_merge(['ccid', 'fnum', 'step_id', 'evaluator', 'updated_by', 'time_date'], array_keys($criteria));

			$rows = [];
			foreach ($files as $file)
			{
				$evaluatorCount = $step->getMultiple() ? random_int(1, max(1, min($maxPerFile, count($evaluatorIds)))) : 1;

				foreach ((array) array_rand(array_flip($evaluatorIds), $evaluatorCount) as $evaluatorId)
				{
					$row = [
						(int) $file->id,
						$this->db->quote($file->fnum),
						$step->getId(),
						(int) $evaluatorId,
						(int) $evaluatorId,
						$this->db->quote(date('Y-m-d H:i:s', time() - random_int(0, self::MAX_AGE))),
					];

					foreach ($criteria as $values)
					{
						$row[] = $this->db->quote($values[array_rand($values)]);
					}

					$rows[] = $row;
				}
			}

			$this->insertRows($step->getTable(), $columns, $rows);
			$stats['created'] += count($rows);
		}

		return $stats;
	}

	/**
	 * Give a random value to every empty criterion of the step's existing evaluations.
	 *
	 * @param   array<string, string[]>  $criteria  column => values to draw from
	 *
	 * @return int  Rows that had at least one criterion filled.
	 */
	private function fillEmptyCriteria(StepEntity $step, array $criteria): int
	{
		if (empty($criteria))
		{
			return 0;
		}

		$sets  = [];
		$empty = [];
		foreach ($criteria as $column => $values)
		{
			$column = $this->db->quoteName($column);

			// ELT picks the n-th value: one random draw per row, from the same pool as new rows.
			$draw    = 'ELT(FLOOR(1 + RAND() * ' . count($values) . '), ' . implode(',', $this->db->quote($values)) . ')';
			$sets[]  = $column . ' = IF(' . $column . ' IS NULL OR ' . $column . ' = ' . $this->db->quote('') . ', ' . $draw . ', ' . $column . ')';
			$empty[] = $column . ' IS NULL OR ' . $column . ' = ' . $this->db->quote('');
		}

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName($step->getTable()))
			->set($sets)
			->where($this->db->quoteName('step_id') . ' = ' . $step->getId())
			->where('(' . implode(' OR ', $empty) . ')');
		$this->db->setQuery($query);
		$this->db->execute();

		return (int) $this->db->getAffectedRows();
	}

	/**
	 * Published files of the step's workflow programs, in one of its entry statuses, with no
	 * evaluation yet for that step.
	 *
	 * @return object[]  {id, fnum}
	 */
	private function loadEligibleFiles(StepEntity $step): array
	{
		if (empty($step->getEntryStatus()))
		{
			return [];
		}

		$alreadyEvaluated = $this->db->getQuery(true)
			->select('1')
			->from($this->db->quoteName($step->getTable(), 'evaluation'))
			->where($this->db->quoteName('evaluation.ccid') . ' = ' . $this->db->quoteName('jecc.id'))
			->where($this->db->quoteName('evaluation.step_id') . ' = ' . $step->getId());

		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('jecc.id'), $this->db->quoteName('jecc.fnum')])
			->from($this->db->quoteName('#__emundus_campaign_candidature', 'jecc'))
			->join('INNER', $this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('jecc.campaign_id'))
			->join('INNER', $this->db->quoteName('#__emundus_setup_programmes', 'sp') . ' ON ' . $this->db->quoteName('sp.code') . ' = ' . $this->db->quoteName('esc.training'))
			->join('INNER', $this->db->quoteName('#__emundus_setup_workflows_programs', 'wp') . ' ON ' . $this->db->quoteName('wp.program_id') . ' = ' . $this->db->quoteName('sp.id'))
			->where($this->db->quoteName('wp.workflow_id') . ' = ' . $step->getWorkflowId())
			->where($this->db->quoteName('jecc.published') . ' = 1')
			->where($this->db->quoteName('jecc.status') . ' IN (' . implode(',', array_map('intval', $step->getEntryStatus())) . ')')
			->where('NOT EXISTS (' . $alreadyEvaluated . ')');
		$this->db->setQuery($query);

		return $this->db->loadObjectList() ?: [];
	}

	/**
	 * Criteria of the step's form whose plugin we know how to fill.
	 *
	 * @return array<string, string[]>  column => values to draw from
	 */
	private function loadCriteria(StepEntity $step): array
	{
		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('fe.name'), $this->db->quoteName('fe.plugin'), $this->db->quoteName('fe.params')])
			->from($this->db->quoteName('#__fabrik_elements', 'fe'))
			->join('INNER', $this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
			->where($this->db->quoteName('ffg.form_id') . ' = ' . (int) $step->getFormId())
			->where($this->db->quoteName('fe.published') . ' = 1')
			->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('criteria\\_%'))
			->where($this->db->quoteName('fe.plugin') . ' IN (' . implode(',', $this->db->quote([ElementPluginEnum::NUMERIC->value, ElementPluginEnum::TEXTAREA->value, ElementPluginEnum::DATABASEJOIN->value])) . ')');
		$this->db->setQuery($query);

		$criteria = [];
		foreach ($this->db->loadObjectList() ?: [] as $element)
		{
			$values = match (ElementPluginEnum::from($element->plugin))
			{
				ElementPluginEnum::NUMERIC      => array_map('strval', range(0, 20)),
				ElementPluginEnum::TEXTAREA     => self::COMMENTS,
				ElementPluginEnum::DATABASEJOIN => $this->joinPools[$element->name] ??= $this->loadJoinPool(json_decode($element->params, true) ?: []),
			};

			if (!empty($values))
			{
				$criteria[$element->name] = $values;
			}
		}

		return $criteria;
	}

	/**
	 * A few random keys of the databasejoin's referential.
	 *
	 * @return string[]
	 */
	private function loadJoinPool(array $params): array
	{
		// Multiple-choice displays store their values in a _repeat table, not in the column.
		if (empty($params['join_db_name']) || empty($params['join_key_column']) || in_array($params['database_join_display_type'] ?? '', ['checkbox', 'multilist'], true))
		{
			return [];
		}

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($params['join_key_column']))
			->from($this->db->quoteName($params['join_db_name']))
			->order('RAND()');
		$this->db->setQuery($query, 0, self::JOIN_POOL_SIZE);

		return array_map('strval', $this->db->loadColumn() ?: []);
	}

	/**
	 * @param   string[]                            $columns
	 * @param   array<int, array<int, int|string>>  $rows     Values already cast or quoted, in $columns order.
	 */
	private function insertRows(string $table, array $columns, array $rows): void
	{
		foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk)
		{
			$query = $this->db->getQuery(true)
				->insert($this->db->quoteName($table))
				->columns($this->db->quoteName($columns));

			foreach ($chunk as $row)
			{
				$query->values(implode(',', $row));
			}

			$this->db->setQuery($query);
			$this->db->execute();
		}
	}
}
