<?php

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Repositories\Profile\ProfileRepository;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

class FileModel extends AdminModel
{
	private const LANGUAGE_TAG = 'fr-FR';

	private $translations = [];

	private ?FabrikRepository $fabrikRepository = null;

	private ?\EmundusHelperFabrik $fabrikHelper = null;

	/**
	 * Constructor
	 *
	 * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   ?MVCFactoryInterface  $factory  The factory.
	 *
	 * @throws  \Exception
	 * @since   3.7.0
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->typeAlias = Factory::getApplication()->getInput()->getCmd('context', 'com_emundus.files') . '.file';
		Log::addLogger(['text_file' => 'com_emundus.api.log'], Log::ALL, 'com_emundus.api');
	}


	public function getItem($pk = null)
	{
		$item = null;

		if (empty($pk))
		{
			$this->loadOverrideTranslations();

			$app = Factory::getApplication();
			$pk  = $app->input->getString('fnum', '');

			if (!empty($pk))
			{
				Log::add('FileModel::getItem() - fnum: ' . $pk, Log::DEBUG, 'com_emundus.api');

				$db    = $this->getDatabase();
				$query = $db->createQuery();

				$query->select('cc.*, p.id as program_id, c.id as campaign_id, c.profile_id')
					->from($db->quoteName('#__emundus_campaign_candidature', 'cc'))
					->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $db->quoteName('cc.campaign_id') . ' = ' . $db->quoteName('c.id'))
					->leftJoin($db->quoteName('#__emundus_setup_programmes', 'p') . ' ON ' . $db->quoteName('c.training') . ' = ' . $db->quoteName('p.code'))
					->where($db->quoteName('fnum') . ' = ' . $db->quote($pk));

				$filters = $app->input->get('filter', [], 'array');

				try
				{
					$db->setQuery($query);
					$item = $db->loadObject();

					if (!empty($item))
					{
						$statusRepository = new StatusRepository();
						$status           = $statusRepository->getByStep($item->status);
						$item->status     = $status->__serialize();
						$statusReferences = [];

						// TODO: Manage external references
						$externalReferenceRepository = new ExternalReferenceRepository();
						$externalReferences          = $externalReferenceRepository->getItemsByFields(['column' => 'jos_emundus_setup_status.step', 'intern_id' => $status->getStep()]);
						if (!empty($externalReferences))
						{
							foreach ($externalReferences as $externalReference)
							{
								$statusReferences[] = [
									'reference'           => $externalReference->reference,
									'reference_object'    => $externalReference->reference_object,
									'reference_attribute' => $externalReference->reference_attribute,
								];
							}
						}
						$item->status['external_references'] = $statusReferences;


						$query->clear()
							->select('esat.id, esat.label')
							->from($this->getDatabase()->quoteName('#__emundus_setup_action_tag', 'esat'))
							->leftJoin(
								$this->getDatabase()->quoteName('#__emundus_tag_assoc', 'eta')
								. ' ON ' . $this->getDatabase()->quoteName('esat.id') . ' = ' . $this->getDatabase()->quoteName('eta.id_tag')
							)
							->where($this->getDatabase()->quoteName('eta.fnum') . ' = ' . $this->getDatabase()->quote($item->fnum));
						$this->getDatabase()->setQuery($query);
						$item->stickers = $this->getDatabase()->loadObjectList();

						// Add application choices
						$addonRepository = new AddonRepository();
						$choices_addon   = $addonRepository->getByName('choices');
						if ($choices_addon->isActivated())
						{
							$applicationChoicesRepository = new ApplicationChoicesRepository();
							$moreFormId = $applicationChoicesRepository->getMoreFormId();
							$choices                      = $applicationChoicesRepository->getChoicesByFnum($item->fnum, [], null, $moreFormId);
							if (!empty($choices))
							{
								$item->choices = [];
								foreach ($choices as $choice)
								{
									$item->choices[] = $choice->__serialize();
								}
							}
						}

						$emundusUserRepository = new EmundusUserRepository();
						$applicant             = $emundusUserRepository->getByUserId($item->applicant_id);
						$item->applicant       = $applicant?->__serialize();
						if(!empty($applicant->getUserCategory()))
						{
							$userCategoryReferences = [];
							$externalReferences = $externalReferenceRepository->getItemsByFields(['column' => 'data_user_category.id', 'intern_id' => $applicant->getUserCategory()->getId()]);
							if (!empty($externalReferences))
							{
								foreach ($externalReferences as $externalReference)
								{
									$userCategoryReferences[] = [
										'reference'           => $externalReference->reference,
										'reference_object'    => $externalReference->reference_object,
										'reference_attribute' => $externalReference->reference_attribute,
									];
								}
							}

							$item->applicant['user_category']['external_references'] = $userCategoryReferences;

						}

						$item->steps = $this->getSteps($item, $filters);
					}
				}
				catch (\Exception $e)
				{
					$app->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}

		return $item;
	}

	/**
	 * Build the application and management steps of a file, with the value of every element they hold.
	 *
	 * @param   object  $item     The application file row.
	 * @param   array   $filters  Request filters, `profile_id` restricts the output to a single application profile.
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	private function getSteps(object $item, array $filters = []): array
	{
		$steps = [];

		$workflowRepository = new WorkflowRepository();
		$workflow           = $workflowRepository->getWorkflowByFnum($item->fnum);

		$profile_ids      = [(int) $item->profile_id];
		$management_steps = [];

		if (!empty($workflow))
		{
			foreach ($workflow->getSteps() as $step)
			{
				if ($step->isApplicantStep())
				{
					if (!empty($step->getProfileId()))
					{
						$profile_ids[] = $step->getProfileId();
					}
				}
				elseif ($step->isEvaluationStep() && !empty($step->getFormId()))
				{
					$management_steps[] = $step;
				}
			}
		}

		$profile_ids = array_values(array_filter(array_unique($profile_ids)));

		if (\array_key_exists('profile_id', $filters))
		{
			$profile_id = (int) InputFilter::getInstance()->clean($filters['profile_id'], 'INT');

			if ($profile_id > 0)
			{
				$profile_ids      = array_values(array_intersect($profile_ids, [$profile_id]));
				$management_steps = [];
			}
		}

		$profileRepository = new ProfileRepository();

		foreach ($profile_ids as $profile_id)
		{
			$profile = $profileRepository->getById($profile_id);

			if (empty($profile))
			{
				continue;
			}

			$forms = [];
			foreach ($this->getFabrikRepository()->getFormsByProfileId($profile_id) as $form)
			{
				$forms[] = $this->getFormData($form, $item->fnum);
			}

			$steps[] = [
				'id'    => $profile->getId(),
				'type'  => 'applicant',
				'label' => $this->translateLabel($profile->getLabel()),
				'forms' => $forms,
			];
		}

		foreach ($management_steps as $management_step)
		{
			$form = $this->getFabrikRepository()->getFormById($management_step->getFormId());

			if (empty($form))
			{
				continue;
			}

			// A management step can be filled once per evaluator, each answer lives in its own row.
			$row_ids = $this->getStepRowIds($management_step, $item->fnum);
			$forms   = [];

			foreach ($row_ids as $row_id)
			{
				$form_data           = $this->getFormData($form, $item->fnum, (int) $row_id);
				$form_data['row_id'] = (int) $row_id;
				$forms[]             = $form_data;
			}

			$steps[] = [
				'id'    => $management_step->getId(),
				'type'  => 'management',
				'label' => $this->translateLabel($management_step->getLabel()),
				'forms' => $forms,
			];
		}

		return $steps;
	}

	/**
	 * @param   FabrikFormEntity  $form    The form to read, groups and elements already loaded.
	 * @param   string            $fnum
	 * @param   int               $row_id  Restrict the read to a single row, needed on multiple management steps.
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	private function getFormData(FabrikFormEntity $form, string $fnum, int $row_id = 0): array
	{
		$elements = [];

		foreach ($form->getGroups() as $group)
		{
			$repeated = !empty($group->getParams()) && $group->getParams()->getRepeatGroupButton() === 1;

			foreach ($group->getElements() as $element)
			{
				$values = $this->getFabrikHelper()->getFabrikElementValue(
					$element->toArray(false),
					$fnum,
					$row_id,
					ValueFormatEnum::BOTH,
					0,
					ExportModeEnum::LEFT_JOIN,
					$this->translations
				);

				$value = $values[$element->getId()][$fnum] ?? [];

				$elements[] = [
					'id'          => $element->getId(),
					'alias'       => $element->getAlias(),
					'name'        => $element->getName(),
					'label'       => $this->translateLabel($element->getLabel()),
					'group_id'    => $group->getId(),
					'group_label' => $this->translateLabel($group->getLabel()),
					'repeated'    => $repeated,
					'raw'         => $value['raw'] ?? '',
					'value'       => $value['val'] ?? '',
				];
			}
		}

		return [
			'id'             => $form->getId(),
			'label'          => $this->translateLabel($form->getLabel()),
			'count_elements' => count($elements),
			'elements'       => $elements,
		];
	}

	/**
	 * @param   StepEntity  $step
	 * @param   string      $fnum
	 *
	 * @return array
	 */
	private function getStepRowIds(StepEntity $step, string $fnum): array
	{
		if (empty($step->getTable()))
		{
			return [];
		}

		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select($db->quoteName('id'))
			->from($db->quoteName($step->getTable()))
			->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum))
			->where($db->quoteName('step_id') . ' = ' . $db->quote($step->getId()))
			->order($db->quoteName('id') . ' ASC');

		try
		{
			$db->setQuery($query);

			return $db->loadColumn();
		}
		catch (\Exception $e)
		{
			Log::add('FileModel::getStepRowIds() - ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');

			return [];
		}
	}

	private function getFabrikRepository(): FabrikRepository
	{
		if (empty($this->fabrikRepository))
		{
			$this->fabrikRepository = new FabrikRepository();
			$this->fabrikRepository->setFactory(new FabrikFactory($this->fabrikRepository));
			$this->fabrikRepository->setElementFilters(['published' => 1]);
		}

		return $this->fabrikRepository;
	}

	private function getFabrikHelper(): \EmundusHelperFabrik
	{
		if (empty($this->fabrikHelper))
		{
			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
			}

			$this->fabrikHelper = new \EmundusHelperFabrik();
		}

		return $this->fabrikHelper;
	}

	private function translateLabel(string $label): string
	{
		return $this->translations[$label] ?? Text::_($label);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \Joomla\CMS\Form\Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return false;
	}

	/**
	 * @param   string  $code
	 *
	 * @return void
	 */
	private function loadOverrideTranslations(string $code = self::LANGUAGE_TAG): void
	{
		$translations = [];

		$file = JPATH_ROOT . '/language/overrides/' . $code . '.override.ini';
		if (file_exists($file))
		{
			$translations = parse_ini_file($file) ?: [];
		}

		// Management form labels never reach an ini file, they only live in database.
		try
		{
			$languageRepository = new LanguageRepository();
			$overrides          = $languageRepository->getAll(['lang_code' => $code, 'published' => 1], true, 0, 0, false);

			foreach ($overrides as $tag => $override)
			{
				$translations[$tag] = $override->override;
			}
		}
		catch (\Exception $e)
		{
			Log::add('FileModel::loadOverrideTranslations() - ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');
		}

		$this->translations = $translations;
	}
}
