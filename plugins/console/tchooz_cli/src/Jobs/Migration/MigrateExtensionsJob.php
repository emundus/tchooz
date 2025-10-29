<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Migration;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Style\EmundusProgressBar;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateExtensionsJob extends TchoozJob
{
	public function __construct(
		private readonly object $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void {
		$section = $output->section();

		$dbTables       = $this->databaseService->getTables();
		$dbSourceTables = $this->databaseServiceSource->getTables();
		if (empty($dbTables) || empty($dbSourceTables))
		{
			Log::add('No tables found in source or destination database', Log::ERROR, self::getJobName());
			throw new \RuntimeException('No tables found in source or destination database');
		}

		$extension_tables = array_filter($dbTables, function ($table) {
			return str_contains($table, 'hikashop') || str_contains($table, 'dropfiles') || str_contains($table, 'falang');
		});

		$this->databaseService->getDatabase()->transactionStart();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 0')->execute();

		$progressBar = new EmundusProgressBar($section, count($extension_tables));
		$progressBar->setMessage('Migrating extension tables from source to destination');
		$progressBar->start();

		foreach ($extension_tables as $table)
		{
			if (in_array($table, $dbSourceTables))
			{
				if (!$this->databaseService->truncateTable($table))
				{
					Log::add('Error while truncating table ' . $table, Log::ERROR, self::getJobName());

					$this->databaseService->getDatabase()->transactionRollback();

					throw new \RuntimeException('Error while truncating table ' . $table);
				}

				$source_columns = $this->databaseServiceSource->getDatabase()->setQuery('SHOW COLUMNS FROM ' . $this->databaseServiceSource->getDatabase()->quoteName($table))->loadAssocList('Field');
				if (empty($source_columns) || !$this->databaseService->mergeColumns($source_columns, $table))
				{
					Log::add('Error while merging columns in table ' . $table, Log::ERROR, self::getJobName());

					throw new \RuntimeException('Error while merging columns in table ' . $table);
				}

				$datas = $this->databaseServiceSource->getDatas($table);
				if( str_contains($table, 'falang_content'))
				{
					$this->rebuildFalangContent($datas);
				}
				else if (!$this->databaseService->insertDatas($datas, $table))
				{
					Log::add('Error while inserting datas in table ' . $table, Log::ERROR, self::getJobName());

					$this->databaseService->getDatabase()->transactionRollback();

					throw new \RuntimeException('Error while inserting datas in table ' . $table);
				}

				Log::add('Table ' . $table . ' migrated', Log::INFO, self::getJobName());
			}

			$progressBar->advance();
		}
		$progressBar->finish('Extension tables migrated');

		Log::add('Extension tables migrated', Log::INFO, self::getJobName());

		$this->databaseService->getDatabase()->transactionCommit();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 1')->execute();
	}
	
	private function rebuildFalangContent(array $datas)
	{
		try
		{
			$falang_mapping = $this->getFalangMapping();

			$query_source = $this->databaseServiceSource->getDatabase()->getQuery(true);
			$query = $this->databaseService->getDatabase()->getQuery(true);

			foreach ($datas as $data)
			{
				// Need to find the new reference id
				$reference_table = 'jos_'.$data['reference_table'];
				$old_ref_id = $data['reference_id'];

				$pkKey = $falang_mapping[$data['reference_table']]['tablepkID'] ?? 'id';

				// Old object
				$query_source->clear()
					->select('*')
					->from($this->databaseServiceSource->getDatabase()->quoteName($reference_table))
					->where($this->databaseServiceSource->getDatabase()->quoteName($pkKey) . ' = ' . (int) $old_ref_id);
				$old_object = $this->databaseServiceSource->getDatabase()->setQuery($query_source)->loadObject();

				// We search the new id depending on the reference table
				switch($data['reference_table']) {
					case 'menu':
						$query->clear()
							->select($this->databaseService->getDatabase()->quoteName('id'))
							->from($this->databaseService->getDatabase()->quoteName('jos_menu'))
							->where($this->databaseService->getDatabase()->quoteName('alias') . ' = ' . $this->databaseService->getDatabase()->quote($old_object->alias))
							->where($this->databaseService->getDatabase()->quoteName('link') . ' = ' . $this->databaseService->getDatabase()->quote($old_object->link))
							->where($this->databaseService->getDatabase()->quoteName('type') . ' = ' . $this->databaseService->getDatabase()->quote($old_object->type));
						$new_ref_id = $this->databaseService->getDatabase()->setQuery($query)->loadResult();
						break;
					case 'modules':
						$query->clear()
							->select($this->databaseService->getDatabase()->quoteName('id'))
							->from($this->databaseService->getDatabase()->quoteName('jos_modules'))
							->where($this->databaseService->getDatabase()->quoteName('title') . ' = ' . $this->databaseService->getDatabase()->quote($old_object->title))
							->where($this->databaseService->getDatabase()->quoteName('module') . ' = ' . $this->databaseService->getDatabase()->quote($old_object->module))
							->where($this->databaseService->getDatabase()->quoteName('client_id') . ' = ' . $this->databaseService->getDatabase()->quote($old_object->client_id))
							->where($this->databaseService->getDatabase()->quoteName('position') . ' = ' . $this->databaseService->getDatabase()->quote($old_object->position));
						$new_ref_id = $this->databaseService->getDatabase()->setQuery($query)->loadResult();
						break;
					default:
						$new_ref_id = $old_ref_id;
				}

				if(empty($new_ref_id))
				{
					Log::add('Could not find new reference id for falang content id '.$data['id'].' with old reference table '.$data['reference_table'].' and old reference id '.$old_ref_id, Log::WARNING, self::getJobName());
					continue;
				}

				$insert = (array) $data;
				$insert['reference_id'] = $new_ref_id;
				unset($insert['id']);
				$insert = (object) $insert;
				$this->databaseService->getDatabase()->transactionStart();

				if(!$this->databaseService->getDatabase()->insertObject('jos_falang_content', $insert))
				{
					Log::add('Error while inserting falang content id '.$data['id'].' with new reference id '.$new_ref_id, Log::ERROR, self::getJobName());

					$this->databaseService->getDatabase()->transactionRollback();

					throw new \RuntimeException('Error while inserting falang content id '.$data['id'].' with new reference id '.$new_ref_id);
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error while rebuilding falang content: '.$e->getMessage(), Log::ERROR, self::getJobName());

			$this->databaseService->getDatabase()->transactionRollback();

			throw new \RuntimeException('Error while rebuilding falang content: '.$e->getMessage() . ' with query ' . $this->databaseServiceSource->getDatabase()->getQuery()->__toString());
		}

	}

	private function getFalangMapping(): array
	{
		$query_source = $this->databaseServiceSource->getDatabase()->getQuery(true);

		$query_source->select('joomlatablename, tablepkID')
			->from($this->databaseServiceSource->getDatabase()->quoteName('jos_falang_tableinfo'));
		return $this->databaseServiceSource->getDatabase()->setQuery($query_source)->loadAssocList('joomlatablename');
	}

	public static function getJobName(): string {
		return 'Extensions';
	}

	public static function getJobDescription(): ?string {
		return 'Migrate extensions';
	}
}