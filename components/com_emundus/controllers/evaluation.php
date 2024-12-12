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
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->tagfile();
	}


	public function deletetags()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->deletetags();
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
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->updatestate();
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

	function delevaluation()
	{
		$fnum = $this->input->getString('fnum', null);
		$ids  = $this->input->getString('ids', null);
		$ids  = json_decode(stripslashes($ids));
		$res  = new stdClass();

		$m_evaluation = $this->getModel('Evaluation');
		foreach ($ids as $id)
		{
			if (!empty($id))
			{
				$eval = $m_evaluation->getEvaluationById($id);
				if (EmundusHelperAccess::asAccessAction(5, 'd', $this->_user->id, $fnum))
				{
					$res->status = $m_evaluation->delevaluation($id);


					require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');

					require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
					$mFile        = $this->getModel('Files');
					$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

					EmundusModelLogs::log($this->_user->id, $applicant_id, $fnum, 5, 'd', 'COM_EMUNDUS_ACCESS_EVALUATION_DELETE');
				}
				else
				{
					$eval = $m_evaluation->getEvaluationById($id);
					if ($eval->user == $this->_user->id)
					{
						$res->status = $m_evaluation->delevaluation($id);

						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');

						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
						$mFile        = $this->getModel('Files');
						$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

						EmundusModelLogs::log($this->_user->id, $applicant_id, $fnum, 5, 'd', 'COM_EMUNDUS_ACCESS_EVALUATION_DELETE');
					}
					else
					{
						$res->status = false;
						$res->msg    = Text::_("ACCESS_DENIED");
					}
				}
			}
		}

		echo json_encode($res);
		exit();
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

		if (!empty($fnum) && EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$readonly =  $this->input->getString('readonly', 0);

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('campaign_id')
				->from('#__emundus_campaign_candidature')
				->where('fnum = ' . $db->quote($fnum));
			$db->setQuery($query);
			$campaign_id = $db->loadResult();

			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			$m_workflow = new EmundusModelWorkflow();
			$steps = $m_workflow->getCampaignSteps($campaign_id);

			if (!empty($steps)) {
				$ccid = EmundusHelperFiles::getIdFromFnum($fnum);
				$response['data'] = [];

				foreach ($steps as $step) {
					$step_data = $m_workflow->getStepData($step->id, $campaign_id);

					$user_access = EmundusHelperAccess::getUserEvaluationStepAccess($ccid, $step_data, $this->_user->id);
					if ($m_workflow->isEvaluationStep($step_data->type) && $user_access['can_see']) {
						if ($readonly && $user_access['can_edit']) {
							continue;
						}

						$view = !$user_access['can_edit'] ? 'details' : 'form';
						$step_data->user_access = EmundusHelperAccess::getUserEvaluationStepAccess($ccid, $step_data, $this->_user->id);
						$step_data->url = '/evaluation-step-form?view=' . $view . '&formid=' . $step_data->form_id . '&' . $step_data->table . '___ccid=' . $ccid . '&' . $step_data->table . '___step_id=' . $step_data->id . '&tmpl=component&iframe=1';
						$response['status'] = true;
						$response['code'] = 200;
						$response['data'][] = $step_data;
					}
				}
			} else {
				$response['msg'] = Text::_('COM_EMUNDUS_EVALUATION_NO_STEPS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

}
