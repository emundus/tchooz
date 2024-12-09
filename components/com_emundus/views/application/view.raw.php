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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewApplication extends HtmlView
{
	private $app;
	private $user;
	protected $euser;

	protected $student;
	protected $ccid;
	protected $fnum;
	protected $synthesis;
	protected $assoc_files;
	protected $columns;
	protected $userAttachments;
	protected $attachmentsProgress;
	protected $nameCategory;
	protected $student_id;
	protected $expert_document_id;
	protected $campaign_id;
	protected $evaluation_select;
	protected $message;
	protected $messages;
	protected $url_form;
	protected $formid;
	protected $fileLogs;
	protected $tags;
	protected $groupedTags;
	protected $pids;
	protected $defaultpid;
	protected $formsProgress;
	protected $forms;
	protected $applicant;
	protected $access;
	protected $defaultActions;
	protected $canUpdateAccess;
	protected $html_form;
	protected $_user;
	protected $sid;
	protected $collaborators;
	protected $is_applicant;

	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'evaluation.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'admission.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'interview.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'filters.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'list.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');

		$this->app = Factory::getApplication();
		$session    = $this->app->getSession();
		$this->user = $this->app->getIdentity();

		$this->euser = $session->get('emundusUser');
		$this->_user = $session->get('emundusUser');
		if(empty($this->euser->fnums)) {
			$this->euser->fnums = array();
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

		$m_profiles = new EmundusModelProfile();
		$fnumInfos  = $m_profiles->getFnumDetails($fnum);
		$this->ccid = $fnumInfos['ccid'];
		$this->fnum = $fnum;
		$this->student = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(intval($fnumInfos['applicant_id']));
		$this->sid   = $fnumInfos['applicant_id'];

		$m_application = $this->getModel('Application');

		$expire = time() + 60 * 60 * 24 * 30;
		setcookie("application_itemid", $jinput->getString('id', 0), $expire);

		if ((EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)
				&& EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
			|| (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums)))
		) {

			switch ($layout) {
				case 'synthesis':
					if(EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
					{
						$this->synthesis = new stdClass();
						$program         = $m_application->getProgramSynthesis($fnumInfos['campaign_id']);

						if (!empty($program->synthesis))
						{
							$campaignInfo = $m_application->getUserCampaigns($fnumInfos['applicant_id'], $fnumInfos['campaign_id']);

							$m_email = new EmundusModelEmails();
							$tag     = array(
								'FNUM'                 => $fnum,
								'CAMPAIGN_NAME'        => $fnumInfos['label'],
								'CAMPAIGN_LABEL'       => $fnumInfos['label'],
								'APPLICATION_STATUS'   => $fnumInfos['value'],
								'APPLICATION_TAGS'     => $fnum,
								'APPLICATION_PROGRESS' => $fnumInfos['form_progress'],
								'ATTACHMENT_PROGRESS'  => $fnumInfos['attachment_progress']
							);

							$tags = $m_email->setTags(intval($fnumInfos['applicant_id']), $tag, $fnum, '', $program->synthesis);

							$this->synthesis->program = $program;
							$this->synthesis->camp    = $campaignInfo;
							$this->synthesis->fnum    = $fnum;
							$this->synthesis->block   = preg_replace($tags['patterns'], $tags['replacements'], $program->synthesis);
							$this->synthesis->block   = $m_email->setTagsFabrik($this->synthesis->block, array($fnum));
						}
					}
					break;

				case 'assoc_files':
					if(EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
					{
						$show_related_files = $params->get('show_related_files', 0);

						if ($show_related_files || EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asManagerAccessLevel($this->user->id))
						{
							$campaignInfo = $m_application->getUserCampaigns($fnumInfos['applicant_id'], null, false);

							$published_campaigns   = array_filter($campaignInfo, function ($campaign) {
								return $campaign->published == 1;
							});
							$unpublished_campaigns = array_filter($campaignInfo, function ($campaign) {
								return $campaign->published != 1;
							});

							foreach ($campaignInfo as $key => $campaign)
							{
								if (!EmundusHelperAccess::isUserAllowedToAccessFnum($this->_user->id, $campaign->fnum))
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
					if (EmundusHelperAccess::asAccessAction(4, 'r', $this->_user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums)))) {
						EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 4, 'r', 'COM_EMUNDUS_ACCESS_ATTACHMENT_READ');
						$this->expert_document_id = $params->get('expert_document_id', '36');

						$search = $jinput->getString('search');

						$m_files = new EmundusModelFiles;

						$this->userAttachments     = $m_application->getUserAttachmentsByFnum($fnum, $search, $fnumInfos['profile_id'], (bool)$this->_user->applicant);
						$this->attachmentsProgress = $m_application->getAttachmentsProgress($fnum);
						$this->nameCategory        = $m_files->getAttachmentCategories();
						$this->student_id          = $fnumInfos['applicant_id'];
						$this->is_applicant = $this->_user->applicant;
						$this->columns = ['check','name', 'date', 'desc', 'category', 'status', 'user', 'modified_by', 'modified', 'permissions', 'sync'];
						if($this->_user->applicant) {
							//TODO: Add menu parameters
							$this->columns = ['name', 'date', 'desc', 'status', 'modified'];
						}
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}
					break;

				case 'assessment':
					if (EmundusHelperAccess::asAccessAction(1, 'r', $this->user->id, $fnum)) {
						$this->campaign_id = $fnumInfos['campaign_id'];
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}
					break;

				case 'evaluation':
					if (EmundusHelperAccess::asAccessAction(5, 'c', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(5, 'r', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(5, 'u', $this->user->id, $fnum)) {
						$params               = JComponentHelper::getParams('com_emundus');
						$can_copy_evaluations = $params->get('can_copy_evaluations', 0);
						$multi_eval           = $params->get('multi_eval', 0);

						$m_evaluation = new EmundusModelEvaluation();

						// get evaluation form ID
						$formid = $m_evaluation->getEvaluationFormByProgramme($fnumInfos['training']);

						$message = 'COM_EMUNDUS_EVALUATIONS_NO_EVALUATION_FORM_SET';
						if (!empty($formid)) {
							$evaluation           = $m_evaluation->getEvaluationUrl($fnum, $formid);
							$message              = $evaluation['message'];
							$this->url_form       = $evaluation['url'];
							$this->url_evaluation = JURI::base() . 'index.php?option=com_emundus&view=evaluation&layout=data&format=raw&Itemid=' . $Itemid . '&cfnum=' . $fnum;
						}
						else {
							$this->url_evaluation = '';
							$this->url_form       = '';
						}

						// This means that a previous evaluation of this user on any other programme can be copied to this one
						if ($can_copy_evaluations == 1) {

							if (EmundusHelperAccess::asAccessAction(1, 'u', $this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(5, 'c', $this->user->id, $fnum)) {

								$m_evaluation            = new EmundusModelEvaluation;
								$h_files                 = new EmundusHelperFiles;
								$eval_fnums              = array();
								$this->evaluation_select = array();

								// Gets all evaluations of this student
								$user_evaluations = $m_evaluation->getEvaluationsByStudent($this->student->id);

								foreach ($user_evaluations as $ue) {
									$eval_fnums[] = $ue->fnum;
								}

								// Evaluation fnums need to be made unique as it is possible to have multiple evals on one fnum by different people
								$eval_fnums = array_unique($eval_fnums);

								// Gets a title for the dropdown menu that is sorted like ['fnum']->['evaluator_id']->title
								foreach ($eval_fnums as $eval_fnum) {
									$this->evaluation_select[] = $h_files->getEvaluation('simple', $eval_fnum);
								}
							}
							else {
								echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
								exit();
							}
						}

						$this->campaign_id = $fnumInfos['campaign_id'];
						$this->message     = $message;

						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
						$mFile        = new EmundusModelFiles();
						$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
						EmundusModelLogs::log($this->user->id, $applicant_id, $fnum, 5, 'r', 'COM_EMUNDUS_ACCESS_EVALUATION_READ');
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'decision':
					if (EmundusHelperAccess::asAccessAction(29, 'r', $this->user->id, $fnum)) {
						$m_evaluation = new EmundusModelEvaluation();
						$myEval       = $m_evaluation->getDecisionFnum($fnum);

						// get evaluation form ID
						$formid = $m_evaluation->getDecisionFormByProgramme($fnumInfos['training']);

						$url_form = '';
						if (!empty($formid)) {
							if (count($myEval) > 0) {

								if (EmundusHelperAccess::asAccessAction(29, 'u', $this->user->id, $fnum))
									$url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								elseif (EmundusHelperAccess::asAccessAction(29, 'r', $this->user->id, $fnum))
									$url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';

							}
							else {

								if (EmundusHelperAccess::asAccessAction(29, 'c', $this->user->id, $fnum))
									$url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								elseif (EmundusHelperAccess::asAccessAction(29, 'r', $this->user->id, $fnum))
									$url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_final_grade___student_id[value]=' . $this->student->id . '&jos_emundus_final_grade___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&jos_emundus_final_grade___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';

							}

							// get evaluation form ID
							$formid_eval = $m_evaluation->getEvaluationFormByProgramme($fnumInfos['training']);
							if (!empty($formid_eval)) {
								$this->url_evaluation = JURI::base() . 'index.php?option=com_emundus&view=evaluation&layout=data&format=raw&Itemid=' . $Itemid . '&cfnum=' . $fnum;
							}
						}

						$this->campaign_id = $fnumInfos['campaign_id'];
						$this->url_form    = $url_form;
						$this->formid      = $formid;

						# ADD 29R HERE
						# get FNUM INFO
						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
						$mFile        = new EmundusModelFiles();
						$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

						// TRACK THE LOGS
						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
						EmundusModelLogs::log($this->user->id, $applicant_id, $fnum, 29, 'r', 'COM_EMUNDUS_DECISION_READ');
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'comment':
					if (EmundusHelperAccess::asAccessAction(10, 'r', $this->user->id, $fnum)) {

						EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 10, 'r', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_READ');

						$this->userComments = $m_application->getFileComments($fnum);

						foreach ($this->userComments as $comment) {
							$comment->date = EmundusHelperDate::displayDate($comment->date, 'DATE_FORMAT_LC2',0);
						}
					}
					elseif (EmundusHelperAccess::asAccessAction(10, 'c', $this->user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums)))) {

						EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 10, 'c', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_CREATE');

						$this->userComments = $m_application->getFileOwnComments($fnum, $this->user->id);

						foreach ($this->userComments as $comment) {
							$comment->date = EmundusHelperDate::displayDate($comment->date, 'DATE_FORMAT_LC2',0);
						}
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'logs':
					if (EmundusHelperAccess::asAccessAction(37, 'r', $this->_user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums)))) {
						EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 37, 'r', 'COM_EMUNDUS_ACCESS_LOGS_READ');
						$m_logs = new EmundusModelLogs();

						if(!empty($fnum) && in_array($fnum, array_keys($this->_user->fnums))) {
							$this->_user->fnum = $fnum;
							$this->app->getSession()->set('emundusUser', $this->_user);
						}

						$actions = [];
						$crud = ["c","r","u","d"];
						if(in_array($fnum, array_keys($this->_user->fnums))) {
							//TODO: Add parameter to menu
							$actions = [1,4,13,28];
							$crud = ["c","u","d"];
						}

						if (is_null($this->_user->fnums)) {
							$this->_user->fnums = [];
						}

						$this->fileLogs = $m_logs->getActionsOnFnum($fnum, null, $actions, $crud);

						foreach ($this->fileLogs as $log) {
							$log->timestamp = EmundusHelperDate::displayDate($log->timestamp);
							$log->details   = $m_logs->setActionDetails($log->action_id, $log->verb, $log->params);
						}
					}
					else {
						echo Text::_("RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'tag':
					if (EmundusHelperAccess::asAccessAction(14, 'r', $this->user->id, $fnum)) {

						EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 14, 'r', 'COM_EMUNDUS_ACCESS_TAGS_READ');

						$m_files           = new EmundusModelFiles();
						$this->tags        = $m_files->getTagsByFnum(array($fnum));
						$this->groupedTags = [];

						$alltags = $m_files->getAllTags();
						foreach ($alltags as $tag) {
							$this->groupedTags[$tag["category"]][] = ["id" => $tag["id"], "label" => $tag["label"]];
						}
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'form':
					if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum) || (!empty($ccid) && !empty($fnum) && in_array($fnum, array_keys($this->_user->fnums)))) {
						$emundus_config = ComponentHelper::getParams('com_emundus');
						$see_only_filled_workflows = $emundus_config->get('see_only_filled_workflows', 0);
						$this->header = $jinput->getString('header', 1);

						EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 1, 'r', 'COM_EMUNDUS_ACCESS_FORM_READ');

						$m_campaign = new EmundusModelCampaign;
						$m_user     = new EmundusModelUsers;
						$applicant  = $m_user->getUserById($fnumInfos['applicant_id']);
						if (!empty($applicant[0]) && !isset($applicant[0]->profile_picture) || empty($applicant[0]->profile_picture)) {
							$applicant[0]->profile_picture = $m_user->getIdentityPhoto($fnum, $fnumInfos['applicant_id']);
						}

						/* detect user_id from fnum */
						$this->userid = $fnumInfos['applicant_id'];
						$pid          = (!empty($fnumInfos['profile_id_form'])) ? $fnumInfos['profile_id_form'] : $fnumInfos['profile_id'];

						/* get all campaigns by user */
						$campaignsRaw = $m_campaign->getCampaignByFnum($fnum);

						/* get all profiles (order by step) by campaign */
						$pidsRaw = $m_profiles->getProfilesIDByCampaign([$campaignsRaw->id], 'object');

                        $pidsStep = [];
                        if (isset($step) && is_numeric($step)) {
                            $pidsStep = $m_profiles->getProfileByStep($step);
                        }

						$noPhasePids  = array();
						$hasPhasePids = array();
						foreach ($pidsRaw as $pidRaw) {
                            if (!empty($pidsStep)) {
                                if (!in_array($pidRaw->pid, $pidsStep)) {
                                    continue;
                                }
                            }

							if ($see_only_filled_workflows) {
								if (!$m_application->isFormFilled($pidRaw->pid, $fnum)) {
									continue;
								}
							}

							if ($pidRaw->pid === $pid) {
								$this->defaultpid = $pidRaw;
							}

							if ($pidRaw->phase === null) {
								if ($pidRaw->pid !== $pid) {
									$noPhasePids['no_step']['lbl']    = Text::_('COM_EMUNDUS_VIEW_FORM_OTHER_PROFILES');
									$noPhasePids['no_step']['data'][] = $pidRaw;
								}
							}
							else {
								$hasPhasePids[] = $pidRaw;
                                if (empty($this->defaultpid)) {
                                    $this->defaultpid = $pidRaw;
                                }
							}
						}

						$profiles_by_phase = array();

						/* group profiles by phase */
						foreach ($hasPhasePids as $ppid) {
                            $profiles_by_phase['step_' . $ppid->phase]['lbl'] = $ppid->label;
							$profiles_by_phase['step_' . $ppid->phase]['data'][] = $ppid;
						}

						$this->pids          = array_merge($profiles_by_phase, $noPhasePids);
						$this->formsProgress = $m_application->getFormsProgress($fnum);
						$this->forms         = $m_application->getForms(intval($fnumInfos['applicant_id']), $fnum, $this->defaultpid->pid);
						$this->applicant     = $applicant[0];
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'share':
					if (EmundusHelperAccess::asAccessAction(11, 'r', $this->user->id, $fnum)) {
						$this->access          = $m_application->getAccessFnum($fnum);
						$this->defaultActions  = $m_application->getActions();
						$this->canUpdateAccess = EmundusHelperAccess::asAccessAction(11, 'u', $this->user->id, $fnum);
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'mail':
					// This view gets a recap of all the emails sent to the User by the platform, requires applicant_email read rights.
					if (EmundusHelperAccess::asAccessAction(9, 'r', $this->user->id, $fnum)) {

						EmundusModelLogs::log($this->user->id, (int) substr($fnum, -7), $fnum, 9, 'r', 'COM_EMUNDUS_ACCESS_MAIL_APPLICANT_READ');

						$m_emails       = new EmundusModelEmails();
						$this->messages = $m_emails->get_messages_to_from_user(intval($fnumInfos['applicant_id']));

					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'admission':
					if (EmundusHelperAccess::asAccessAction(32, 'r', $this->user->id, $fnum)) {

						$m_admission   = new EmundusModelAdmission();
						$m_application = new EmundusModelApplication();
						$m_files       = new EmundusModelFiles();

						$myAdmission_form_id = $m_files->getAdmissionFormidByFnum($fnum);
						$admission_form      = $m_admission->getAdmissionFormByProgramme($fnumInfos['training']);

						if (!empty($admission_form)) {
							$admission_row_id = $m_admission->getAdmissionId($admission_form->db_table_name, $fnum);
						}

						if (empty($myAdmission_form_id)) {
							$this->html_form = '<p>' . Text::_('COM_EMUNDUS_NO_USER_ADMISSION_FORM') . '</p>';
						}
						else {
							$this->html_form = $m_application->getFormByFabrikFormID($myAdmission_form_id, $this->student->id, $fnum);
						}

						$this->url_form = '';
						if (!empty($admission_form->form_id)) {
							if (EmundusHelperAccess::asAccessAction(32, 'u', $this->user->id, $fnum)) {
								$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $admission_form->form_id . '&rowid=' . $admission_row_id . '&' . $admission_form->db_table_name . '___student_id[value]=' . $this->student->id . '&' . $admission_form->db_table_name . '___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&' . $admission_form->db_table_name . '___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
							}
							elseif (EmundusHelperAccess::asAccessAction(32, 'r', $this->user->id, $fnum)) {
								$this->url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $admission_form->form_id . '&rowid=' . $admission_row_id . '&' . $admission_form->db_table_name . '___student_id[value]=' . $this->student->id . '&' . $admission_form->db_table_name . '___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&' . $admission_form->db_table_name . '___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
							}
							elseif (EmundusHelperAccess::asAccessAction(32, 'c', $this->user->id, $fnum)) {
								$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $admission_form->form_id . '&rowid=&' . $admission_form->db_table_name . '___student_id[value]=' . $this->student->id . '&' . $admission_form->db_table_name . '___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&' . $admission_form->db_table_name . '___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
							}
						}

						$this->campaign_id = $fnumInfos['campaign_id'];
						$this->form_id     = $admission_form->form_id;

						# ADD 32R HERE
						# get FNUM INFO
						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
						$mFile        = new EmundusModelFiles();
						$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

						// TRACK THE LOGS
						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
						EmundusModelLogs::log($this->user->id, $applicant_id, $fnum, 32, 'r', 'COM_EMUNDUS_ADMISSION_READ');

					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;

				case 'interview':
					if (EmundusHelperAccess::asAccessAction(34, 'r', $this->user->id, $fnum)) {

						// No call to EmundusModelLogs::log() because the logging in handled in a Fabrik script on form load.

						$params     = JComponentHelper::getParams('com_emundus');
						$multi_eval = $params->get('multi_eval', 0);

						$m_interview = new EmundusModelInterview();
						$myEval      = $m_interview->getEvaluationsFnumUser($fnum, $this->user->id);
						$evaluations = $m_interview->getEvaluationsByFnum($fnum);

						// get evaluation form ID
						$formid = $m_interview->getInterviewFormByProgramme($fnumInfos['training']);


						if (!empty($formid)) {

							if (count($myEval) > 0) {

								if (EmundusHelperAccess::asAccessAction(34, 'u', $this->user->id, $fnum)) {
									$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}
								elseif (EmundusHelperAccess::asAccessAction(34, 'r', $this->user->id, $fnum)) {
									$this->url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $myEval[0]->id . '&jos_emundus_evaluations___student_id[value]=' . $this->student->id . '&jos_emundus_evaluations___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&jos_emundus_evaluations___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}

							}
							else {

								if (EmundusHelperAccess::asAccessAction(34, 'c', $this->user->id, $fnum)) {

									if ($multi_eval == 0 && count($evaluations) > 0 && EmundusHelperAccess::asAccessAction(34, 'u', $this->user->id, $fnum)) {
										$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=' . $evaluations[0]->id . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
									}
									else {
										$this->url_form = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid . '&rowid=&jos_emundus_evaluations___student_id[value]=' . $this->student->id . '&jos_emundus_evaluations___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&jos_emundus_evaluations___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
									}

								}
								elseif (EmundusHelperAccess::asAccessAction(34, 'r', $this->user->id, $fnum)) {
									$this->url_form = 'index.php?option=com_fabrik&c=form&view=details&formid=' . $formid . '&rowid=' . $evaluations[0]->id . '&jos_emundus_evaluations___student_id[value]=' . $this->student->id . '&jos_emundus_evaluations___campaign_id[value]=' . $fnumInfos['campaign_id'] . '&jos_emundus_evaluations___fnum[value]=' . $fnum . '&student_id=' . $this->student->id . '&tmpl=component&iframe=1';
								}
							}

							if (!empty($formid))
								$this->url_evaluation = JURI::base() . 'index.php?option=com_emundus&view=evaluation&layout=data&format=raw&Itemid=' . $Itemid . '&cfnum=' . $fnum;

						}
						else {
							$this->url_evaluation = '';
							$this->url_form       = '';
						}

						$this->campaign_id = $fnumInfos['campaign_id'];
					}
					else {
						echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
						exit();
					}

					break;
				case 'collaborate':
					$this->collaborators = $m_application->getSharedFileUsers($ccid, $fnum);
			}

			parent::display($tpl);

		}
		else {
			echo Text::_("COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS");
		}
	}
}
