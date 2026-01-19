<?php
/**
 * @package    Joomla
 * @subpackage eMundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');

/**
 * eMundus Component Controller
 *
 * @package    Joomla.emundus
 * @subpackage Components
 */
class EmundusControllerEvaluation extends BaseController
{
	protected $app;

	private $_user;
	private $_db;
	private $_session;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'filters.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'list.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');

		$this->app      = Factory::getApplication();
		$this->_db      = Factory::getDbo();
		$this->_user    = $this->app->getIdentity();
		$this->_session = $this->app->getSession();
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *
	 * @return  DisplayController  This object to support chaining.
	 *
	 * @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @since      1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view'))
		{
			$default = 'files';
			$this->input->set('view', $default);
		}
		parent::display();
	}


	public function applicantEmail()
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		EmundusHelperEmails::sendApplicantEmail();
	}

	public function clear()
	{
		EmundusHelperFiles::clear();
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function setfilters()
	{
		$filterName = $this->input->getString('id', null);
		$elements   = $this->input->getString('elements', null);
		$multi      = $this->input->getString('multi', null);

		EmundusHelperFiles::clearfilter();

		if ($multi == "true")
		{
			$filterval = $this->input->get('val', array(), 'ARRAY');
		}
		else
		{
			$filterval = $this->input->getString('val', null);
		}

		$params = $this->_session->get('filt_params');

		if ($elements == 'false')
		{
			$params[$filterName] = $filterval;
		}
		else
		{
			$vals = (array) json_decode(stripslashes($filterval));
			if (count($vals) > 0)
			{
				foreach ($vals as $val)
				{
					if ($val->adv_fil)
					{
						$params['elements'][$val->name]['value']  = $val->value;
						$params['elements'][$val->name]['select'] = $val->select;
					}
					else
					{
						$params[$val->name] = $val->value;
					}
				}

			}
			else
			{
				$params['elements'][$filterName]['value'] = $filterval;
			}
		}

		$this->_session->set('last-filters-use-advanced', false);
		$this->_session->set('filt_params', $params);
		$this->_session->set('limitstart', 0);
		echo json_encode((object) (array('status' => true)));
		exit();
	}

	public function loadfilters()
	{
		try
		{
			$id                      = $this->input->getInt('id', null);
			$filter                  = EmundusHelperFiles::getEmundusFilters($id);
			$params                  = (array) json_decode($filter->constraints);
			$params['select_filter'] = $id;
			$params                  = json_decode($filter->constraints, true);

			$this->_session->set('select_filter', $id);
			if (isset($params['filter_order']))
			{
				$this->_session->set('filter_order', $params['filter_order']);
				$this->_session->set('filter_order_Dir', $params['filter_order_Dir']);
			}
			$this->_session->set('filt_params', $params['filter']);
			if (!empty($params['col']))
			{
				$this->_session->set('adv_cols', $params['col']);
			}

			echo json_encode((object) (array('status' => true)));
			exit();
		}
		catch (Exception $e)
		{
			throw new Exception;
		}
	}

	public function order()
	{
		$order        = $this->input->getString('filter_order', null);
		$ancientOrder = $this->_session->get('filter_order');
		$params       = $this->_session->get('filt_params');
		$this->_session->set('filter_order', $order);
		$params['filter_order'] = $order;

		if ($order == $ancientOrder)
		{
			if ($this->_session->get('filter_order_Dir') == 'desc')
			{
				$this->_session->set('filter_order_Dir', 'asc');
				$params['filter_order_Dir'] = 'asc';
			}
			else
			{
				$this->_session->set('filter_order_Dir', 'desc');
				$params['filter_order_Dir'] = 'desc';
			}
		}
		else
		{
			$this->_session->set('filter_order_Dir', 'asc');
			$params['filter_order_Dir'] = 'asc';
		}
		$this->_session->set('filt_params', $params);
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function setlimit()
	{
		$limit = $this->input->getInt('limit', null);

		$this->_session->set('limit', $limit);
		$this->_session->set('limitstart', 0);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function savefilters()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->savefilters();
	}

	public function deletefilters()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->deletefilters();
	}

	public function setlimitstart()
	{
		$limistart  = $this->input->getInt('limitstart', null);

		$limit      = intval($this->_session->get('limit'));
		$limitstart = ($limit != 0 ? ($limistart > 1 ? (($limistart - 1) * $limit) : 0) : 0);

		$this->_session->set('limitstart', $limitstart);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function getadvfilters()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->getadvfilters();
	}

	public function addcomment()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->addcomment();
	}

	public function getevsandgroups()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$m_files    = $this->getModel('Files');
			$evalGroups = $m_files->getEvalGroups();
			$actions    = $m_files->getAllActions('1,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18');
			$response   = [
				'status'       => true,
				'code'         => 200,
				'groups'       => $evalGroups['groups'],
				'users'        => $evalGroups['users'],
				'actions'      => $actions,
				'group'        => Text::_('COM_EMUNDUS_GROUPS_GROUP_EVAL'),
				'eval'         => Text::_('COM_EMUNDUS_EVALUATION_EVALUATORS'),
				'select_group' => Text::_('COM_EMUNDUS_GROUPS_PLEASE_SELECT_GROUP'),
				'select_eval'  => Text::_('COM_EMUNDUS_GROUPS_PLEASE_SELECT_ASSESSOR'),
				'check'        => Text::_('COM_EMUNDUS_ACCESS_CHECK_ACL'),
				'create'       => Text::_('COM_EMUNDUS_ACCESS_CREATE'),
				'retrieve'     => Text::_('COM_EMUNDUS_ACCESS_RETRIEVE'),
				'update'       => Text::_('COM_EMUNDUS_ACCESS_UPDATE'),
				'delete'       => Text::_('COM_EMUNDUS_ACTIONS_DELETE'),
			];
		}

		echo json_encode((object) $response);
		exit;
	}

	public function gettags()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->gettags();
	}

	/**
	 * Add a tag to an application
	 */
	public function tagfile()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('BAD_REQUEST')];

		$fnums = $this->input->getString('fnums', null);
		$tag   = (array) $this->input->get('tag', []);

		if (!empty($fnums) && !empty($tag)) {
			$m_files = $this->getModel('Files');
			$m_evaluation = $this->getModel('Evaluation');
			$fnums   = $fnums === 'all' ? $m_evaluation->getAllFnums($this->_user->id) : (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);

			if (!empty($fnums)) {
				$validFnums = [];
				foreach ($fnums as $fnum) {
					if ($fnum != 'em-check-all' && EmundusHelperAccess::asAccessAction(14, 'c', $this->_user->id, $fnum)) {
						$validFnums[] = $fnum;
					}
				}
				unset($fnums);
				$response['status'] = $m_files->tagFile($validFnums, $tag);

				if ($response['status']) {
					$response['code']   = 200;
					$response['msg']    = Text::_('COM_EMUNDUS_TAGS_SUCCESS');
					$response['tagged'] = $validFnums;
				}
				else {
					$response['code'] = 500;
					$response['msg']  = Text::_('FAIL');
				}
			}
		}

		echo json_encode((object) ($response));
		exit;
	}


	public function deletetags()
	{

		$fnums = $this->input->getString('fnums', null);
		$tags  = $this->input->getVar('tag', null);

		$fnums = ($fnums == 'all') ? 'all' : (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);

		$m_application = $this->getModel('Application');
		$m_evaluation = $this->getModel('Evaluation');
		$m_files       = $this->getModel('Files');

		if ($fnums == "all") {
			$fnums = $m_evaluation->getAllFnums($this->_user->id);
		}

		PluginHelper::importPlugin('emundus');
		$this->app->triggerEvent('onCallEventHandler', ['onBeforeTagRemove', ['fnums' => $fnums, 'tags' => $tags]]);

		foreach ($fnums as $fnum) {
			if ($fnum != 'em-check-all') {
				foreach ($tags as $tag) {
					$hastags = $m_files->getTagsByIdFnumUser($tag, $fnum, $this->_user->id);
					if ($hastags) {
						$m_application->deleteTag($tag, $fnum);
					}
					else {
						if (EmundusHelperAccess::asAccessAction(14, 'd', $this->_user->id, $fnum)) {
							$m_application->deleteTag($tag, $fnum);
						}
					}
				}
			}
		}

		$this->app->triggerEvent('onCallEventHandler', ['onAfterTagRemove', ['fnums' => $fnums, 'tags' => $tags]]);

		unset($fnums);
		unset($tags);

		echo json_encode((object) (array('status' => true, 'msg' => Text::_('COM_EMUNDUS_TAGS_DELETE_SUCCESS'))));
		exit;
	}

	public function share()
	{

		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->share();
	}

	public function getstate()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->getstate();
	}

	public function updatestate()
	{
		$fnums = $this->input->getString('fnums', null);
		$state = $this->input->getInt('state', null);
		$this->app->getSession()->set('last_status_selected', $state);

		$m_evaluation    = $this->getModel('Evaluation');
		$m_files = $this->getModel('Files');

		if ($fnums == "all") {
			$fnums = $m_evaluation->getAllFnums((int)$this->_user->id);
		}

		if (!is_array($fnums)) {
			$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);
		}

		if (count($fnums) == 0 || !is_array($fnums)) {
			$res = false;
			$msg = Text::_('STATE_ERROR');

			echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
			exit;
		}

		$validFnums = array();

		foreach ($fnums as $fnum) {
			if (EmundusHelperAccess::asAccessAction(13, 'u', $this->_user->id, $fnum)) {
				$validFnums[] = $fnum;
			}
		}

		$res = $m_files->updateState($validFnums, $state, $this->_user->id);
		$msg = '';

		if (is_array($res)) {
			$msg = isset($res['msg']) ? $res['msg'] : '';
			$res = isset($res['status']) ? $res['status'] : true;
		}

		if ($res !== false) {
			$msg .= Text::_('COM_EMUNDUS_APPLICATION_STATE_SUCCESS');
		}
		else {
			$msg = empty($msg) ? Text::_('STATE_ERROR') : $msg;
		}

		echo json_encode(array('status' => $res, 'msg' => $msg));
		exit;
	}

	public function updatepublish()
	{
		$publish = $this->input->getInt('publish', null);
		$m_files = $this->getModel('Files');
		$m_evaluation = $this->getModel('Evaluation');

		$fnums_post  = $this->input->getString('fnums', null);
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		if ($fnums_array == 'all') {
			$fnums = $m_evaluation->getAllFnums($this->_user->id);
		}
		else {
			$fnums = array();
			foreach ($fnums_array as $value) {
				$fnums[] = $value;
			}
		}

		$validFnums = [];
		foreach ($fnums as $fnum) {
			if (is_numeric($fnum) && EmundusHelperAccess::asAccessAction(13, 'u', $this->_user->id, $fnum))
				$validFnums[] = $fnum;
		}
		$res = $m_files->updatePublish($validFnums, $publish);
		if ($res !== false) {
			$msg = Text::_('COM_EMUNDUS_APPLICATION_PUBLISHED_STATE_SUCCESS');
		} else {
			$msg = Text::_('STATE_ERROR');
		}

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}

	public function unlinkevaluators()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->unlinkevaluators();
	}

	public function getfnuminfos()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files  = new EmundusControllerFiles();
		$c_files->getfnuminfos();
	}

	public function deletefile()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->deletefile();
	}

	public function getformelem()
	{
		//Filters

		$code = $this->input->getVar('code', null);
		$code = explode(',', $code);


		$m_evaluation    = $this->getModel('Evaluation');
		$defaultElements = $m_evaluation->getEvaluationElementsName(0, 1, $code);
		if (!empty($defaultElements))
		{
			foreach ($defaultElements as $kde => $de)
			{
				if ($de->element_name == 'id' || $de->element_name == 'fnum' || $de->element_name == 'student_id')
				{
					unset($defaultElements[$kde]);
				}
			}
		}
		$elements = EmundusHelperFiles::getElements();
		$res      = array('status' => true, 'elts' => $elements, 'defaults' => $defaultElements);
		echo json_encode((object) $res);
		exit;
	}

	/**
	 * Function called by an Ajax script, copies a row in the evaluations table
	 *
	 * @since version 1.0.0
	 */
	public function getevalcopy()
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			die (Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$h_files = new EmundusHelperFiles;
		$m_files = $this->getModel('files');

		$fnum      = $this->input->getString('fnum', null);
		$evaluator = $this->input->get('evaluator', null);

		if (!empty($fnum) && !empty($evaluator))
		{
			$evaluation = $h_files->getEvaluation('html', $fnum);
			$evaluation = $evaluation[$fnum][$evaluator];

			$form   = $m_files->getEvalByFnumAndEvaluator($fnum, $evaluator);
			$formID = $form[0]['id'];

			$result = ['status' => true, 'evaluation' => $evaluation, 'formID' => $formID];

		}
		else $result = ['status' => false];

		echo json_encode((object) $result);
		exit();
	}

	// Function called by an Ajax script, copies a row in the evaluations table
	public function copyeval()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			die (Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$fromID = $this->input->getString('from', null);
		$toID   = $this->input->getString('to', null);
		$fnum   = $this->input->getString('fnum', null);
		$studID = $this->input->get('student', null);

		if (!empty($fromID) && !empty($fnum))
		{

			$m_evaluation = $this->getModel('Evaluation');
			$res          = $m_evaluation->copyEvaluation($fromID, $toID, $fnum, $studID, $this->_user->id);

			$result = ['status' => $res];

		}
		else $result = ['status' => false];

		echo json_encode((object) $result);
		exit();
	}

	function pdf()
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');

		$fnum       = $this->input->getString('fnum', null);
		$student_id = $this->input->getInt('student_id', $this->input->getInt('user', $this->_user->id));

		if (!EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $fnum))
		{
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$m_profile  = $this->getModel('Profile');
		$m_campaign = $this->getModel('Campaign');

		if (!empty($fnum))
		{
			$candidature = $m_profile->getFnumDetails($fnum);
			$campaign    = $m_campaign->getCampaignByID($candidature['campaign_id']);
			$name        = 'evaluation-' . $fnum . '.pdf';
			$tmpName     = JPATH_SITE . DS . 'tmp' . DS . $name;
		}

		$file = JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf_evaluation' . $campaign['training'] . '.php';

		if (!file_exists($file))
		{
			$file = JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf_evaluation.php';
		}

		if (!file_exists(EMUNDUS_PATH_ABS . $student_id))
		{
			mkdir(EMUNDUS_PATH_ABS . $student_id);
			chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
		}

		require_once($file);
		pdf_evaluation($student_id, $fnum, true, $name);

		exit();
	}

	function deleteEvaluation() {
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];
		$fnum = $this->input->getString('fnum', '');
		$step_id = $this->input->getInt('step_id', 0);
		$row_id = $this->input->getInt('row_id', 0);

		require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
		$m_workflow = new EmundusModelWorkflow();
		$step_data = $m_workflow->getStepData($step_id);

		if (EmundusHelperAccess::asAccessAction($step_data->action_id, 'd', $this->_user->id, $fnum)) {
			$response = ['status' => false, 'code' => 500, 'msg' => Text::_('COM_EMUNDUS_EVALUATION_DELETE_ERROR')];
			$m_evaluation = $this->getModel('Evaluation');
			$deleted = $m_evaluation->deleteEvaluation($fnum, $step_data, $row_id, $this->_user->id);

			if ($deleted) {
				$response = ['status' => true, 'code' => 200, 'msg' => Text::_('COM_EMUNDUS_EVALUATION_DELETE_SUCCESS')];
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	function pdf_decision()
	{
		$fnum       = $this->input->getString('fnum', null);
		$student_id = $this->input->getString('student_id', null);

		if (!EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $fnum))
		{
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		$m_profile  = $this->getModel('Profile');
		$m_campaign = $this->getModel('Campaign');

		if (!empty($fnum))
		{
			$candidature = $m_profile->getFnumDetails($fnum);
			$campaign    = $m_campaign->getCampaignByID($candidature['campaign_id']);
		}

		$file = JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf_decision_' . $campaign['training'] . '.php';

		if (!file_exists($file))
		{
			$file = JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf_decision.php';
		}

		if (!file_exists(EMUNDUS_PATH_ABS . $student_id))
		{
			mkdir(EMUNDUS_PATH_ABS . $student_id);
			chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
		}

		require_once($file);
		pdf_decision(!empty($student_id) ? $student_id : $this->_user->id, $fnum);

		exit();
	}

	public function return_bytes($val)
	{
		$val  = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		switch ($last)
		{
			// Le modifieur 'G' est disponible depuis PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	public function sortArrayByArray($array, $orderArray)
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		return $c_files->sortArrayByArray($array, $orderArray);
	}

	public function create_file_csv()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->create_file_csv();
	}

	public function getfnums_csv()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->getfnums_csv();
	}

	public function getcolumn($elts)
	{
		return (array) json_decode(stripcslashes($elts));
	}

	public function generate_array()
	{

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$m_files = $this->getModel('Files');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . '/models/application.php');
		$m_application = $this->getModel('Application');


		$fnums = $this->_session->get('fnums_export');


		$file      = $this->input->getVar('file', null, 'STRING');
		$totalfile = $this->input->getVar('totalfile', null);
		$start     = $this->input->getInt('start', 0);
		$limit     = $this->input->getInt('limit', 0);
		$nbcol     = $this->input->getVar('nbcol', 0);
		$elts      = $this->input->getString('elts', null);
		$objs      = $this->input->getString('objs', null);

		$col = $this->getcolumn($elts);

		$colsup = $this->getcolumn($objs);
		$colOpt = array();
		if (!$csv = fopen(JPATH_SITE . DS . 'tmp' . DS . $file, 'a'))
		{
			$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_OPEN_FILE') . ' : ' . $file);
			echo json_encode((object) $result);
			exit();
		}

		$elements = @EmundusHelperFiles::getElementsName(implode(',', $col));

		// re-order elements
		$ordered_elements = array();
		foreach ($col as $c)
		{
			$ordered_elements[$c] = $elements[$c];
		}
		$fnumsArray = $m_files->getFnumArray($fnums, $ordered_elements, 0, $start, $limit, 0);

		// On met a jour la liste des fnums traités
		$fnums = array();
		foreach ($fnumsArray as $fnum)
		{
			$fnums[] = $fnum['fnum'];
		}
		foreach ($colsup as $col)
		{
			$col = explode('.', $col);
			switch ($col[0])
			{
				case "photo":
					$photos = $m_files->getPhotos($fnums);
					if (count($photos) > 0)
					{
						$pictures = array();
						foreach ($photos as $photo)
						{

							$folder = JURI::base() . EMUNDUS_PATH_REL . $photo['user_id'];

							$link                     = '=HYPERLINK("' . $folder . '/tn_' . $photo['filename'] . '","' . $photo['filename'] . '")';
							$pictures[$photo['fnum']] = $link;
						}
						$colOpt['PHOTO'] = $pictures;
					}
					else
					{
						$colOpt['PHOTO'] = array();
					}
					break;
				case "forms":
					$colOpt['forms'] = $m_application->getFormsProgress($fnums);
					break;
				case "attachment":
					$colOpt['attachment'] = $m_application->getAttachmentsProgress($fnums);
					break;
				case "assessment":
					$colOpt['assessment'] = @EmundusHelperFiles::getEvaluation('text', $fnums);
					break;
				case "comment":
					$colOpt['comment'] = $m_files->getCommentsByFnum($fnums);
					break;
				case 'evaluators':
					$colOpt['evaluators'] = @EmundusHelperFiles::createEvaluatorList($col[1], $m_files);
					break;
			}
		}
		$status      = $m_files->getStatusByFnums($fnums);
		$line        = "";
		$element_csv = array();
		$i           = $start;

		// On traite les en-têtes
		if ($start == 0)
		{
			$line  = Text::_('COM_EMUNDUS_FILE_F_NUM') . "\t" . Text::_('COM_EMUNDUS_STATUS') . "\t" . Text::_('COM_EMUNDUS_FORM_LAST_NAME') . "\t" . Text::_('COM_EMUNDUS_FORM_FIRST_NAME') . "\t" . Text::_('COM_EMUNDUS_EMAIL') . "\t" . Text::_('COM_EMUNDUS_CAMPAIGN') . "\t";
			$nbcol = 6;
			foreach ($ordered_elements as $fKey => $fLine)
			{
				//if ($fLine->element_name != 'fnum' && $fLine->element_name != 'code' && $fLine->element_name != 'campaign_id') {
				$line .= strip_tags($fLine->element_label) . "\t";
				$nbcol++;
				//}
			}
			foreach ($colsup as $kOpt => $vOpt)
			{
				if ($vOpt == "forms" || $vOpt == "attachment")
				{
					$line .= Text::_('COM_EMUNDUS_' . strtoupper($vOpt)) . " (%)\t";
				}
				else
				{
					$line .= $vOpt . "\t";
				}

				$nbcol++;
			}
			// On met les en-têtes dans le CSV
			$element_csv[] = $line;
			$line          = "";
		}

		// On parcours les fnums
		foreach ($fnumsArray as $fnum)
		{
			// On traitre les données du fnum
			foreach ($fnum as $k => $v)
			{
				if ($k != 'code' && $k != 'campaign_id' && $k != 'jos_emundus_campaign_candidature___campaign_id' && $k != 'c___campaign_id')
				{
					if ($k === 'fnum')
					{
						$line       .= $v . "\t";
						$line       .= $status[$v]['value'] . "\t";
						$uid        = intval(substr($v, 21, 7));
						$userProfil = JUserHelper::getProfile($uid)->emundus_profile;
						$lastname   = (!empty($userProfil['lastname'])) ? $userProfil['lastname'] : JFactory::getUser($uid)->name;
						$line       .= strtoupper($lastname) . "\t";
						$line       .= $userProfil['firstname'] . "\t";
					}
					else
					{
						$line .= strip_tags($v) . "\t";
					}
				}
			}
			// On ajoute les données supplémentaires
			foreach ($colOpt as $kOpt => $vOpt)
			{
				switch ($kOpt)
				{
					case "PHOTO":
						$line .= Text::_('photo') . "\t";
						break;
					case "forms":
					case "attachment":
						if (array_key_exists($fnum['fnum'], $vOpt))
						{
							$val  = $vOpt[$fnum['fnum']];
							$line .= $val . "\t";
						}
						else
						{
							$line .= "\t";
						}
						break;
					case "assessment":
						$eval = '';
						if (array_key_exists($fnum['fnum'], $vOpt))
						{
							$evaluations = $vOpt[$fnum['fnum']];
							foreach ($evaluations as $evaluation)
							{
								$eval .= $evaluation;
								$eval .= chr(10) . '______' . chr(10);
							}
							$line .= $eval . "\t";
						}
						else
						{
							$line .= "\t";
						}
						break;
					case "comment":
						$comments = "";
						if (array_key_exists($fnum['fnum'], $vOpt))
						{
							foreach ($colOpt['comment'] as $comment)
							{
								if ($comment['fnum'] == $fnum['fnum'])
								{
									$comments .= $comment['reason'] . " | " . $comment['comment_body'] . "\rn";
								}
							}
							$line .= $comments . "\t";
						}
						else
						{
							$line .= "\t";
						}
						break;
					case 'evaluators':
						if (array_key_exists($fnum['fnum'], $vOpt))
						{
							$line .= $vOpt[$fnum['fnum']] . "\t";
						}
						else
						{
							$line .= "\t";
						}
						break;
				}
			}
			// On met les données du fnum dans le CSV
			$element_csv[] = $line;
			$line          = "";
			$i++;
		}
		// On remplit le fichier CSV
		foreach ($element_csv as $data)
		{
			$res = fputcsv($csv, explode("\t", $data), "\t");
			if (!$res)
			{
				$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_WRITE_TO_FILE' . ' : ' . $csv));
				echo json_encode((object) $result);
				exit();
			}
		}
		if (!fclose($csv))
		{
			$result = array('status' => false, 'msg' => Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_CLOSE_CSV_FILE'));
			echo json_encode((object) $result);
			exit();
		}

		$start      = $i;
		$dataresult = array('start' => $start, 'limit' => $limit, 'totalfile' => $totalfile, 'methode' => 0, 'elts' => $elts, 'objs' => $objs, 'nbcol' => $nbcol, 'file' => $file);
		$result     = array('status' => true, 'json' => $dataresult);
		echo json_encode((object) $result);
		//var_dump($result);
		exit();
	}

	function get_mime_type($filename, $mimePath = '../etc')
	{
		$fileext = substr(strrchr($filename, '.'), 1);
		if (empty($fileext)) return (false);
		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
		$lines = file("$mimePath/mime.types");
		foreach ($lines as $line)
		{
			if (substr($line, 0, 1) == '#') continue; // skip comments
			$line = rtrim($line) . " ";
			if (!preg_match($regex, $line, $matches)) continue; // no match to the extension

			return ($matches[1]);
		}

		return (false); // no match at all
	}

	public function download()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->download();
	}

	/*
	*   Create a zip file containing all documents attached to application fil number
	*/
	function export_zip($fnums)
	{
		$view = $this->input->get('view');
		if ((!@EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) &&
			$view != 'renew_application'
		)
		{
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf.php');

		$zip = new ZipArchive();

		$nom     = date("Y-m-d") . '_' . rand(1000, 9999) . '_x' . (count($fnums) - 1) . '.zip';
		$path    = JPATH_SITE . DS . 'tmp' . DS . $nom;
		$m_files = $this->getModel('Files');
		$files   = $m_files->getFilesByFnums($fnums);

		if (file_exists($path))
		{
			unlink($path);
		}

		$users = array();
		foreach ($fnums as $fnum)
		{
			$sid          = intval(substr($fnum, -7));
			$users[$fnum] = JFactory::getUser($sid);

			if (!is_numeric($sid) || empty($sid))
			{
				continue;
			}

			if ($zip->open($path, ZipArchive::CREATE) == true)
			{
				$dossier = EMUNDUS_PATH_ABS . $users[$fnum]->id . DS;

				application_form_pdf($users[$fnum]->id, $fnum, false);
				$application_pdf = 'application.pdf';

				$filename = $fnum . '_' . $users[$fnum]->name . DS . $application_pdf;

				if (!$zip->addFile($dossier . DS . $application_pdf, $filename))
				{
					echo "-" . $dossier . $filename;
					continue;

				}

				$zip->close();
			}
			else
			{
				die ("ERROR");
			}
		}

		if ($zip->open($path, ZipArchive::CREATE) == true)
		{
			$todel = array();
			$i     = 0;
			$error = 0;
			foreach ($files as $key => $file)
			{
				$filename = $file['fnum'] . '_' . $users[$file['fnum']]->name . DS . $file['filename'];

				$dossier = EMUNDUS_PATH_ABS . $users[$file['fnum']]->id . DS;

				if (!$zip->addFile($dossier . $file['filename'], $filename))
				{
					echo "-" . $dossier . $file['filename'];
					continue;

				}
			}

			$zip->close();

		}
		else
		{
			die ("ERROR");
		}

		return $nom;
	}

	// controller of get all letters
	public function getattachmentletters()
	{
		$result = ['status' => false, 'attachment_letters' => null];

		if(EmundusHelperAccess::asAccessAction(27,'c',$this->_user->id))
		{
			$fnums = $this->input->getRaw('fnums', null);

			$m_evaluation       = $this->getModel('Evaluation');
			$result['attachment_letters'] = $m_evaluation->getLettersByFnums($fnums, true);
			$result['status'] = true;
		}

		echo json_encode((object) $result);
		exit;
	}

	public function getmyevaluations()
	{
		$result = array('status' => false, 'files' => []);

		$campaign = $this->input->getInt('campaign',0);
		$module   = $this->input->getInt('module',0);

		if(EmundusHelperAccess::asPartnerAccessLevel($this->_user->id) && !empty($campaign) && !empty($module))
		{
			$m_evaluation      = $this->getModel('Evaluation');
			$result['files'] = $m_evaluation->getMyEvaluations($this->_user->id, $campaign, $module);
			$result['status'] = true;
		}

		echo json_encode((object) $result);
		exit;
	}

	public function getcampaignstoevaluate()
	{
		$result = array('status' => false, 'campaigns' => []);

		$module = $this->input->getInt('module',0);

		if(!empty($module) && EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$m_evaluation        = $this->getModel('Evaluation');
			$result['campaigns'] = $m_evaluation->getCampaignsToEvaluate($this->_user->id, $module);
			$result['status']    = true;
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * readonly parameter is used to get only the evaluations that the user can see but not edit
	 *
	 * @return void
	 * @throws Exception
	 */
	public function getevaluationsforms()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		$fnum = $this->input->getString('fnum', null);

		if (!empty($fnum) && EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
			$ccid = EmundusHelperFiles::getIdFromFnum($fnum);
			/*
			 * 3 cases possible
			 *
			 * Evaluator has only Create Access right -> can only see his own evaluations
			 * Evaluator has Read Access right -> can see all evaluations
			 * Evaluator has Update Access right -> can see and edit all evaluations
			 */
			$workflowRepository = new \Tchooz\Repositories\Workflow\WorkflowRepository();
			$workflow = $workflowRepository->getWorkflowByFnum($fnum);


			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$workflowModel = new EmundusModelWorkflow();

			$stepsWithEvaluations = [];
			foreach ($workflow->getSteps() as $step)
			{
				assert($step instanceof Tchooz\Entities\Workflow\StepEntity);

				if ($step->isEvaluationStep())
				{
					$updateAccess = EmundusHelperAccess::asAccessAction($step->getType()->getActionId(), 'u', $this->_user->id, $fnum);
					$readAccess = EmundusHelperAccess::asAccessAction($step->getType()->getActionId(), 'r', $this->_user->id, $fnum);
					$createAccess = EmundusHelperAccess::asAccessAction($step->getType()->getActionId(), 'c', $this->_user->id, $fnum);

					if (!$readAccess && !$createAccess && !$updateAccess) {
						continue;
					}

					$stepWithEvaluations = $step->serialize();
					$stepWithEvaluations['default_evaluation_form_url'] = '/evaluation-step-form?view=form&formid=' . $step->getFormId() . '&' . $step->getTable() . '___ccid=' . $ccid . '&' . $step->getTable() . '___step_id=' . $step->getId() . '&tmpl=component&iframe=1';

					if (!$updateAccess && !$readAccess)
					{
						$allEvaluations = $workflowModel->getStepEvaluationsForFile($step->getId(), $ccid, 'form', $this->_user->id);
						// get only user's evaluations
						$userEvaluations = array_filter($allEvaluations, function($eval) {
							return $eval->evaluator == $this->_user->id;
						});
						$stepWithEvaluations['evaluations'] = array_values($userEvaluations);
					} else
					{
						if ($updateAccess)
						{
							$allEvaluations = $workflowModel->getStepEvaluationsForFile($step->getId(), $ccid, 'form');
						}
						else
						{
							$allEvaluations = [];

							if ($createAccess)
							{
								$allEvaluations = $workflowModel->getStepEvaluationsForFile($step->getId(), $ccid, 'form', $this->_user->id);

							}

							$otherEvaluations = $workflowModel->getStepEvaluationsForFile($step->getId(), $ccid);
							// keep only evaluation ids not already in allEvaluations
							$existingEvalIds = array_map(function($eval) {
								return $eval['id'];
							}, $allEvaluations);

							foreach ($otherEvaluations as $otherEval)
							{
								if (!in_array($otherEval['id'], $existingEvalIds))
								{
									$allEvaluations[] = $otherEval;
								}
							}
						}

						$stepWithEvaluations['evaluations'] = $allEvaluations;
					}

					if ($createAccess || $updateAccess)
					{
						// make sure there's a line for creating a new evaluation, if none exists yet for this user, unless step is not multiple evaluations
						$hasUserEvaluation = false;
						foreach ($stepWithEvaluations['evaluations'] as $eval) {
							if ($eval['evaluator'] == $this->_user->id) {
								$hasUserEvaluation = true;
								break;
							}
						}

						if (!$hasUserEvaluation && ($step->getMultiple() || count($stepWithEvaluations['evaluations']) === 0)) {
							$currentUserEvaluation = (object) [
								'id' => 0,
								'evaluator' => $this->_user->id,
								'ccid' => $ccid,
								'fnum' => $fnum,
								'step_id' => $step->getId(),
								'evaluator_name' => $this->_user->name,
								'url' => '/evaluation-step-form?view=form&formid=' . $step->getFormId() . '&' . $step->getTable() . '___ccid=' . $ccid . '&' . $step->getTable() . '___step_id=' . $step->getId() . '&tmpl=component&iframe=1'
							];

							// add at the beginning of evaluations array
							array_unshift($stepWithEvaluations['evaluations'], $currentUserEvaluation);
						}
						else if (!$step->getMultiple())
						{
							// for non-multiple evaluation steps, ensure the current user's evaluation has the form URL
							foreach ($stepWithEvaluations['evaluations'] as &$eval) {
								$eval['url'] = str_replace('details', 'form', $eval['url'] ?? '');
							}
						}
					}

					$stepsWithEvaluations[] = $stepWithEvaluations;
				}
			}

			$response = [
				'status' => true,
				'code' => 200,
				'msg' => Text::_('SUCCESS'),
				'data' => $stepsWithEvaluations
			];
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getstepevaluationsforfile()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		$ccid = $this->input->getInt('ccid', 0);
		$fnum = EmundusHelperFiles::getFnumFromId($ccid);
		$step_id = $this->input->getInt('step_id', 0);

		if (!empty($fnum) && !empty($step_id) && EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			$m_workflow = new EmundusModelWorkflow();

			$step = $m_workflow->getStepData($step_id);
			if (EmundusHelperAccess::asAccessAction($step->action_id, 'r', $this->_user->id, $fnum)) {
				$response['data'] = $m_workflow->getStepEvaluationsForFile($step_id, $ccid);
				$response['code'] = 200;
				$response['status'] = true;
				$response['msg'] = Text::_('SUCCESS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}
}
