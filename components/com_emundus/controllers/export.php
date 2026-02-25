<?php
/**
 * @version         $Id: export.php 750 2020-05-05 22:29:38Z brivalland $
 * @package         Joomla
 * @copyright   (C) 2020 eMundus LLC. All rights reserved.
 * @license         GNU General Public License
 */

defined('_JEXEC') or die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\User\User;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Entities\Actions\ActionEntity as AccessActionEntity;
use Tchooz\Entities\Automation\Actions\ActionExport;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\List\AdditionalColumnTag;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Actions\ActionRepository as AccessActionRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Task\TaskRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;
use Tchooz\EmundusResponse;
use Tchooz\Services\Export\Excel\ExcelService;
use Tchooz\Services\Export\Export;
use Tchooz\Traits\TraitResponse;

class EmundusControllerExport extends BaseController
{
	use TraitResponse;

	private ?User $_user;

	private bool $exportAction;

	private AccessActionEntity $exportActionExcel;

	private AccessActionEntity $exportActionPdf;

	private AccessActionEntity $exportActionZip;

	private ExportRepository $exportRepository;

	public function __construct(array $config = array())
	{
		parent::__construct($config);

		$this->_user = $this->app->getIdentity();

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		}
		if (!class_exists('EmundusHelperExport'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/export.php');
		}

		$actionRepository = new AccessActionRepository();
		//$this->exportAction      = $actionRepository->getByName('export');

		$this->exportActionExcel = $actionRepository->getByName('export_excel');
		$this->exportActionPdf   = $actionRepository->getByName('export_zip');
		$this->exportActionZip   = $actionRepository->getByName('export_pdf');

		$this->exportAction = EmundusHelperAccess::asAccessAction($this->exportActionExcel->getId(), CrudEnum::CREATE->value, $this->_user->id) || EmundusHelperAccess::asAccessAction($this->exportActionPdf->getId(), CrudEnum::CREATE->value, $this->_user->id) || EmundusHelperAccess::asAccessAction($this->exportActionZip->getId(), CrudEnum::CREATE->value, $this->_user->id);

		$this->exportRepository = new ExportRepository();
	}

	public function display($cachable = false, $urlparams = false): void
	{
		// Set a default view if none exists
		if (!$this->input->get('view'))
		{
			$default = 'application_form';
			$this->input->set('view', $default);
		}

		parent::display();
	}

