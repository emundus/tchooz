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

class MigrateFabrikJob extends TchoozJob
{
	const FABRIK_TABLES = [
		'jos_fabrik_cron',
		'jos_fabrik_elements',
		'jos_fabrik_formgroup',
		'jos_fabrik_forms',
		'jos_fabrik_groups',
		'jos_fabrik_joins',
		'jos_fabrik_jsactions',
		'jos_fabrik_lists',
		'jos_fabrik_validations',
		'jos_fabrik_visualizations',
	];

	const PATTERNS = [
		// DATABASE
		'JFactory::getDbo()'                => 'JFactory::getContainer()->get(\'DatabaseDriver\')',
		'$query = $db->createQuery();'      => '$query = $db->createQuery();',

		// INPUT
		'JFactory::getApplication()->input' => 'JFactory::getApplication()->getInput()',
		'$app->input'                       => '$app->getInput()',
		'$mainframe->input'                 => '$mainframe->getInput()',
		'JRequest::getVar'                  => 'JFactory::getApplication()->getInput()->get',

		// SESSION
		'JFactory::getSession()'            => 'JFactory::getApplication()->getSession()',

		// USER
		'JFactory::getUser()'               => 'JFactory::getApplication()->getIdentity()',

		// CONFIG
		'getCfg'                            => 'get',
		'JFactory::getConfig()'             => 'JFactory::getApplication()->get(',
	];

	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService,
		private readonly int $limit = 1000
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$section = $output->section();

		$dbTables       = $this->databaseService->getTables();
		$dbSourceTables = $this->databaseServiceSource->getTables();
		if (empty($dbTables) || empty($dbSourceTables))
		{
			Log::add('No tables found in source or destination database', Log::ERROR, self::getJobName());
			throw new \RuntimeException('No tables found in source or destination database');
		}

		$fabrikTables = array_filter($dbSourceTables, function ($table) {
			return in_array($table, self::FABRIK_TABLES);
		});

		$this->databaseService->startTransaction();

		$progressBar = new EmundusProgressBar($section, count($fabrikTables));
		$progressBar->setMessage('Migrating Fabrik tables from source to destination');
		$progressBar->start();

		foreach ($fabrikTables as $table)
		{
			if (in_array($table, $dbTables))
			{
				if (!$this->databaseService->truncateTable($table))
				{
					Log::add('Error while truncating table ' . $table, Log::ERROR, self::getJobName());

					$this->databaseService->rollbackTransaction();

					throw new \RuntimeException('Error while truncating table ' . $table);
				}

				$count = $this->databaseServiceSource->getDatasCount($table);

				// If datas are too many, we have to split the insertions
				if ($count > $this->limit)
				{
					$limit = $this->limit;
					$offset = 0;
					$datas = $this->databaseServiceSource->getDatas($table, $limit, $offset);
					while (!empty($datas))
					{
						if (!$this->databaseService->insertDatas($datas, $table))
						{
							Log::add('Error while inserting datas in table ' . $table, Log::ERROR, self::getJobName());

							$this->databaseService->rollbackTransaction();

							throw new \RuntimeException('Error while inserting datas in table ' . $table);
						}

						$offset += $limit;
						$datas = $this->databaseServiceSource->getDatas($table, $limit, $offset);
					}
				}
				else
				{
					$datas = $this->databaseServiceSource->getDatas($table);
					if (!$this->databaseService->insertDatas($datas, $table))
					{
						Log::add('Error while inserting datas in table ' . $table, Log::ERROR, self::getJobName());

						$this->databaseService->rollbackTransaction();

						throw new \RuntimeException('Error while inserting datas in table ' . $table);
					}
				}
			}
			$progressBar->advance();

			$this->databaseService->commitTransaction();
		}
		$progressBar->finish('Fabrik tables migrated');

