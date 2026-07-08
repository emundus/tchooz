<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Joomla\Database\DatabaseAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'tchooz:fix:evaluations-data', description: 'Restore evaluator lost during the evaluations migration')]
class TchoozFixEvaluationsDataCommand extends TchoozCommand
{
	use DatabaseAwareTrait;

	protected static $defaultName = 'tchooz:fix:evaluations-data';

	private const INDEX_LABEL = 'evaluation_row_id';

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> restores the <comment>evaluator</comment> column "
			. "left NULL/0 in evaluation tables migrated from the old fabrik tables.\n"
			. "It relies on <comment>#__emundus_indexes</comment> rows saved during the migration (label = '" . self::INDEX_LABEL . "') "
			. "to find back the original row, and only writes when the old and new <comment>fnum</comment> match.";

		$this->addOption('table', null, InputOption::VALUE_OPTIONAL, 'Restrict the fix to a single new table (e.g. jos_emundus_evaluations_00)');
		$this->addOption('check-only', null, InputOption::VALUE_NONE, 'List what would be updated without writing anything');
		$this->setDescription('Restore evaluator lost during the evaluations migration');
		$this->setHelp($help);
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$ioStyle = new SymfonyStyle($input, $output);
		$ioStyle->title('Restoring evaluations data lost during migration');

		$checkOnly = (bool) $input->getOption('check-only');
		$onlyTable = $input->getOption('table');

		if ($checkOnly)
		{
			$ioStyle->note('Check-only mode: no data will be written');
		}

		$pairs = $this->getTablePairs($onlyTable);

		if (empty($pairs))
		{
			$ioStyle->warning('No migrated table found in #__emundus_indexes for label "' . self::INDEX_LABEL . '"');

			return Command::SUCCESS;
		}

		$dbTables        = $this->db->getTableList();
		$totalFixed      = 0;
		$totalMismatched = 0;

		foreach ($pairs as $pair)
		{
			$oldTable = $pair->old_table;
			$newTable = $pair->new_table;

			if (empty($oldTable) || empty($newTable))
			{
				continue;
			}

			if (!in_array($oldTable, $dbTables, true) || !in_array($newTable, $dbTables, true))
			{
				$ioStyle->writeln("- <comment>$oldTable -> $newTable</comment>: skipped, old or new table does not exist anymore");
				continue;
			}

			$oldColumns = $this->getColumns($oldTable);
			$newColumns = $this->getColumns($newTable);

			if (!in_array('user', $oldColumns, true) || !in_array('fnum', $oldColumns, true))
			{
				continue;
			}

			if (!in_array('evaluator', $newColumns, true) || !in_array('fnum', $newColumns, true))
			{
				continue;
			}

			$brokenRows = $this->getBrokenRows($newTable);

			if (empty($brokenRows))
			{
				continue;
			}

			$ioStyle->section("$oldTable -> $newTable (" . count($brokenRows) . ' row(s) to check)');

			foreach ($brokenRows as $row)
			{
				$oldId = $this->findOldIndex($oldTable, $newTable, (int) $row->id);

				if (empty($oldId))
				{
					$ioStyle->writeln("  row #$row->id: no matching index found, skipped");
					continue;
				}

				$oldRow = $this->getOldRow($oldTable, $oldId);

				if (empty($oldRow))
				{
					$ioStyle->writeln("  row #$row->id: old row #$oldId not found in $oldTable, skipped");
					continue;
				}

				if ((string) $oldRow->fnum !== (string) $row->fnum)
				{
					$ioStyle->writeln("  <error>fnum mismatch</error> on row #$row->id ($newTable): expected {$row->fnum}, found {$oldRow->fnum} in $oldTable#$oldId");
					$totalMismatched++;
					continue;
				}

				if (empty($oldRow->user))
				{
					$ioStyle->writeln("  row #$row->id: old row #$oldId in $oldTable has no user either, skipped");
					continue;
				}

				$ioStyle->writeln(
					"  row #$row->id: evaluator " . var_export($row->evaluator, true) . ' -> ' . var_export($oldRow->user, true)
				);

				if (!$checkOnly)
				{
					$this->updateRow($newTable, (int) $row->id, $oldRow->user);
				}

				$totalFixed++;
			}
		}

		$ioStyle->success(($checkOnly ? '[check-only] ' : '') . "$totalFixed row(s) restored, $totalMismatched fnum mismatch(es) skipped");

		return Command::SUCCESS;
	}

	private function getTablePairs(?string $onlyTable): array
	{
		$query = $this->db->createQuery();
		$query->select(
			'DISTINCT JSON_UNQUOTE(JSON_EXTRACT(params, "$.old_table")) as old_table, '
			. 'JSON_UNQUOTE(JSON_EXTRACT(params, "$.new_table")) as new_table'
		)
			->from($this->db->quoteName('#__emundus_indexes'))
			->where('label = ' . $this->db->quote(self::INDEX_LABEL));

		$this->db->setQuery($query);
		$pairs = $this->db->loadObjectList();

		if (!empty($onlyTable))
		{
			$pairs = array_values(array_filter($pairs, fn($pair) => $pair->new_table === $onlyTable));
		}

		return $pairs;
	}

	private function getColumns(string $table): array
	{
		$columns = $this->db->setQuery('SHOW COLUMNS FROM ' . $this->db->quoteName($table))->loadAssocList();

		return array_column($columns, 'Field');
	}

	private function getBrokenRows(string $newTable): array
	{
		$query = $this->db->createQuery();
		$query->select('id, fnum, evaluator')
			->from($this->db->quoteName($newTable))
			->where('(evaluator IS NULL OR evaluator = 0)');

		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}

	private function findOldIndex(string $oldTable, string $newTable, int $newId): string
	{
		$query = $this->db->createQuery();
		$query->select('old_index')
			->from($this->db->quoteName('#__emundus_indexes'))
			->where('label = ' . $this->db->quote(self::INDEX_LABEL))
			->where('new_index = ' . $this->db->quote($newId))
			->where('JSON_EXTRACT(params, "$.old_table") = ' . $this->db->quote($oldTable))
			->where('JSON_EXTRACT(params, "$.new_table") = ' . $this->db->quote($newTable));

		$this->db->setQuery($query);

		return (string) $this->db->loadResult();
	}

	private function getOldRow(string $oldTable, string $oldId): ?object
	{
		$query = $this->db->createQuery();
		$query->select('id, fnum, user')
			->from($this->db->quoteName($oldTable))
			->where('id = ' . $this->db->quote($oldId));

		$this->db->setQuery($query);

		return $this->db->loadObject();
	}

	private function updateRow(string $newTable, int $id, $evaluator): void
	{
		$query = $this->db->createQuery();
		$query->update($this->db->quoteName($newTable))
			->set('evaluator = ' . $this->db->quote($evaluator))
			->where('id = ' . $id);

		$this->db->setQuery($query);
		$this->db->execute();
	}
}
