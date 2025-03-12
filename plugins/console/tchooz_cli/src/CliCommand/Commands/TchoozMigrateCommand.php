<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

defined('_JEXEC') or die;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(name: 'tchooz:migrate', description: 'Migrate your Core site to Tchooz v2')]
class TchoozMigrateCommand extends TchoozCommand
{
	use DatabaseAwareTrait;

	protected static $defaultName = 'tchooz:migrate';

	private ProgressBar $progressBar;

	private string $project_to_migrate;

	private DatabaseInterface $db_source;

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

	const JOOMLA_TABLES = [
		'jos_action_log_config',
		'jos_action_logs',
		'jos_action_logs_extensions',
		'jos_action_logs_users',
		'jos_associations',
		'jos_banner_clients',
		'jos_banner_tracks',
		'jos_banners',
		'jos_categories',
		'jos_contact_details',
		'jos_content',
		'jos_content_frontpage',
		'jos_content_rating',
		'jos_content_types',
		'jos_contentitem_tag_map',
		'jos_dropfiles',
		'jos_dropfiles_dropbox_files',
		'jos_dropfiles_files',
		'jos_dropfiles_google_files',
		'jos_dropfiles_onedrive_business_files',
		'jos_dropfiles_onedrive_files',
		'jos_dropfiles_options',
		'jos_dropfiles_statistics',
		'jos_dropfiles_tokens',
		'jos_dropfiles_versions',
		'jos_extensions',
		'jos_fields',
		'jos_fields_categories',
		'jos_fields_groups',
		'jos_fields_values',
		'jos_finder_filters',
		'jos_finder_links',
		'jos_finder_links_terms',
		'jos_finder_logging',
		'jos_finder_taxonomy',
		'jos_finder_taxonomy_map',
		'jos_finder_terms',
		'jos_finder_terms_common',
		'jos_finder_tokens',
		'jos_finder_tokens_aggregate',
		'jos_finder_types',
		'jos_guidedtour_steps',
		'jos_guidedtours',
		'jos_languages',
		'jos_mail_templates',
		'jos_menu',
		'jos_menu_types',
		'jos_messages',
		'jos_messages_cfg',
		'jos_modules',
		'jos_newsfeeds',
		'jos_overrider',
		'jos_postinstall_messages',
		'jos_privacy_consents',
		'jos_privacy_requests',
		'jos_redirect_links',
		'jos_user_keys',
		'jos_user_mfa',
		'jos_user_notes',
		'jos_user_profiles',
		'jos_user_usergroup_map',
		'jos_usergroups',
		'jos_users',
		'jos_viewlevels'
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

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will migrate your Core site to Tchooz v2";

		$this->addOption('project_to_migrate', null, InputOption::VALUE_REQUIRED, 'Path to the project to migrate', '/mnt/data/web/core');
		$this->addOption('skip_extensions_check', null, InputOption::VALUE_NONE, 'Skip checking of extensions');
		$this->addOption('extensions_check', null, InputOption::VALUE_NONE, 'Not run migration, only check extensions');
		$this->addOption('fix_assets', null, InputOption::VALUE_NONE, 'Not run migration, fix assets only');
		$this->addOption('steps', null, InputOption::VALUE_OPTIONAL, 'Run migration step by step', 'emundus,views,fabrik,joomla,extensions,others,templates,files');

		$this->setDescription('Migrate your Core site to Tchooz v2');
		$this->setHelp($help);
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		// docker exec -it multiphase php cli/joomla.php tchooz:migrate -n --project_to_migrate=/mnt/data/web/multiphase

		$this->configureIO($input, $output);
		$this->ioStyle->title('Migrate to Joomla 5!');

		$projectToMigrate = $this->getStringFromOption('project_to_migrate', 'Path to the project to migrate: ', true);
		if (!is_dir($projectToMigrate))
		{
			throw new InvalidOptionException('The path to the project to migrate is not valid!');
		}
		if (!file_exists($projectToMigrate . '/configuration.php'))
		{
			throw new InvalidOptionException('We did not find the configuration.php file in the path you provided!');
		}

		// If the user wants to skip the extensions check when migrate
		$skipExtensionsCheck = $input->getOption('skip_extensions_check');

		// Get optional options
		$extensionsCheck = $input->getOption('extensions_check');
		$fixAssets       = $input->getOption('fix_assets');
		$steps           = $input->getOption('steps');
		$steps           = explode(',', $steps);
		$steps           = array_filter($steps);

		$configuration_file = $projectToMigrate . '/configuration.php';
		$databaseService    = new DatabaseService($configuration_file);
		$this->db_source    = $databaseService->getDatabase();
		$db_name            = $databaseService->getDbName();

		$this->db->setQuery('SET sql_mode = ""')->execute();
		$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();
		$this->db->setQuery('SET UNIQUE_CHECKS = 0')->execute();
		$this->db_source->setQuery('SET sql_mode = ""')->execute();
		$this->db_source->setQuery('SET FOREIGN_KEY_CHECKS = 0')->execute();
		$this->db_source->setQuery('SET UNIQUE_CHECKS = 0')->execute();

		// Fix assets only
		if ($fixAssets)
		{
			$this->ioStyle->info('Fixing assets table...');
			$fixed = $this->fixAssets();

			if (!$fixed)
			{
				$this->ioStyle->error('Error while fixing assets table');

				return Command::FAILURE;
			}
			else
			{
				$this->ioStyle->success('Assets table fixed');

				return Command::SUCCESS;
			}
		}
		//

		// Check if some extensions are missing in the destination database
		if (!$skipExtensionsCheck || $extensionsCheck)
		{
			$this->ioStyle->section('Checking extensions...');
			$check_status = $this->checkExtensions();

			if ($extensionsCheck)
			{
				return Command::SUCCESS;
			}

			if (!$check_status)
			{
				$question = new ConfirmationQuestion('Please read the above warnings carefully before proceeding. If you are sure you want to continue, type "yes" and press enter: ', false);
				if (!$this->ioStyle->askQuestion($question))
				{
					$this->db->transactionRollback();

					return Command::FAILURE;
				}
			}
		}
		//

		$this->db->transactionStart();
		$this->db->setQuery('SET AUTOCOMMIT = 0')->execute();
		$this->db_source->setQuery('SET AUTOCOMMIT = 0')->execute();

		if (!empty($steps))
		{
			// Prepare tables
			$db_tables        = $this->db->getTableList();
			$db_source_tables = $this->db_source->getTableList();
			if (empty($db_tables) || empty($db_source_tables))
			{
				$this->ioStyle->error('Error while getting tables list');

				return Command::FAILURE;
			}

			$views             = $this->getViews($this->db_source, $db_name);
			$emundus_tables    = array_filter($db_source_tables, function ($table) {
				return strpos($table, 'jos_emundus_') !== false;
			});
			$tables_to_migrate = $emundus_tables;
			$tables_to_migrate = array_diff($tables_to_migrate, $views);
			$tables_to_drop    = array_intersect($db_tables, $tables_to_migrate);
			$tables_to_ignore  = ['jos_emundus_version'];
			//

			// Step 1: eMundus
			if (in_array('emundus', $steps))
			{
				$this->ioStyle->section('Migrating Emundus tables...');
				$this->ioStyle->text('Start dropping Emundus tables from destination database...');
				foreach ($tables_to_drop as $table)
				{
					if (!$this->dropTable($table))
					{
						$this->ioStyle->error('Error while dropping table ' . $table);

						return Command::FAILURE;
					}
				}
				$this->ioStyle->info('Emundus tables dropped from destination database');

				$this->ioStyle->text('Start migrating Emundus tables to destination database...');
				foreach ($tables_to_migrate as $table)
				{
					$this->ioStyle->text('Migrating table ' . $table);
					if (in_array($table, $tables_to_ignore))
					{
						continue;
					}

					if (!$this->fixFnumLengthAndCollation($table, $this->db_source))
					{
						$this->ioStyle->error('Error while fixing fnum length and collation in table ' . $table);

						return Command::FAILURE;
					}

					if (!$this->convertToUtf8mb4($table, $this->db_source))
					{
						$this->ioStyle->error('Error while converting table ' . $table);

						return Command::FAILURE;
					}

					if (!$this->createTable($table))
					{
						$this->ioStyle->error('Error while creating table ' . $table);

						return Command::FAILURE;
					}

					if (!$this->insertDatas($table))
					{
						$this->ioStyle->error('Error while inserting datas in table ' . $table);

						$this->db->transactionRollback();

						return Command::FAILURE;
					}
					$this->ioStyle->info('Table ' . $table . ' migrated to destination database');
				}
				$this->ioStyle->info('Emundus tables migrated to destination database');
			}
			//

			// Step 2: Views
			if (in_array('views', $steps))
			{
				$this->ioStyle->section('Migrating views...');
				foreach ($views as $view)
				{
					$this->ioStyle->text('Migrating view ' . $view);
					if (!$this->createView($view))
					{
						$this->ioStyle->error('Error while creating view ' . $view);

						return Command::FAILURE;
					}
					$this->ioStyle->info('View ' . $view . ' migrated to destination database');
				}
				$this->ioStyle->info('Views migrated to destination database');
			}
			//

			// Step 3: Fabrik
			if (in_array('fabrik', $steps))
			{
				$this->ioStyle->section('Migrating Fabrik tables...');
				$fabrik_tables = array_filter($db_source_tables, function ($table) {
					if (in_array($table, self::FABRIK_TABLES))
					{
						return true;
					}
				});

				foreach ($fabrik_tables as $table)
				{
					if (in_array($table, $db_tables))
					{
						$this->ioStyle->text('Migrating table ' . $table);
						if (!$this->truncateTable($table, $this->db))
						{
							$this->ioStyle->error('Error while truncating table ' . $table);

							$this->db->transactionRollback();

							return Command::FAILURE;
						}

						if (!$this->insertDatas($table))
						{
							$this->ioStyle->error('Error while inserting datas in table ' . $table);

							$this->db->transactionRollback();

							return Command::FAILURE;
						}
						$this->ioStyle->info('Table ' . $table . ' migrated to destination database');
					}
				}
				$this->ioStyle->info('Fabrik tables migrated to destination database');
			}
			//

			// Step 4: Joomla
			if (in_array('joomla', $steps))
			{
				$this->ioStyle->section('Migrating Joomla tables...');
				$joomla_tables = array_filter($db_source_tables, function ($table) {
					if (in_array($table, self::JOOMLA_TABLES))
					{
						return true;
					}
				});

				foreach ($joomla_tables as $table)
				{
					if (in_array($table, $db_tables))
					{
						$this->ioStyle->text('Migrating table ' . $table);
						switch ($table)
						{
							case 'jos_assets':
							case 'jos_messages':
								$this->ioStyle->text('Assets table ignored, we will rebuild it after');
								break;
							case 'jos_extensions':
								$merged = $this->mergeExtensions();

								if (!$merged)
								{
									$this->ioStyle->error('Error while merging extensions');

									$this->db->transactionRollback();

									return Command::FAILURE;
								}

								$this->ioStyle->info('Extensions migrated to destination database');
								break;
							case 'jos_menu':
								$merged = $this->mergeMenus();

								if (!$merged)
								{
									$this->ioStyle->error('Error while merging menus');

									$this->db->transactionRollback();

									return Command::FAILURE;
								}

								$this->ioStyle->info('Menus migrated to destination database');
								break;
							case 'jos_modules':
								$merged = $this->mergeModules();

								if (!$merged)
								{
									$this->ioStyle->error('Error while merging modules');

									$this->db->transactionRollback();

									return Command::FAILURE;
								}

								$this->ioStyle->info('Modules migrated to destination database');
								break;
							default:
								if (!$this->truncateTable($table, $this->db))
								{
									$this->ioStyle->error('Error while truncating table ' . $table);

									$this->db->transactionRollback();

									return Command::FAILURE;
								}

								if (!$this->mergeColumns($table))
								{
									$this->ioStyle->error('Error while merging columns in table ' . $table);

									return Command::FAILURE;
								}

								if (!$this->insertDatas($table))
								{
									$this->ioStyle->error('Error while inserting datas in table ' . $table);

									$this->db->transactionRollback();

									return Command::FAILURE;
								}

								if ($table == 'jos_content')
								{
									$publish_down_null = $this->fixContentPublishDownNull();
									$created           = $this->createWorkflowsAssociations();

									if (!$created || !$publish_down_null)
									{
										$this->ioStyle->error('Error while creating workflows associations');

										$this->db->transactionRollback();

										return Command::FAILURE;
									}

									$this->ioStyle->info('Content migrated to destination database');
								}
								else
								{
									$this->ioStyle->info('Table ' . $table . ' migrated to destination database');
								}
						}
					}
				}
				$this->ioStyle->info('Joomla tables migrated to destination database');
			}
			//

			// Step 5: Extensions
			if (in_array('extensions', $steps))
			{
				$this->ioStyle->section('Migrating extensions tables...');
				$extension_tables = array_filter($db_source_tables, function ($table) {
					if (strpos($table, 'hikashop') !== false || strpos($table, 'dropfiles') !== false || strpos($table, 'falang') !== false)
					{
						return true;
					}
				});

				foreach ($extension_tables as $table)
				{
					if (in_array($table, $db_tables))
					{
						$this->ioStyle->text('Migrating table ' . $table);
						if (!$this->truncateTable($table, $this->db))
						{
							$this->ioStyle->error('Error while truncating table ' . $table);

							$this->db->transactionRollback();

							return Command::FAILURE;
						}

						if (!$this->mergeColumns($table))
						{
							$this->ioStyle->error('Error while merging columns in table ' . $table);

							return Command::FAILURE;
						}

						if (!$this->insertDatas($table))
						{
							$this->ioStyle->error('Error while inserting datas in table ' . $table);

							$this->db->transactionRollback();

							return Command::FAILURE;
						}

						$this->ioStyle->info('Table ' . $table . ' migrated to destination database');
					}
				}
				$this->ioStyle->info('Extension tables migrated to destination database');
			}
			//

			// Step 6: Other tables
			if (in_array('others', $steps))
			{
				$this->ioStyle->section('Migrating other tables...');
				$other_tables = array_filter($db_source_tables, function ($table) {
					if (strpos($table, 'jos_') === false)
					{
						return true;
					}
				});

				foreach ($other_tables as $table)
				{
					$this->ioStyle->text('Migrating table ' . $table);

					if (!$this->dropTable($table))
					{
						$this->ioStyle->error('Error while dropping table ' . $table);

						return Command::FAILURE;
					}

					if (!$this->createTable($table))
					{
						$this->ioStyle->error('Error while creating table ' . $table);

						return Command::FAILURE;
					}

					if (!$this->insertDatas($table))
					{
						$this->ioStyle->error('Error while inserting datas in table ' . $table);

						$this->db->transactionRollback();

						return Command::FAILURE;
					}
					$this->ioStyle->info('Table ' . $table . ' migrated to destination database');
				}
				$this->ioStyle->info('Other tables migrated to destination database');
			}
			//

			// Step 7: Templates
			if (in_array('templates', $steps))
			{
				$this->ioStyle->section('Start merging templates...');
				$merged = $this->mergeTemplates();
				if (!$merged)
				{
					$this->ioStyle->error('Error while merging templates');

					$this->db->transactionRollback();

					return Command::FAILURE;
				}
				$this->ioStyle->info('Templates merged');
			}
			//

			$this->db->transactionCommit();

			// Step 8: Files
			if (in_array('files', $steps))
			{
				$this->ioStyle->section('Migrating files...');
				$uploaded = $this->migrateFiles();
				if (!$uploaded)
				{
					$this->ioStyle->error('Error while migrating files');

					return Command::FAILURE;
				}
				$this->ioStyle->info('Files migrated');
			}
			//

			//TODO: Migration of images

			//TODO: Migration of g5_helium configuration

			//TODO: Rebuild fnum elements to use fnum query parameter and if we not found it fnum of session. Champs calc ?

			//TODO: Remove inlineedit plugins from fabrik list

			// Rebuild assets
			$this->ioStyle->section('Fixing assets table...');
			$fixed = $this->fixAssets();
			if (!$fixed)
			{
				$this->ioStyle->error('Error while fixing assets table');

				return Command::FAILURE;
			}
			$this->ioStyle->info('Assets table fixed');
			//

			// Merge rules assets
			$this->ioStyle->section('Merging rules assets...');
			$merged = $this->mergeRulesAssets();
			if (!$merged)
			{
				$this->ioStyle->error('Error while merging assets table');

				return Command::FAILURE;
			}
			$this->ioStyle->info('Rules assets merged');
			//
		}

		$this->db->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
		$this->db->setQuery('SET UNIQUE_CHECKS = 1')->execute();
		$this->db->setQuery('SET AUTOCOMMIT = 1')->execute();
		$this->db_source->setQuery('SET FOREIGN_KEY_CHECKS = 1')->execute();
		$this->db_source->setQuery('SET UNIQUE_CHECKS = 1')->execute();
		$this->db_source->setQuery('SET AUTOCOMMIT = 1')->execute();

		$this->ioStyle->success("Migration completed successfully!");

		return Command::SUCCESS;
	}

