<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.Checkgantrymode
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Createcampaigns\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Http\HttpFactory;
use Joomla\Uri\Uri;
use PhpOffice\PhpWord\Exception\Exception;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Log\Log;

/**
 * Task plugin with routines to check in a checked out item.
 *
 * @since  5.0.0
 */
class Createcampaigns extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	private $json_endpoint = '';

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	protected const TASKS_MAP = [
		'plg_task_createcampaigns_task_get' => [
			'langConstPrefix' => 'PLG_TASK_CREATECAMPAIGNS',
			'form'            => 'cron',
			'method'          => 'launchCron',
		],
	];


	public function __construct(DispatcherInterface $dispatcher, array $config = [])
	{
		parent::__construct($dispatcher, $config);

		Log::addLogger(['text_file' => 'com_emundus.task_create_campaigns.php'], Log::ALL, ['com_emundus.task_create_campaigns.php']);
	}

	/**
	 * @var boolean
	 * @since 5.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * Standard method for the checkin routine.
	 *
	 * @param   ExecuteTaskEvent  $event  The onExecuteTask event
	 *
	 * @return  integer  The exit code
	 *
	 * @since   5.0.0
	 */
	protected function launchCron(ExecuteTaskEvent $event): int
	{
		$failed = true;

		$params = $event->getArgument('params');

		if (!empty($params->json_endpoint))
		{
			$this->json_endpoint = $params->json_endpoint;
			$this->params        = $params;

			$succeed = $this->createPrograms();

			if ($succeed)
			{
				$succeed = $this->createCampaigns();

				if ($succeed)
				{
					$failed = false;
				}
			}
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}

	private function createPrograms(): bool
	{
		$succeed = false;
		$db      = $this->getDatabase();
		$query   = $db->createQuery();

		if (!empty($this->params->program_json_array_entry) && !empty($this->params->program_code_mapping))
		{
			$data = $this->getJsonData($this->json_endpoint);

			if (!empty($data))
			{
				$entry = $this->params->program_json_array_entry;

				// entry is a string containing the path to the array of objects
				// e.g. "data.programs"
				// we will split the string by '.' and loop through the array
				// to get the array of objects

				$entry_parts = explode('.', $entry);
				$entry_data  = $data;

				foreach ($entry_parts as $part)
				{
					$entry_data = $entry_data[$part];
				}

				if (!empty($entry_data))
				{
					PluginHelper::importPlugin('emundus');
					$dispatcher = Factory::getApplication()->getDispatcher();
					$tasks      = [];

					if (!class_exists('EmundusHelperEvents'))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/helpers/events.php');
					}
					$helper_events    = new \EmundusHelperEvents();
					$created_programs = [];
					$updated_programs = [];

					foreach ($entry_data as $program)
					{
						$code = $this->getValueFromJson($program, $this->params->program_code_mapping);

						if (!empty($code))
						{
							$label = !empty($this->params->program_label_mapping) ? $this->getValueFromJson($program, $this->params->program_label_mapping) : '';

							// check if program already exists or not
							$query->clear()
								->select('id')
								->from('#__emundus_setup_programmes')
								->where('code LIKE ' . $db->quote($code));

							try
							{
								$db->setQuery($query);
								$program_id = $db->loadResult();
							}
							catch (Exception $e)
							{

							}

							$another_columns_by_table = [];

							$create = false;
							if (!empty($program_id))
							{
								Log::add('Program with code ' . $code . ' already exists, ID ' . $program_id, Log::INFO, 'com_emundus.task_create_campaigns.php');

								$query->clear()
									->update('#__emundus_setup_programmes')
									->set('label = ' . $db->quote($label));

								foreach ($this->params->program_fields_mapping as $mapping)
								{
									if (!empty($mapping->params->emundus_column) && !empty($mapping->params->custom_column))
									{
										if (str_contains($mapping->params->emundus_column, '.'))
										{
											list($table, $column) = explode('.', $mapping->params->emundus_column);

											if ($table !== 'jos_emundus_setup_programmes')
											{
												if (!isset($another_columns_by_table[$table]))
												{
													$another_columns_by_table[$table] = [];
												}

												$another_columns_by_table[$table][] = [
													'column' => $column,
													'value'  => $this->getValueFromJson($program, $mapping->params->custom_column)
												];

												continue;
											}
										}

										$value = $this->getValueFromJson($program, $mapping->params->custom_column);
										$query->set($db->quoteName($mapping->params->emundus_column) . ' = ' . $db->quote($value));
									}
								}

								$query->where('id = ' . $program_id);
							}
							else
							{
								Log::add('Program with code ' . $code . ' does not exist, creating it', Log::INFO, 'com_emundus.task_create_campaigns.php');


								$create  = true;
								$columns = ['code', 'label'];
								$values  = [$db->quote($code), $db->quote($label)];

								foreach ($this->params->program_fields_mapping as $mapping)
								{
									if (!empty($mapping->params->emundus_column) && !empty($mapping->params->custom_column))
									{
										if (str_contains($mapping->params->emundus_column, '.'))
										{
											list($table, $column) = explode('.', $mapping->params->emundus_column);
											if ($table !== 'jos_emundus_setup_programmes')
											{
												if (!isset($another_columns_by_table[$table]))
												{
													$another_columns_by_table[$table] = [];
												}

												$another_columns_by_table[$table][] = [
													'column' => $column,
													'value'  => $this->getValueFromJson($program, $mapping->params->custom_column)
												];

												continue;
											}
										}

										$columns[] = $mapping->params->emundus_column;
										$values[]  = $db->quote($this->getValueFromJson($program, $mapping->params->custom_column));
									}
								}

								// create the program
								$query->clear()
									->insert('#__emundus_setup_programmes')
									->columns(implode(', ', $columns))
									->values(implode(', ', $values));
							}

							try
							{
								$db->setQuery($query);
								$tasks[] = $db->execute();

								if (empty($program_id))
								{
									$program_id = $db->insertid();
								}

								if (!empty($program_id))
								{
									Log::add('Program with code ' . $code . ' created or updated, ID ' . $program_id, Log::INFO, 'com_emundus.task_create_campaigns.php');

									$event_data        = new \stdClass();
									$event_data->id    = $program_id;
									$event_data->code  = $code;
									$event_data->label = $label;

									if ($create)
									{
										$created_programs[] = $program_id;

										$helper_events->onAfterProgramCreate([
											'data' => ['jos_emundus_setup_programmes___code_raw' => $code, 'jos_emundus_setup_programmes___evaluation_form_raw' => '']
										]);
										$onAfterProgramCreateEvent = new GenericEvent('onCallEventHandler', ['onAfterProgramCreate', ['programme' => $event_data, 'params' => $program]]);
										$dispatcher->dispatch('onCallEventHandler', $onAfterProgramCreateEvent);


										Log::add('Program with code ' . $code . ' and id ' . $program_id . ' event onAfterProgramCreate dispatched', Log::INFO, 'com_emundus.task_create_campaigns.php');
									}
									else
									{
										$updated_programs[] = $program_id;
									}

									if (!empty($another_columns_by_table))
									{
										// check all the columns that are not in the jos_emundus_setup_programmes table
										$query->clear();

										require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
										$helper_files = new \EmundusHelperFiles();
										foreach ($another_columns_by_table as $table => $columns)
										{
											$joins = $helper_files->findJoinsBetweenTablesRecursively($table, 'jos_emundus_setup_programmes');

											if (!empty($joins) && !empty($joins[0]['table_key']))
											{
												$query->clear()
													->select('id')
													->from($db->quoteName($table))
													->where($db->quoteName($joins[0]['table_key']) . ' = ' . $db->quote($program_id));

												$db->setQuery($query);
												$program_table_id = $db->loadResult();

												if (empty($program_table_id))
												{
													$columns[] = [
														'column' => $joins[0]['table_key'],
														'value'  => $program_id
													];

													$query->clear()
														->insert($db->quoteName($table));

													$keys   = array_map(function ($column) use ($db) {
														return $db->quoteName($column['column']);
													}, $columns);
													$values = array_map(function ($column) use ($db) {
														return $db->quote($column['value']);
													}, $columns);

													$query->columns(implode(', ', $keys))
														->values(implode(', ', $values));
												}
												else
												{
													$query->clear()
														->update($db->quoteName($table));

													foreach ($columns as $column)
													{
														$query->set($db->quoteName($column['column']) . ' = ' . $db->quote($column['value']));
													}

													$query->where($db->quoteName($joins[0]['table_key']) . ' = ' . $db->quote($program_id));

												}
												$db->setQuery($query);
												$tasks[] = $db->execute();
											}
										}
									}
								} else {
									Log::add('Program with code ' . $code . ' could not be created or updated', Log::ERROR, 'com_emundus.task_create_campaigns.php');
								}
							}
							catch (ExecutionFailureException $e)
							{
								$tasks[] = false;
							}
						}
					}

					$succeed = !in_array(false, $tasks);
					if ($succeed)
					{
						$onTaskCreateProgramsEvent = new GenericEvent('onCallEventHandler', ['onTaskCreatePrograms', ['json' => $data, 'created_programs' => $created_programs, 'updated_programs' => $updated_programs]]);
						$dispatcher->dispatch('onCallEventHandler', $onTaskCreateProgramsEvent);

						Log::add('All programs created or updated successfully', Log::INFO, 'com_emundus.task_create_campaigns.php');
					} else {
						Log::add('Some programs could not be created or updated', Log::ERROR, 'com_emundus.task_create_campaigns.php');
					}
				}
			}
		}

		return $succeed;
	}

	private function createCampaigns(): bool
	{
		$succeed = true;

		if (!empty($this->params->campaign_json_array_entry))
		{
			// get the data from the json endpoint
			$data = $this->getJsonData($this->json_endpoint);

			if (!empty($data))
			{
				PluginHelper::importPlugin('emundus');
				$dispatcher = Factory::getApplication()->getDispatcher();

				$entry = $this->params->campaign_json_array_entry;

				$campaigns = $this->getEntriesFromJson($data, $entry);

				$tasks = [];

				if (!empty($campaigns))
				{
					foreach ($campaigns as $campaign)
					{
						$old_campaign_data = [];
						$campaign_id = 0;
						$custom_campaign_id = $this->getValueFromJson($campaign, $this->params->campaign_id_mapping_custom);

						if (empty($custom_campaign_id))
						{
							Log::add('Failed to found custom campaign id for campaign ' . json_encode($campaign), Log::ERROR, 'com_emundus.task_create_campaigns.php');
							continue;
						}

						// check if campaign already exists or not
						$db    = $this->getDatabase();
						$query = $db->createQuery();

						$query->clear()
							->select('id')
							->from('#__emundus_setup_campaigns')
							->where($db->quoteName($this->params->campaign_id_mapping_emundus) . ' = ' . $db->quote($custom_campaign_id));

						try
						{
							$db->setQuery($query);
							$campaign_id = $db->loadResult();

							if (!empty($campaign_id))
							{
								Log::add('Campaign with custom id ' . $custom_campaign_id . ' found, ID ' . $campaign_id, Log::INFO, 'com_emundus.task_create_campaigns.php');

								$query->clear()
									->select('*')
									->from('#__emundus_setup_campaigns')
									->where('id = ' . $campaign_id);

								$db->setQuery($query);
								$old_campaign_data = $db->loadAssoc();

								$columns = [];
								$values = [];

								if (!empty($this->params->campaign_label_mapping))
								{
									$columns[] = 'label';
									$values[]  = $db->quote($this->getValueFromJson($campaign, $this->params->campaign_label_mapping));
								}

								if (!empty($this->params->campaign_training_mapping))
								{
									$columns[] = 'training';
									$values[]  = $db->quote($this->getValueFromJson($campaign, $this->params->campaign_training_mapping));
								}

								if (!empty($this->params->campaign_start_date_mapping))
								{
									$start_date = str_replace('T', ' ', $this->getValueFromJson($campaign, $this->params->campaign_start_date_mapping));
									$start_date = str_replace('.000Z', '', $start_date);
									$columns[]  = 'start_date';
									$values[]   = $db->quote($start_date);
								}

								if (!empty($this->params->campaign_end_date_mapping))
								{
									$end_date  = str_replace('T', ' ', $this->getValueFromJson($campaign, $this->params->campaign_end_date_mapping));
									$end_date  = str_replace('.000Z', '', $end_date);
									$columns[] = 'end_date';
									$values[]  = $db->quote($end_date);
								}

								if (empty($columns)) {
									Log::add('Nothing to update for campaign with custom id ' . $custom_campaign_id, Log::INFO, 'com_emundus.task_create_campaigns.php');
								}

								$query->clear()
									->update('#__emundus_setup_campaigns');

								foreach ($columns as $key => $column)
								{
									$query->set($db->quoteName($column) . ' = ' . $values[$key]);
								}

								foreach ($this->params->campaign_fields_mapping as $mapping)
								{
									if (!empty($mapping->params->emundus_column) && !empty($mapping->params->custom_column))
									{
										$value = $this->getValueFromJson($campaign, $mapping->params->custom_column);
										$query->set($db->quoteName($mapping->params->emundus_column) . ' = ' . $db->quote($value));
									}
								}

								$query->where('id = ' . $db->quote($campaign_id));

							}
							else
							{
								Log::add('Campaign with custom id ' . $custom_campaign_id . ' not found, creating it', Log::INFO, 'com_emundus.task_create_campaigns.php');

								// create the campaign
								$columns = [
									$this->params->campaign_id_mapping_emundus,
									'label',
									'training'
								];
								$values  = [
									$db->quote($custom_campaign_id),
									$db->quote($this->getValueFromJson($campaign, $this->params->campaign_label_mapping)),
									$db->quote($this->getValueFromJson($campaign, $this->params->campaign_training_mapping)),
								];

								if (!empty($this->params->campaign_start_date_mapping))
								{
									$start_date = str_replace('T', ' ', $this->getValueFromJson($campaign, $this->params->campaign_start_date_mapping));
									$start_date = str_replace('.000Z', '', $start_date);
									$columns[]  = 'start_date';
									$values[]   = $db->quote($start_date);
								}

								if (!empty($this->params->campaign_end_date_mapping))
								{
									$end_date  = str_replace('T', ' ', $this->getValueFromJson($campaign, $this->params->campaign_end_date_mapping));
									$end_date  = str_replace('.000Z', '', $end_date);
									$columns[] = 'end_date';
									$values[]  = $db->quote($end_date);
								}

								$query->clear()
									->insert('#__emundus_setup_campaigns')
									->columns(implode(', ', $columns))
									->values(implode(', ', $values));
							}

							$db->setQuery($query);
							$inserted = $db->execute();

							if ($inserted) {
								Log::add('Campaign with custom id ' . $custom_campaign_id . ' created or updated', Log::INFO, 'com_emundus.task_create_campaigns.php');

								if (empty($campaign_id))
								{
									$campaign_id = $db->insertid();
									Log::add('Campaign with custom id ' . $custom_campaign_id . ' created, new ID ' . $campaign_id, Log::INFO, 'com_emundus.task_create_campaigns.php');

									$onAfterCampaignCreateCallEventHandler = new GenericEvent('onCallEventHandler', ['onAfterCampaignCreate', ['campaign' => $campaign_id, 'campaign_id' => $campaign_id, 'params' => $campaign]]);
									$dispatcher->dispatch('onCallEventHandler', $onAfterCampaignCreateCallEventHandler);

									$query->clear()
										->select('*')
										->from('#__emundus_setup_campaigns')
										->where('id = ' . $campaign_id);

									$db->setQuery($query);
									$campaign_data = $db->loadAssoc();

									$onAfterCampaignCreateEvent = new GenericEvent('onAfterCampaignCreate', [$campaign_data]);
									$dispatcher->dispatch('onAfterCampaignCreate', $onAfterCampaignCreateEvent);

									Log::add('Campaign with custom id ' . $custom_campaign_id . ' event onAfterCampaignCreate dispatched', Log::INFO, 'com_emundus.task_create_campaigns.php');
								}
								else
								{
									$query->clear()
										->select('*')
										->from('#__emundus_setup_campaigns')
										->where('id = ' . $campaign_id);

									$db->setQuery($query);
									$campaign_data = $db->loadAssoc();

									$onAfterCampaignUpdateEvent = new GenericEvent('onAfterCampaignUpdate', [$campaign_data, $old_campaign_data]);
									$dispatcher->dispatch('onAfterCampaignUpdate', $onAfterCampaignUpdateEvent);

									Log::add('Campaign with custom id ' . $custom_campaign_id . ' event onAfterCampaignUpdate dispatched', Log::INFO, 'com_emundus.task_create_campaigns.php');
								}
							}

							$tasks[] = $inserted;
						} catch (ExecutionFailureException $e)
						{
							Log::add('Something went wrong for campaign custom id ' . $custom_campaign_id . '  ' . $e->getMessage(),  Log::ERROR, 'com_emundus.task_create_campaigns.php');
							$tasks[] = false;
						}
					}
				}

				$succeed = !in_array(false, $tasks);

				if ($succeed)
				{
					$onTaskCreateCampaignsEvent = new GenericEvent('onCallEventHandler', ['onTaskCreateCampaigns', ['json' => $data]]);
					$dispatcher->dispatch('onCallEventHandler', $onTaskCreateCampaignsEvent);

					Log::add('All campaigns created or updated successfully', Log::INFO, 'com_emundus.task_create_campaigns.php');
				} else {
					Log::add('Some campaigns could not be created or updated', Log::ERROR, 'com_emundus.task_create_campaigns.php');
				}
			}
		}

		return $succeed;
	}

	private function getJsonData($url): array
	{
		$data = [];

		if (!empty($url))
		{
			if (file_exists($url))
			{
				$data = json_decode(file_get_contents($url), true);
			}
			else
			{
				$http     = new HttpFactory();
				$http     = $http->getHttp();
				$response = $http->get(new Uri($url));

				if ($response->getStatusCode() === 200)
				{
					$data = json_decode($response->getBody(), true);
				}
			}
		}

		return $data;
	}


	private function getEntriesFromJson($json, $entry)
	{
		$entries = [];

		if (!empty($json) && !empty($entry))
		{
			$entry_parts = explode('.', $entry);
			$entry_data  = $json;

			foreach ($entry_parts as $part)
			{
				if (str_ends_with($part, '[]'))
				{
					// we need to loop through the array
					$part = str_replace('[]', '', $part);

					// next entries
					foreach ($entry_data[$part] as $data)
					{
						$tmp     = $this->getEntriesFromJson($data, implode('.', array_slice($entry_parts, array_search($part, $entry_parts) + 1)));
						$entries = array_merge($entries, $tmp);
					}
				}
				else
				{
					$entry_data = $entry_data[$part];
				}
			}

			if (!empty($entry_data))
			{
				if (is_array($entry_data))
				{
					$entries = array_merge($entries, $entry_data);
				}
				else
				{
					$entries[] = $entry_data;
				}
			}
		}

		return $entries;
	}


	private function getValueFromJson($json, $entry)
	{
		$value = null;


		if (!empty($json) && !empty($entry))
		{
			$entry_parts = explode('.', $entry);
			$entry_data  = $json;

			foreach ($entry_parts as $part)
			{
				$entry_data = $entry_data[$part];
			}

			$value = is_bool($entry_data) ? ($entry_data ? 1 : 0) : $entry_data;
		}

		return $value;
	}
}
