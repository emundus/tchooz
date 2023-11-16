<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Filter\InputFilter;
use Joomla\Registry\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

class TchoozMigrateCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected static $defaultName = 'tchooz:migrate';

	/**
	 * SymfonyStyle Object
	 * @var   object
	 * @since 4.0.0
	 */
	private $ioStyle;

	/**
	 * Stores the Input Object
	 * @var   object
	 * @since 4.0.0
	 */
	private $cliInput;

	/**
	 * @var
	 * @since 5.0.0
	 */
	private $project_to_migrate;

	/**
	 * @var object
	 * @since version 5.0.0
	 */
	private $db;

	/**
	 * @var object
	 * @since version 5.0.0
	 */
	private $db_source;

	/**
	 * @var string[]
	 * @since version 5.0.0
	 */
	private $joomla_table_to_migrate = [
		'jos_assets',
		'jos_categories',
		'jos_contact_details',
		'jos_content',
		'jos_content_frontpage',
		'jos_content_types',
		'jos_extensions',
		'jos_languages',
		'jos_menu',
		'jos_menu_types',
		'jos_messages',
		'jos_messages_cfg',
		'jos_modules',
		//'jos_modules_menu',
		'jos_user_profiles',
		'jos_user_usergroup_map',
		'jos_usergroups',
		'jos_users',
		'jos_viewlevels'
	];

	/**
	 * @var string[]
	 * @since version 5.0.0
	 */
	private $fabrik_tables = [
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

	/**
	 * @var array
	 * @since version 5.0.0
	 */
	private $pattern = [
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

	/**
	 * Command constructor.
	 *
	 * @param   DatabaseInterface  $db  The database
	 *
	 * @since   4.2.0
	 */
	public function __construct(DatabaseInterface $db)
	{
		parent::__construct();

		$this->setDatabase($db);
	}

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 *
	 * @since   4.0.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->ioStyle->title('Migrate to Joomla 5!');
		//$this->project_to_migrate       = $this->getStringFromOption('project_to_migrate', 'Enter the path to the project to migrate: ');

		$this->project_to_migrate = '/mnt/data/web/core_j3';
		if (!is_dir($this->project_to_migrate)) {
			throw new InvalidOptionException('The path to the project to migrate is not valid!');
		}

		if (!file_exists($this->project_to_migrate . '/configuration.php')) {
			throw new InvalidOptionException('We did not find the configuration.php file in the path you provided!');
		}

		$progressBar = new ProgressBar($output, 10);
		$progressBar->start();

		$configuration_file = $this->project_to_migrate . '/configuration.php';
		if (is_file($configuration_file)) {
			$copied = copy($configuration_file, JPATH_ROOT . '/configuration_old.php');
			if ($copied) {
				$source_config = $this->getConfigFromFile(JPATH_ROOT . '/configuration_old.php', 'PHP', 'Old');

				if (!empty($source_config)) {
					$options             = array();
					$options['driver']   = isset($source_config->dbtype) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $source_config->dbtype) : 'mysqli';
					$options['database'] = $source_config->db;
					$options['user']     = $source_config->user;
					$options['password'] = $source_config->password;
					$options['select']   = true;
					$options['monitor']  = null;
					$options['host']     = $source_config->host;

					$db_factory = new DatabaseFactory();

					$this->db        = $this->getDatabase();
					$this->db_source = $db_factory->getDriver('mysqli', $options);

					$db_tables        = $this->db->getTableList();
					$db_source_tables = $this->db_source->getTableList();
					if (empty($db_tables) || empty($db_source_tables)) {
						$this->ioStyle->error('Error while getting tables from database!');

						return Command::FAILURE;
					}

					$this->ioStyle->info('Databases connected start datas migration...');
					$progressBar->advance();

					$this->db->setQuery('SET sql_mode = ""')->execute();
					$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();
					$this->db_source->setQuery('SET sql_mode = ""')->execute();
					$this->db_source->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();

					// Migrate tables that does not need merge
					$views = $this->getViews($this->db_source, $source_config->db);
					$data_tables       = array_filter($db_source_tables, function ($table) {
						return strpos($table, 'data_') !== false;
					});
					$emundus_tables    = array_filter($db_source_tables, function ($table) {
						return strpos($table, 'jos_emundus_') !== false;
					});
					$tables_to_migrate = array_merge($data_tables, $emundus_tables);
					$tables_to_migrate = array_diff($tables_to_migrate, $views);
					$tables_to_drop    = array_intersect($db_tables, $tables_to_migrate);
					$tables_to_ignore = ['jos_emundus_version'];

					foreach ($tables_to_drop as $table) {
						if (!$this->dropTable($table)) {
							$this->ioStyle->error('Error while dropping table ' . $table);

							return Command::FAILURE;
						}
					}
					$this->ioStyle->info('Emundus and datas table dropped from destination database');
					$progressBar->advance();

					foreach ($tables_to_migrate as $table) {
						if(in_array($table, $tables_to_ignore)) {
							continue;
						}

						if (!$this->fixFnumLengthAndCollation($table, $this->db_source)) {
							$this->ioStyle->error('Error while fixing fnum length and collation in table ' . $table);

							return Command::FAILURE;
						}

						if (!$this->convertToUtf8mb4($table, $this->db_source)) {
							$this->ioStyle->error('Error while converting table ' . $table);

							return Command::FAILURE;
						}

						if (!$this->createTable($table)) {
							$this->ioStyle->error('Error while creating table ' . $table);

							return Command::FAILURE;
						}

						if (!$this->insertDatas($table)) {
							$this->ioStyle->error('Error while inserting datas in table ' . $table);
							$this->ioStyle->error('Error while inserting datas in table ' . $table);

							return Command::FAILURE;
						}
					}
					$this->ioStyle->info('Emundus and datas table migrated to destination database');
					$progressBar->advance();

					foreach ($views as $view) {
						if (!$this->createView($view)) {
							$this->ioStyle->error('Error while creating view ' . $view);

							return Command::FAILURE;
						}
					}
					$this->ioStyle->info('Views migrated to destination database');
					$progressBar->advance();
					//

					// Merge datas from Joomla table
					$joomla_tables = array_filter($db_source_tables, function ($table) {
						if(in_array($table, $this->joomla_table_to_migrate)) {
							return true;
						}
					});

					foreach ($joomla_tables as $table) {
						if(in_array($table, $db_tables)) {
							switch($table) {
								case 'jos_extensions':
									$this->mergeExtensions();
									$this->ioStyle->info('Extensions migrated to destination database');
									$progressBar->advance();
									break;
								case 'jos_menu':
									$this->mergeMenus();
									$this->ioStyle->info('Menus migrated to destination database');
									$progressBar->advance();
									break;
								case 'jos_modules':
									$this->mergeModules();
									$this->ioStyle->info('Modules migrated to destination database');
									$progressBar->advance();
									break;
								default:
									if (!$this->truncateTable($table, $this->db)) {
										$this->ioStyle->error('Error while truncating table ' . $table);

										return Command::FAILURE;
									}

									if (!$this->mergeColumns($table)) {
										$this->ioStyle->error('Error while merging columns in table ' . $table);

										return Command::FAILURE;
									}

									if (!$this->insertDatas($table)) {
										$this->ioStyle->error('Error while inserting datas in table ' . $table);

										return Command::FAILURE;
									}
							}
						}
					}
					$this->ioStyle->info('Joomla tables migrated to destination database');
					$progressBar->advance();
					//

					// Merge datas from Fabrik table
					$fabrik_tables = array_filter($db_source_tables, function ($table) {
						if(in_array($table, $this->fabrik_tables)) {
							return true;
						}
					});

					foreach ($fabrik_tables as $table) {
						if(in_array($table, $db_tables)) {
							if (!$this->truncateTable($table, $this->db)) {
								$this->ioStyle->error('Error while truncating table ' . $table);

								return Command::FAILURE;
							}

							if (!$this->insertDatas($table)) {
								$this->ioStyle->error('Error while inserting datas in table ' . $table);

								return Command::FAILURE;
							}
						}
					}
					$this->ioStyle->info('Fabrik tables migrated to destination database');
					$progressBar->advance();
					//

					// Other tables (Hikashop, Dropfiles, DpCalendar, Falang)
					$other_tables = array_filter($db_source_tables, function ($table) {
						if(strpos($table, 'hikashop') !== false || strpos($table, 'dropfiles') !== false || strpos($table, 'falang') !== false) {
							return true;
						}
					});

					foreach ($other_tables as $table) {
						if(in_array($table, $db_tables)) {
							if (!$this->truncateTable($table, $this->db)) {
								$this->ioStyle->error('Error while truncating table ' . $table);

								return Command::FAILURE;
							}

							if(!$this->mergeColumns($table)) {
								$this->ioStyle->error('Error while merging columns in table ' . $table);

								return Command::FAILURE;
							}

							if (!$this->insertDatas($table)) {
								$this->ioStyle->error('Error while inserting datas in table ' . $table);

								return Command::FAILURE;
							}
						}
					}
					$this->ioStyle->info('Other tables migrated to destination database');
					$progressBar->advance();
					$this->ioStyle->newLine();
					//

					//TODO: Migration of files

					//TODO: Migration of images

					//TODO: Migration of g5_helium configuration

					$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
					$this->db_source->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
				}
			}
		}

		$progressBar->finish();

		/*$types = [
			'fabrik_elements',
			'fabrik_forms',
		];
		foreach ($types as $type) {
			$this->getCode($type);
		}*/

		$this->ioStyle->success("Migration completed successfully!");

		return Command::SUCCESS;
	}

	protected function getCode($type): array
	{
		$results = [];

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		switch ($type) {
			case 'fabrik_elements':

				$query->clear()
					->select([$db->quoteName('id'), $db->quoteName('default'), $db->quoteName('params')])
					->from($db->quoteName('#__fabrik_elements'));
				$db->setQuery($query);
				$elements = $db->loadAssocList();

				foreach ($elements as $element) {
					$to_update = false;
					$query->clear();

					if (!empty($element['default'])) {
						$to_update          = true;
						$element['default'] = $this->replace($element['default']);
						$query->set($db->quoteName('default') . ' = ' . $db->quote($element['default']));
					}

					if (!empty($element['params'])) {
						$params = json_decode($element['params'], true);
						if (!empty($params['calc_calculation'])) {
							$to_update                  = true;
							$params['calc_calculation'] = $this->replace($params['calc_calculation'], false);
						}

						if (!empty($params['validations'])) {
							foreach ($params['validations']['plugin'] as $key => $validation) {
								if ($validation == 'php' && (!empty($params['php-code'][$key]) || !empty($params['php-validation_condition'][$key]))) {
									$to_update = true;
									if (!empty($params['php-code'][$key])) {
										$params['php-code'][$key] = $this->replace($params['php-code'][$key], false);
									}
									if (!empty($params['php-validation_condition'][$key])) {
										$params['php-validation_condition'][$key] = $this->replace($params['php-validation_condition'][$key], false);
									}
								}

								if ($validation == 'notempty' && !empty($params['notempty-validation_condition'][$key])) {
									$to_update                                     = true;
									$params['notempty-validation_condition'][$key] = $this->replace($params['notempty-validation_condition'][$key], false);
								}
							}
						}

						if (!empty($params['rollover']) && $params['tipseval'] == 1) {
							$to_update          = true;
							$params['rollover'] = $this->replace($params['rollover'], false);
						}

						$query->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)));
					}

					if ($to_update) {
						$query->update($db->quoteName('#__fabrik_elements'))
							->where($db->quoteName('id') . ' = ' . $db->quote($element['id']));
						$db->setQuery($query);

						$results['fabrik_elements'][$element['id']]['status'] = $db->execute();
					}
				}
				break;

			case 'fabrik_forms':
				$query->clear()
					->select([$db->quoteName('id'), $db->quoteName('params')])
					->from($db->quoteName('#__fabrik_forms'));
				$db->setQuery($query);
				$forms = $db->loadAssocList();

				foreach ($forms as $form) {
					$to_update = false;
					$query->clear();

					if (!empty($form['params'])) {
						$params = json_decode($form['params'], true);

						if (!empty($params['plugins'])) {
							foreach ($params['plugins'] as $key => $plugin) {
								if ($plugin == 'php' && !empty($params['curl_code'][$key])) {
									$to_update                 = true;
									$params['curl_code'][$key] = $this->replace($params['curl_code'][$key], false);
								}
							}
						}

						if ($to_update) {
							$query->update($db->quoteName('#__fabrik_forms'))
								->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
								->where($db->quoteName('id') . ' = ' . $db->quote($form['id']));
							$db->setQuery($query);

							$results['fabrik_forms'][$form['id']]['status'] = $db->execute();
						}
					}
				}

				break;
		}

		return $results;
	}

	protected function replace($code, $breakline_interpreter = true): string
	{
		$result = str_ireplace(array_keys($this->pattern), array_values($this->pattern), $code);

		if ($breakline_interpreter) {
			return str_replace('\n', "\n", $result);
		}
		else {
			return $result;
		}
	}

	protected function getViews($db, $db_name): array
	{
		$views = $db->setQuery('SHOW FULL TABLES WHERE Table_type = \'VIEW\'')->loadAssocList();

		return array_map(function ($view) use ($db_name) {
			return $view['Tables_in_' . $db_name];
		}, $views);
	}

	protected function dropTable($table, $db = null): bool
	{
		if (empty($db)) {
			$db = $this->db;
		}

		$db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName($table));

		return $db->execute();
	}

	protected function createTable($table): bool
	{
		$created = false;
		$dump    = $this->db_source->getTableCreate($table);

		if (!empty($dump[$table])) {
			try {
				$this->db->setQuery($dump[$table]);
				$created = $this->db->execute();
			}
			catch (\Exception $e) {
				$this->ioStyle->error($e->getMessage());
			}

		}

		return $created;
	}

	protected function createView($view): bool
	{
		$created = false;
		$dump    = $this->db_source->setQuery('SHOW CREATE VIEW ' . $this->db_source->quoteName($view))->loadAssoc();

		if(!empty($dump['Create View'])) {
			try {
				$re = '/(ALGORITHM.*DEFINER)/m';
				$new_dump = preg_replace($re, '', $dump['Create View']);

				$this->db->setQuery('DROP VIEW IF EXISTS ' . $this->db->quoteName($view))->execute();
				$created = $this->db->setQuery($new_dump)->execute();
			}
			catch (\Exception $e) {
				$this->ioStyle->error($e->getMessage());
			}
		}

		return $created;
	}

	protected function fixFnumLengthAndCollation($table, $db): bool
	{
		$fixed = true;

		$columns = $db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' WHERE Field LIKE "fnum%" AND Type NOT LIKE "varchar(28)"')->loadAssocList();
		if (!empty($columns)) {
			foreach ($columns as $column) {
				$fixed = $db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' MODIFY COLUMN ' . $db->quoteName($column['Field']) . ' varchar(28)')->execute();
			}
		}

		return $fixed;
	}

	protected function convertToUtf8mb4($table, $db): bool
	{
		$converted = false;
		try {
			$query = 'ALTER TABLE ' . $db->quoteName($table) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci';
			$converted = $db->setQuery($query)->execute();
		}
		catch (\Exception $e) {
			$this->ioStyle->info($query);
			$this->ioStyle->error($e->getMessage());
		}

		return $converted;
	}

	protected function convertToInnodb($table, $db): bool
	{
		$converted = false;
		try {
			$converted = $db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' ENGINE=INNODB')->execute();
		}
		catch (\Exception $e) {
			$this->ioStyle->error($e->getMessage());
		}

		return $converted;
	}

	protected function insertDatas($table): bool
	{
		$inserted = true;
		$query    = $this->db_source->getQuery(true);

		$query->select('*')
			->from($this->db_source->quoteName($table));
		$this->db_source->setQuery($query);
		$datas = $this->db_source->loadAssocList();

		if (!empty($datas)) {
			$query = $this->db->getQuery(true);
			foreach ($datas as $data) {
				$query->clear();

				try {
					$query->insert($this->db->quoteName($table))
						->columns($this->db->quoteName(array_keys($data)))
						->values(implode(',', $this->db->quote($data)));
					$this->db->setQuery($query);

					if (!$this->db->execute()) {
						$inserted = false;
					}
				}
				catch (\Exception $e) {
					$this->ioStyle->error($e->getMessage());
					$inserted = false;
					break;
				}

			}
		}

		return $inserted;
	}

	protected function truncateTable($table, $db): bool
	{
		$truncated = false;
		try {
			$truncated = $db->setQuery('TRUNCATE TABLE ' . $db->quoteName($table))->execute();
		}
		catch (\Exception $e) {
			$this->ioStyle->error($e->getMessage());
		}

		return $truncated;
	}

	protected function mergeColumns($table): bool
	{
		$merged = true;

		$source_columns = $this->db_source->setQuery('SHOW COLUMNS FROM ' . $this->db_source->quoteName($table))->loadAssocList('Field');
		$columns        = $this->db->setQuery('SHOW COLUMNS FROM ' . $this->db->quoteName($table))->loadAssocList('Field');

		$diffs = array_diff_key($source_columns, $columns);
		if(!empty($diffs)) {
			foreach ($diffs as $diff) {
				if($diff['Null'] == 'NO') {
					$diff['Null'] = 'NOT NULL';
				}
				else {
					$diff['Null'] = 'NULL';
				}
				$query = 'ALTER TABLE ' . $this->db->quoteName($table) . ' ADD COLUMN ' . $this->db->quoteName($diff['Field']) . ' ' . $diff['Type'] . ' ' . $diff['Null'];
				$merged = $this->db->setQuery($query)->execute();
			}

		}
		
		return $merged;
	}

	protected function mergeMenus(): bool
	{
		$merged = true;

		$query_source = $this->db_source->getQuery(true);
		$query = $this->db->getQuery(true);
		try {
			// We saved main menus fo current db to insert it after with id conflict
			$query->select('*')
				->from($this->db->quoteName('jos_menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('main'));
			$this->db->setQuery($query);
			$main_menus = $this->db->loadAssocList();

			if($this->truncateTable('jos_menu', $this->db)) {
				$query_source->select('*')
					->from($this->db_source->quoteName('jos_menu'))
					->where($this->db_source->quoteName('menutype') . ' NOT IN (' . implode(',',$this->db_source->quote(['main','menu'])) . ')');
				$this->db_source->setQuery($query_source);
				$menus = $this->db_source->loadAssocList();

				if (!empty($menus)) {
					foreach ($menus as $menu) {
						// We get element, folder, client_id and type of old extension to get the new extension_id
						if(!empty($menu['component_id'])) {
							$query_source->clear()
								->select('type,element,folder,client_id')
								->from($this->db_source->quoteName('jos_extensions'))
								->where($this->db_source->quoteName('extension_id') . ' = ' . $this->db_source->quote($menu['component_id']));
							$this->db_source->setQuery($query_source);
							$extension = $this->db_source->loadAssoc();

							if(!empty($extension)) {
								$query->clear()
									->select('extension_id')
									->from($this->db->quoteName('jos_extensions'))
									->where($this->db->quoteName('type') . ' = ' . $this->db->quote($extension['type']))
									->where($this->db->quoteName('element') . ' = ' . $this->db->quote($extension['element']))
									->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($extension['folder']))
									->where($this->db->quoteName('client_id') . ' = ' . $this->db->quote($extension['client_id']));
								$this->db->setQuery($query);
								$extension_id = $this->db->loadResult();

								if(!empty($extension_id)) {
									$menu['component_id'] = $extension_id;
								}
							}
						}

						$query->clear()
							->insert($this->db->quoteName('jos_menu'))
							->columns($this->db->quoteName(array_keys($menu)))
							->values(implode(',', $this->db->quote($menu)));
						$this->db->setQuery($query);
						$merged = $this->db->execute();
					}
				}

				foreach ($main_menus as $menu) {
					$query->clear()
						->select('moduleid')
						->from($this->db->quoteName('jos_modules_menu'))
						->where($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu['id']));
					$this->db->setQuery($query);
					$modules = $this->db->loadColumn();

					$query->clear()
						->delete($this->db->quoteName('jos_modules_menu'))
						->where($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu['id']));
					$this->db->setQuery($query);

					if($this->db->execute()) {
						unset($menu['id']);

						$query->clear()
							->insert($this->db->quoteName('jos_menu'))
							->columns($this->db->quoteName(array_keys($menu)))
							->values(implode(',', $this->db->quote($menu)));
						$this->db->setQuery($query);
						$merged = $this->db->execute();

						if($merged) {
							$menuid = $this->db->insertid();

							if(!empty($modules)) {
								foreach ($modules as $module) {
									$query->clear()
										->insert($this->db->quoteName('jos_modules_menu'))
										->columns($this->db->quoteName(['moduleid', 'menuid']))
										->values($this->db->quote($module) . ',' . $this->db->quote($menuid));
									$this->db->setQuery($query);
									$merged = $this->db->execute();
								}
							}
						}
					}
				}
			}
		}
		catch (\Exception $e) {
			$this->ioStyle->error($e->getMessage());
		}

		return $merged;
	}

	protected function mergeExtensions(): bool
	{
		$merged = true;

		$query_source = $this->db_source->getQuery(true);
		$query = $this->db->getQuery(true);

		try {
			$query_source->select('*')
				->from($this->db_source->quoteName('jos_extensions'));
			$this->db_source->setQuery($query_source);
			$extensions = $this->db_source->loadAssocList();

			foreach ($extensions as $extension) {
				unset($extension['extension_id']);
				unset($extension['system_data']);

				$query->clear()
					->select('extension_id')
					->from($this->db->quoteName('jos_extensions'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote($extension['type']))
					->where($this->db->quoteName('element') . ' = ' . $this->db->quote($extension['element']))
					->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($extension['folder']))
					->where($this->db->quoteName('client_id') . ' = ' . $this->db->quote($extension['client_id']));
				$this->db->setQuery($query);
				$extension_id = $this->db->loadResult();

				if(!empty($extension_id)) {
					$query->clear()
						->update($this->db->quoteName('jos_extensions'))
						->set($this->db->quoteName('params') . ' = ' . $this->db->quote($extension['params']))
						->set($this->db->quoteName('enabled') . ' = ' . $this->db->quote($extension['enabled']))
						->set($this->db->quoteName('custom_data') . ' = ' . $this->db->quote($extension['custom_data']))
						->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($extension_id));
					$this->db->setQuery($query);
					$merged = $this->db->execute();
				} else {
					$query->clear()
						->insert($this->db->quoteName('jos_extensions'))
						->columns($this->db->quoteName(array_keys($extension)))
						->values(implode(',', $this->db->quote($extension)));
					$this->db->setQuery($query);
					$merged = $this->db->execute();
				}
			}

		}
		catch (\Exception $e) {
			$this->ioStyle->error($e->getMessage());
			$merged = false;
		}

		return $merged;
	}

	protected function mergeModules(): bool
	{
		$merged = true;

		$query_source = $this->db_source->getQuery(true);
		$query = $this->db->getQuery(true);

		try {
			$query->clear()
				->delete($this->db->quoteName('jos_modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus%'))
				->orWhere($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_custom%'))
				->orWhere($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_jumi%'));
			$this->db->setQuery($query);

			if($this->db->execute()) {
				$query_source->clear()
					->select('*')
					->from($this->db_source->quoteName('jos_modules'))
					->where($this->db_source->quoteName('module') . ' LIKE ' . $this->db_source->quote('mod_emundus%'))
					->orWhere($this->db_source->quoteName('module') . ' LIKE ' . $this->db_source->quote('mod_custom%'))
					->orWhere($this->db_source->quoteName('module') . ' LIKE ' . $this->db_source->quote('mod_jumi%'));
				$this->db_source->setQuery($query_source);
				$modules = $this->db_source->loadAssocList();

				if(!empty($modules)) {
					foreach ($modules as $module) {
						$query_source->clear()
							->select('menuid')
							->from($this->db_source->quoteName('jos_modules_menu'))
							->where($this->db_source->quoteName('moduleid') . ' = ' . $this->db_source->quote($module['id']));
						$this->db_source->setQuery($query_source);
						$menus = $this->db_source->loadColumn();

						unset($module['id']);
						unset($module['checked_out']);
						unset($module['checked_out_time']);
						unset($module['publish_up']);
						unset($module['publish_down']);
						$query->clear()
							->insert($this->db->quoteName('jos_modules'))
							->columns($this->db->quoteName(array_keys($module)))
							->values(implode(',', $this->db->quote($module)));
						$this->db->setQuery($query);

						if($this->db->execute()) {
							$moduleid = $this->db->insertid();

							if(!empty($menus)) {
								foreach ($menus as $menu) {
									$query->clear()
										->insert($this->db->quoteName('jos_modules_menu'))
										->columns($this->db->quoteName(['moduleid', 'menuid']))
										->values($this->db->quote($moduleid) . ',' . $this->db->quote($menu));
									$this->db->setQuery($query);
									$merged = $this->db->execute();
								}
							}
						}
					}
				}
			}
		}
		catch (\Exception $e) {
			$merged = false;
			$this->ioStyle->error($e->getMessage());
		}

		return $merged;
	}

	/**
	 * Configure the IO.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	private function configureIO(InputInterface $input, OutputInterface $output): void
	{
		$this->cliInput = $input;
		$this->ioStyle  = new SymfonyStyle($input, $output);
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will migrate your Joomla 3.x site to Joomla 5.x.\n
		\nUsage: <info>php %command.full_name%</info>";

		$this->addOption('project_to_migrate', null, InputOption::VALUE_OPTIONAL, 'Path to the project to migrate');

		$this->setDescription('Migrate your Joomla 3.x site to Joomla 5.x.');
		$this->setHelp($help);
	}

	/**
	 * Method to get a value from option
	 *
	 * @param   string  $option    set the option name
	 * @param   string  $question  set the question if user enters no value to option
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 */
	public function getStringFromOption($option, $question): string
	{
		$answer = (string) $this->cliInput->getOption($option);

		while (!$answer) {
			if ($option === 'password') {
				$answer = (string) $this->ioStyle->askHidden($question);
			}
			else {
				$answer = (string) $this->ioStyle->ask($question);
			}
		}

		return $answer;
	}

	public function getConfigFromFile($file, $type = 'PHP', $namespace = '')
	{
		$file_content = file_get_contents($file);
		$file_content = str_replace('JConfig', 'JConfigOld', $file_content);
		file_put_contents($file, $file_content);
		if (is_file($file)) {
			include_once $file;
		}

		// Sanitize the namespace.
		$namespace = ucfirst((string) preg_replace('/[^A-Z_]/i', '', $namespace));

		// Build the config name.
		$name = 'JConfig' . $namespace;

		$config = null;
		// Handle the PHP configuration type.
		if ($type === 'PHP' && class_exists($name)) {
			// Create the JConfig object
			$config = new $name();
		}

		return $config;
	}
}