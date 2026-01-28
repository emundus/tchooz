<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

use EmundusModelEvaluation;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Factories\TransformerFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Label\LabelRepository;
use Tchooz\Repositories\Workflow\StepRepository;

class Export
{
	protected FabrikRepository $fabrikRepository;

	protected \EmundusHelperFabrik $helperFabrik;

	protected LabelRepository $labelRepository;

	protected CampaignRepository $campaignRepository;

	protected array|false $translations = [];

	const CAMPAIGN_ELEMENTS = [
		1 => [
			'id'    => HeadersEnum::CAMPAIGN_LABEL->value,
			'label' => 'COM_EMUNDUS_ONBOARD_ADDCAMP_CAMPNAME',
		],
		2 => [
			'id'    => 'campaign_year',
			'label' => 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_CAMP_GROUP_BY_YEAR',
		],
		3 => [
			'id'    => 'campaign_start_date',
			'label' => 'COM_EMUNDUS_ONBOARD_START_DATE',
		],
		4 => [
			'id'    => 'campaign_end_date',
			'label' => 'COM_EMUNDUS_ONBOARD_END_DATE',
		]
	];

	const PROGRAM_ELEMENTS = [
		1 => [
			'id'    => 'program_name',
			'label' => 'COM_EMUNDUS_ONBOARD_PROGNAME',
		]
	];

	const MISCELLANEOUS_ELEMENTS = [
		1 => [
			'id'    => 'progress_forms',
			'label' => 'COM_EMUNDUS_FORMS',
		],
		2 => [
			'id'    => 'progress_attachments',
			'label' => 'COM_EMUNDUS_ATTACHMENT_PROGRESS',
		],
		3 => [
			'id'    => 'stickers',
			'label' => 'COM_EMUNDUS_FILES_TAGS',
		],
		4 => [
			'id'    => 'fnum',
			'label' => 'FNUM',
		],
		5 => [
			'id'    => 'submitted_date',
			'label' => 'APPLICATION_SENT_ON',
		],
		6 => [
			'id'    => 'printed_date',
			'label' => 'COM_EMUNDUS_EXPORT_CURRENT_DATE',
		],
		7 => [
			'id'    => 'status',
			'label' => 'COM_EMUNDUS_EXPORTS_PDF_STATUS',
		],
	];

	const MANAGEMENT_OTHER_ELEMENTS = [
		[
			'id'    => 'average_score_by_steps',
			'label' => 'COM_EMUNDUS_EXPORT_AVERAGE_SCORE_BY_STEPS',
		]
	];

	const USER_ELEMENTS = [
		1 => [
			'id'    => 'lastname',
			'label' => 'COM_EMUNDUS_FORM_LAST_NAME',
		],
		2 => [
			'id'    => 'firstname',
			'label' => 'COM_EMUNDUS_FORM_FIRST_NAME',
		],
		3 => [
			'id'    => 'fullname',
			'label' => 'COM_EMUNDUS_ONBOARD_LABEL_CONTACTS',
		],
		4 => [
			'id'    => 'email',
			'label' => 'COM_EMUNDUS_EMAIL',
		],
		5 => [
			'id'    => 'id',
			'label' => 'COM_EMUNDUS_USERNAME',
		],
	];

	public function __construct(string $langCode = 'fr-FR')
	{
		$this->registerClasses();

		$this->loadOverrideTranslations($langCode);
	}

