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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateJoomlaJob extends TchoozJob
{
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

		$joomlaTables = array_filter($dbSourceTables, function ($table) {
			return in_array($table, self::JOOMLA_TABLES);
		});

		$this->databaseService->getDatabase()->transactionStart();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 0')->execute();

		$progressBarGlobal = new EmundusProgressBar($section, count($joomlaTables));
		$progressBarGlobal->setMessage('Migrating Joomla tables from source to destination');
		$progressBarGlobal->start();

		foreach ($joomlaTables as $table)
		{
			if (in_array($table, $dbTables))
			{
				switch ($table)
				{
					case 'jos_assets':
						Log::add('Assets and messages tables will be ignored, we rebuild them after', Log::INFO, self::getJobName());
						break;
					case 'jos_extensions':
						if (!$this->mergeExtensions($output->section()))
						{
							Log::add('Error while merging extensions', Log::ERROR, self::getJobName());

							$this->databaseService->getDatabase()->transactionRollback();

							throw new \RuntimeException('Error while merging extensions');
						}

						Log::add('Extensions migrated to destination database', Log::INFO, self::getJobName());
						break;
					case 'jos_menu':
						if (!$this->mergeMenus($output->section()))
						{
							Log::add('Error while merging menus', Log::ERROR, self::getJobName());

							$this->databaseService->getDatabase()->transactionRollback();

							throw new \RuntimeException('Error while merging menus');
						}

						Log::add('Menus migrated to destination database', Log::INFO, self::getJobName());
						break;
					case 'jos_modules':
						if (!$this->mergeModules($output->section()))
						{
							Log::add('Error while merging modules', Log::ERROR, self::getJobName());

							$this->databaseService->getDatabase()->transactionRollback();

							throw new \RuntimeException('Error while merging modules');
						}

						if (!$this->verifyModules($output->section()))
						{
							Log::add('Error while verifying modules', Log::ERROR, self::getJobName());

							$this->databaseService->getDatabase()->transactionRollback();

							throw new \RuntimeException('Error while verifying modules');
						}

						Log::add('Modules migrated to destination database', Log::INFO, self::getJobName());
						break;
					default:
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

						if ($table == 'jos_content')
						{
							$publish_down_null = $this->fixContentPublishDownNull();
							$created           = $this->createWorkflowsAssociations();

							if (!$created || !$publish_down_null)
							{
								Log::add('Error while creating workflows associations', Log::ERROR, self::getJobName());

								$this->databaseService->getDatabase()->transactionRollback();

								throw new \RuntimeException('Error while creating workflows associations');
							}

							Log::add('Workflows associations created', Log::INFO, self::getJobName());
						}

						Log::add('Table ' . $table . ' migrated', Log::INFO, self::getJobName());
				}
			}
			$progressBarGlobal->advance();
		}
		$progressBarGlobal->finish('Joomla tables migrated');

		Log::add('Joomla tables migrated', Log::INFO, self::getJobName());

		$this->databaseService->getDatabase()->transactionCommit();
		$this->databaseService->getDatabase()->setQuery('SET AUTOCOMMIT = 1')->execute();
	}

	private function mergeExtensions(OutputInterface $outputSection): bool
	{
		$merged = true;

		$query_source = $this->databaseServiceSource->getDatabase()->getQuery(true);
		$query        = $this->databaseService->getDatabase()->getQuery(true);

		$query_source->select('*')
			->from($this->databaseServiceSource->getDatabase()->quoteName('jos_extensions'));
		$this->databaseServiceSource->getDatabase()->setQuery($query_source);
		$extensions = $this->databaseServiceSource->getDatabase()->loadAssocList();

		$progressBarExtensions = new EmundusProgressBar($outputSection, count($extensions));
		$progressBarExtensions->setMessage('Migrating extensions');
		$progressBarExtensions->start();
		foreach ($extensions as $extension)
		{
			unset($extension['extension_id']);
			unset($extension['system_data']);

			$query->clear()
				->select('extension_id')
				->from($this->databaseService->getDatabase()->quoteName('jos_extensions'))
				->where($this->databaseService->getDatabase()->quoteName('type') . ' = ' . $this->databaseService->getDatabase()->quote($extension['type']))
				->where($this->databaseService->getDatabase()->quoteName('element') . ' = ' . $this->databaseService->getDatabase()->quote($extension['element']))
				->where($this->databaseService->getDatabase()->quoteName('folder') . ' = ' . $this->databaseService->getDatabase()->quote($extension['folder']));
			$this->databaseService->getDatabase()->setQuery($query);
			$extension_id = $this->databaseService->getDatabase()->loadResult();

			if (!empty($extension_id))
			{
				$query->clear()
					->update($this->databaseService->getDatabase()->quoteName('jos_extensions'))
					->set($this->databaseService->getDatabase()->quoteName('params') . ' = ' . $this->databaseService->getDatabase()->quote($extension['params']))
					->set($this->databaseService->getDatabase()->quoteName('enabled') . ' = ' . $this->databaseService->getDatabase()->quote($extension['enabled']))
					->set($this->databaseService->getDatabase()->quoteName('state') . ' = ' . $this->databaseService->getDatabase()->quote($extension['state']))
					->set($this->databaseService->getDatabase()->quoteName('custom_data') . ' = ' . $this->databaseService->getDatabase()->quote($extension['custom_data']))
					->where($this->databaseService->getDatabase()->quoteName('extension_id') . ' = ' . $this->databaseService->getDatabase()->quote($extension_id));
				$this->databaseService->getDatabase()->setQuery($query);
				$merged = $this->databaseService->getDatabase()->execute();
			}
			else
			{
				$query->clear()
					->insert($this->databaseService->getDatabase()->quoteName('jos_extensions'))
					->columns($this->databaseService->getDatabase()->quoteName(array_keys($extension)))
					->values(implode(',', $this->databaseService->getDatabase()->quote($extension)));
				$this->databaseService->getDatabase()->setQuery($query);
				$merged = $this->databaseService->getDatabase()->execute();
			}

			$progressBarExtensions->advance();
		}
		$progressBarExtensions->finish('Extensions migrated');

		return $merged;
	}

	private function mergeMenus(OutputInterface $outputSection): bool
	{
		$merged = true;

		$query_source = $this->databaseServiceSource->getDatabase()->getQuery(true);
		$query        = $this->databaseService->getDatabase()->getQuery(true);

		// We saved main menus fo current db to insert it after with id conflict
		$query->select('*')
			->from($this->databaseService->getDatabase()->quoteName('jos_menu'))
			->where($this->databaseService->getDatabase()->quoteName('menutype') . ' LIKE ' . $this->databaseService->getDatabase()->quote('main'))
			->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = 1');
		$this->databaseService->getDatabase()->setQuery($query);
		$main_menus = $this->databaseService->getDatabase()->loadAssocList();

		foreach ($main_menus as &$main_menu)
		{
			$query->clear()
				->select('*')
				->from($this->databaseService->getDatabase()->quoteName('jos_menu'))
				->where($this->databaseService->getDatabase()->quoteName('menutype') . ' LIKE ' . $this->databaseService->getDatabase()->quote('main'))
				->where($this->databaseService->getDatabase()->quoteName('parent_id') . ' = ' . $main_menu['id']);
			$this->databaseService->getDatabase()->setQuery($query);
			$main_menu['childs'] = $this->databaseService->getDatabase()->loadAssocList();
		}
		//

		if ($this->databaseService->truncateTable('jos_menu'))
		{
			$query_source->select('*')
				->from($this->databaseServiceSource->getDatabase()->quoteName('jos_menu'))
				->where($this->databaseServiceSource->getDatabase()->quoteName('menutype') . ' NOT IN (' . implode(',', $this->databaseServiceSource->getDatabase()->quote(['main', 'menu'])) . ')');
			$this->databaseServiceSource->getDatabase()->setQuery($query_source);
			$menus = $this->databaseServiceSource->getDatabase()->loadAssocList();

			$progressBarMenus = new EmundusProgressBar($outputSection, count($menus)+count($main_menus));
			$progressBarMenus->setMessage('Migrating menus');
			$progressBarMenus->start();

			if (!empty($menus))
			{
				foreach ($menus as $menu)
				{
					// We get element, folder, client_id and type of old extension to get the new extension_id
					if (!empty($menu['component_id']))
					{
						$query_source->clear()
							->select('type,element,folder,client_id')
							->from($this->databaseServiceSource->getDatabase()->quoteName('jos_extensions'))
							->where($this->databaseServiceSource->getDatabase()->quoteName('extension_id') . ' = ' . $this->databaseServiceSource->getDatabase()->quote($menu['component_id']));
						$this->databaseServiceSource->getDatabase()->setQuery($query_source);
						$extension = $this->databaseServiceSource->getDatabase()->loadAssoc();

						if (!empty($extension))
						{
							$query->clear()
								->select('extension_id')
								->from($this->databaseService->getDatabase()->quoteName('jos_extensions'))
								->where($this->databaseService->getDatabase()->quoteName('type') . ' = ' . $this->databaseService->getDatabase()->quote($extension['type']))
								->where($this->databaseService->getDatabase()->quoteName('element') . ' = ' . $this->databaseService->getDatabase()->quote($extension['element']))
								->where($this->databaseService->getDatabase()->quoteName('folder') . ' = ' . $this->databaseService->getDatabase()->quote($extension['folder']))
								->where($this->databaseService->getDatabase()->quoteName('client_id') . ' = ' . $this->databaseService->getDatabase()->quote($extension['client_id']));
							$this->databaseService->getDatabase()->setQuery($query);
							$extension_id = $this->databaseService->getDatabase()->loadResult();

							if (!empty($extension_id))
							{
								$menu['component_id'] = $extension_id;
							}
						}
					}

					$query->clear()
						->insert($this->databaseService->getDatabase()->quoteName('jos_menu'))
						->columns($this->databaseService->getDatabase()->quoteName(array_keys($menu)))
						->values(implode(',', $this->databaseService->getDatabase()->quote($menu)));
					$this->databaseService->getDatabase()->setQuery($query);
					$merged = $this->databaseService->getDatabase()->execute();

					$progressBarMenus->advance();
				}
			}

			foreach ($main_menus as $menu)
			{
				$query->clear()
					->select('moduleid')
					->from($this->databaseService->getDatabase()->quoteName('jos_modules_menu'))
					->where($this->databaseService->getDatabase()->quoteName('menuid') . ' = ' . $this->databaseService->getDatabase()->quote($menu['id']));
				$this->databaseService->getDatabase()->setQuery($query);
				$modules = $this->databaseService->getDatabase()->loadColumn();

				$query->clear()
					->delete($this->databaseService->getDatabase()->quoteName('jos_modules_menu'))
					->where($this->databaseService->getDatabase()->quoteName('menuid') . ' = ' . $this->databaseService->getDatabase()->quote($menu['id']));
				$this->databaseService->getDatabase()->setQuery($query);

				if ($this->databaseService->getDatabase()->execute())
				{
					unset($menu['id']);
					$childs_menus = !empty($menu['childs']) ? $menu['childs'] : [];
					unset($menu['childs']);

					$query->clear()
						->insert($this->databaseService->getDatabase()->quoteName('jos_menu'))
						->columns($this->databaseService->getDatabase()->quoteName(array_keys($menu)))
						->values(implode(',', $this->databaseService->getDatabase()->quote($menu)));
					$this->databaseService->getDatabase()->setQuery($query);
					$merged = $this->databaseService->getDatabase()->execute();

					if ($merged)
					{
						$menuid = $this->databaseService->getDatabase()->insertid();

						if (!empty($childs_menus))
						{
							foreach ($childs_menus as $child_menu)
							{
								$query->clear()
									->select('moduleid')
									->from($this->databaseService->getDatabase()->quoteName('jos_modules_menu'))
									->where($this->databaseService->getDatabase()->quoteName('menuid') . ' = ' . $this->databaseService->getDatabase()->quote($child_menu['id']));
								$this->databaseService->getDatabase()->setQuery($query);
								$child_modules = $this->databaseService->getDatabase()->loadColumn();

								$query->clear()
									->delete($this->databaseService->getDatabase()->quoteName('jos_modules_menu'))
									->where($this->databaseService->getDatabase()->quoteName('menuid') . ' = ' . $this->databaseService->getDatabase()->quote($child_menu['id']));
								$this->databaseService->getDatabase()->setQuery($query);

								unset($child_menu['id']);
								$child_menu['parent_id'] = $menuid;

								$query->clear()
									->insert($this->databaseService->getDatabase()->quoteName('jos_menu'))
									->columns($this->databaseService->getDatabase()->quoteName(array_keys($child_menu)))
									->values(implode(',', $this->databaseService->getDatabase()->quote($child_menu)));
								$this->databaseService->getDatabase()->setQuery($query);
								$merged = $this->databaseService->getDatabase()->execute();

								$child_menu_id = $this->databaseService->getDatabase()->insertid();

								if ($merged)
								{
									if (!empty($child_modules))
									{
										foreach ($child_modules as $module)
										{
											$query->clear()
												->insert($this->databaseService->getDatabase()->quoteName('jos_modules_menu'))
												->columns($this->databaseService->getDatabase()->quoteName(['moduleid', 'menuid']))
												->values($this->databaseService->getDatabase()->quote($module) . ',' . $this->databaseService->getDatabase()->quote($child_menu_id));
											$this->databaseService->getDatabase()->setQuery($query);
											$merged = $this->databaseService->getDatabase()->execute();
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
									->insert($this->databaseService->getDatabase()->quoteName('jos_modules_menu'))
									->columns($this->databaseService->getDatabase()->quoteName(['moduleid', 'menuid']))
									->values($this->databaseService->getDatabase()->quote($module) . ',' . $this->databaseService->getDatabase()->quote($menuid));
								$this->databaseService->getDatabase()->setQuery($query);
								$merged = $this->databaseService->getDatabase()->execute();
							}
						}
					}
				}

				$progressBarMenus->advance();
			}

			$progressBarMenus->finish('Menus migrated');
		}

		return $merged;
	}

	private function mergeModules(OutputInterface $outputSection): bool
	{
		$merged = true;

		$query_source = $this->databaseServiceSource->getDatabase()->getQuery(true);
		$query        = $this->databaseService->getDatabase()->getQuery(true);

		// Manage only front modules
		$query->clear()
			->delete($this->databaseService->getDatabase()->quoteName('jos_modules'))
			->where($this->databaseService->getDatabase()->quoteName('client_id') . ' = 0');
		$this->databaseService->getDatabase()->setQuery($query);

		if ($this->databaseService->getDatabase()->execute())
		{
			$query_source->clear()
				->select('*')
				->from($this->databaseServiceSource->getDatabase()->quoteName('jos_modules'))
				->where($this->databaseServiceSource->getDatabase()->quoteName('client_id') . ' = 0');
			$this->databaseServiceSource->getDatabase()->setQuery($query_source);
			$modules = $this->databaseServiceSource->getDatabase()->loadAssocList();

			if (!empty($modules))
			{
				$progressBarModules = new EmundusProgressBar($outputSection, count($modules));
				$progressBarModules->setMessage('Migrating modules');
				$progressBarModules->start();

				foreach ($modules as $module)
				{
					$query_source->clear()
						->select('menuid')
						->from($this->databaseServiceSource->getDatabase()->quoteName('jos_modules_menu'))
						->where($this->databaseServiceSource->getDatabase()->quoteName('moduleid') . ' = ' . $this->databaseServiceSource->getDatabase()->quote($module['id']));
					$this->databaseServiceSource->getDatabase()->setQuery($query_source);
					$menus = $this->databaseServiceSource->getDatabase()->loadColumn();

					unset($module['id']);
					unset($module['checked_out']);
					unset($module['checked_out_time']);
					unset($module['publish_up']);
					unset($module['publish_down']);
					$query->clear()
						->insert($this->databaseService->getDatabase()->quoteName('jos_modules'))
						->columns($this->databaseService->getDatabase()->quoteName(array_keys($module)))
						->values(implode(',', $this->databaseService->getDatabase()->quote($module)));
					$this->databaseService->getDatabase()->setQuery($query);

					if ($this->databaseService->getDatabase()->execute())
					{
						$moduleid = $this->databaseService->getDatabase()->insertid();

						if (!empty($menus))
						{
							foreach ($menus as $menu)
							{
								$query->clear()
									->insert($this->databaseService->getDatabase()->quoteName('jos_modules_menu'))
									->columns($this->databaseService->getDatabase()->quoteName(['moduleid', 'menuid']))
									->values($this->databaseService->getDatabase()->quote($moduleid) . ',' . $this->databaseService->getDatabase()->quote($menu));
								$this->databaseService->getDatabase()->setQuery($query);
								$merged = $this->databaseService->getDatabase()->execute();
							}
						}
					}

					$progressBarModules->advance();
				}

				$progressBarModules->finish('Modules migrated');
			}
		}

		return $merged;
	}

	private function verifyModules(OutputInterface $outputSection): bool
	{
		$verified = true;

		// component emundus, param form_builder_page_creation_modules must have Flow and Forms modules
		$tchoozParams = ComponentHelper::getParams('com_emundus');
		$form_builder_page_creation_modules = $tchoozParams->get('form_builder_page_creation_modules', []);

		$query = $this->databaseService->getDatabase()->createQuery();
		$query->select('id')
			->from($this->databaseService->getDatabase()->quoteName('jos_modules'))
			->where($this->databaseService->getDatabase()->quoteName('module') . ' IN (' . implode(',', $this->databaseService->getDatabase()->quote(['mod_emundus_checklist', 'mod_emundusflow'])) . ')')
			->andWhere($this->databaseService->getDatabase()->quoteName('published') . ' = 1');

		$this->databaseService->getDatabase()->setQuery($query);
		$requiredModules = $this->databaseService->getDatabase()->loadColumn();
		$missingModules = array_diff($requiredModules, $form_builder_page_creation_modules);

		if (!empty($missingModules)) {
			$tchoozParams->set('form_builder_page_creation_modules', $requiredModules);

			$query->clear()
				->update($this->databaseService->getDatabase()->quoteName('#__extensions'))
				->set($this->databaseService->getDatabase()->quoteName('params') . ' = ' . $this->databaseService->getDatabase()->quote((string)$tchoozParams))
				->where($this->databaseService->getDatabase()->quoteName('element') . ' = ' . $this->databaseService->getDatabase()->quote('com_emundus'))
				->andWhere($this->databaseService->getDatabase()->quoteName('type') . ' = ' . $this->databaseService->getDatabase()->quote('component'));

			$this->databaseService->getDatabase()->setQuery($query);
			$updated = $this->databaseService->getDatabase()->execute();

			if (!$updated) {
				$verified = false;
			}
		}

		return $verified;
	}

	private function fixContentPublishDownNull(): bool
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$query->update($this->databaseService->getDatabase()->quoteName('jos_content'))
			->set($this->databaseService->getDatabase()->quoteName('publish_down') . ' = NULL');
		$this->databaseService->getDatabase()->setQuery($query);

		return $this->databaseService->getDatabase()->execute();
	}

	private function createWorkflowsAssociations(): bool
	{
		$articles_associated = [];

		$query = $this->databaseService->getDatabase()->getQuery(true);

		$this->databaseService->truncateTable('jos_workflow_associations');

		$query->select('id')
			->from($this->databaseService->getDatabase()->quoteName('jos_content'));
		$this->databaseService->getDatabase()->setQuery($query);
		$articles = $this->databaseService->getDatabase()->loadColumn();

		foreach ($articles as $article)
		{
			$insert_workflow_association = [
				'item_id'   => $article,
				'stage_id'  => 1,
				'extension' => 'com_content.article',
			];
			$insert_workflow_association = (object) $insert_workflow_association;
			$articles_associated[]       = $this->databaseService->getDatabase()->insertObject('jos_workflow_associations', $insert_workflow_association);
		}

		// Return false if one of the insert failed
		return !in_array(false, $articles_associated);
	}

	public static function getJobName(): string
	{
		return 'Joomla';
	}

	public static function getJobDescription(): ?string
	{
		return 'Migrate Joomla Core tables';
	}
}