	/**
	 * @depecated Need to be removed after migration of exports to task system
	 */
	public function getprofiles(): void
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}
		else
		{
			$code = $this->input->getString('code', '');
			$camp = $this->input->getString('camp', '');

			$code = explode(',', $code);
			$camp = explode(',', $camp);

			if (!class_exists('EmundusModelProfile'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
			}
			$m_profile = new EmundusModelProfile();
			$profiles  = $m_profile->getProfileIDByCampaigns($camp, $code);

			echo json_encode((object) $profiles);
			exit();
		}
	}

	public function formats(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$formats       = [];
			$exportFormats = ExportFormatEnum::cases();
			foreach ($exportFormats as $format)
			{
				$formats[] = [
					'value' => $format->value,
					'label' => $format->getLabel(),
					'image' => $format->getImage(),
				];
			}

			$response = EmundusResponse::ok($formats, Text::_('COM_EMUNDUS_EXPORT_FORMATS_RETRIEVED_SUCCESSFULLY'));
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function elements(): void
	{
		try {
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$type = $this->input->getString('type', 'applicant');

			$fnums = $this->input->post->getString('fnums');
			if (empty($fnums))
			{
				$fnums = $this->app->getUserState('com_emundus.files.export.fnums');
			}

			if (empty($fnums))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_NO_FILES_SELECTED'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			$worlflowRepository = new WorkflowRepository();
			$workflows = $worlflowRepository->getWorkflowsByFnums($fnums);

			$elements = [];

			switch($type)
			{
				case 'applicant':
					$campaignRepository = new CampaignRepository(false);
					$campaigns = $campaignRepository->getCampaignsByFnums($fnums);

					foreach ($campaigns as $campaign)
					{
						if (!in_array($campaign->getProfileId(), array_keys($elements)))
						{
							$elements[$campaign->getProfileId()] = [
								'label' => $campaign->getLabel(),
								'profile_id' => $campaign->getProfileId(),
								'campaign_id' => $campaign->getId(),
								'campaign_label' => sizeof($campaigns) > 1 ? $campaign->getLabel() : '',
								'forms'          => []
							];
						}
					}

					foreach ($workflows as $workflow)
					{
						$applicantSteps = array_filter($workflow->getSteps(), function($step)
						{
							return $step->isApplicantStep();
						});

						foreach ($applicantSteps as $applicantStep)
						{
							if (!in_array($applicantStep->getProfileId(), array_keys($elements)))
							{
								$elements[$applicantStep->getProfileId()] = [
									'label'          => $applicantStep->getLabel(),
									'profile_id'     => $applicantStep->getProfileId(),
									'campaign_id'    => $workflow->getId(),
									'campaign_label' => sizeof($workflows) > 1 ? $workflow->getLabel() : '',
									'forms'          => []
								];
							}
						}
					}

					break;
				case 'management':
					foreach ($workflows as $workflow)
					{
						$managementSteps = array_filter($workflow->getSteps(), function($step)
						{
							return $step->isEvaluationStep();
						});

						foreach ($managementSteps as $managementStep)
						{
							if (!in_array($managementStep->getFormId(), array_keys($elements)))
							{
								$elements[$managementStep->getFormId()] = [
									'label'          => $managementStep->getLabel(),
									'profile_id'     => $managementStep->getFormId(),
									'campaign_id'    => $workflow->getId(),
									'campaign_label' => sizeof($workflows) > 1 ? $workflow->getLabel() : '',
									'forms'          => []
								];
							}
						}
					}
					break;
				case 'attachments':
					$campaignRepository = new CampaignRepository(true);
					$campaigns = $campaignRepository->getCampaignsByFnums($fnums);

					$hFiles = new EmundusHelperFiles();
					if (!empty($campaigns))
					{
						$nbStepsAttachments = 0;
						$nbLettersAttachments = 0;
						$campaignAttachments = [];
						$attachmentTypeRepository = new AttachmentTypeRepository();
						foreach ($campaigns as $campaign)
						{
							$campaignAttachments = $hFiles->getAttachmentsTypesByProfileID([$campaign->getProfileId()]);
						}

						$workflowAttachments = [];
						foreach ($workflows as $workflow)
						{
							foreach ($workflow->getSteps() as $step)
							{
								if ($step->isApplicantStep() && !empty($step->getProfileId()) && !in_array($step->getProfileId(), array_keys($elements)))
								{
									$workflowAttachments = $hFiles->getAttachmentsTypesByProfileID([$step->getProfileId()]);
								}
							}
						}

						if (!empty($campaignAttachments) || !empty($workflowAttachments))
						{
							$elements['attachments'] = [
								'label'          => Text::_('COM_EMUNDUS_EXPORTS_ATTACHMENT_TAB'),
								'profile_id'     => 'attachments',
								'campaign_id'    => 'attachments',
								'campaign_label' => '',
								'forms'          => [
									'attachments' => [
										'id' => 'attachments',
										'label' => Text::_('COM_EMUNDUS_EXPORTS_ATTACHMENT_TAB'),
										'groups' => [
											'attachments' => ['label' => '', 'elements' => []],
										]
									],
								]
							];

							$allAttachments = array_merge($campaignAttachments, $workflowAttachments);
							$alreadyAdded = [];
							foreach ($allAttachments as $attachment)
							{
								if (!in_array($attachment->id, $alreadyAdded))
								{
									$attachmentObject                                                                       = new stdClass();
									$attachmentObject->id                                                                   = $attachment->id;
									$attachmentObject->label                                                                = $attachment->value;
									$elements['attachments']['forms']['attachments']['groups']['attachments']['elements'][] = $attachmentObject;
									$alreadyAdded[] = $attachment->id;
									$nbStepsAttachments++;
								}
							}
							$elements['attachments']['forms']['attachments']['label'] .= ' (' . $nbStepsAttachments . ')';
						}

						$attachmentLetters = $attachmentTypeRepository->getAttachmentLettersByCampaigns($campaigns);
						if (!empty($attachmentLetters))
						{
							$elements['attachments']['forms']['letters'] = [
								'id' => 'letters',
								'label' => Text::_('COM_EMUNDUS_EVENT_CATEGORY_LETTERS'),
								'groups' => [
									'letters' => ['label' => '', 'elements' => []]
								]
							];

							foreach ($attachmentLetters as $attachmentLetter)
							{
								$attachmentObject                                                           = new stdClass();
								$attachmentObject->id                                                       = $attachmentLetter->getId();
								$attachmentObject->label                                                    = $attachmentLetter->getName();
								$elements['attachments']['forms']['letters']['groups']['letters']['elements'][] = $attachmentObject;
								$nbLettersAttachments++;
							}
							$elements['attachments']['forms']['letters']['label'] .= ' (' . $nbLettersAttachments . ')';
						}

						$elements['attachments']['label'] .= ' (' . ($nbStepsAttachments+$nbLettersAttachments) . ')';
					}

					break;
				case 'other':
					$elements['campaign']   = Export::getCampaignColumns();
					$elements['programme']  = Export::getProgramColumns();
					$elements['others']     = Export::getMiscellaneousColumns();
					$elements['management'] = Export::getManagementColumns();
					$elements['user']       = Export::getUserColumns();
					break;
			}
			$elements = array_values($elements);

			$response = EmundusResponse::ok($elements, Text::_('COM_EMUNDUS_EXPORT_ELEMENTS_RETRIEVED_SUCCESSFULLY'));
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function getSubElements(): void
	{
		try {
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$type = $this->input->getString('type', 'applicant');
			$elementId = $this->input->getInt('elementId', 0);

			if (empty($elementId))
			{
				throw new Exception(Text::_('MISSING_PARAMETER'), EmundusResponse::HTTP_BAD_REQUEST);
			}
			$subElements = [];

			$fabrikRepository = new FabrikRepository();
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			$fabrikRepository->setElementFilters(['excluded_elements' => ['id', 'user', 'time_date', 'fnum', 'date_time', 'parent_id']]);
			switch ($type)
			{
				case 'applicant':
					$forms       = $fabrikRepository->getFormsByProfileId($elementId);
					$subElements = array_map(function ($form) {
						return $form->__serialize();
					}, $forms);

					break;
				case 'management':
					$form        = $fabrikRepository->getFormById($elementId);
					$subElements = [$form->__serialize()];
					break;
				default:
			}


			$response = EmundusResponse::ok($subElements, Text::_('COM_EMUNDUS_EXPORT_STEPS_RETRIEVED_SUCCESSFULLY'));

		} catch (\Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}


		$this->sendJsonResponse($response);
	}

	public function defaultsynthesis(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$format = $this->input->getString('format', 'xlsx');
			$format = ExportFormatEnum::tryFrom($format);
			if (empty($format))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_INVALID_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			$fabrikRepository = new FabrikRepository();
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			$parameterKey = $format->getSynthesisParameterKey();

			$emConfig  = ComponentHelper::getParams('com_emundus');
			$synthesis = $emConfig->get($parameterKey, []);

			$data = [];
			foreach ($synthesis as $synthesisItem)
			{
				if (!empty($synthesisItem->element))
				{
					if (is_numeric($synthesisItem->element))
					{
						$element = $fabrikRepository->getElementById($synthesisItem->element);
						if (!empty($element))
						{
							$data[] = $element->toArray();
						}
					}
					else
					{
						$element = Export::getColumnFromKey($synthesisItem->element);
						if (!empty($element))
						{
							$data[] = $element;
						}
					}
				}
			}

			$response = EmundusResponse::ok($data, Text::_('COM_EMUNDUS_EXPORT_DEFAULT_SYNTHESIS_RETRIEVED_SUCCESSFULLY'));
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function defaultheader(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$fabrikRepository = new FabrikRepository();
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			$emConfig = ComponentHelper::getParams('com_emundus');
			$header   = $emConfig->get('default_header_pdf', []);

			$data = [];
			foreach ($header as $headerItem)
			{
				if (!empty($headerItem->element))
				{
					if (is_numeric($headerItem->element))
					{
						$element = $fabrikRepository->getElementById($headerItem->element);
						if (!empty($element))
						{
							$data[] = $element->toArray();
						}
					}
					else
					{
						$element = Export::getColumnFromKey($headerItem->element);
						if (!empty($element))
						{
							$data[] = $element;
						}
					}
				}
			}

			$response = EmundusResponse::ok($data, Text::_('COM_EMUNDUS_EXPORT_DEFAULT_HEADER_RETRIEVED_SUCCESSFULLY'));
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function export(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$currentLang = $this->app->getLanguage()->getTag();

			$emConfig      = ComponentHelper::getParams('com_emundus');
			$allowAsync    = $emConfig->get('async_export', 0);
			$exportVersion = $this->input->getString('version', 'default');

			$async = false;
			if ($allowAsync)
			{
				$async = $this->input->getString('async', false);
				$async = filter_var($async, FILTER_VALIDATE_BOOLEAN);
			}

			$format = $this->input->getString('format');
			$format = ExportFormatEnum::tryFrom($format);
			if (empty($format))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_INVALID_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			if ($format === ExportFormatEnum::XLSX && $exportVersion === 'default')
			{
				$elts = $this->input->getString('elts', '');
				if (empty($elts))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_NO_ELEMENTS_SELECTED'), EmundusResponse::HTTP_BAD_REQUEST);
				}
			}
			else
			{
				$elements = $this->input->post->getString('elements', '');
				if (empty($elements))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_NO_ELEMENTS_SELECTED'), EmundusResponse::HTTP_BAD_REQUEST);
				}
			}

			if ($format === ExportFormatEnum::PDF)
			{
				$headers     = $this->input->getString('headers', '');
				$attachments = $this->input->getString('attachments', '');
			}
			$synthesis = $this->input->getString('synthesis', '');

			$fnums = $this->input->post->getString('fnums');
			if (empty($fnums))
			{
				$fnums = $this->app->getUserState('com_emundus.files.export.fnums');
			}
			// Filter fnums that we can actually export
			if (!is_array($fnums))
			{
				$fnums = explode(',', $fnums);
			}

			if(empty($fnums))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_NO_FILES_SELECTED'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			$campaign = $this->input->getInt('campaign', 0);
			if (empty($campaign))
			{
				$applicationFileRepository = new ApplicationFileRepository();
				$campaignIds               = $applicationFileRepository->getCampaignIds($fnums);

				// Get campaigns of fnums and keep the campaign with max occurrences
				$campaignIdCounts = array_count_values($campaignIds);
				arsort($campaignIdCounts);
				$campaign = key($campaignIdCounts);
			}

			$parameters = [
				'format'   => $format->value,
				'campaign' => $campaign
			];

			if ($format === ExportFormatEnum::XLSX && $exportVersion === 'default')
			{
				$file      = $this->input->getString('file');
				$totalfile = $this->input->getInt('totalfile', 0);
				$start     = $this->input->getInt('start', 0);
				$limit     = $this->input->getInt('limit', 100);
				$nbcol     = $this->input->getInt('nbcol', 0);
				$elts      = $this->input->getString('elts', '');
				$step_elts = $this->input->getString('step_elts', []);
				if (!is_array($step_elts))
				{
					$step_elts = json_decode($step_elts, true);
				}
				$objs           = $this->input->getString('objs', null);
				$opts           = $this->input->getString('opts', null);
				$method         = $this->input->getInt('methode', 0);
				$objclass       = $this->input->get('objclass', null);
				$excel_filename = $this->input->getString('excelfilename', 'export.xlsx');

				// Sanitize excel_filename
				if (!class_exists('HtmlSanitizerSingleton'))
				{
					require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
				}
				$htmlSanitizer  = HtmlSanitizerSingleton::getInstance();
				$excel_filename = $htmlSanitizer->sanitize($excel_filename);

				// Remove /\ and whitespace characters from filename
				$excel_filename = preg_replace('/[\/\\\\]+/', '_', $excel_filename);
				$excel_filename = preg_replace('/\s+/', '_', $excel_filename);
				$excel_filename = strtolower($excel_filename);

				$parameters = array_merge($parameters, [
					'export_version' => $exportVersion,
					'tmp_file'       => $file,
					'totalfile'      => $totalfile,
					'start'          => $start,
					'limit'          => $limit,
					'nbcol'          => $nbcol,
					'elts'           => $elts,
					'step_elts'      => $step_elts,
					'objs'           => $objs,
					'opts'           => $opts,
					'method'         => $method,
					'objclass'       => $objclass,
					'excel_filename' => $excel_filename,
					'lang'           => $currentLang,
				]);

				// Delete tmp file if exists
				$tmpFilename = 'tmp/' . $file;
				$tmpFile     = JPATH_SITE . '/' . $tmpFilename;
				if (file_exists($tmpFile))
				{
					unlink($tmpFile);
				}
			}
			else
			{
				$parameters = array_merge($parameters, [
					'export_version' => $exportVersion,
					'elements'       => $elements ?? [],
					'headers'        => $headers ?? [],
					'synthesis'      => $synthesis ?? [],
					'attachments'    => $attachments ?? [],
					'lang'           => $currentLang,
				]);
			}

			$exportEntity = null;
			if (isset($tmpFilename))
			{
				$exportEntity = $this->exportRepository->getByFilenameAndUser($tmpFilename, $this->_user->id);
			}
			elseif ($async)
			{
				$exportEntity = $this->exportRepository->getLastExportByUser($this->_user->id);
			}

			if (empty($exportEntity))
			{
				$exportEntity = new ExportEntity(
					id: 0,
					createdAt: new \DateTime(),
					createdBy: $this->_user,
					filename: $tmpFilename ?? '',
					format: $format,
					expiredAt: null,
					task: null,
					hits: 0,
					progress: 0.0,
				);
				if (!$this->exportRepository->flush($exportEntity))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_SAVE_EXPORT_RECORD'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
				}
			}

			$exportAction = new ActionExport($parameters);
			$targets      = array_map(function ($fnum) use ($exportEntity) {
				return new ActionTargetEntity($this->_user, $fnum, null, ['export_id' => $exportEntity->getId()]);
			}, $fnums);

			if ($async)
			{
				// Check if we have an existing pending or in-progress task for this export
				if (empty($exportEntity->getTask()))
				{
					// If export asynchronous, create a task
					$task = new TaskEntity(0, TaskStatusEnum::PENDING, null, $this->_user->id, ['actionEntity' => $exportAction->serialize(), 'fnums' => array_map(function ($target) {
						return $target->getFile();
					}, $targets)]);

					$taskRepository = new TaskRepository();
					if (!$taskRepository->saveTask($task))
					{
						throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_SAVE_TASK'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
					}

					$exportEntity->setCancelled(true);
					$exportEntity->setTask($task);
					if (!$this->exportRepository->flush($exportEntity))
					{
						throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_UPDATE_EXPORT_WITH_TASK'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
					}
				}

				$response = EmundusResponse::ok(['task_id' => $exportEntity->getTask()->getId()], Text::_('COM_EMUNDUS_EXPORT_TASK_QUEUED_SUCCESSFULLY'));
				//
			}
			else
			{
				// Synchronous export
				if ($exportAction->with($exportEntity)->execute($targets) !== ActionExecutionStatusEnum::COMPLETED)
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_EXECUTE_EXPORT'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
				}

				// Get last export for user
				$exportResult = $this->exportRepository->getLastExportByUser($this->_user->id);

				$response = EmundusResponse::ok($exportResult->__serialize(), Text::_('COM_EMUNDUS_EXPORT_COMPLETED_SUCCESSFULLY'));
			}
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function getexports(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$lim    = $this->input->getInt('lim', 0);
			$page   = $this->input->getInt('page', 0);
			$status = $this->input->getString('status', 'all');
			if (!in_array($status, ['all', 'in_progress', 'completed']))
			{
				$status = 'all';
			}
			$sortDir = $this->input->getString('sort', 'DESC');
			if (!in_array($sortDir, ['ASC', 'DESC']))
			{
				$sortDir = 'DESC';
			}

			$exports = $this->exportRepository->getAll($lim, $page, $sortDir, $status, $this->_user->id);

			if ($exports->getTotalItems() > 0)
			{
				$exportsSerialized = array_map(function ($exportEntity) {
					assert($exportEntity instanceof ExportEntity);
					$export = (object) $exportEntity->__serialize();

					$createdAt = EmundusHelperDate::displayDate($exportEntity->getCreatedAt()->format('Y-m-d H:i:s'), 'DATE_FORMAT_LC2', 0);

					if (!empty($exportEntity->getTask()))
					{
						$metadata = $exportEntity->getTask()->getMetadata();
						if (!empty($metadata['actionEntity']['parameter_values']['time_estimate']))
						{
							// Calculate end time based on created at + time estimate
							$timeEstimateSeconds = (int) $metadata['actionEntity']['parameter_values']['time_estimate'];
							$endTime             = clone $exportEntity->getCreatedAt();
							$endTime->modify('+' . $timeEstimateSeconds . ' seconds');
							$export->estimated_end_time = EmundusHelperDate::displayDate($endTime->format('Y-m-d H:i:s'), 'DATE_FORMAT_LC2', 0);
						}
					}

					$export->label = ['fr' => 'Export du ' . $createdAt, 'en' => 'Export of ' . $createdAt];

					$status = (Text::_('COM_EMUNDUS_EXPORTS_STATUS_IN_PROGRESS') . ' (' . $exportEntity->getProgress() . '%)');
					$class  = 'tw-bg-blue-500';
					if ($exportEntity->isFailed())
					{
						$status = Text::_('COM_EMUNDUS_EXPORTS_STATUS_FAILED');
						$class  = 'tw-bg-red-500';
					}
					elseif ($exportEntity->getProgress() >= 100)
					{
						$status = Text::_('COM_EMUNDUS_EXPORTS_STATUS_COMPLETED');
						$class  = 'tw-bg-green-500';
					}

					$export->additional_columns = [
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_EXPORTS_FORMAT'),
							'',
							ListDisplayEnum::ALL,
							'',
							Text::_($exportEntity->getFormat()->getLabel()),
						),
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_EXPORTS_STATUS'),
							'',
							ListDisplayEnum::ALL,
							'',
							'',
							[new AdditionalColumnTag(
								Text::_('COM_EMUNDUS_EXPORTS_STATUS'),
								$status,
								$exportEntity->getProgress(),
								'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-text-white ' . $class
							)],
							ListColumnTypesEnum::TAGS
						),
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_EXPORTS_HITS'),
							'',
							ListDisplayEnum::ALL,
							'',
							$exportEntity->getHits(),
						),
					];

					return $export;
				}, $exports->getItems());

				$response = EmundusResponse::ok(
					['datas' => $exportsSerialized, 'count' => $exports->getTotalItems()],
					Text::_('COM_EMUNDUS_EXPORTS_RETRIEVED_SUCCESSFULLY')
				);
			}
			else
			{
				$response = EmundusResponse::ok(
					['datas' => [], 'count' => 0],
					Text::_('COM_EMUNDUS_EXPORTS_NO_EXPORTS_FOUND')
				);
			}
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function downloadexport(): void
	{
		try
		{
			$id = $this->input->getInt('id', 0);

			// Get link to export file
			$export = $this->exportRepository->getById($id);
			if (empty($export))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
			}

			// Check if user has access to download this export
			if ($export->getCreatedBy() !== $this->_user->id && !EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			// Update hits
			$export->setHits($export->getHits() + 1);
			if (!$this->exportRepository->flush($export))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_UPDATE_HITS'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
			}

			if (str_ends_with($export->getFilename(), '.json'))
			{
				// Create a temporary csv file from json export
				$jsonFilePath = JPATH_SITE . '/' . $export->getFilename();

				$data = json_decode(file_get_contents($jsonFilePath), true);
				if (empty($data) || !is_array($data))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_READ_JSON_FILE'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
				}

				$excelService = new ExcelService();
				$filePath     = $excelService->fillCsv('tmp/', $data);
				if (empty($filePath))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_CREATE_CSV_FILE'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
				}
			}
			else
			{
				$filePath = $export->getFilename();
			}

			$response = EmundusResponse::ok(
				['download_file' => '/' . $filePath],
				Text::_('COM_EMUNDUS_EXPORT_RETRIEVED_SUCCESSFULLY')
			);
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function delete(): void
	{
		try
		{
			$exports_ids = [];
			$ids         = $this->input->getString('ids', '');
			if (!empty($ids))
			{
				$exports_ids = explode(',', $ids);
			}
			$id = $this->input->getInt('id', 0);
			if ($id > 0)
			{
				$exports_ids[] = $id;
			}

			foreach ($exports_ids as $id)
			{
				$export = $this->exportRepository->getById($id);
				if (empty($export))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
				}

				// Check if user has access to download this export
				if ($export->getCreatedBy() !== $this->_user->id && !EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
				{
					throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
				}

				// Delete export record and task associated if any
				if (!$this->exportRepository->delete($export->getId()))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_DELETE_EXPORT'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
				}

				$task = $export->getTask();
				if (!empty($task))
				{
					$taskRepository = new TaskRepository();
					if (!$taskRepository->deleteTaskById($task->getId()))
					{
						throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_DELETE_ASSOCIATED_TASK'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
					}
				}

				if (!$this->exportRepository->delete($export->getId()))
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_FAILED_TO_DELETE_EXPORT'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
				}
			}

			$response = EmundusResponse::ok(
				[],
				Text::_('COM_EMUNDUS_EXPORT_DELETED_SUCCESSFULLY')
			);
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function templates(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$templates = $this->exportRepository->getAllExportTemplates($this->_user->id);

			$response = EmundusResponse::ok(
				$templates,
				Text::_('COM_EMUNDUS_EXPORT_TEMPLATES_RETRIEVED_SUCCESSFULLY')
			);
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function elementsfromsavedexport(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$id = $this->input->getInt('id', 0);
			if ($id <= 0)
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_INVALID_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			$exportTemplate = $this->exportRepository->getExportTemplate($id);
			if (empty($exportTemplate) || $exportTemplate->user !== $this->_user->id)
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_TEMPLATE_NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
			}

			$fabrikRepository = new FabrikRepository();
			$fabrikFactory    = new FabrikFactory($fabrikRepository);
			$fabrikRepository->setFactory($fabrikFactory);

			$constraints = json_decode($exportTemplate->constraints);

			$data = [
				'elements'    => [],
				'headers'     => [],
				'synthesis'   => [],
				'attachments' => [],
				'format'      => $constraints->format,
				'name'        => $exportTemplate->name
			];

			$elements = json_decode($constraints->elements);
			foreach ($elements as $elementId)
			{
				if (is_numeric($elementId))
				{
					$element = $fabrikRepository->getElementById($elementId);
					if (!empty($element))
					{
						$data['elements'][] = $element->toArray();
					}
				}
				else
				{
					$element = Export::getColumnFromKey($elementId);
					if (!empty($element))
					{
						$data['elements'][] = $element;
					}
				}
			}

			$headers = json_decode($constraints->headers);
			foreach ($headers as $headerId)
			{
				if (is_numeric($headerId))
				{
					$element = $fabrikRepository->getElementById($headerId);
					if (!empty($element))
					{
						$data['headers'][] = $element->toArray();
					}
				}
				else
				{
					$element = Export::getColumnFromKey($headerId);
					if (!empty($element))
					{
						$data['headers'][] = $element;
					}
				}
			}

			$synthesis = json_decode($constraints->synthesis);
			foreach ($synthesis as $synthesisId)
			{
				if (is_numeric($synthesisId))
				{
					$element = $fabrikRepository->getElementById($synthesisId);
					if (!empty($element))
					{
						$data['synthesis'][] = $element->toArray();
					}
				}
				else
				{
					$element = Export::getColumnFromKey($synthesisId);
					if (!empty($element))
					{
						$data['synthesis'][] = $element;
					}
				}
			}

			$attachmentRepository = new AttachmentTypeRepository();
			$attachments          = json_decode($constraints->attachments);
			foreach ($attachments as $attachmentId)
			{
				if (is_numeric($attachmentId))
				{
					$attachment = $attachmentRepository->loadAttachmentTypeById($attachmentId);
					if (!empty($attachment))
					{
						$attachmentObject        = new stdClass();
						$attachmentObject->id    = $attachment->getId();
						$attachmentObject->label = $attachment->getName();

						$data['attachments'][] = $attachmentObject;
					}
				}
			}

			$response = EmundusResponse::ok(
				$data,
				Text::_('COM_EMUNDUS_EXPORT_TEMPLATE_RETRIEVED_SUCCESSFULLY')
			);
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function saveexport(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$id = $this->input->getInt('id', 0);
			if ($id > 0)
			{
				// Check if export template exist and belong to user
				$exportTemplate = $this->exportRepository->getExportTemplate($id);
				if (empty($exportTemplate) || $exportTemplate->user !== $this->_user->id)
				{
					throw new Exception(Text::_('COM_EMUNDUS_EXPORT_TEMPLATE_NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
				}
			}

			$name = $this->input->getString('name', '');
			$name = strip_tags($name);

			$format = $this->input->getString('format', '');
			if (empty($name) || empty($format))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_INVALID_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			$format = ExportFormatEnum::tryFrom($format);
			if (empty($format))
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_INVALID_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			$elements    = $this->input->getString('elements', '');
			$elements    = !empty($elements) ? explode(',', $elements) : [];
			$headers     = $this->input->getString('headers', '');
			$headers     = !empty($headers) ? explode(',', $headers) : [];
			$synthesis   = $this->input->getString('synthesis', '');
			$synthesis   = !empty($synthesis) ? explode(',', $synthesis) : [];
			$attachments = $this->input->getString('attachments', '');
			$attachments = !empty($attachments) ? explode(',', $attachments) : [];

			$saved = $this->exportRepository->saveExportTemplate($name, $format, $elements, $headers, $synthesis, $attachments, $this->_user->id, $id);

			$response = EmundusResponse::ok(
				$saved,
				Text::_('COM_EMUNDUS_EXPORT_TEMPLATE_SAVED_SUCCESSFULLY')
			);
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}

	public function deletetemplate(): void
	{
		try
		{
			if (!$this->exportAction)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$id = $this->input->getInt('id', 0);
			if ($id <= 0)
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_INVALID_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
			}

			$exportTemplate = $this->exportRepository->getExportTemplate($id);
			if (empty($exportTemplate) || $exportTemplate->user !== $this->_user->id)
			{
				throw new Exception(Text::_('COM_EMUNDUS_EXPORT_TEMPLATE_NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
			}

			$this->exportRepository->deleteExportTemplate($id);

			$response = EmundusResponse::ok(
				[],
				Text::_('COM_EMUNDUS_EXPORT_TEMPLATE_DELETED_SUCCESSFULLY')
			);
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}
}
