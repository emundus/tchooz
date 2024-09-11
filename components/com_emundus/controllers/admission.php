<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;

/**
 * Emundus Admission Controller
 *
 * @since 1.0.0
 * @deprecated 2.0.0 Use EmundusControllerFiles instead
 */
class EmundusControllerAdmission extends BaseController
{
	/**
	 * User object
	 *
	 * @var \Joomla\CMS\User\User|\JUser|mixed|null
	 * @since version 1.0.0
	 */
	private $user;

	/**
	 * Database object
	 *
	 * @var \JDatabaseDriver|\Joomla\Database\DatabaseDriver|null
	 * @since version 1.0.0
	 */
	private $_db;

	/**
	 * Session object
	 *
	 * @var \Joomla\Session\SessionInterface|\JSession
	 * @since version 1.0.0
	 */
	private $session;

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

		$this->user    = $this->app->getIdentity();
		$this->session = $this->app->getSession();
		$this->_db     = Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @return  EmundusControllerAdmission  This object to support chaining.
	 *
	 * @since   1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		if (!$this->input->get('view')) {
			$default = 'files';
			$this->input->set('view', $default);
		}

		parent::display();

		return $this;
	}

	/**
	 * Clear session and reinit values by default
	 *
	 * @since version 1.0.0
	 */
	public function clear()
	{
		EmundusHelperFiles::clear();
		
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	/**
	 * Set filters of admission view
	 *
	 * @since version 1.0.0
	 */
	public function setfilters()
	{
		$filterName = $this->input->getString('id', null);
		$elements   = $this->input->getString('elements', null);
		$multi      = $this->input->getString('multi', null);

		EmundusHelperFiles::clearfilter();

		if ($multi == "true") {
			$filterval = $this->input->get('val', array(), 'ARRAY');
		}
		else {
			$filterval = $this->input->getString('val', null);
		}

		$params = $this->session->get('filt_params');

		if ($elements == 'false') {
			$params[$filterName] = $filterval;
		}
		else {
			$vals = (array) json_decode(stripslashes($filterval));
			if (count($vals) > 0) {
				foreach ($vals as $val) {
					if ($val->adv_fil) {
						$params['elements'][$val->name]['value']  = $val->value;
						$params['elements'][$val->name]['select'] = $val->select;
					}
					else {
						$params[$val->name] = $val->value;
					}
				}
			}
			else {
				$params['elements'][$filterName]['value'] = $filterval;
			}
		}

		$this->session->set('filt_params', $params);
		$this->session->set('limitstart', 0);

		echo json_encode((object) (array('status' => true)));
		exit();
	}

	/**
	 * Load filters of admission view
	 *
	 * @since version 1.0.0
	 */
	public function loadfilters()
	{
		try {
			$id = $this->input->getInt('id', null);

			$filter                  = EmundusHelperFiles::getEmundusFilters($id);
			$params                  = (array) json_decode($filter->constraints);
			$params['select_filter'] = $id;
			$params                  = json_decode($filter->constraints, true);

			$this->session->set('select_filter', $id);
			if (isset($params['filter_order'])) {
				$this->session->set('filter_order', $params['filter_order']);
				$this->session->set('filter_order_Dir', $params['filter_order_Dir']);
			}

			$this->session->set('filt_params', $params['filter']);
			if (!empty($params['col'])) {
				$this->session->set('adv_cols', $params['col']);
			}

			echo json_encode((object) (array('status' => true)));
			exit();
		}
		catch (\Exception $e) {
			throw new \Exception;
		}
	}

	/**
	 * Reorder the list of applications in admission view
	 *
	 * @since version 1.0.0
	 */
	public function order()
	{
		$order = $this->input->getString('filter_order', null);

		$ancientOrder = $this->session->get('filter_order');
		$params       = $this->session->get('filt_params');
		$this->session->set('filter_order', $order);
		$params['filter_order'] = $order;

		if ($order == $ancientOrder) {

			if ($this->session->get('filter_order_Dir') == 'desc') {
				$this->session->set('filter_order_Dir', 'asc');
				$params['filter_order_Dir'] = 'asc';
			}
			else {
				$this->session->set('filter_order_Dir', 'desc');
				$params['filter_order_Dir'] = 'desc';
			}

		}
		else {
			$this->session->set('filter_order_Dir', 'asc');
			$params['filter_order_Dir'] = 'asc';
		}

		$this->session->set('filt_params', $params);
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	/**
	 * Set the limit of applications in admission view
	 *
	 * @since version 1.0.0
	 */
	public function setlimit()
	{
		$limit = $this->input->getInt('limit', null);

		$this->session->set('limit', $limit);
		$this->session->set('limitstart', 0);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	/**
	 * Save a custom filter
	 *
	 * @since version 1.0.0
	 */
	public function savefilters()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->savefilters();
	}

	/**
	 * Delete a saved filter
	 *
	 * @since version 1.0.0
	 */
	public function deletefilters()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->deletefilters();
	}

	/**
	 * Set the start of the list of applications in admission view
	 *
	 * @since version 1.0.0
	 */
	public function setlimitstart()
	{
		$limistart = $this->input->getInt('limitstart', null);

		$limit      = intval($this->session->get('limit'));
		$limitstart = ($limit != 0 ? ($limistart > 1 ? (($limistart - 1) * $limit) : 0) : 0);

		$this->session->set('limitstart', $limitstart);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	/**
	 * Get the list of advanced filters
	 *
	 * @since version 1.0.0
	 */
	public function getadvfilters()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->getadvfilters();
	}

	/**
	 * Add a comment
	 *
	 * @since version 1.0.0
	 */
	public function addcomment()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->addcomment();
	}

	/**
	 * Get list of evaluation groups and users
	 *
	 * @since version 1.0.0
	 */
	public function getevsandgroups()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			$m_files    = $this->getModel('Files');
			$evalGroups = $m_files->getEvalGroups();
			$actions    = $m_files->getAllActions();
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

	/**
	 * Get list of tags for applications
	 *
	 * @since version 1.0.0
	 */
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
	 * Add a tag to applications
	 *
	 * @since version 1.0.0
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

	/**
	 * Delete a tag from applications
	 *
	 * @since version 1.0.0
	 */
	public function deletetags()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->deletetags();
	}

	/**
	 * Share files with groups or/and users
	 *
	 * @since version 1.0.0
	 */
	public function share()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->share();
	}

	/**
	 * Get list of status available for applications
	 *
	 * @since version 1.0.0
	 */
	public function getstate()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->getstate();
	}

	/**
	 * Update the status of applications
	 *
	 * @since version 1.0.0
	 */
	public function updatestate()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->updatestate();
	}

	/**
	 * Unlink evaluators from a single application file
	 *
	 * @since version 1.0.0
	 * TODO: Manage access
	 */
	public function unlinkevaluators()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->unlinkevaluators();
	}

	/**
	 * Get details of a single application file
	 *
	 * @since version 1.0.0
	 */
	public function getfnuminfos() {
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->getfnuminfos();
	}

	/**
	 * Move an application file to trash
	 *
	 * @since version 1.0.0
	 */
	public function deletefile()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->deletefile();
	}

	/**
	 * Get elements from a program
	 *
	 * @since version 1.0.0
	 */
	public function getformelem()
	{
		$form = $this->input->getString('form', null);
		$code = $this->input->get('code', null);
		$code = explode(',', $code);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'admission.php');
		$m_admission = $this->getModel('Admission');

		$defaultElements = $m_admission->getAdmissionElementsName(0, 1, $code);
		if (!empty($defaultElements)) {
			foreach ($defaultElements as $kde => $de) {
				if ($de->element_name == 'id' || $de->element_name == 'fnum' || $de->element_name == 'student_id' || $de->element_name == 'user') {
					unset($defaultElements[$kde]);
				}
			}
		}

		if ($form == "admission") {
			$elements = $m_admission->getApplicantAdmissionElementsName(0, 0, $code);
		}
		elseif ($form == "decision") {
			$elements = $m_admission->getAdmissionElementsName(0, 0, $code);
		}
		else {
			$elements = EmundusHelperFiles::getElements();
		}

		$res = [
			'status'   => true,
			'elts'     => $elements,
			'defaults' => $defaultElements
		];

		echo json_encode((object) $res);
		exit;
	}

	/**
	 * Export a single application in PDF format
	 *
	 * @since version 1.0.0
	 */
	function pdf_admission()
	{
		$fnum       = $this->input->getString('fnum', null);
		$student_id = $this->input->getString('student_id', null);

		if (!EmundusHelperAccess::asAccessAction(8, 'c', $this->user->id, $fnum)) {
			if (EmundusHelperAccess::asApplicantAccessLevel($this->user->id)) {
				$student_id = $this->user->id;
			}
			else {
				die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
			}
		}

		$m_profile  = $this->getModel('Profile');
		$m_campaign = $this->getModel('Campaign');

		if (!empty($fnum)) {
			$candidature = $m_profile->getFnumDetails($fnum);
			$campaign    = $m_campaign->getCampaignByID($candidature['campaign_id']);
		}

		$file = JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf_admission_' . $campaign['training'] . '.php';

		if (!file_exists($file))
			$file = JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf_admission.php';

		if (!file_exists(EMUNDUS_PATH_ABS . $student_id)) {
			mkdir(EMUNDUS_PATH_ABS . $student_id);
			chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
		}

		require_once($file);
		pdf_admission(!empty($student_id) ? $student_id : $this->user->id, $fnum);

		exit();
	}

	/**
	 * Export applications in CSV format
	 *
	 * @since version 1.0.0
	 */
	public function create_file_csv()
	{
		if(!EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$today  = date_default_timezone_get();
		$name   = md5($today . rand(0, 10));
		$name   = $name . '.csv';
		$chemin = JPATH_SITE . DS . 'tmp' . DS . $name;

		if (!$fichier_csv = fopen($chemin, 'w+')) {
			$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_OPEN_FILE') . ' : ' . $chemin);
			echo json_encode((object) $result);
			exit();
		}

		fprintf($fichier_csv, chr(0xEF) . chr(0xBB) . chr(0xBF));

		if (!fclose($fichier_csv)) {
			$result = array('status' => false, 'msg' => Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_CLOSE_CSV_FILE'));
			echo json_encode((object) $result);
			exit();
		}

		$result = array('status' => true, 'file' => $name);
		echo json_encode((object) $result);
		exit();
	}

	/**
	 * Prepare the list of applications to export in CSV format
	 *
	 * @since version 1.0.0
	 */
	public function getfnums_csv()
	{
		$fnums_post  = $this->input->get('fnums', null);
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);
		$m_files     = $this->getModel('Files');

		if ($fnums_array == 'all') {
			$fnums = $m_files->getAllFnums();
		}
		else {
			$fnums = array();
			foreach ($fnums_array as $key => $value) {
				$fnums[] = $value;
			}
		}

		$validFnums = array();
		foreach ($fnums as $fnum) {
			if (EmundusHelperAccess::asAccessAction(13, 'u', $this->user->id, $fnum) && $fnum != 'em-check-all-all' && $fnum != 'em-check-all')
				$validFnums[] = $fnum;
		}
		$totalfile = sizeof($validFnums);

		$this->session->set('fnums_export', $validFnums);

		$result = array('status' => true, 'totalfile' => $totalfile);
		echo json_encode((object) $result);
		exit();
	}

	/**
	 * Get column from elements for CSV export
	 *
	 * @param $elts
	 *
	 * @return array
	 *
	 * @since version 1.0.0
	 */
	public function getcolumn($elts)
	{
		return (array) json_decode(stripcslashes($elts));
	}

	/**
	 * Generate array to export in CSV format
	 *
	 * @since version 1.0.0
	 */
	public function generate_array()
	{

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$m_files       = $this->getModel('Files');
		$m_application = $this->getModel('Application');

		$fnums = $this->session->get('fnums_export');
		if (count($fnums) == 0) {
			$fnums = array($this->session->get('application_fnum'));
		}


		$file      = $this->input->getString('file', null);
		$totalfile = $this->input->getInt('totalfile', null);
		$start     = $this->input->getInt('start', 0);
		$limit     = $this->input->getInt('limit', 0);
		$nbcol     = $this->input->getInt('nbcol', 0);
		$elts      = $this->input->getString('elts', null);
		$objs      = $this->input->getString('objs', null);

		$col = $this->getcolumn($elts);

		$colsup = $this->getcolumn($objs);
		$colOpt = array();
		if (!$csv = fopen(JPATH_SITE . DS . 'tmp' . DS . $file, 'a')) {
			$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_OPEN_FILE') . ' : ' . $file);
			echo json_encode((object) $result);
			exit();
		}

		$elements = EmundusHelperFiles::getElementsName(implode(',', $col));

		// re-order elements
		$ordered_elements = array();
		foreach ($col as $c) {
			$ordered_elements[$c] = $elements[$c];
		}
		$fnumsArray = $m_files->getFnumArray($fnums, $ordered_elements, 0, $start, $limit, 0);

		// On met a jour la liste des fnums traités
		$fnums = array();
		foreach ($fnumsArray as $fnum) {
			$fnums[] = $fnum['fnum'];
		}

		foreach ($colsup as $col) {
			$col = explode('.', $col);
			switch ($col[0]) {
				case "photo":
					$photos = $m_files->getPhotos($fnums);
					if (count($photos) > 0) {
						$pictures = array();
						foreach ($photos as $photo) {

							$folder = Uri::base() . EMUNDUS_PATH_REL . $photo['user_id'];

							$link                     = '=HYPERLINK("' . $folder . '/tn_' . $photo['filename'] . '","' . $photo['filename'] . '")';
							$pictures[$photo['fnum']] = $link;
						}
						$colOpt['PHOTO'] = $pictures;
					}
					else {
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
					$colOpt['assessment'] = EmundusHelperFiles::getEvaluation('text', $fnums);
					break;
				case "comment":
					$colOpt['comment'] = $m_files->getCommentsByFnum($fnums);
					break;
				case 'evaluators':
					$colOpt['evaluators'] = EmundusHelperFiles::createEvaluatorList($col[1], $m_files);
					break;
			}
		}
		$status      = $m_files->getStatusByFnums($fnums);
		$line        = "";
		$element_csv = array();
		$i           = $start;

		// On traite les en-têtes
		if ($start == 0) {
			$line  = Text::_('COM_EMUNDUS_FILE_F_NUM') . "\t" . Text::_('COM_EMUNDUS_STATUS') . "\t" . Text::_('COM_EMUNDUS_FORM_LAST_NAME') . "\t" . Text::_('COM_EMUNDUS_FORM_FIRST_NAME') . "\t" . Text::_('COM_EMUNDUS_EMAIL') . "\t" . Text::_('COM_EMUNDUS_CAMPAIGN') . "\t";
			$nbcol = 6;

			foreach ($ordered_elements as $fLine) {
				if ($fLine->element_name != 'fnum' && $fLine->element_name != 'code' && $fLine->element_name != 'campaign_id') {
					$line .= $fLine->element_label . "\t";
					$nbcol++;
				}
			}

			foreach ($colsup as $kOpt => $vOpt) {
				if ($vOpt == "forms" || $vOpt == "attachment")
					$line .= Text::_('COM_EMUNDUS_'.strtoupper($vOpt))." (%)\t";
				else
					$line .= $vOpt . "\t";

				$nbcol++;
			}

			// On met les en-têtes dans le CSV
			$element_csv[] = $line;
			$line          = "";
		}

		// On parcours les fnums
		foreach ($fnumsArray as $fnum) {
			// On traitre les données du fnum
			foreach ($fnum as $k => $v) {
				if ($k != 'code' && $k != 'campaign_id' && $k != 'jos_emundus_campaign_candidature___campaign_id' && $k != 'jos_emundus_final_grade___campaign_id' && $k != 'c___campaign_id') {

					if ($k === 'fnum') {

						$line       .= $v . "\t";
						$line       .= $status[$v]['value'] . "\t";
						$uid        = intval(substr($v, 21, 7));
						$userProfil = UserHelper::getProfile($uid)->emundus_profile;
						$line       .= strtoupper($userProfil['lastname']) . "\t";
						$line       .= $userProfil['firstname'] . "\t";

					}
					elseif ($k === 'jos_emundus_evaluations___user' || $k === "user")
						$line .= strip_tags(Factory::getUser($v)->name) . "\t";
					else
						$line .= strip_tags($v) . "\t";
				}
			}

			// On ajoute les données supplémentaires
			foreach ($colOpt as $kOpt => $vOpt) {
				switch ($kOpt) {

					case "PHOTO":
						if (array_key_exists($fnum['fnum'], $vOpt)) {
							$val = $vOpt[$fnum['fnum']];
							// Img comes in form of html tag
							$dom_document = new DOMDocument();
							$xpath        = new DOMXPath($dom_document->loadHTML($val));
							$src          = $xpath->evaluate("string(//img/@src)");
							$line         .= $src . "\t";
							// This only prints the link to the image, in order to add an img to the csv you have to superpose it over a cell
						}
						else {
							$line .= "\t";
						}
						break;

					case "forms":
					case "attachment":
						if (array_key_exists($fnum['fnum'], $vOpt)) {
							$val  = $vOpt[$fnum['fnum']];
							$line .= $val . "\t";
						}
						else {
							$line .= "\t";
						}
						break;

					case "assessment":
						$eval = '';
						if (array_key_exists($fnum['fnum'], $vOpt)) {
							$evaluations = $vOpt[$fnum['fnum']];
							foreach ($evaluations as $evaluation) {
								$eval .= $evaluation;
								$eval .= chr(10) . '______' . chr(10);
							}
							$line .= $eval . "\t";
						}
						else {
							$line .= "\t";
						}
						break;

					case "comment":
						$comments = "";
						if (array_key_exists($fnum['fnum'], $vOpt)) {
							foreach ($colOpt['comment'] as $comment) {
								if ($comment['fnum'] == $fnum['fnum']) {
									$comments .= $comment['reason'] . " | " . $comment['comment_body'] . "\rn";
								}
							}
							$line .= $comments . "\t";
						}
						else {
							$line .= "\t";
						}
						break;

					case 'evaluators':
						if (array_key_exists($fnum['fnum'], $vOpt)) {
							$line .= $vOpt[$fnum['fnum']] . "\t";
						}
						else {
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
		foreach ($element_csv as $data) {
			$res = fputcsv($csv, explode("\t", $data), "\t");
			if (!$res) {
				$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_WRITE_TO_FILE' . ' : ' . $csv));
				echo json_encode((object) $result);
				exit();
			}
		}
		if (!fclose($csv)) {
			$result = array('status' => false, 'msg' => Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_CLOSE_CSV_FILE'));
			echo json_encode((object) $result);
			exit();
		}

		$start      = $i;
		$dataresult = array('start' => $start, 'limit' => $limit, 'totalfile' => $totalfile, 'methode' => 0, 'elts' => $elts, 'objs' => $objs, 'nbcol' => $nbcol, 'file' => $file);
		$result     = array('status' => true, 'json' => $dataresult);
		echo json_encode((object) $result);
		exit();
	}

	/**
	 * Get mime type of a file
	 *
	 * @param $filename
	 * @param $mimePath
	 *
	 * @return false|string
	 *
	 * @since version 1.0.0
	 */
	function get_mime_type($filename, $mimePath = '../etc')
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		return $c_files->get_mime_type($filename, $mimePath);
	}

	/**
	 * Download tmp file (from exports)
	 *
	 * @since version 1.0.0
	 */
	public function download()
	{
		if (!class_exists('EmundusControllerFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/controllers/files.php');
		}

		$c_files = new EmundusControllerFiles();
		$c_files->download();
	}

	/**
	 * Export applications in ZIP format
	 *
	 * @param $fnums
	 *
	 * @return string|void
	 *
	 * @throws \Exception
	 * @since version 1.0.0
	 */
	function export_zip($fnums)
	{
		$view = $this->input->get('view');

		if ((!EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) && $view != 'renew_application') {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf.php');

		$zip = new ZipArchive();

		$nom  = date("Y-m-d") . '_' . rand(1000, 9999) . '_x' . (count($fnums) - 1) . '.zip';
		$path = JPATH_SITE . DS . 'tmp' . DS . $nom;

		$m_files = $this->getModel('Files');
		$files   = $m_files->getFilesByFnums($fnums);

		if (file_exists($path))
			unlink($path);

		$users = array();
		foreach ($fnums as $fnum) {
			$sid          = intval(substr($fnum, -7));
			$users[$fnum] = Factory::getUser($sid);

			if (!is_numeric($sid) || empty($sid))
				continue;

			if ($zip->open($path, ZipArchive::CREATE)) {
				$dossier = EMUNDUS_PATH_ABS . $users[$fnum]->id . DS;

				application_form_pdf($users[$fnum]->id, $fnum, false);
				$application_pdf = 'application.pdf';

				$filename = $fnum . '_' . $users[$fnum]->name . DS . $application_pdf;

				if (!$zip->addFile($dossier . DS . $application_pdf, $filename)) {
					echo "-" . $dossier . $filename;
					continue;
				}

				$zip->close();
			}
			else {
				die ("ERROR");
			}
		}

		if ($zip->open($path, ZipArchive::CREATE)) {
			foreach ($files as $file) {
				$filename = $file['fnum'] . '_' . $users[$file['fnum']]->name . DS . $file['filename'];

				$dossier = EMUNDUS_PATH_ABS . $users[$file['fnum']]->id . DS;

				if (!$zip->addFile($dossier . $file['filename'], $filename)) {
					echo "-" . $dossier . $file['filename'];
				}
			}

			$zip->close();
		}
		else {
			die ("ERROR");
		}

		return $nom;
	}
}