	/**
	 * @param   int|string                    $elementId
	 * @param   array<ApplicationFileEntity>  $files
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function getData(int|string $elementId, array $files, ValueFormatEnum $format = ValueFormatEnum::RAW): array
	{
		$result = [
			'label' => '',
			'data'  => [],
			'is_evaluation' => false,
			'db_table_name' => '',
		];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		if (empty((int) $elementId))
		{
			$header = HeadersEnum::tryFrom($elementId);
			if (!empty($header))
			{
				// todo: change this, it looks rough to handle header specificity here
				if ($header === HeadersEnum::AVERAGE_SCORE_BY_STEPS)
				{
					$fnums = array_map(function ($file) {
						return $file->getFnum();
					}, $files);

					if (!class_exists('EmundusModelEvaluation'))
					{
						require_once(JPATH_ROOT.'/components/com_emundus/models/evaluation.php');
					}
					$evaluationModel = new EmundusModelEvaluation();
					$averagesBySteps = $evaluationModel->getEvaluationAverageBySteps($fnums, 0);

					if (!empty($averagesBySteps))
					{
						$stepRepository = new StepRepository();

						foreach ($averagesBySteps as $stepId => $averageByFnum)
						{
							$step = $stepRepository->getStepById($stepId);
							$result['label'] = $this->getTranslation($header->getLabel()) . ' - ' . Text::_($step->label);
							foreach ($files as $file)
							{
								$result['data'][$file->getFnum()] = $averageByFnum[$file->getFnum()] ?? '';
							}
						}
					}
				}
				else
				{
					$result['label'] = $this->getTranslation($header->getLabel());
					foreach ($files as $file)
					{
						$result['data'][$file->getFnum()] = $header->transform($file, $this->labelRepository, null, $format);
					}
				}

				return $result;
			}

			if (str_starts_with($elementId, 'campaign_more_'))
			{
				$elementParts = explode('_', $elementId);
				$moreFieldId  = (int) end($elementParts);
				if (!empty($moreFieldId))
				{
					$element = $this->fabrikRepository->getElementById($moreFieldId);
					if (!empty($element))
					{
						$result['label'] = $this->getTranslation($element->getLabel());

						foreach ($files as $file)
						{
							if (empty($campaignMoreData[$file->getCampaign()->getId()]))
							{
								$campaignMoreData[$file->getCampaign()->getId()] = $this->campaignRepository->getMoreData($file->getCampaign()->getId());
							}

							$transformedValue = '';
							if (!empty($campaignMoreData) && !empty($campaignMoreData[$file->getCampaign()->getId()]))
							{
								// Search in more data fields
								$value = $campaignMoreData[$file->getCampaign()->getId()][$element->getName()];

								if ($element->getPlugin() === ElementPluginEnum::DATABASEJOIN)
								{
									$value = \EmundusHelperFabrik::formatElementValue($element->getName(), $value, $element->getGroupId());
								}
								$transformer      = TransformerFactory::make($element->getPlugin()->value, $element->getParamsArray(), $element->getGroupParamsArray());
								$transformedValue = $transformer->transform($value);
							}

							$result['data'][$file->getFnum()] = $transformedValue ?? '';
						}
					}
				}
			}
		}
		else
		{
			$element = $this->fabrikRepository->getElementById($elementId);
			if (!empty($element))
			{
				$elementSerialized = $element->toArray();
				$eltParams         = $element->getParams();

				$elementsByAliases = [];
				if (!empty($eltParams) && !empty($eltParams->alias))
				{
					$elementsByAliases = \EmundusHelperFabrik::getElementsByAlias($eltParams->alias);
				}

				$result['label'] = $this->getTranslation($element->getLabel());
				$result['db_table_name'] = $element->getDbTableName();

				foreach ($files as $file)
				{
					if(str_starts_with($element->getDbTableName(), 'jos_emundus_evaluations_')) {
						$result['is_evaluation'] = true;

						// Get row(s) id
						$query->clear()
							->select($db->quoteName('id'))
							->from($db->quoteName($element->getDbTableName()))
							->where($db->quoteName('fnum') . ' = ' . $db->quote($file->getFnum()))
							->order('evaluator ASC');
						$db->setQuery($query);
						$rowIds = $db->loadColumn();

						$evaluationValues = [];
						foreach ($rowIds as $rowId)
						{
							$elementValuePart = $this->helperFabrik->getFabrikElementValue($elementSerialized, $file->getFnum(), $rowId, ValueFormatEnum::FORMATTED, 0, ExportModeEnum::GROUP_CONCAT, $this->translations);
							if ($elementValuePart && !empty($elementValuePart[$element->getId()]) && !empty($elementValuePart[$element->getId()][$file->getFnum()]))
							{
								$evaluationValues[$rowId] = $elementValuePart[$element->getId()][$file->getFnum()]['val'];
							}
						}

						$elementValue[$element->getId()][$file->getFnum()]['val'] = implode(',', $evaluationValues);
					}
					else
					{
						$elementValue = $this->helperFabrik->getFabrikElementValue($elementSerialized, $file->getFnum(), 0, ValueFormatEnum::FORMATTED, 0, ExportModeEnum::GROUP_CONCAT, $this->translations);
					}

					$result['data'][$file->getFnum()] = '';
					if ($elementValue && !empty($elementValue[$element->getId()]) && !empty($elementValue[$element->getId()][$file->getFnum()]))
					{
						if (isset($elementValue[$element->getId()][$file->getFnum()]['raw']))
						{
							$result['data'][$file->getFnum()] = $elementValue[$element->getId()][$file->getFnum()]['val'];
						}
						// Search via aliases if campaign is different
						else
						{
							foreach ($elementsByAliases as $elementByAlias)
							{
								$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
									->createCacheController('output', ['defaultgroup' => 'com_emundus']);
								$cache_key = 'db_columns_' . $elementByAlias->db_table_name;
								if ($cache->contains($cache_key))
								{
									$columns = $cache->get($cache_key);
								}

								if (empty($columns))
								{
									// Be sure that fnum column exists in this table
									$fnum_query = 'SHOW COLUMNS FROM ' . $db->quoteName($elementByAlias->db_table_name);
									$db->setQuery($fnum_query);
									$columns = $db->loadColumn();

									$cache->store($columns, $cache_key);
								}

								if (!in_array('fnum', $columns))
								{
									continue;
								}

								$aliasedElement           = $this->fabrikRepository->getElementById($elementByAlias->id);
								$aliasedElementSerialized = $aliasedElement->toArray();
								$aliasedElementValue      = $this->helperFabrik->getFabrikElementValue($aliasedElementSerialized, $file->getFnum(), 0, ValueFormatEnum::FORMATTED, 0, ExportModeEnum::GROUP_CONCAT, $this->translations);
								if ($aliasedElementValue && !empty($aliasedElementValue[$aliasedElement->getId()]) && !empty($aliasedElementValue[$aliasedElement->getId()][$file->getFnum()]) && isset($aliasedElementValue[$aliasedElement->getId()][$file->getFnum()]['val']))
								{
									$result['data'][$file->getFnum()] = $aliasedElementValue[$aliasedElement->getId()][$file->getFnum()]['val'];
									break;
								}
							}
						}
					}

					if ($result['data'][$file->getFnum()] !== '')
					{
						if (in_array($element->getPlugin(), [ElementPluginEnum::TEXTAREA, ElementPluginEnum::CALC]))
						{
							$result['data'][$file->getFnum()] = strip_tags($result['data'][$file->getFnum()]);
						}
						elseif (in_array($element->getPlugin(), [ElementPluginEnum::CHECKBOX, ElementPluginEnum::DROPDOWN, ElementPluginEnum::RADIO]))
						{
							if ($element->getGroupParamsArray()['repeat_group_button'] == 1 || $element->getPlugin() == ElementPluginEnum::CHECKBOX)
							{
								// Explode by , first
								$values = explode(',', $result['data'][$file->getFnum()]);
							}

							if (isset($values) && is_array($values))
							{
								$transformedValues = [];
								foreach ($values as $value)
								{
									$transformedValues[] = $this->getTranslation($value);
								}
								$result['data'][$file->getFnum()] = implode(', ', $transformedValues);
							}
							else
							{
								// Translate value
								$result['data'][$file->getFnum()] = $this->getTranslation($result['data'][$file->getFnum()]);
							}
						}
					}

					// Add blank space if value starts with = to avoid excel formula injection
					if (is_string($result['data'][$file->getFnum()]) && str_starts_with($result['data'][$file->getFnum()], '='))
					{
						$result['data'][$file->getFnum()] = ' ' . $result['data'][$file->getFnum()];
					}
				}
			}
		}

		return $result;
	}

	protected function getTranslation(string $key): string
	{
		$key = trim($key);
		if (isset($this->translations[$key]))
		{
			return $this->translations[$key];
		}

		return Text::_($key);
	}

	private function loadOverrideTranslations(string $code = 'fr-FR'): void
	{
		try
		{
			$file = JPATH_ROOT . '/language/overrides/' . $code . '.override.ini';
			if (file_exists($file))
			{
				$this->translations = parse_ini_file($file);

				if ($this->translations === false)
				{
					$this->translations = [];
				}
			}
		}
		catch (\Exception $e)
		{
			$this->translations = [];
		}
	}

	private function registerClasses(): void
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		$this->fabrikRepository = new FabrikRepository();
		$fabrikFactory          = new FabrikFactory($this->fabrikRepository);
		$this->fabrikRepository->setFactory($fabrikFactory);

		$this->helperFabrik       = new \EmundusHelperFabrik();
		$this->labelRepository    = new LabelRepository();
		$this->campaignRepository = new CampaignRepository();
	}

	public static function getCampaignColumns(): array
	{
		$campaignColumns = [];

		$campaignElements = self::CAMPAIGN_ELEMENTS;
		foreach ($campaignElements as &$element)
		{
			$element['label']       = Text::_($element['label']);
			$element['plugin_name'] = Text::_('COM_EMUNDUS_CAMPAIGN');
		}

		$campaignColumns = [
			'label'      => Text::_('COM_EMUNDUS_CAMPAIGN'),
			'profile_id' => 'campaign',
			'forms'      => [
				'campaign_general' => [
					'id'     => 'campaign_general',
					'label'  => Text::_('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL'),
					'groups' => [
						'campaign_general_group_1' => [
							'label'    => '',
							'elements' => $campaignElements
						]
					]
				]
			]
		];

		$campaignRepository   = new CampaignRepository();
		$campaignMoreElements = $campaignRepository->getCampaignMoreElements();
		foreach ($campaignMoreElements as $moreElement)
		{
			if ($moreElement['hidden'] !== 1 && $moreElement['published'] === 1)
			{
				if (!isset($campaignColumns['forms']['campaign_more']))
				{
					$campaignColumns['forms']['campaign_more'] = [
						'id'     => 'campaign_more',
						'label'  => Text::_('COM_EMUNDUS_CAMPAIGN_MORE'),
						'groups' => [
							1 => [
								'label'    => '',
								'elements' => []
							]
						]
					];
				}

				$campaignColumns['forms']['campaign_more']['groups'][1]['elements'][] = [
					'id'          => 'campaign_more_' . $moreElement['id'],
					'label'       => Text::_($moreElement['label']),
					'plugin_name' => Text::_('COM_EMUNDUS_CAMPAIGN')
				];
			}
		}

		return $campaignColumns;
	}

	public static function getProgramColumns(): array
	{
		$programElements = self::PROGRAM_ELEMENTS;
		foreach ($programElements as &$element)
		{
			$element['label']       = Text::_($element['label']);
			$element['plugin_name'] = Text::_('COM_EMUNDUS_ONBOARD_PROGRAM');
		}

		return [
			'label'      => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM'),
			'profile_id' => 'programme',
			'forms'      => [
				'programme_general' => [
					'id'     => 'programme_general',
					'label'  => Text::_('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL'),
					'groups' => [
						1 => [
							'label'    => '',
							'elements' => $programElements
						]
					]
				]
			]
		];
	}

	public static function getMiscellaneousColumns(): array
	{
		$miscellaneousColumns = [
			'label'      => Text::_('COM_EMUNDUS_EXPORT_DIVERS'),
			'profile_id' => 'others',
			'forms'      => []
		];

		$miscellaneousElements = self::MISCELLANEOUS_ELEMENTS;
		foreach ($miscellaneousElements as &$element)
		{
			$element['label']       = Text::_($element['label']);
			$element['plugin_name'] = Text::_('COM_EMUNDUS_EXPORT_DIVERS');
		}

		$miscellaneousColumns['forms']['other_progress'] = [
			'id'     => 'other_progress',
			'label'  => Text::_('COM_EMUNDUS_EXPORT_APPLICATION_FILE'),
			'groups' => [
				1 => [
					'label'    => '',
					'elements' => $miscellaneousElements
				]
			]
		];

		$moreColumns = self::getMoreColumns();
		if (!empty($moreColumns))
		{
			$miscellaneousColumns['forms']['more_data'] = $moreColumns;
		}

		return $miscellaneousColumns;
	}

	public static function getMoreColumns(): array
	{
		$moreColumns = [];

		$emConfig           = ComponentHelper::getParams('com_emundus');
		$exportMoreElements = $emConfig->get('more_export_elements', []);

		if (!empty($exportMoreElements))
		{
			$fabrikRepository = new FabrikRepository();
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			foreach ($exportMoreElements as $element)
			{
				$elementEntity = $fabrikRepository->getElementById((int) $element->element);
				if (!empty($elementEntity))
				{
					if (!isset($elements['others']['forms']['more_data']))
					{
						$moreColumns = [
							'id'     => 'more_data',
							'label'  => Text::_('COM_EMUNDUS_EXPORT_MORE'),
							'groups' => [
								1 => [
									'label'    => '',
									'elements' => []
								]
							]
						];
					}

					$moreColumns['groups'][1]['elements'][] = [
						'id'          => $elementEntity->getId(),
						'label'       => $elementEntity->getLabel(),
						'plugin_name' => Text::_('COM_EMUNDUS_EXPORT_DIVERS'),
					];
				}
			}
		}

		return $moreColumns;
	}

	public static function getManagementColumns(): array
	{
		$managementElements = self::MANAGEMENT_OTHER_ELEMENTS;
		foreach ($managementElements as &$element)
		{
			$element['label']       = Text::_($element['label']);
			$element['plugin_name'] = Text::_('COM_EMUNDUS_MANAGEMENT');
		}

		return [
			'label'      => Text::_('COM_EMUNDUS_MANAGEMENT'),
			'profile_id' => 'management',
			'forms'      => [
				'management_other' => [
					'id'     => 'management_other',
					'label'  => Text::_('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL'),
					'groups' => [
						1 => [
							'label'    => '',
							'elements' => $managementElements
						]
					]
				]
			]
		];
	}

	public static function getUserColumns(): array
	{
		$userElements = self::USER_ELEMENTS;
		foreach ($userElements as &$element)
		{
			$element['label']       = Text::_($element['label']);
			$element['plugin_name'] = Text::_('COM_EMUNDUS_ACCESS_USER');
		}

		return [
			'label'      => Text::_('COM_EMUNDUS_ACCESS_USER'),
			'profile_id' => 'user',
			'forms'      => [
				'user_general' => [
					'id'     => 'user_general',
					'label'  => Text::_('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL'),
					'groups' => [
						1 => [
							'label'    => '',
							'elements' => $userElements
						]
					]
				]
			]
		];
	}

	public static function getColumnFromKey(string $key): ?array
	{
		$allColumns = array_merge(
			[self::getCampaignColumns()],
			[self::getProgramColumns()],
			[self::getMiscellaneousColumns()],
			[self::getManagementColumns()],
			[self::getUserColumns()]
		);

		foreach ($allColumns as $section)
		{
			foreach ($section['forms'] as $form)
			{
				foreach ($form['groups'] as $group)
				{
					foreach ($group['elements'] as $element)
					{
						if ($element['id'] === $key)
						{
							return $element;
						}
					}
				}
			}
		}

		return null;
	}
}