		Log::add('Fabrik tables migrated', Log::INFO, self::getJobName());
	}

	private function getCode(string $type): array
	{
		$results = [];

		$query = $this->databaseService->getDatabase()->getQuery(true);

		switch ($type)
		{
			case 'fabrik_elements':

				$query->clear()
					->select([$this->databaseService->getDatabase()->quoteName('id'), $this->databaseService->getDatabase()->quoteName('default'), $this->databaseService->getDatabase()->quoteName('params')])
					->from($this->databaseService->getDatabase()->quoteName('jos_fabrik_elements'));
				$this->databaseService->getDatabase()->setQuery($query);
				$elements = $this->databaseService->getDatabase()->loadAssocList();

				foreach ($elements as $element)
				{
					$to_update = false;
					$query->clear();

					if (!empty($element['default']))
					{
						$to_update          = true;
						$element['default'] = $this->replace($element['default']);
						$query->set($this->databaseService->getDatabase()->quoteName('default') . ' = ' . $this->databaseService->getDatabase()->quote($element['default']));
					}

					if (!empty($element['params']))
					{
						$params = json_decode($element['params'], true);
						if (!empty($params['calc_calculation']))
						{
							$to_update                  = true;
							$params['calc_calculation'] = $this->replace($params['calc_calculation'], false);
						}

						if (!empty($params['validations']))
						{
							foreach ($params['validations']['plugin'] as $key => $validation)
							{
								if ($validation == 'php' && (!empty($params['php-code'][$key]) || !empty($params['php-validation_condition'][$key])))
								{
									$to_update = true;
									if (!empty($params['php-code'][$key]))
									{
										$params['php-code'][$key] = $this->replace($params['php-code'][$key], false);
									}
									if (!empty($params['php-validation_condition'][$key]))
									{
										$params['php-validation_condition'][$key] = $this->replace($params['php-validation_condition'][$key], false);
									}
								}

								if ($validation == 'notempty' && !empty($params['notempty-validation_condition'][$key]))
								{
									$to_update                                     = true;
									$params['notempty-validation_condition'][$key] = $this->replace($params['notempty-validation_condition'][$key], false);
								}
							}
						}

						if (!empty($params['rollover']) && $params['tipseval'] == 1)
						{
							$to_update          = true;
							$params['rollover'] = $this->replace($params['rollover'], false);
						}

						$query->set($this->databaseService->getDatabase()->quoteName('params') . ' = ' . $this->databaseService->getDatabase()->quote(json_encode($params)));
					}

					if ($to_update)
					{
						$query->update($this->databaseService->getDatabase()->quoteName('jos_fabrik_elements'))
							->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($element['id']));
						$this->databaseService->getDatabase()->setQuery($query);

						$results['fabrik_elements'][$element['id']]['status'] = $this->databaseService->getDatabase()->execute();
					}

					if ($element['plugin'] === 'emundus_fileupload')
					{
						$query = 'SELECT COLUMN_TYPE
							FROM INFORMATION_SCHEMA.COLUMNS
							WHERE TABLE_NAME = ' . $element['db_table_name'] . '
							  AND COLUMN_NAME = ' . $element['name'];

						$this->databaseService->getDatabase()->setQuery($query);
						$columnType = $this->databaseService->getDatabase()->loadResult();

						if (in_array($columnType, ['INT', 'int', 'int(11)'])) {
							if (!class_exists('EmundusHelperUpdate')) {
								require_once(JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');
							}
							\EmundusHelperUpdate::alterColumn($element['db_table_name'], $element['name'], 'VARCHAR', 255);
						}
					}
				}
				break;

			case 'fabrik_forms':
				$query->clear()
					->select([$this->databaseService->getDatabase()->quoteName('id'), $this->databaseService->getDatabase()->quoteName('params')])
					->from($this->databaseService->getDatabase()->quoteName('jos_fabrik_forms'));
				$this->databaseService->getDatabase()->setQuery($query);
				$forms = $this->databaseService->getDatabase()->loadAssocList();

				foreach ($forms as $form)
				{
					$to_update = false;
					$query->clear();

					if (!empty($form['params']))
					{
						$params = json_decode($form['params'], true);

						if (!empty($params['plugins']))
						{
							foreach ($params['plugins'] as $key => $plugin)
							{
								if ($plugin == 'php' && !empty($params['curl_code'][$key]))
								{
									$to_update                 = true;
									$params['curl_code'][$key] = $this->replace($params['curl_code'][$key], false);
								}
							}
						}

						if ($to_update)
						{
							$query->update($this->databaseService->getDatabase()->quoteName('jos_fabrik_forms'))
								->set($this->databaseService->getDatabase()->quoteName('params') . ' = ' . $this->databaseService->getDatabase()->quote(json_encode($params)))
								->where($this->databaseService->getDatabase()->quoteName('id') . ' = ' . $this->databaseService->getDatabase()->quote($form['id']));
							$this->databaseService->getDatabase()->setQuery($query);

							$results['fabrik_forms'][$form['id']]['status'] = $this->databaseService->getDatabase()->execute();
						}
					}
				}

				break;
		}

		return $results;
	}

	private function replace(string $code, bool $breakline_interpreter = true): string
	{
		$result = str_ireplace(array_keys(self::PATTERNS), array_values(self::PATTERNS), $code);

		if ($breakline_interpreter)
		{
			return str_replace('\n', "\n", $result);
		}
		else
		{
			return $result;
		}
	}

	public static function getJobName(): string
	{
		return 'Fabrik';
	}

	public static function getJobDescription(): ?string
	{
		return 'Migrate Fabrik tables';
	}
}