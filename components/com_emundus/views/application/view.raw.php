<?php
/**
 * @package    Joomla
 * @subpackage emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Tchooz\Entities\Reference\InternalReferenceEntity;
use Tchooz\Enums\CrudEnum;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;
use Tchooz\Repositories\Settings\ConfigurationRepository;
use Tchooz\Services\Reference\InternalReferenceFormat;
use Tchooz\Services\Reference\InternalReferenceService;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewApplication extends HtmlView
{
	private CMSApplicationInterface $app;

	private ?User $user;
	protected ?User $student;

	protected int $student_id;
	protected int $sid;

	protected int $ccid;
	protected string $fnum;
	protected int $campaign_id;

	protected object $synthesis;
	protected object $assoc_files;

	protected array $columns;
	protected array $userAttachments;
	protected mixed $attachmentsProgress;
	protected array $nameCategory;

	protected string|int $expert_document_id;

	protected array $evaluation_select;
	protected string $message;
	protected array $messages;

	protected string $url_form;
	protected int $formid;

	protected array $fileLogs;

	protected array $tags;
	protected array $groupedTags;
	protected bool|int $displayTagCategories;

	protected array $pids;
	protected mixed $defaultpid;
	protected mixed $formsProgress;
	protected string $forms;
	protected object $applicant;
	protected array $access;
	protected array $defaultActions;
	protected bool $canUpdateAccess;
	protected ?string $html_form;
	protected mixed $_user;
	protected ?array $collaborators;
	protected bool $is_applicant;

	protected ?InternalReferenceEntity $reference;
	protected ?string $shortReference;
	protected bool $showReference;

	function __construct($config = array())
	{
		parent::__construct($config);

		if (!class_exists('EmundusModelLogs'))
		{
			require_once(JPATH_BASE . '/components/com_emundus/models/logs.php');
		}
		require_once(JPATH_BASE . '/components/com_emundus/helpers/filters.php');
		require_once(JPATH_BASE . '/components/com_emundus/helpers/list.php');
		require_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');
		require_once(JPATH_BASE . '/components/com_emundus/helpers/emails.php');
		require_once(JPATH_BASE . '/components/com_emundus/helpers/export.php');
		require_once(JPATH_BASE . '/components/com_emundus/helpers/menu.php');

		$this->app  = Factory::getApplication();
		$session    = $this->app->getSession();
		$this->user = $this->app->getIdentity();

		$this->_user = $session->get('emundusUser');
		if(empty($this->_user->fnums)) {
			$this->_user->fnums = [];
		}
	}

	function display($tpl = null)
	{
		$params = ComponentHelper::getParams('com_emundus');

		$jinput = $this->app->input;
		$fnum   = $jinput->getString('fnum', '');
		$ccid   = $jinput->getInt('ccid', 0);
		$layout = $jinput->getString('layout', 0);
		$Itemid = $jinput->get('Itemid', 0);

		$applicationFileRepository = new ApplicationFileRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($fnum);

		$this->ccid        = $applicationFile->getId();
		$this->fnum        = $fnum;
		$this->student     = $applicationFile->getUser();
		$this->sid         = $applicationFile->getUser()->id;
		$this->student_id  = $applicationFile->getUser()->id;
		$this->userid      = $applicationFile->getUser()->id;
		$this->campaign_id = $applicationFile->getCampaign()->getId();

		if (!class_exists('EmundusModelApplication'))
		{
			require_once(JPATH_BASE . '/components/com_emundus/models/application.php');
		}
		$m_application = new EmundusModelApplication();

		$expire = time() + 60 * 60 * 24 * 30;
		setcookie("application_itemid", $jinput->getString('id', 0), $expire);

		if (
			(EmundusHelperAccess::asAccessAction(1, 'r', $this->user->id, $fnum) && EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
			||
			(!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums)))
		)
		{
			switch ($layout)
			{
				case 'synthesis':
					if (!class_exists('EmundusModelUsers'))
					{
						require_once(JPATH_BASE . '/components/com_emundus/models/users.php');
					}
					$m_users   = new EmundusModelUsers;
					$applicant = $m_users->getUserById($this->sid);
					if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id) && $applicant[0]->is_anonym != 1)
					{
						$this->synthesis = new stdClass();
						$program         = $m_application->getProgramSynthesis($applicationFile->getCampaign()->getId());
						if (!empty($program->synthesis))
						{
							$campaignInfo = $m_application->getUserCampaigns($this->sid, $applicationFile->getCampaign()->getId());

							if (!class_exists('EmundusModelEmails'))
							{
								require_once(JPATH_BASE . '/components/com_emundus/models/emails.php');
							}
							$m_emails = new EmundusModelEmails();
							$tag      = array(
								'FNUM'                 => $fnum,
								'CAMPAIGN_NAME'        => $applicationFile->getCampaign()->getLabel(),
								'CAMPAIGN_LABEL'       => $applicationFile->getCampaign()->getLabel(),
								'APPLICATION_STATUS'   => $applicationFile->getStatus()->getLabel(),
								'APPLICATION_TAGS'     => $fnum,
								'APPLICATION_PROGRESS' => $applicationFile->getFormProgress(),
								'ATTACHMENT_PROGRESS'  => $applicationFile->getAttachmentProgress()
							);

							$tags = $m_emails->setTags($this->sid, $tag, $fnum, '', $program->synthesis);

							$this->synthesis->program = $program;
							$this->synthesis->camp    = $campaignInfo;
							$this->synthesis->fnum    = $fnum;
							$this->synthesis->block   = preg_replace($tags['patterns'], $tags['replacements'], $program->synthesis);
							$this->synthesis->block   = $m_emails->setTagsFabrik($this->synthesis->block, array($fnum));
						}
					}
					break;

				case 'assoc_files':
					if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
					{
						if (!class_exists('EmundusModelFiles'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/files.php');
						}
						$m_files   = new EmundusModelFiles();
						$fnumInfos = $m_files->getFnumInfos($fnum);

						$show_related_files = $params->get('show_related_files', 0);

						if ($show_related_files || EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asManagerAccessLevel($this->user->id))
						{
							$campaignInfo = $m_application->getUserCampaigns($this->sid, null, false);

							$published_campaigns   = array_filter($campaignInfo, function ($campaign) {
								return $campaign->published == 1;
							});
							$unpublished_campaigns = array_filter($campaignInfo, function ($campaign) {
								return $campaign->published != 1;
							});

							foreach ($campaignInfo as $key => $campaign)
							{
								if (!EmundusHelperAccess::isUserAllowedToAccessFnum($this->user->id, $campaign->fnum))
								{
									unset($campaignInfo[$key]);
								}
							}

						}
						else
						{
							$published_campaigns   = $m_application->getCampaignByFnum($fnum);
							$unpublished_campaigns = [];
						}

						$this->assoc_files                        = new stdClass();
						$this->assoc_files->published_campaigns   = $published_campaigns;
						$this->assoc_files->unpublished_campaigns = $unpublished_campaigns;
						$this->assoc_files->fnumInfos             = $fnumInfos;
						$this->assoc_files->fnum                  = $fnum;
					}
					break;

				case 'attachment':
					if (EmundusHelperAccess::asAccessAction(4, 'r', $this->user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums))))
					{
						if (!class_exists('EmundusModelFiles'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/files.php');
						}
						$m_files   = new EmundusModelFiles();
						$fnumInfos = $m_files->getFnumInfos($fnum);

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 4, 'r', 'COM_EMUNDUS_ACCESS_ATTACHMENT_READ');
						$this->expert_document_id = $params->get('expert_document_id', '36');

						$search = $jinput->getString('search');

						$this->userAttachments     = $m_application->getUserAttachmentsByFnum($fnum, $search, $fnumInfos['profile_id'], (bool) $this->_user->applicant);
						$this->attachmentsProgress = $m_application->getAttachmentsProgress($fnum);
						$this->nameCategory        = $m_files->getAttachmentCategories();
						$this->is_applicant        = $this->_user->applicant;
						$this->columns             = ['check', 'name', 'date', 'desc', 'category', 'status', 'user', 'modified_by', 'modified', 'permissions', 'sync', 'sign'];
						if ($this->_user->applicant)
						{
							//TODO: Add menu parameters
							$this->columns = ['name', 'date', 'desc', 'status', 'modified', 'sign'];
						}
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}
					break;

				case 'applicationchoices':
				case 'assessment':
					break;

				case 'evaluation':
					if (EmundusHelperAccess::asAccessAction(5, 'c', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(5, 'r', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(5, 'u', $this->user->id, $fnum))
					{
						$can_copy_evaluations = $params->get('can_copy_evaluations', 0);

						if (!class_exists('EmundusModelEvaluation'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/evaluation.php');
						}
						$m_evaluation = new EmundusModelEvaluation();

						// get evaluation form ID
						$formid = $m_evaluation->getEvaluationFormByProgramme($applicationFile->getCampaign()->getProgram()->getCode());

						$message = 'COM_EMUNDUS_EVALUATIONS_NO_EVALUATION_FORM_SET';
						if (!empty($formid))
						{
							$evaluation           = $m_evaluation->getEvaluationUrl($fnum, $formid);
							$message              = $evaluation['message'];
							$this->url_form       = $evaluation['url'];
							$this->url_evaluation = Uri::base() . 'index.php?option=com_emundus&view=evaluation&layout=data&format=raw&Itemid=' . $Itemid . '&cfnum=' . $fnum;
						}
						else
						{
							$this->url_evaluation = '';
							$this->url_form       = '';
						}

						// This means that a previous evaluation of this user on any other programme can be copied to this one
						if ($can_copy_evaluations == 1)
						{

							if (EmundusHelperAccess::asAccessAction(1, 'u', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(5, 'c', $this->user->id, $fnum))
							{

								$h_files                 = new EmundusHelperFiles;
								$eval_fnums              = array();
								$this->evaluation_select = array();

								// Gets all evaluations of this student
								$user_evaluations = $m_evaluation->getEvaluationsByStudent($this->sid);
								foreach ($user_evaluations as $ue)
								{
									$eval_fnums[] = $ue->fnum;
								}

								// Evaluation fnums need to be made unique as it is possible to have multiple evals on one fnum by different people
								$eval_fnums = array_unique($eval_fnums);

								// Gets a title for the dropdown menu that is sorted like ['fnum']->['evaluator_id']->title
								foreach ($eval_fnums as $eval_fnum)
								{
									$this->evaluation_select[] = $h_files->getEvaluation('simple', $eval_fnum);
								}
							}
							else
							{
								echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
								exit();
							}
						}

						$this->message = $message;

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 5, 'r', 'COM_EMUNDUS_ACCESS_EVALUATION_READ');
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'decision':
					if (EmundusHelperAccess::asAccessAction(29, 'r', $this->user->id, $fnum))
					{
						if (!class_exists('EmundusModelEvaluation'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/evaluation.php');
						}
						$m_evaluation = new EmundusModelEvaluation();
						$myEval       = $m_evaluation->getDecisionFnum($fnum);

						// get evaluation form ID
						$formid = $m_evaluation->getDecisionFormByProgramme($applicationFile->getCampaign()->getProgram()->getCode());

						$url_form = '';
						if (!empty($formid))
						{
							if (count($myEval) > 0)
							{

								if (EmundusHelperAccess::asAccessAction(29, 'u', $this->user->id, $fnum))
								{
									$url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $this->campaign_id . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}
								elseif (EmundusHelperAccess::asAccessAction(29, 'r', $this->user->id, $fnum))
								{
									$url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $this->campaign_id . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}

							}
							else
							{

								if (EmundusHelperAccess::asAccessAction(29, 'c', $this->user->id, $fnum))
								{
									$url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $this->campaign_id . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}
								elseif (EmundusHelperAccess::asAccessAction(29, 'r', $this->user->id, $fnum))
								{
									$url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $this->campaign_id . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}

							}

							// get evaluation form ID
							$formid_eval = $m_evaluation->getEvaluationFormByProgramme($applicationFile->getCampaign()->getProgram()->getCode());
							if (!empty($formid_eval))
							{
								$this->url_evaluation = Uri::base() . 'index.php?option=com_emundus&view=evaluation&layout=data&format=raw&Itemid=' . $Itemid . '&cfnum=' . $fnum;
							}
						}

						$this->url_form = $url_form;
						$this->formid   = $formid;

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 29, 'r', 'COM_EMUNDUS_DECISION_READ');
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'comment':
					if (EmundusHelperAccess::asAccessAction(10, 'r', $this->user->id, $fnum))
					{

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 10, 'r', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_READ');

						$this->userComments = $m_application->getFileComments($fnum);

						foreach ($this->userComments as $comment)
						{
							$comment->date = EmundusHelperDate::displayDate($comment->date, 'DATE_FORMAT_LC2', 0);
						}
					}
					elseif (EmundusHelperAccess::asAccessAction(10, 'c', $this->user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums))))
					{

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 10, 'c', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_CREATE');

						$this->userComments = $m_application->getFileOwnComments($fnum, $this->user->id);

						foreach ($this->userComments as $comment)
						{
							$comment->date = EmundusHelperDate::displayDate($comment->date, 'DATE_FORMAT_LC2', 0);
						}
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'logs':
					if (EmundusHelperAccess::asAccessAction(37, 'r', $this->_user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums))))
					{
						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 37, 'r', 'COM_EMUNDUS_ACCESS_LOGS_READ');
						$m_logs = new EmundusModelLogs();

						if (!empty($fnum) && in_array($fnum, array_keys($this->_user->fnums)))
						{
							$this->_user->fnum = $fnum;
							$this->app->getSession()->set('emundusUser', $this->_user);
						}

						$actions = [];
						$crud    = ["c", "r", "u", "d"];
						if (in_array($fnum, array_keys($this->_user->fnums)))
						{
							//TODO: Add parameter to menu
							$actions = [1, 4, 13, 28];
							$crud    = ["c", "u", "d"];
						}

						if (is_null($this->_user->fnums))
						{
							$this->_user->fnums = [];
						}

						$this->fileLogs = $m_logs->getActionsOnFnum($fnum, null, $actions, $crud);

						foreach ($this->fileLogs as $log)
						{
							$log->timestamp                  = EmundusHelperDate::displayDate($log->timestamp);
							$log->details                    = $m_logs->setActionDetails($log->action_id, $log->verb, $log->params);
							$log->details['action_name']     = Text::_($log->message);
							$log->details['action_category'] = Text::_($log->details['action_category']);
						}
					}
					else
					{
						echo Text::_("RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'tag':
					if (EmundusHelperAccess::asAccessAction(14, 'r', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(14, 'c', $this->user->id, $fnum))
					{

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 14, 'r', 'COM_EMUNDUS_ACCESS_TAGS_READ');

						if (!class_exists('EmundusModelFiles'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/files.php');
						}
						$m_files    = new EmundusModelFiles();
						$this->tags = $m_files->getTagsByFnum(array($fnum));
						if (!EmundusHelperAccess::asAccessAction(14, 'r', $this->user->id, $fnum) && EmundusHelperAccess::asAccessAction(14, 'c', $this->user->id, $fnum))
						{
							foreach ($this->tags as $key => $tag)
							{
								if ($tag['user_id'] != $this->user->id)
								{
									unset($this->tags[$key]);
								}
							}

							$this->tags = array_values($this->tags);
						}
						$this->groupedTags = [];

						$alltags                    = $m_files->getAllTags();
						$this->displayTagCategories = ComponentHelper::getParams('com_emundus')->get('com_emundus_show_tags_category', 1);

						if ($this->displayTagCategories == 1)
						{
							foreach ($alltags as $tag)
							{
								$this->groupedTags[$tag["category"]][] = ["id" => $tag["id"], "label" => $tag["label"]];
							}
						}
						else
						{
							$this->groupedTags = $alltags;
						}
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'form':
					if (EmundusHelperAccess::asAccessAction(1, 'r', $this->user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums))))
					{
						if (!class_exists('EmundusModelFiles'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/files.php');
						}
						$m_files   = new EmundusModelFiles();
						$fnumInfos = $m_files->getFnumInfos($fnum);

						$this->context             = $jinput->getString('context', 'default');
						$emundus_config            = ComponentHelper::getParams('com_emundus');
						$see_only_filled_workflows = $emundus_config->get('see_only_filled_workflows', 0);
						$this->header              = $jinput->getString('header', 1);

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 1, 'r', 'COM_EMUNDUS_ACCESS_FORM_READ');

						if (!class_exists('EmundusModelProfile'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/profile.php');
						}
						$m_profile = new EmundusModelProfile();
						if (!class_exists('EmundusModelCampaign'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/campaign.php');
						}
						$m_campaign = new EmundusModelCampaign();
						if (!class_exists('EmundusModelUsers'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/users.php');
						}
						$m_users   = new EmundusModelUsers();
						$applicant = $m_users->getUserById($this->sid);
						if (!empty($applicant[0]) && !isset($applicant[0]->profile_picture) || empty($applicant[0]->profile_picture))
						{
							$applicant[0]->profile_picture = $m_users->getIdentityPhoto($fnum, $this->sid);
						}

						/* detect user_id from fnum */
						$pid = (!empty($fnumInfos['profile_id_form'])) ? $fnumInfos['profile_id_form'] : $fnumInfos['profile_id'];

						/* get all campaigns by user */
						$campaignsRaw = $m_campaign->getCampaignByFnum($fnum);

						/* get all profiles (order by step) by campaign */
						$pidsRaw = $m_profile->getProfilesIDByCampaign([$campaignsRaw->id], 'object');

						$pidsStep = [];
						if (isset($step) && is_numeric($step))
						{
							$pidsStep = $m_profile->getProfileByStep($step);
						}

						$noPhasePids  = array();
						$hasPhasePids = array();
						foreach ($pidsRaw as $pidRaw)
						{
							if (!empty($pidsStep))
							{
								if (!in_array($pidRaw->pid, $pidsStep))
								{
									continue;
								}
							}

							if ($see_only_filled_workflows)
							{
								if (!$m_application->isFormFilled($pidRaw->pid, $fnum))
								{
									continue;
								}
							}

							if ($pidRaw->pid === $pid)
							{
								$this->defaultpid = $pidRaw;
							}

							if ($pidRaw->phase === null)
							{
								if ($pidRaw->pid !== $pid)
								{
									$noPhasePids['no_step']['lbl']    = Text::_('COM_EMUNDUS_VIEW_FORM_OTHER_PROFILES');
									$noPhasePids['no_step']['data'][] = $pidRaw;
								}
							}
							else
							{
								$hasPhasePids[] = $pidRaw;
								if (empty($this->defaultpid))
								{
									$this->defaultpid = $pidRaw;
								}
							}
						}

						$profiles_by_phase = array();

						/* group profiles by phase */
						foreach ($hasPhasePids as $ppid)
						{
							$profiles_by_phase['step_' . $ppid->phase]['lbl']    = $ppid->label;
							$profiles_by_phase['step_' . $ppid->phase]['data'][] = $ppid;
						}

						$this->pids          = array_merge($profiles_by_phase, $noPhasePids);
						$this->formsProgress = $m_application->getFormsProgress($fnum);
						$this->forms         = $m_application->getForms($this->sid, $fnum, $this->defaultpid->pid);
						$this->applicant     = $applicant[0];
						$this->shortReference        = $applicationFile->getShortReference();

						$internalReferenceService = new InternalReferenceService(
							new DateProvider(),
							new ApplicationFileRepository()
						);
						$customReferenceFormatEntity = $internalReferenceService->getCustomReferenceFormatEntity();

						$this->showReference = $customReferenceFormatEntity->isShowInFiles();
						if ($this->showReference)
						{
							$internalReferenceRepository = new InternalReferenceRepository();
							$this->reference             = $internalReferenceRepository->getActiveReference($applicationFile->getId());
						}

					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'share':
					$actionRepository       = new ActionRepository();
					$accessFileGroupsAction = $actionRepository->getByName('access_file');
					$accessFileUsersAction  = $actionRepository->getByName('access_file_users');

					if (EmundusHelperAccess::asAccessAction($accessFileGroupsAction->getId(), CrudEnum::READ->value, $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction($accessFileUsersAction->getId(), CrudEnum::READ->value, $this->user->id, $fnum))
					{
						$this->access          = $m_application->getAccessFnum($fnum);
						$this->defaultActions  = $m_application->getActions();
						$this->canUpdateAccess = EmundusHelperAccess::asAccessAction($accessFileGroupsAction->getId(), 'u', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction($accessFileUsersAction->getId(), 'u', $this->user->id, $fnum);
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'mail':
					// This view gets a recap of all the emails sent to the User by the platform, requires applicant_email read rights.
					if (EmundusHelperAccess::asAccessAction(9, 'r', $this->user->id, $fnum))
					{

						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 9, 'r', 'COM_EMUNDUS_ACCESS_MAIL_APPLICANT_READ');

						if (!class_exists('EmundusModelEmails'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/emails.php');
						}
						$m_emails       = new EmundusModelEmails();
						$this->messages = $m_emails->get_messages_to_from_user($this->sid);

					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;
				case 'sms':
					require_once(JPATH_SITE . '/components/com_emundus/models/sms.php');
					$m_sms = new EmundusModelSms();

					if (EmundusHelperAccess::asAccessAction($m_sms->getSmsActionId(), 'r', $this->user->id, $fnum))
					{
						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, $m_sms->getSmsActionId(), 'r', 'COM_EMUNDUS_ACCESS_SMS_APPLICANT_READ');
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}
					break;

				case 'cart':
					$payment_repository = new PaymentRepository();

					if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'r', $this->user->id, $fnum))
					{
						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, $payment_repository->getActionId(), 'r', 'COM_EMUNDUS_ACCESS_CART_APPLICANT_READ');
					}
					else
					{
						echo Text::_("ACCESS_DENIED");
						exit();
					}
					break;

				case 'admission':
					if (EmundusHelperAccess::asAccessAction(32, 'r', $this->user->id, $fnum))
					{

						if (!class_exists('EmundusModelAdmission'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/admission.php');
						}
						$m_admission = new EmundusModelAdmission();
						if (!class_exists('EmundusModelFiles'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/files.php');
						}
						$m_files = new EmundusModelFiles();

						$myAdmission_form_id = $m_files->getAdmissionFormidByFnum($fnum);
						$admission_form      = $m_admission->getAdmissionFormByProgramme($applicationFile->getCampaign()->getProgram()->getCode());

						if (!empty($admission_form))
						{
							$admission_row_id = $m_admission->getAdmissionId($admission_form->db_table_name, $fnum);
						}

						if (empty($myAdmission_form_id))
						{
							$this->html_form = '<p>' . Text::_('COM_EMUNDUS_NO_USER_ADMISSION_FORM') . '</p>';
						}
						else
						{
							$this->html_form = $m_application->getFormByFabrikFormID($myAdmission_form_id, $this->student->id, $fnum);
						}

						$this->url_form = '';
						if (!empty($admission_form->form_id))
						{
							if (EmundusHelperAccess::asAccessAction(32, 'u', $this->user->id, $fnum))
							{
								$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $admission_form->form_id . '&rowid=' . $admission_row_id . '&' . $admission_form->db_table_name . '___student_id[value]=' . $this->student->id . '&' . $admission_form->db_table_name . '___campaign_id[value]=' . $this->campaign_id . '&' . $admission_form->db_table_name . '___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
							}
							elseif (EmundusHelperAccess::asAccessAction(32, 'r', $this->user->id, $fnum))
							{
								$this->url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $admission_form->form_id . '&rowid=' . $admission_row_id . '&' . $admission_form->db_table_name . '___student_id[value]=' . $this->student->id . '&' . $admission_form->db_table_name . '___campaign_id[value]=' . $this->campaign_id . '&' . $admission_form->db_table_name . '___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
							}
							elseif (EmundusHelperAccess::asAccessAction(32, 'c', $this->user->id, $fnum))
							{
								$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $admission_form->form_id . '&rowid=&' . $admission_form->db_table_name . '___student_id[value]=' . $this->student->id . '&' . $admission_form->db_table_name . '___campaign_id[value]=' . $this->campaign_id . '&' . $admission_form->db_table_name . '___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
							}
						}

						$this->form_id = $admission_form->form_id;

						// TRACK THE LOGS
						EmundusModelLogs::log($this->user->id, $this->sid, $fnum, 32, 'r', 'COM_EMUNDUS_ADMISSION_READ');

					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'interview':
					if (EmundusHelperAccess::asAccessAction(34, 'r', $this->user->id, $fnum))
					{
						$multi_eval = $params->get('multi_eval', 0);

						if (!class_exists('EmundusModelInterview'))
						{
							require_once(JPATH_BASE . '/components/com_emundus/models/interview.php');
						}
						$m_interview = new EmundusModelInterview();
						$myEval      = $m_interview->getEvaluationsFnumUser($fnum, $this->user->id);
						$evaluations = $m_interview->getEvaluationsByFnum($fnum);

						// get evaluation form ID
						$formid = $m_interview->getInterviewFormByProgramme($applicationFile->getCampaign()->getProgram()->getCode());


						if (!empty($formid))
						{

							if (count($myEval) > 0)
							{

								if (EmundusHelperAccess::asAccessAction(34, 'u', $this->user->id, $fnum))
								{
									$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}
								elseif (EmundusHelperAccess::asAccessAction(34, 'r', $this->user->id, $fnum))
								{
									$this->url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_evaluations___student_id[value]=' . $this->student->id . '&jos_emundus_evaluations___campaign_id[value]=' . $this->campaign_id . '&jos_emundus_evaluations___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}

							}
							else
							{

								if (EmundusHelperAccess::asAccessAction(34, 'c', $this->user->id, $fnum))
								{

									if ($multi_eval == 0 && count($evaluations) > 0 && EmundusHelperAccess::asAccessAction(34, 'u', $this->user->id, $fnum))
									{
										$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=' . $evaluations[0]->id . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
									}
									else
									{
										$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=&jos_emundus_evaluations___student_id[value]=' . $this->student->id . '&jos_emundus_evaluations___campaign_id[value]=' . $this->campaign_id . '&jos_emundus_evaluations___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
									}

								}
								elseif (EmundusHelperAccess::asAccessAction(34, 'r', $this->user->id, $fnum))
								{
									$this->url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $evaluations[0]->id . '&jos_emundus_evaluations___student_id[value]=' . $this->student->id . '&jos_emundus_evaluations___campaign_id[value]=' . $this->campaign_id . '&jos_emundus_evaluations___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}
							}

							$this->url_evaluation = Uri::base() . 'index.php?option=com_emundus&view=evaluation&layout=data&format=raw&Itemid=' . $Itemid . '&cfnum=' . $fnum;

						}
						else
						{
							$this->url_evaluation = '';
							$this->url_form       = '';
						}
					}
					else
					{
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;
				case 'collaborate':
					$this->collaborators = $m_application->getSharedFileUsers($ccid, $fnum);
			}

			parent::display($tpl);

		}
		else
		{
			echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
		}
	}
}