	/**
	 * Get PHP Code from specific elements
	 *
	 * @param $type
	 *
	 * @return array
	 */
	private function getCode($type): array
	{
		$results = [];

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		switch ($type)
		{
			case 'fabrik_elements':

				$query->clear()
					->select([$db->quoteName('id'), $db->quoteName('default'), $db->quoteName('params')])
					->from($db->quoteName('jos_fabrik_elements'));
				$db->setQuery($query);
				$elements = $db->loadAssocList();

				foreach ($elements as $element)
				{
					$to_update = false;
					$query->clear();

					if (!empty($element['default']))
					{
						$to_update          = true;
						$element['default'] = $this->replace($element['default']);
						$query->set($db->quoteName('default') . ' = ' . $db->quote($element['default']));
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

						$query->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)));
					}

					if ($to_update)
					{
						$query->update($db->quoteName('jos_fabrik_elements'))
							->where($db->quoteName('id') . ' = ' . $db->quote($element['id']));
						$db->setQuery($query);

						$results['fabrik_elements'][$element['id']]['status'] = $db->execute();
					}
				}
				break;

			case 'fabrik_forms':
				$query->clear()
					->select([$db->quoteName('id'), $db->quoteName('params')])
					->from($db->quoteName('jos_fabrik_forms'));
				$db->setQuery($query);
				$forms = $db->loadAssocList();

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
							$query->update($db->quoteName('jos_fabrik_forms'))
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

	/**
	 * Replace deprecated code from J3!
	 *
	 * @param         $code
	 * @param   bool  $breakline_interpreter
	 *
	 * @return string
	 */
	private function replace($code, bool $breakline_interpreter = true): string
	{
		$result = str_ireplace(array_keys($this->pattern), array_values($this->pattern), $code);

		if ($breakline_interpreter)
		{
			return str_replace('\n', "\n", $result);
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Get views from a database
	 *
	 * @param $db
	 * @param $db_name
	 *
	 * @return array
	 */
	private function getViews($db, $db_name): array
	{
		$views = $db->setQuery('SHOW FULL TABLES WHERE Table_type = \'VIEW\'')->loadAssocList();

		return array_map(function ($view) use ($db_name) {
			return $view['Tables_in_' . $db_name];
		}, $views);
	}

	/**
	 * Dropt a table from the destination database
	 *
	 * @param $table
	 * @param $db
	 *
	 * @return bool
	 */
	private function dropTable($table, $db = null): bool
	{
		if (empty($db))
		{
			$db = $this->db;
		}

		$db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName($table));

		return $db->execute();
	}

	/**
	 * Migrate tables from source database to destination database
	 *
	 * @param $table
	 *
	 * @return bool
	 */
	private function createTable($table): bool
	{
		$created = false;
		$dump    = $this->db_source->getTableCreate($table);

		if (!empty($dump[$table]))
		{
			try
			{
				$this->db->setQuery($dump[$table]);
				$created = $this->db->execute();
			}
			catch (\Exception $e)
			{
				$this->ioStyle->error($e->getMessage());
			}

		}

		return $created;
	}

	/**
	 * Migrate SQL view from source database to destination database
	 *
	 * @param $view
	 *
	 * @return bool
	 */
	private function createView($view): bool
	{
		$created = false;
		$dump    = $this->db_source->setQuery('SHOW CREATE VIEW ' . $this->db_source->quoteName($view))->loadAssoc();

		if (!empty($dump['Create View']))
		{
			try
			{
				$re       = '/(ALGORITHM.*DEFINER)/m';
				$new_dump = preg_replace($re, '', $dump['Create View']);

				$this->db->setQuery('DROP VIEW IF EXISTS ' . $this->db->quoteName($view))->execute();
				$created = $this->db->setQuery($new_dump)->execute();
			}
			catch (\Exception $e)
			{
				$this->ioStyle->error($e->getMessage());
			}
		}

		return $created;
	}

	/**
	 * Fix fnum length to varchar(28) and remove collation incompatibility
	 *
	 * @param $table
	 * @param $db
	 *
	 * @return bool
	 */
	private function fixFnumLengthAndCollation($table, $db): bool
	{
		$fixed = true;

		$columns = $db->setQuery('SHOW COLUMNS FROM ' . $db->quoteName($table) . ' WHERE Field LIKE "fnum%" AND Type NOT LIKE "varchar(28)"')->loadAssocList();
		if (!empty($columns))
		{
			foreach ($columns as $column)
			{
				$fixed = $db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' MODIFY COLUMN ' . $db->quoteName($column['Field']) . ' varchar(28)')->execute();
			}
		}

		return $fixed;
	}

	/**
	 * Convert a table to utf8mb4 charset with utf8mb4_0900_ai_ci collation
	 *
	 * @param $table
	 * @param $db
	 *
	 * @return bool
	 */
	private function convertToUtf8mb4($table, $db): bool
	{
		$converted = false;
		try
		{
			$query     = 'ALTER TABLE ' . $db->quoteName($table) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci';
			$converted = $db->setQuery($query)->execute();
		}
		catch (\Exception $e)
		{
			$this->ioStyle->info($query);
			$this->ioStyle->error($e->getMessage());
		}

		return $converted;
	}

	/**
	 * Convert a table to InnoDB engine
	 *
	 * @param $table
	 * @param $db
	 *
	 * @return bool
	 */
	private function convertToInnodb($table, $db): bool
	{
		$converted = false;
		try
		{
			$converted = $db->setQuery('ALTER TABLE ' . $db->quoteName($table) . ' ENGINE=INNODB')->execute();
		}
		catch (\Exception $e)
		{
			$this->ioStyle->error($e->getMessage());
		}

		return $converted;
	}

	/**
	 * Insert datas from a table of db source to the same table in db destination
	 *
	 * @param $table
	 *
	 * @return bool
	 */
	private function insertDatas($table): bool
	{
		$inserted = true;
		$query    = $this->db_source->getQuery(true);

		$query->select('*')
			->from($this->db_source->quoteName($table));
		$this->db_source->setQuery($query);
		$datas = $this->db_source->loadAssocList();

		if (!empty($datas))
		{
			$query = $this->db->getQuery(true);
			foreach ($datas as $data)
			{
				$query->clear();

				try
				{
					// Do not quote values if null
					foreach ($data as $key => $value)
					{
						if ($value === null)
						{
							$data[$key] = 'NULL';
						}
						else
						{
							$data[$key] = $this->db->quote($value);
						}
					}

					$query->insert($this->db->quoteName($table))
						->columns($this->db->quoteName(array_keys($data)))
						->values(implode(',', $data));
					$this->db->setQuery($query);

					if (!$this->db->execute())
					{
						$inserted = false;
					}
				}
				catch (\Exception $e)
				{
					$this->ioStyle->error($e->getMessage());
					$inserted = false;
					break;
				}

			}
		}

		return $inserted;
	}

	/**
	 * Truncate a table from a database
	 *
	 * @param $table
	 * @param $db
	 *
	 * @return bool
	 */
	private function truncateTable($table, $db): bool
	{
		$truncated = false;
		try
		{
			$truncated = $db->setQuery('TRUNCATE TABLE ' . $db->quoteName($table))->execute();
		}
		catch (\Exception $e)
		{
			$this->ioStyle->error($e->getMessage());
		}

		return $truncated;
	}

	/**
	 * Merge columns of a table
	 *
	 * @param $table
	 *
	 * @return bool
	 */
	private function mergeColumns($table): bool
	{
		$merged = true;

		$source_columns = $this->db_source->setQuery('SHOW COLUMNS FROM ' . $this->db_source->quoteName($table))->loadAssocList('Field');
		$columns        = $this->db->setQuery('SHOW COLUMNS FROM ' . $this->db->quoteName($table))->loadAssocList('Field');

		$diffs = array_diff_key($source_columns, $columns);
		if (!empty($diffs))
		{
			foreach ($diffs as $diff)
			{
				if ($diff['Null'] == 'NO')
				{
					$diff['Null'] = 'NOT NULL';
				}
				else
				{
					$diff['Null'] = 'NULL';
				}
				$query  = 'ALTER TABLE ' . $this->db->quoteName($table) . ' ADD COLUMN ' . $this->db->quoteName($diff['Field']) . ' ' . $diff['Type'] . ' ' . $diff['Null'];
				$merged = $this->db->setQuery($query)->execute();
			}

		}

		return $merged;
	}

	/**
	 * Merge menus
	 *
	 * @return bool
	 */
	private function mergeMenus(): bool
	{
		$merged = true;

		$query_source = $this->db_source->getQuery(true);
		$query        = $this->db->getQuery(true);
		try
		{
			// We saved main menus fo current db to insert it after with id conflict
			$query->select('*')
				->from($this->db->quoteName('jos_menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('main'))
				->where($this->db->quoteName('parent_id') . ' = 1');
			$this->db->setQuery($query);
			$main_menus = $this->db->loadAssocList();

			foreach ($main_menus as &$main_menu)
			{
				$query->clear()
					->select('*')
					->from($this->db->quoteName('jos_menu'))
					->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('main'))
					->where($this->db->quoteName('parent_id') . ' = ' . $main_menu['id']);
				$this->db->setQuery($query);
				$main_menu['childs'] = $this->db->loadAssocList();
			}
			//

			if ($this->truncateTable('jos_menu', $this->db))
			{
				$query_source->select('*')
					->from($this->db_source->quoteName('jos_menu'))
					->where($this->db_source->quoteName('menutype') . ' NOT IN (' . implode(',', $this->db_source->quote(['main', 'menu'])) . ')');
				$this->db_source->setQuery($query_source);
				$menus = $this->db_source->loadAssocList();

				if (!empty($menus))
				{
					foreach ($menus as $menu)
					{
						// We get element, folder, client_id and type of old extension to get the new extension_id
						if (!empty($menu['component_id']))
						{
							$query_source->clear()
								->select('type,element,folder,client_id')
								->from($this->db_source->quoteName('jos_extensions'))
								->where($this->db_source->quoteName('extension_id') . ' = ' . $this->db_source->quote($menu['component_id']));
							$this->db_source->setQuery($query_source);
							$extension = $this->db_source->loadAssoc();

							if (!empty($extension))
							{
								$query->clear()
									->select('extension_id')
									->from($this->db->quoteName('jos_extensions'))
									->where($this->db->quoteName('type') . ' = ' . $this->db->quote($extension['type']))
									->where($this->db->quoteName('element') . ' = ' . $this->db->quote($extension['element']))
									->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($extension['folder']))
									->where($this->db->quoteName('client_id') . ' = ' . $this->db->quote($extension['client_id']));
								$this->db->setQuery($query);
								$extension_id = $this->db->loadResult();

								if (!empty($extension_id))
								{
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

				foreach ($main_menus as $menu)
				{
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

					if ($this->db->execute())
					{
						unset($menu['id']);
						$childs_menus = !empty($menu['childs']) ? $menu['childs'] : [];
						unset($menu['childs']);

						$query->clear()
							->insert($this->db->quoteName('jos_menu'))
							->columns($this->db->quoteName(array_keys($menu)))
							->values(implode(',', $this->db->quote($menu)));
						$this->db->setQuery($query);
						$merged = $this->db->execute();

						if ($merged)
						{
							$menuid = $this->db->insertid();

							if (!empty($childs_menus))
							{
								foreach ($childs_menus as $child_menu)
								{
									$query->clear()
										->select('moduleid')
										->from($this->db->quoteName('jos_modules_menu'))
										->where($this->db->quoteName('menuid') . ' = ' . $this->db->quote($child_menu['id']));
									$this->db->setQuery($query);
									$child_modules = $this->db->loadColumn();

									$query->clear()
										->delete($this->db->quoteName('jos_modules_menu'))
										->where($this->db->quoteName('menuid') . ' = ' . $this->db->quote($child_menu['id']));
									$this->db->setQuery($query);

									unset($child_menu['id']);
									$child_menu['parent_id'] = $menuid;

									$query->clear()
										->insert($this->db->quoteName('jos_menu'))
										->columns($this->db->quoteName(array_keys($child_menu)))
										->values(implode(',', $this->db->quote($child_menu)));
									$this->db->setQuery($query);
									$merged = $this->db->execute();

									$child_menu_id = $this->db->insertid();

									if ($merged)
									{
										if (!empty($child_modules))
										{
											foreach ($child_modules as $module)
											{
												$query->clear()
													->insert($this->db->quoteName('jos_modules_menu'))
													->columns($this->db->quoteName(['moduleid', 'menuid']))
													->values($this->db->quote($module) . ',' . $this->db->quote($child_menu_id));
												$this->db->setQuery($query);
												$merged = $this->db->execute();
											}
										}
									}
								}
							}

							if (!empty($modules))
							{
								foreach ($modules as $module)
								{
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
		catch (\Exception $e)
		{
			$this->ioStyle->error($e->getMessage());
		}

		return $merged;
	}

	/**
	 * Merge extensions from source database to destination database
	 *
	 * @return bool
	 */
	private function mergeExtensions(): bool
	{
		$merged = true;

		$query_source = $this->db_source->getQuery(true);
		$query        = $this->db->getQuery(true);

		try
		{
			$query_source->select('*')
				->from($this->db_source->quoteName('jos_extensions'));
			$this->db_source->setQuery($query_source);
			$extensions = $this->db_source->loadAssocList();

			foreach ($extensions as $extension)
			{
				unset($extension['extension_id']);
				unset($extension['system_data']);

				$query->clear()
					->select('extension_id')
					->from($this->db->quoteName('jos_extensions'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote($extension['type']))
					->where($this->db->quoteName('element') . ' = ' . $this->db->quote($extension['element']))
					->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($extension['folder']));
				$this->db->setQuery($query);
				$extension_id = $this->db->loadResult();

				if (!empty($extension_id))
				{
					$query->clear()
						->update($this->db->quoteName('jos_extensions'))
						->set($this->db->quoteName('params') . ' = ' . $this->db->quote($extension['params']))
						->set($this->db->quoteName('enabled') . ' = ' . $this->db->quote($extension['enabled']))
						->set($this->db->quoteName('custom_data') . ' = ' . $this->db->quote($extension['custom_data']))
						->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($extension_id));
					$this->db->setQuery($query);
					$merged = $this->db->execute();
				}
				else
				{
					$query->clear()
						->insert($this->db->quoteName('jos_extensions'))
						->columns($this->db->quoteName(array_keys($extension)))
						->values(implode(',', $this->db->quote($extension)));
					$this->db->setQuery($query);
					$merged = $this->db->execute();
				}
			}

		}
		catch (\Exception $e)
		{
			$this->ioStyle->error($e->getMessage());
			$merged = false;
		}

		return $merged;
	}

	/**
	 * Merge modules from source database to destination database
	 *
	 * @return bool
	 */
	private function mergeModules(): bool
	{
		$merged = true;

		$query_source = $this->db_source->getQuery(true);
		$query        = $this->db->getQuery(true);

		try
		{
			// Manage only front modules
			$query->clear()
				->delete($this->db->quoteName('jos_modules'))
				->where($this->db->quoteName('client_id') . ' = 0');
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				$query_source->clear()
					->select('*')
					->from($this->db_source->quoteName('jos_modules'))
					->where($this->db_source->quoteName('client_id') . ' = 0');
				$this->db_source->setQuery($query_source);
				$modules = $this->db_source->loadAssocList();

				if (!empty($modules))
				{
					foreach ($modules as $module)
					{
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

						if ($this->db->execute())
						{
							$moduleid = $this->db->insertid();

							if (!empty($menus))
							{
								foreach ($menus as $menu)
								{
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
		catch (\Exception $e)
		{
			$merged = false;
			$this->ioStyle->error($e->getMessage());
		}

		return $merged;
	}

	private function mergeTemplates(): bool
	{
		$merged = true;

		$query_source = $this->db_source->getQuery(true);
		$query        = $this->db->getQuery(true);

		try
		{
			$query_source->clear()
				->select('*')
				->from($this->db_source->quoteName('jos_template_styles'))
				->where($this->db_source->quoteName('template') . ' LIKE ' . $this->db_source->quote('yootheme%'));
			$this->db_source->setQuery($query_source);
			$templates = $this->db_source->loadAssocList();

			foreach ($templates as $template)
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('jos_template_styles'))
					->where($this->db->quoteName('template') . ' = ' . $this->db->quote($template['template']));
				$this->db->setQuery($query);
				$template_id = $this->db->loadResult();

				if (!empty($template_id))
				{
					$query->clear()
						->update($this->db->quoteName('jos_template_styles'))
						->set($this->db->quoteName('params') . ' = ' . $this->db->quote($template['params']))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($template_id));
					$this->db->setQuery($query);
					$merged = $this->db->execute();

					if ($merged)
					{
						$query->clear()
							->update($this->db->quoteName('jos_menu'))
							->set($this->db->quoteName('template_style_id') . ' = ' . $this->db->quote($template_id))
							->where($this->db->quoteName('template_style_id') . ' = ' . $this->db->quote($template['id']));
						$this->db->setQuery($query);
						$merged = $this->db->execute();
					}
				}
				else
				{
					$query->clear()
						->insert($this->db->quoteName('jos_template_styles'))
						->columns($this->db->quoteName(array_keys($template)))
						->values(implode(',', $this->db->quote($template)));
					$this->db->setQuery($query);
					$merged = $this->db->execute();
				}
			}

			// Check if we found directories with the name yootheme_ in templates folder of old project then copy them to the destination
			$source_template_path      = $this->project_to_migrate . '/templates/';
			$destination_template_path = JPATH_SITE . '/templates/';
			$source_template_folders   = array_filter(glob($source_template_path . 'yootheme_*'), 'is_dir');
			if (!empty($source_template_folders))
			{
				foreach ($source_template_folders as $source_template_folder)
				{
					$destination_template_folder = $destination_template_path . basename($source_template_folder);

					// Create the destination directory if it doesn't exist
					if (!is_dir($destination_template_folder))
					{
						mkdir($destination_template_folder, 0755, true);
					}

					// Call the function to copy files and folders recursively
					$this->copyFolderContents($source_template_folder, $destination_template_folder);
				}
			}
		}
		catch (\Exception $e)
		{
			$merged = false;
			$this->ioStyle->error($e->getMessage());
		}

		return $merged;
	}

	/**
	 * Migration of files from source to destination
	 *
	 * @return bool
	 */
	private function migrateFiles(): bool
	{
		$uploaded = false;

		// Merge files from source to destination (images/custom)
		$source_files_path      = $this->project_to_migrate . '/images/custom/';
		$destination_files_path = JPATH_SITE . '/images/custom/';

		// Check if the source directory exists
		if (is_dir($source_files_path))
		{
			// Check if the destination directory exists
			if (!is_dir($destination_files_path))
			{
				mkdir($destination_files_path, 0755, true);
			}

			// Call the function to copy files and folders recursively
			$this->copyFolderContents($source_files_path, $destination_files_path);

			$uploaded = true;
		}

		return $uploaded;
	}

	/**
	 * Copy the content of a folder to another
	 *
	 * @param $source
	 * @param $destination
	 *
	 * @return void
	 */
	private function copyFolderContents($source, $destination)
	{
		$dir = opendir($source);

		while (($file = readdir($dir)) !== false)
		{
			if ($file !== '.' && $file !== '..')
			{
				$sourcePath      = $source . '/' . $file;
				$destinationPath = $destination . '/' . $file;

				if (is_dir($sourcePath))
				{
					// Create the directory in the destination if it doesn't exist
					if (!is_dir($destinationPath))
					{
						mkdir($destinationPath, 0755, true);
					}
					// Recursive call for subdirectory
					$this->copyFolderContents($sourcePath, $destinationPath);
				}
				else
				{
					// Copy file
					copy($sourcePath, $destinationPath);
				}
			}
		}

		closedir($dir);
	}

	/**
	 * Check if all extensions are present in the destination database
	 *
	 * @return bool
	 */
	private function checkExtensions(): bool
	{
		$check_status = true;

		$query_source                 = $this->db_source->getQuery(true);
		$components_not_found_safe    = [];
		$components_not_found_warning = [];
		$modules_not_found_safe       = [];
		$modules_not_found_warning    = [];
		$libraries_not_found          = [];
		$plugins_not_found_safe       = [];
		$plugins_not_found_warning    = [];
		$templates_not_found_safe     = [];
		$templates_not_found_warning  = [];

		$components_to_warning = ['com_dpcalendar', 'com_eventbooking', 'com_externallogin', 'com_hikamarket', 'com_miniorange_saml', 'com_loginguard', 'com_jce'];
		$modules_to_warning    = ['mod_emundus_evaluations'];
		$plugins_to_warning    = ['content/emundusSchoolyear', 'authentication/emundus_oauth2_cci'];
		$template_to_warning   = ['yootheme', 'emundus_vanilla'];

		try
		{
			$component_base_dir = [
				0 => 'components',
				1 => 'administrator/components'
			];
			$modules_base_dir   = [
				0 => 'modules',
				1 => 'administrator/modules'
			];
			$templates_base_dir = [
				0 => 'templates',
				1 => 'administrator/templates'
			];

			$query_source->select('*')
				->from($this->db_source->quoteName('jos_extensions'))
				->order('type ASC');
			$this->db_source->setQuery($query_source);
			$extensions = $this->db_source->loadAssocList();

			foreach ($extensions as $extension)
			{
				if ($extension['type'] == 'component')
				{
					if (!is_dir($component_base_dir[$extension['client_id']] . '/' . $extension['element']))
					{
						if (in_array($extension['element'], $components_to_warning))
						{
							$components_not_found_warning[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
						else
						{
							$components_not_found_safe[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
					}
				}

				if ($extension['type'] == 'module')
				{
					if (!is_dir($modules_base_dir[$extension['client_id']] . '/' . $extension['element'])
						&& (strpos($extension['element'], 'mod_eb') === false)
						&& (strpos($extension['element'], 'hikashop') === false)
						&& (strpos($extension['element'], 'dpcalendar') === false)
						&& (strpos($extension['element'], 'loginguard') === false)
						&& (strpos($extension['element'], 'jce') === false)
						&& (strpos($extension['element'], 'externallogin') === false)
					)
					{
						if (in_array($extension['element'], $modules_to_warning))
						{
							$modules_not_found_warning[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
						else
						{
							$modules_not_found_safe[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
					}
				}

				if ($extension['type'] == 'library')
				{
					if (!is_dir('libraries/' . $extension['element']))
					{
						$libraries_not_found[] = $extension['name'] . ' [' . $extension['element'] . ']';
					}
				}

				// We exclude plugins link to components
				if ($extension['type'] == 'plugin')
				{
					if (!is_dir('plugins/' . $extension['folder'] . '/' . $extension['element'])
						&& (strpos($extension['folder'], 'eventbooking') === false && strpos($extension['element'], 'eventbooking') === false)
						&& (strpos($extension['folder'], 'hikashop') === false && strpos($extension['element'], 'hikashop') === false)
						&& (strpos($extension['folder'], 'dpcalendar') === false && strpos($extension['element'], 'dpcalendar') === false)
						&& (strpos($extension['folder'], 'loginguard') === false && strpos($extension['element'], 'loginguard') === false)
						&& (strpos($extension['folder'], 'jce') === false && strpos($extension['element'], 'jce') === false)
					)
					{
						if (in_array($extension['folder'] . '/' . $extension['element'], $plugins_to_warning))
						{
							$plugins_not_found_warning[] = $extension['name'] . ' [' . $extension['folder'] . '/' . $extension['element'] . ']';
						}
						else
						{
							$plugins_not_found_safe[] = $extension['name'] . ' [' . $extension['folder'] . '/' . $extension['element'] . ']';
						}
					}
				}

				if ($extension['type'] == 'template')
				{
					if (!is_dir($templates_base_dir[$extension['client_id']] . '/' . $extension['element']))
					{
						if (in_array($extension['element'], $template_to_warning))
						{
							$templates_not_found_warning[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
						else
						{
							$templates_not_found_safe[] = $extension['name'] . ' [' . $extension['element'] . ']';
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			$this->ioStyle->error($e->getMessage());
		}

		if (!empty($components_not_found_safe) || $components_not_found_warning)
		{
			if (!empty($components_not_found_safe))
			{
				$this->ioStyle->note('Some components are not found in Tchooz V2, these components have been replaced or have never been used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($components_not_found_safe);
			}
			if (!empty($components_not_found_warning))
			{
				$this->ioStyle->warning('These following components are not found in Tchooz V2, you should check if they are still used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($components_not_found_warning);
			}

			$check_status = false;
		}

		if (!empty($modules_not_found_safe) || !empty($modules_not_found_warning))
		{
			if (!empty($modules_not_found_safe))
			{
				$this->ioStyle->note('Some modules are not found in Tchooz V2, these components have been replaced or have never been used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($modules_not_found_safe);
			}
			if (!empty($modules_not_found_warning))
			{
				$this->ioStyle->warning('These following modules are not found in Tchooz V2, you should check if they are still used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($modules_not_found_warning);
			}

			$check_status = false;
		}

		if (!empty($libraries_not_found))
		{
			$this->ioStyle->note('Some libraries are not found in the destination site:' . PHP_EOL);
			$this->ioStyle->listing($libraries_not_found);
			$check_status = false;
		}

		if (!empty($plugins_not_found_safe) || !empty($plugins_not_found_warning))
		{
			if (!empty($plugins_not_found_safe))
			{
				$this->ioStyle->note('Some plugins are not found in Tchooz V2, these components have been replaced or have never been used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($plugins_not_found_safe);
			}
			if (!empty($plugins_not_found_warning))
			{
				$this->ioStyle->warning('These following plugins are not found in Tchooz V2, you should check if they are still used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($plugins_not_found_warning);
			}

			$check_status = false;
		}

		if (!empty($templates_not_found_safe) || !empty($templates_not_found_warning))
		{
			if (!empty($templates_not_found_safe))
			{
				$this->ioStyle->note('Some templates are not found in Tchooz V2, these components have been replaced or have never been used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($templates_not_found_safe);
			}
			if (!empty($templates_not_found_warning))
			{
				$this->ioStyle->warning('These following templates are not found in Tchooz V2, you should check if they are still used in the platform:' . PHP_EOL);
				$this->ioStyle->listing($templates_not_found_warning);
			}

			$check_status = false;
		}

		if ($check_status)
		{
			$this->ioStyle->success('All extensions are found in the destination site. Nothing to do.');
		}

		return $check_status;
	}

	/**
	 * In J5! articles need to be associated with a default workflow
	 *
	 * @return bool
	 */
	private function createWorkflowsAssociations(): bool
	{
		$articles_associated = [];

		$query = $this->db->getQuery(true);

		$this->truncateTable('jos_workflow_associations', $this->db);

		$query->select('id')
			->from($this->db->quoteName('jos_content'));
		$this->db->setQuery($query);
		$articles = $this->db->loadColumn();

		foreach ($articles as $article)
		{
			$insert_workflow_association = [
				'item_id'   => $article,
				'stage_id'  => 1,
				'extension' => 'com_content.article',
			];
			$insert_workflow_association = (object) $insert_workflow_association;
			$articles_associated[]       = $this->db->insertObject('jos_workflow_associations', $insert_workflow_association);
		}

		// Return false if one of the insert failed
		return !in_array(false, $articles_associated);
	}

	/**
	 * Fix the publish_down field of the content table
	 *
	 * @return bool
	 */
	private function fixContentPublishDownNull(): bool
	{
		$fixed = false;

		$query = $this->db->getQuery(true);

		try
		{
			$query->update($this->db->quoteName('jos_content'))
				->set($this->db->quoteName('publish_down') . ' = NULL');
			$this->db->setQuery($query);
			$fixed = $this->db->execute();
		}
		catch (\Exception $e)
		{
			$this->ioStyle->error($e->getMessage());
		}

		return $fixed;
	}

	/**
	 * Merge rules of assets
	 *
	 * @return bool
	 */
	private function mergeRulesAssets(): bool
	{
		$merged = false;

		$query_source = $this->db_source->getQuery(true);
		$query        = $this->db->getQuery(true);

		$query_source->select('name,rules')
			->from($this->db_source->quoteName('jos_assets'));
		$this->db_source->setQuery($query_source);
		$assets = $this->db_source->loadAssocList();

		foreach ($assets as $asset)
		{
			$query->clear()
				->update($this->db->quoteName('jos_assets'))
				->set($this->db->quoteName('rules') . ' = ' . $this->db->quote($asset['rules']))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote($asset['name']));
			$this->db->setQuery($query);
			$merged = $this->db->execute();
		}

		return $merged;
	}

	/**
	 * Remove and rebuild assets table
	 *
	 * @return bool
	 */
	private function fixAssets(): bool
	{
		$fixed = false;

		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName('jos_assets'))
			->where($this->db->quoteName('parent_id') . ' <> 0');
		$this->db->setQuery($query);
		$deleted = $this->db->execute();

		if ($deleted)
		{
			$asset = Table::getInstance('Asset');

			$asset->loadByName('root.1');
			if ($asset)
			{
				$rootId = (int) $asset->id;
			}

			if ($rootId && ($asset->level != 0 || $asset->parent_id != 0))
			{
				$this->fixRoot($rootId);
			}

			if (!$asset->id)
			{
				$rootId = $this->getAssetRootId();
				$this->fixRoot($rootId);
			}

			if ($rootId)
			{
				// Insert extensions as assets
				$query->clear()
					->select('extension_id,name')
					->from($this->db->quoteName('jos_extensions'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'));
				$this->db->setQuery($query);
				$components = $this->db->loadObjectList();

				foreach ($components as $component)
				{
					$insert_asset = [
						'parent_id' => $rootId,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 1,
						'name'      => $component->name,
						'title'     => $component->name,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->db->insertObject('jos_assets', $insert_asset);
				}
				//

				// Insert menus as assets
				$query->clear()
					->select('id,title')
					->from($this->db->quoteName('jos_menu_types'));
				$this->db->setQuery($query);
				$menu_types = $this->db->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->db->quoteName('jos_assets'))
					->where($this->db->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_menus'));
				$this->db->setQuery($query);
				$menu_parent_id = $this->db->loadResult();

				foreach ($menu_types as $menu_type)
				{
					$insert_asset = [
						'parent_id' => $menu_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_menus.menu.' . $menu_type->id,
						'title'     => $menu_type->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->db->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->db->insertid();

					$query->clear()
						->update($this->db->quoteName('jos_menu_types'))
						->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($menu_type->id));
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//

				// Insert content category as assets
				$query->clear()
					->select('id,title,extension')
					->from($this->db->quoteName('jos_categories'))
					->where($this->db->quoteName('extension') . ' NOT LIKE ' . $this->db->quote('system'));
				$this->db->setQuery($query);
				$categories = $this->db->loadObjectList();

				foreach ($categories as $category)
				{
					$query->clear()
						->select('id')
						->from($this->db->quoteName('jos_assets'))
						->where($this->db->quoteName('parent_id') . ' = ' . $rootId)
						->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote($category->extension));
					$this->db->setQuery($query);
					$parent_id = $this->db->loadResult();

					$insert_asset = [
						'parent_id' => $parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => $category->extension . '.category.' . $category->id,
						'title'     => $category->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->db->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->db->insertid();

					$query->clear()
						->update($this->db->quoteName('jos_categories'))
						->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($category->id));
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//

				// Insert content articles as assets
				$query->clear()
					->select('id,title,catid')
					->from($this->db->quoteName('jos_content'));
				$this->db->setQuery($query);
				$articles = $this->db->loadObjectList();

				foreach ($articles as $article)
				{
					$query->clear()
						->select('id')
						->from($this->db->quoteName('jos_assets'))
						->where($this->db->quoteName('parent_id') . ' = ' . $rootId)
						->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_content.category.' . $article->catid));
					$this->db->setQuery($query);
					$category_parent_id = $this->db->loadResult();

					if (!empty($category_parent_id))
					{
						$insert_asset = [
							'parent_id' => $category_parent_id,
							'lft'       => 0,
							'rgt'       => 0,
							'level'     => 3,
							'name'      => 'com_content.article.' . $article->id,
							'title'     => $article->title,
							'rules'     => '{}'
						];
						$insert_asset = (object) $insert_asset;
						$this->db->insertObject('jos_assets', $insert_asset);
					}
				}
				//

				// Insert workflow as assets
				$query->clear()
					->select('id,title')
					->from($this->db->quoteName('jos_workflows'));
				$this->db->setQuery($query);
				$workflows = $this->db->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->db->quoteName('jos_assets'))
					->where($this->db->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_content'));
				$this->db->setQuery($query);
				$content_parent_id = $this->db->loadResult();

				foreach ($workflows as $workflow)
				{
					$insert_asset = [
						'parent_id' => $content_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 1,
						'name'      => 'com_content.workflow.' . $workflow->id,
						'title'     => $workflow->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->db->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->db->insertid();

					$query->clear()
						->update($this->db->quoteName('jos_workflows'))
						->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($workflow->id));
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//

				// Insert workflow stages as assets
				$query->clear()
					->select('id,title,workflow_id')
					->from($this->db->quoteName('jos_workflow_stages'));
				$this->db->setQuery($query);
				$stages = $this->db->loadObjectList();

				foreach ($stages as $stage)
				{
					$query->clear()
						->select('id')
						->from($this->db->quoteName('jos_assets'))
						->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_content.workflow.' . $stage->workflow_id));
					$this->db->setQuery($query);
					$workflow_parent_id = $this->db->loadResult();

					if (!empty($workflow_parent_id))
					{
						$insert_asset = [
							'parent_id' => $workflow_parent_id,
							'lft'       => 0,
							'rgt'       => 0,
							'level'     => 3,
							'name'      => 'com_content.stage.' . $stage->id,
							'title'     => $stage->title,
							'rules'     => '{}'
						];
						$insert_asset = (object) $insert_asset;
						$this->db->insertObject('jos_assets', $insert_asset);

						$asset_id = $this->db->insertid();

						$query->clear()
							->update($this->db->quoteName('jos_workflow_stages'))
							->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
							->where($this->db->quoteName('id') . ' = ' . $this->db->quote($stage->id));
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}
				//

				// Insert workflow transitions as assets
				$query->clear()
					->select('id,title,workflow_id')
					->from($this->db->quoteName('jos_workflow_transitions'));
				$this->db->setQuery($query);
				$transitions = $this->db->loadObjectList();

				foreach ($transitions as $transition)
				{
					$query->clear()
						->select('id')
						->from($this->db->quoteName('jos_assets'))
						->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_content.workflow.' . $transition->workflow_id));
					$this->db->setQuery($query);
					$workflow_parent_id = $this->db->loadResult();

					if (!empty($workflow_parent_id))
					{
						$insert_asset = [
							'parent_id' => $workflow_parent_id,
							'lft'       => 0,
							'rgt'       => 0,
							'level'     => 3,
							'name'      => 'com_content.transition.' . $transition->id,
							'title'     => $transition->title,
							'rules'     => '{}'
						];
						$insert_asset = (object) $insert_asset;
						$this->db->insertObject('jos_assets', $insert_asset);

						$asset_id = $this->db->insertid();

						$query->clear()
							->update($this->db->quoteName('jos_workflow_transitions'))
							->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
							->where($this->db->quoteName('id') . ' = ' . $this->db->quote($transition->id));
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}
				//

				// Insert languages as assets
				$query->clear()
					->select('lang_id, title')
					->from($this->db->quoteName('jos_languages'));
				$this->db->setQuery($query);
				$languages = $this->db->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->db->quoteName('jos_assets'))
					->where($this->db->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_languages'));
				$this->db->setQuery($query);
				$language_parent_id = $this->db->loadResult();

				foreach ($languages as $language)
				{
					$insert_asset = [
						'parent_id' => $language_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_languages.language.' . $language->lang_id,
						'title'     => $language->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->db->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->db->insertid();

					$query->clear()
						->update($this->db->quoteName('jos_languages'))
						->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
						->where($this->db->quoteName('lang_id') . ' = ' . $this->db->quote($language->lang_id));
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//

				// Insert modules as assets
				$query->clear()
					->select('id,title')
					->from($this->db->quoteName('jos_modules'));
				$this->db->setQuery($query);
				$modules = $this->db->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->db->quoteName('jos_assets'))
					->where($this->db->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_modules'));
				$this->db->setQuery($query);
				$module_parent_id = $this->db->loadResult();

				foreach ($modules as $module)
				{
					$insert_asset = [
						'parent_id' => $module_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_modules.module.' . $module->id,
						'title'     => $module->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->db->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->db->insertid();

					$query->clear()
						->update($this->db->quoteName('jos_modules'))
						->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($module->id));
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//

				// Insert schedules as assets
				$query->clear()
					->select('id,title')
					->from($this->db->quoteName('jos_scheduler_tasks'));
				$this->db->setQuery($query);
				$schedules = $this->db->loadObjectList();

				$query->clear()
					->select('id')
					->from($this->db->quoteName('jos_assets'))
					->where($this->db->quoteName('parent_id') . ' = ' . $rootId)
					->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_scheduler'));
				$this->db->setQuery($query);
				$schedule_parent_id = $this->db->loadResult();

				foreach ($schedules as $schedule)
				{
					$insert_asset = [
						'parent_id' => $schedule_parent_id,
						'lft'       => 0,
						'rgt'       => 0,
						'level'     => 2,
						'name'      => 'com_scheduler.task.' . $schedule->id,
						'title'     => $schedule->title,
						'rules'     => '{}'
					];
					$insert_asset = (object) $insert_asset;
					$this->db->insertObject('jos_assets', $insert_asset);

					$asset_id = $this->db->insertid();

					$query->clear()
						->update($this->db->quoteName('jos_scheduler_tasks'))
						->set($this->db->quoteName('asset_id') . ' = ' . $this->db->quote($asset_id))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($schedule->id));
					$this->db->setQuery($query);
					$this->db->execute();
				}
				//

				$fixed = $asset->rebuild($rootId);
			}
		}

		return $fixed;
	}

	private function getAssetRootId()
	{
		// Test for a unique record with parent_id = 0
		$query = $this->db->getQuery(true);

		$query->select($this->db->quote('id'))
			->from($this->db->quoteName('jos_assets'))
			->where($this->db->quote('parent_id') . ' = 0');
		$result = $this->db->setQuery($query)->loadColumn();

		if (count($result) == 1)
		{
			return $result[0];
		}

		// Test for a unique record with lft = 0
		$query->clear()
			->select('id')
			->from($this->db->quoteName('jos_assets'))
			->where($this->db->quote('lft') . ' = 0');

		$result = $this->db->setQuery($query)->loadColumn();

		if (count($result) == 1)
		{
			return $result[0];
		}

		// Test for a unique record alias = root
		$query->clear()
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('jos_assets'))
			->where('name LIKE ' . $this->db->quote('root%'));

		$result = $this->db->setQuery($query)->loadColumn();

		if (count($result) == 1)
		{
			return $result[0];
		}

		return false;
	}

	/**
	 * @param $rootId
	 *
	 * @return void
	 */
	private function fixRoot($rootId)
	{
		$fixed = false;
		$query = $this->db->getQuery(true);

		try
		{
			$query->update($this->db->quoteName('jos_assets'));
			$query->set($this->db->quoteName('parent_id') . ' = 0 ')
				->set($this->db->quoteName('level') . ' =  0 ')
				->set($this->db->quoteName('lft') . ' = 1 ')
				->set($this->db->quoteName('name') . ' = ' . $this->db->quote('root.' . (int) $rootId));
			$query->where('id = ' . (int) $rootId);

			$this->db->setQuery($query);
			$fixed = $this->db->execute();
		}
		catch (\Exception $e)
		{
			$this->ioStyle->error($e->getMessage());
		}

		return $fixed;
	}
}