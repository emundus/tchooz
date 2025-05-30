<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2015 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use enshrined\svgSanitize\Sanitizer;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use \setasign\Fpdi\Fpdi;
use \setasign\Fpdi\PdfReader;
use Component\Emundus\Helpers\HtmlSanitizerSingleton;

/**
 * eMundus Component Controller
 *
 * @package    eMundus
 * @subpackage Components
 */
class EmundusController extends JControllerLegacy
{
	private $_user;
	private $_db;

	protected $app;

	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		include_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
		include_once(JPATH_ROOT . '/components/com_emundus/helpers/menu.php');

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getSession()->get('emundusUser');
		$this->_db   = Factory::getDBO();
	}

	function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view')) {
			if (!empty($this->_user->usertype) && $this->_user->usertype == "Registered") {
				$checklist = $this->getView('checklist', 'html');
				$checklist->setModel($this->getModel('checklist'), true);
				$checklist->display();
			}
			else {
				$default = 'users';
			}
			$this->input->set('view', $default);
		}

		parent::display();

	}

	function clear()
	{
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/filters.php');
		EmundusHelperFilters::clear();
	}

	function getCampaign()
	{
		$query = $this->_db->getQuery(true);
		$query->select('year as schoolyear')->from($this->_db->quoteName('#__emundus_setup_campaigns'))->where($this->_db->quoteName('published') . ' = 1');
		$this->_db->setQuery($query);
		$syear = $this->_db->loadRow();

		return $syear[0];
	}

	function pdf()
	{
		$student_id = $this->input->get('user', null, 'string');
		$fnum       = $this->input->get('fnum', null, 'string');
		$profile    = $this->input->get('profile', null, 'string');

		$fnum       = !empty($fnum) ? $fnum : $this->_user->fnum;
		$m_profile  = $this->getModel('Profile');
		$m_campaign = $this->getModel('Campaign');
		$m_workflow = $this->getModel('Workflow');

		$can_access = false;
		if (EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $fnum)) {
			$can_access = true;
		}
		else {
			$afnums = $m_profile->getApplicantFnums($this->_user->id);
			if (!empty($afnums)) {
				$afnums = array_keys($afnums);
			}

			if (in_array($fnum, $afnums)) {
				$can_access = true;
			}
		}

		if ($can_access) {
			$options = array(
				'aemail',
				'afnum',
				'adoc-print',
				'aapp-sent',
			);

			$infos          = $m_profile->getFnumDetails($fnum);
			$workflow_infos = $m_workflow->getCurrentWorkflowStepFromFile($fnum);

			if ($profile == null) {
				$profile = !empty($infos['profile']) ? $infos['profile'] : $infos['profile_id'];
			}

			if ($workflow_infos->profile !== null) {
				$profile = $workflow_infos->profile;
			}

			$h_menu     = new EmundusHelperMenu;
			$getformids = $h_menu->getUserApplicationMenu($profile);

			$formid = [];
			foreach ($getformids as $getformid) {
				$formid[] = $getformid->form_id;
			}

			if (!empty($fnum)) {
				$candidature = $m_profile->getFnumDetails($fnum);
				$campaign    = $m_campaign->getCampaignByID($candidature['campaign_id']);
			}

			$file        = JPATH_LIBRARIES . DS . 'emundus/pdf_' . $campaign['training'] . '.php';
			$file_custom = JPATH_LIBRARIES . DS . 'emundus/custom/pdf_' . $campaign['training'] . '.php';
			if (!file_exists($file) && !file_exists($file_custom)) {
				$file = JPATH_LIBRARIES . DS . 'emundus/pdf.php';
			}
			else {
				if (file_exists($file_custom)) {
					$file = $file_custom;
				}
			}

			if (!file_exists(EMUNDUS_PATH_ABS . $student_id)) {
				mkdir(EMUNDUS_PATH_ABS . $student_id);
				chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
			}

			require_once($file);

			if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
				application_form_pdf(!empty($student_id) ? $student_id : $this->_user->id, $fnum, true, 1, null, $options, null, $profile, null, null);
				exit;
			}
			elseif (EmundusHelperAccess::isApplicant($this->_user->id)) {
				application_form_pdf($this->_user->id, $fnum, true, 1, $formid, $options, null, $profile, null, null);
				exit;
			}
			else {
				die(JText::_('ACCESS_DENIED'));
			}
		}
		else {
			die(JText::_('ACCESS_DENIED'));
		}
	}

	function pdf_by_form()
	{
		$user = $this->app->getSession()->get('emundusUser');

		$student_id = $this->input->get('user', null, 'string');
		$fnum       = $this->input->get('fnum', null, 'string');
		$formid     = [$this->input->get('form', null, 'string')];

		$fnum       = !empty($fnum) ? $fnum : $user->fnum;
		$m_profile  = $this->getModel('profile');
		$m_campaign = $this->getModel('campaign');

		$options = array(
			'aemail',
			'afnum',
			'adoc-print',
			'aapp-sent',
		);

		$infos = $m_profile->getFnumDetails($fnum);


		if (!empty($fnum)) {
			$candidature = $m_profile->getFnumDetails($fnum);
			$campaign    = $m_campaign->getCampaignByID($candidature['campaign_id']);
		}

		$file        = JPATH_LIBRARIES . DS . 'emundus/pdf_' . $campaign['training'] . '.php';
		$file_custom = JPATH_LIBRARIES . DS . 'emundus/custom/pdf_' . $campaign['training'] . '.php';
		if (!file_exists($file) && !file_exists($file_custom)) {
			$file = JPATH_LIBRARIES . DS . 'emundus/pdf.php';
		}
		else {
			if (file_exists($file_custom)) {
				$file = $file_custom;
			}
		}

		if (!file_exists(EMUNDUS_PATH_ABS . $student_id)) {
			mkdir(EMUNDUS_PATH_ABS . $student_id);
			chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
		}

		require_once($file);

		// Here we call the profile by fnum function, which will get the candidate's profile in the status table
		// $profile_id = $m_profile->getProfileByFnum($fnum);

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			//application_form_pdf(!empty($student_id)?$student_id:$user->id, $fnum, true, 1, null, $options, null, $profile_id,null,null);
			application_form_pdf(!empty($student_id) ? $student_id : $user->id, $fnum, true, 1, $formid, $options);
			exit;
		}
		else {
			die(JText::_('ACCESS_DENIED'));
		}
	}


	/**
	 * Function that will print the candidat's PDF depending on their file status
	 * Returns
	 * @throws Exception
	 */
	function pdf_by_status()
	{
		$user = $this->app->getSession()->get('emundusUser');

		$student_id = $this->input->get('user', null, 'string');
		$profile    = $this->input->get('profile', null, 'string');

		$fnum = $this->input->get('fnum', null, 'string');
		$fnum = !empty($fnum) ? $fnum : $user->fnum;
		// Don't go any further if we don't find a fnum
		if (empty($fnum)) {
			die(JText::_('ACCESS_DENIED'));
		}

		$m_profile  = $this->getModel('profile');
		$m_campaign = $this->getModel('campaign');

		// We need to determine if the profile is set by the status or the campaign
		$infos = $m_profile->getProfileByStatus($fnum);
		if (empty($infos['profile'])) {
			$infos = $m_profile->getFnumDetails($fnum);
		}

		if (empty($profile)) {
			$profile = !empty($infos['profile']) ? $infos['profile'] : $infos['profile_id'];
		}

		// Now we can start gettting the forms linked to the correct profile
		$h_menu     = new EmundusHelperMenu;
		$getformids = $h_menu->getUserApplicationMenu($profile);

		$formid = [];
		foreach ($getformids as $getformid) {
			$formid[] = $getformid->form_id;
		}

		$campaign = $m_campaign->getCampaignByID($infos['campaign_id']);

		$file = JPATH_LIBRARIES . DS . 'emundus/pdf_' . @$campaign['training'] . '.php';
		if (!file_exists($file)) {
			$file = JPATH_LIBRARIES . DS . 'emundus/pdf.php';
		}

		if (!file_exists(EMUNDUS_PATH_ABS . $student_id)) {
			mkdir(EMUNDUS_PATH_ABS . $student_id);
			chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
		}

		if (file_exists($file)) {
			require_once($file);

			// Here we call the profile by fnum function, which will get the candidate's profile in the status table
			if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
				$student = !empty($student_id) ? $student_id : $user->id;
				application_form_pdf($student, $fnum, true, 1, null, null, null, $profile);
				exit;
			}
			elseif (EmundusHelperAccess::isApplicant($user->id)) {
				application_form_pdf($user->id, $fnum, true, 1, null, null, null, $profile);
				exit;
			}
			else {
				die(JText::_('ACCESS_DENIED'));
			}
		}
		else {
			die(JText::_('ACCESS_DENIED'));
		}
	}

	function pdf_emploi()
	{
		$user       = $this->app->getSession()->get('emundusUser');
		$student_id = $this->input->get('user', null, 'GET', 'none', 0);
		$rowid      = explode('-', $this->input->get('rowid', null, 'GET', 'none', 0));

		$file = JPATH_LIBRARIES . DS . 'emundus/pdf_emploi.php';

		if (!file_exists($file)) {
			die(JText::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND'));
		}
		if (!file_exists(EMUNDUS_PATH_ABS . $student_id)) {
			mkdir(EMUNDUS_PATH_ABS . $student_id);
			chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
		}

		require_once($file);

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			application_form_pdf(!empty($student_id) ? $student_id : $user->id, $rowid[0], true);
		}
		else {
			die(JText::_('ACCESS_DENIED'));
		}

		exit();
	}

	function pdf_thesis()
	{
		$user       = $this->app->getSession()->get('emundusUser');
		$student_id = $this->input->get('user', null, 'GET', 'none', 0);
		$fnum       = $this->input->get('fnum', null, 'GET', 'none', 0);
		$rowid      = explode('-', $this->input->get('rowid', null, 'GET', 'none', 0));

		$file = JPATH_LIBRARIES . DS . 'emundus/pdf_thesis.php';

		if (!file_exists($file)) {
			die(JText::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND'));
		}
		if (!file_exists(EMUNDUS_PATH_ABS . $student_id)) {
			mkdir(EMUNDUS_PATH_ABS . $student_id);
			chmod(EMUNDUS_PATH_ABS . $student_id, 0755);
		}

		require_once($file);

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id) || EmundusHelperAccess::isApplicant($user->id)) {
			application_form_pdf(!empty($student_id) ? $student_id : $user->id, $rowid[0], true);
		}
		else {
			die(JText::_('ACCESS_DENIED'));
		}

		exit();
	}

	/*
        Delete file
    */
	function deletefile()
	{
		//@TODO ADD COMMENT ON DELETE
		$m_profile = $this->getModel('Profile');

		$student_id = $this->input->getInt('sid', null);
		$fnum       = $this->input->getString('fnum', null);
		$redirect   = $this->input->getBase64('redirect', null);
		// Redirect URL is currently only used in Hesam template of mod_emundus_application, it allows for the module to be located on a page other than index.php.

		if (empty($redirect)) {
			$redirect = 'index.php';
		}
		else {
			$redirect = base64_decode($redirect);
		}

		if (empty($fnum)) {
			$this->app->redirect($redirect);
		}

		$current_user = $this->app->getSession()->get('emundusUser');
		$m_files      = $this->getModel('Files');

        $fnumInfos = $m_files->getFnumInfos($fnum);

		if (in_array($fnum, array_keys($current_user->fnums))) {
			$user = $current_user;
			$m_files->deleteFile($fnum);
			EmundusModelLogs::log($current_user->id, (int)$fnumInfos['applicant_id'], $fnum, 1, 'd', 'COM_EMUNDUS_ACCESS_FORM_DELETE');
		}
		elseif (EmundusHelperAccess::asAccessAction(1, 'd', $current_user->id, $fnum) || EmundusHelperAccess::asAdministratorAccessLevel($current_user->id)) {
			$user = $m_profile->getEmundusUser($student_id);
		}
		else {
			$this->app->redirect($redirect);

			return false;
		}

		// track the LOGS (ATTACHMENT_DELETE)
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/logs.php');
		$user = $this->app->getSession()->get('emundusUser');     # logged user #
		EmundusModelLogs::log($current_user->id, (int)$fnumInfos['applicant_id'], $fnum, 1, 'd', 'COM_EMUNDUS_ACCESS_FILE_DELETE', '');

		unset($current_user->fnums[$fnum]);

		if (in_array($user->fnum, array_keys($user->fnums))) {
			$this->app->redirect($redirect);
		}
		else {
			array_shift($current_user->fnums);
			$this->app->redirect($redirect);
		}

		return true;
	}

	/* complete file */
	function completefile()
	{


		$m_profile = $this->getModel('Profile');

		$student_id = $this->input->get->get('sid', null);
		$fnum       = $this->input->get->getVar('fnum', null);
		$status     = $this->input->get->get('status', null);
		$redirect   = $this->input->get->getBase64('redirect', null);
		// Redirect URL is currently only used in Hesam template of mod_emundus_application, it allows for the module to be located on a page other than index.php.
		if (empty($redirect) || empty($status)) {
			$redirect = 'index.php';
		}
		else {
			$redirect = base64_decode($redirect);
		}

		if (empty($fnum)) {
			$this->app->redirect($redirect);
		}

		$current_user = $this->app->getSession()->get('emundusUser');
		$m_files      = $this->getModel('files');
		if (EmundusHelperAccess::isApplicant($current_user->id) && in_array($fnum, array_keys($current_user->fnums))) {
			$user = $current_user;
			$m_files->updateState($fnum, $status);
		}
		elseif (EmundusHelperAccess::asAccessAction(1, 'd', $current_user->id, $fnum) || EmundusHelperAccess::asAdministratorAccessLevel($current_user->id)) {
			$user = $m_profile->updateState($student_id);
		}
		else {
			$this->app->redirect($redirect);

			return false;
		}

		if (in_array($user->fnum, array_keys($user->fnums))) {
			$this->app->redirect($redirect);
		}
		else {
			array_shift($current_user->fnums);
			$this->app->redirect($redirect);
		}

		return true;
	}

	/* publish file */
	function publishfile()
	{


		$m_profile = $this->getModel('Profile');

		$student_id = $this->input->get->get('sid', null);
		$fnum       = $this->input->get->getVar('fnum', null);
		$status     = $this->input->get->get('status', null);
		$redirect   = $this->input->get->getBase64('redirect', null);
		// Redirect URL is currently only used in Hesam template of mod_emundus_application, it allows for the module to be located on a page other than index.php.

		if (empty($redirect))
			$redirect = 'index.php';
		else
			$redirect = base64_decode($redirect);

		if (empty($fnum))
			$this->app->redirect($redirect);

		$current_user = $this->app->getSession()->get('emundusUser');
		$m_files      = $this->getModel('files');

		if (EmundusHelperAccess::isApplicant($current_user->id) && in_array($fnum, array_keys($current_user->fnums))) {
			$user   = $current_user;
			$result = $m_files->updateState($fnum, $status);

		}
		elseif (EmundusHelperAccess::asAccessAction(1, 'd', $current_user->id, $fnum) ||
			EmundusHelperAccess::asAdministratorAccessLevel($current_user->id)) {
			$user = $m_profile->getEmundusUser($student_id);

		}
		else {
			$this->app->redirect($redirect);

			return false;
		}

		if (in_array($user->fnum, array_keys($user->fnums))) {
			$this->app->redirect($redirect);
		}
		else {
			array_shift($current_user->fnums);
			$this->app->redirect($redirect);
		}

		return true;

	}

	/*
        Delete document from application file
    */
	function delete()
	{
		//TODO: ADD COMMENT ON DELETE
		$eMConfig              = JComponentHelper::getParams('com_emundus');
		$copy_application_form = $eMConfig->get('copy_application_form', 0);
		$m_profile             = $this->getModel('Profile');


		$student_id      = $this->input->get->get('sid');
		$upload_id       = $this->input->get->get('uid');
		$attachment_id   = $this->input->get->get('aid');
		$duplicate       = $this->input->get->get('duplicate');
		$nb              = $this->input->get->get('nb');
		$layout          = $this->input->get->get('layout');
		$format          = $this->input->get->get('format');
		$itemid          = $this->input->get('Itemid');
		$fnum            = $this->input->get->get('fnum');
		$current_user    = $this->app->getSession()->get('emundusUser');
		$status_for_send = $eMConfig->get('status_for_send', 0);
		$chemin          = EMUNDUS_PATH_ABS;


		if (EmundusHelperAccess::isApplicant($current_user->id)) {
			$user = $current_user;
			$fnum = $user->fnum;
			$fnums = $this->_db->quote($fnum);
			$where = ' AND user_id=' . $user->id . ' AND id=' . $upload_id;
		}
		elseif (EmundusHelperAccess::asAccessAction(4, 'd', $current_user->id, $fnum) || EmundusHelperAccess::asAdministratorAccessLevel($current_user->id)) {
			$user  = $m_profile->getEmundusUser($student_id);
			$fnums = $this->_db->quote($fnum);
		}
		else {
			JError::raiseError(500, JText::_('ACCESS_DENIED'));

			return false;
		}

		JPluginHelper::importPlugin('emundus', 'sync_file');
		$this->app->triggerEvent('onDeleteFile', array(array('upload_id' => $upload_id)));

		if (isset($layout))
			$url = 'index.php?option=com_emundus&view=checklist&layout=attachments&sid=' . $user->id . '&tmpl=component&Itemid=' . $itemid;
		else
			$url = 'index.php?option=com_emundus&view=checklist&Itemid=' . $itemid;


		$query  = 'SELECT id, filename
                    FROM #__emundus_uploads
                    WHERE user_id = '.$user->id.'
                    AND fnum IN ('.$fnums.')';
		if(!empty($where)) {
			$query .= $where;
		}

		try {

			$this->_db->setQuery($query);
			$files = $this->_db->loadAssocList();

			$fileName = reset($files)['filename'];

			// call to application model
			require_once(JPATH_SITE . DS . 'components/com_emundus/models/application.php');
			$mApp          = $this->getModel('Application');
			$attachmentTpe = $mApp->getAttachmentByID($attachment_id)['value'];

			if (count($files) == 0) {
				$message = JText::_('Error : empty file');
				if ($format == 'raw') {
					echo '{"status":false, "message":"' . $message . '"}';

					return false;
				}
				else $this->setRedirect($url, $message, 'error');

			}
			else {

				try {

					$file_id = array();
					$message = '';

					foreach ($files as $file) {
						$file_id[] = $file['id'];

						if (unlink($chemin . $user->id . DS . $file['filename'])) {
							if (is_file($chemin . $user->id . DS . 'tn_' . $file['filename'])) {
								unlink($chemin . $user->id . DS . 'tn_' . $file['filename']);
							}
							$message .= '<br>' . JText::_('COM_EMUNDUS_ATTACHMENTS_DELETED') . ' : ' . $file['filename'] . '. ';

						}
						else {
							$message .= '<br>' . JText::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND') . ' : ' . $file['filename'] . '. ';
						}
					}

					$query = 'DELETE FROM #__emundus_uploads
                                WHERE id IN (' . implode(',', $file_id) . ')
                                AND user_id = ' . $user->id . '
                                AND fnum IN (' . $fnums . ')';
					$this->_db->setQuery($query);
					$this->_db->execute();

					if ($format == 'raw')
						echo '{"status":true, "message":"' . $message . '"}';
					else
						$this->setRedirect($url, $message, 'message');
				}
				catch (Exception $e) {
					$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $e->getMessage();
					Log::add($error, Log::ERROR, 'com_emundus');
					if ($format == "raw")
						echo '{"status":false,"message":"' . $error . '"}';
					else JError::raiseError(500, $e->getMessage());

					return false;
				}
			}
			# get the logged user id    $user->id
			# get the fnum              $fnum
			require_once(JPATH_SITE . DS . 'components/com_emundus/models/logs.php');
			$user = JFactory::getSession()->get('emundusUser');     # logged user #

			# get FNUM INFO
			require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
			$mFile        = $this->getModel('Files');
			$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

			// set logs
			$logsStd = new stdClass();

			// get attachment data
			$logsStd->element = "[" . $attachmentTpe . "]";
			$logsStd->details = $fileName;
			$logsParams       = array('deleted' => [$logsStd]);
			EmundusModelLogs::log($user->id, $applicant_id, $fnum, 4, 'd', 'COM_EMUNDUS_ACCESS_ATTACHMENT_DELETE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));

		}
		catch (Exception $e) {
			$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $e->getMessage();
			Log::add($error, Log::ERROR, 'com_emundus');
			if ($format == "raw")
				echo '{"aid":"0","status":false,"message":"' . $error . '" }';
			else JError::raiseError(500, $e->getMessage());

			return false;
		}

		return true;
	}

	/*
        Open application form from fnum  for applicant
    */
	function openfile()
	{

		require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');

		$session = $this->app->getSession();

		$fnum    = $this->input->get->get('fnum', null);
		$confirm = $this->input->get->get('confirm', null);

		// Redirection URL used to bring the user back to the right spot.
		$redirect = $this->input->get->getBase64('redirect', null);

		if (!empty($redirect)) {
			$redirect = base64_decode($redirect);

			if (!empty($fnum) && !str_contains($redirect, 'fnum=')) {
				$redirect .= '&fnum=' . $fnum;
			}
		}

		if (empty($fnum)) {
			$this->app->redirect($redirect);
		}

		$aid = $session->get('emundusUser');

		$m_application = $this->getModel('Application');
		$m_profile = $this->getModel('Profile');

		$infos     = $m_profile->getFnumDetails($fnum);

		$my_shared_files = $m_application->getMyFilesRequests($aid->id);
		$fnums_shared = array_map(function($item) {
			return $item->fnum;
		}, $my_shared_files);

		if ($aid->id != $infos['applicant_id'] && !in_array($fnum, $fnums_shared)) {
			return;
		}

		$m_profile->initEmundusSession($fnum);

		if (empty($redirect)) {
			if (empty($confirm)) {
				$redirect = $m_application->getFirstPage();

				if ($redirect == '/index.php') {
					$this->app->enqueueMessage(Text::_('COM_EMUNDUS_APPLICATION_CANNOT_OPEN_FILE'), 'error');
				}
			}
			else {
				$redirect = $m_application->getConfirmUrl();
			}
		}

		# get the fnum          $fnum
		# get the logged user   $aid->id
		# get FNUM INFO
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
		$mFile        = $this->getModel('Files');
		$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

		require_once(JPATH_SITE . DS . 'components/com_emundus/models/logs.php');
		EmundusModelLogs::log($this->app->getIdentity()->id, $applicant_id, $fnum, 1, 'r', 'COM_EMUNDUS_ACCESS_FILE_READ');

		$this->app->triggerEvent('onBeforeApplicantEnterApplication', ['fnum' => $fnum, 'aid' => $applicant_id, 'redirect' => $redirect]);
		$this->app->triggerEvent('onCallEventHandler', ['onBeforeApplicantEnterApplication', ['fnum' => $fnum, 'aid' => $applicant_id, 'redirect' => $redirect]]);

		$this->app->redirect(Route::_($redirect));
	}

	// *****************switch profile controller************
	function switchprofile()
	{
		include_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
		include_once(JPATH_SITE . '/components/com_emundus/models/users.php');

		$profile_fnum = $this->input->get('profnum', null);
		$redirect     = $this->input->get('redirect', null);

		$ids     = explode('.', $profile_fnum);
		$profile = $ids[0];

		$session = $this->app->getSession();
		$aid     = $session->get('emundusUser');

		$m_profile          = $this->getModel('Profile');
		$applicant_profiles = $m_profile->getApplicantsProfilesArray();
		foreach ($aid->emProfiles as $emProfile) {
			if ($emProfile->id == $profile) {

				if (in_array($profile, $applicant_profiles)) {
					$fnum = $ids[1];
					if ($fnum !== "") {
						$infos = $m_profile->getFnumDetails($fnum);

						$profile     = $m_profile->getProfileByCampaign($infos['campaign_id']);
						$campaign    = $m_profile->getCampaignById($infos['campaign_id']);
						$application = $m_profile->getFnumDetails($fnum);

						if ($aid->id != $infos['applicant_id'])
							return;

						$aid->profile                = $profile['profile_id'];
						$aid->profile_label          = $profile['label'];
						$aid->menutype               = $profile['menutype'];
						$aid->start_date             = $profile['start_date'];
						$aid->end_date               = $profile['end_date'];
						$aid->candidature_posted     = $infos['submitted'];
						$aid->candidature_incomplete = $infos['status'] == 0 ? 1 : 0;
						$aid->schoolyear             = $campaign['year'];
						$aid->code                   = $campaign['training'];
						$aid->campaign_id            = $infos['campaign_id'];
						$aid->campaign_name          = $campaign['label'];
						$aid->fnum                   = $fnum;
						$aid->university_id          = null;
						$aid->applicant              = 1;
						$aid->status                 = $application['status'];
					}
					else {
						$aid->profile       = $profile;
						$aid->fnum          = $ids[1];
						$profiles           = $m_profile->getProfileById($profile);
						$aid->applicant     = 1;
						$aid->profile_label = $profiles["label"];
						$aid->menutype      = $profiles["menutype"];
					}
				}
				else {
					if (isset($aid->start_date))
						unset($aid->start_date);
					if (isset($aid->end_date))
						unset($aid->end_date);
					if (isset($aid->candidature_posted))
						unset($aid->candidature_posted);
					if (isset($aid->candidature_incomplete))
						unset($aid->candidature_incomplete);
					if (isset($aid->schoolyear))
						unset($aid->schoolyear);
					if (isset($aid->code))
						unset($aid->code);
					if (isset($aid->campaign_id))
						unset($aid->campaign_id);
					if (isset($aid->campaign_name))
						unset($aid->campaign_name);
					if (isset($aid->fnum))
						unset($aid->fnum);
					if (isset($aid->status))
						unset($aid->status);
					if (isset($aid->fnums))
						unset($aid->fnums);

					$aid->profile = $profile;

					$profiles = $m_profile->getProfileById($profile);

					$aid->profile_label = $profiles["label"];
					$aid->menutype      = $profiles["menutype"];
					$aid->applicant     = 0;
				}
			}
		}
		$session->set('emundusUser', $aid);

		if (!empty($redirect)) {
			$this->app->redirect($redirect);
		}
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	function upload()
	{
		$eMConfig              = ComponentHelper::getParams('com_emundus');
		$copy_application_form = $eMConfig->get('copy_application_form', 0);
		$can_submit_encrypted  = $eMConfig->get('can_submit_encrypted', 1);

		require_once(JPATH_ROOT . '/components/com_emundus' . '/helpers/checklist.php');
		require_once(JPATH_ROOT . '/components/com_emundus' . '/helpers/date.php');
		require_once(JPATH_ROOT . '/components/com_emundus' . '/helpers/export.php');
		$h_checklist = new EmundusHelperChecklist;
		$h_date      = new EmundusHelperDate;

		$m_application = $this->getModel('Application');
		$m_checklist   = $this->getModel('Checklist');
		$m_profile     = $this->getModel('Profile');
		$m_files       = $this->getModel('Files');

		$query_updating_file = null;

		$student_id = $this->input->get->get('sid', null);
		$duplicate  = $this->input->get->get('duplicate', null);
		$layout     = $this->input->get->get('layout', null);
		$format     = $this->input->get->get('format', null);
		$itemid     = $this->input->get('Itemid', null);
		$fnum       = $this->input->get->get('fnum', null);

		$fnums        = array();
		$current_user = $this->app->getSession()->get('emundusUser');

		if (EmundusHelperAccess::isApplicant($current_user->id)) {
			$user = $current_user;
			$fnum = $user->fnum;

			if ($copy_application_form == 1 && $duplicate == 1) {
				$fnums = array_keys($user->fnums);
			}
			else {
				$fnums[] = $fnum;
			}

			$query_updating_file = $this->_db->getQuery(true);

			$query_updating_file->update($this->_db->quoteName('#__emundus_campaign_candidature'))
				->set($this->_db->quoteName('updated') . ' = ' . $this->_db->quote(date('Y-m-d H:i:s')))
				->set($this->_db->quoteName('updated_by') . ' = ' . $this->_user->id)
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));

		}
		elseif (EmundusHelperAccess::asAccessAction(4, 'c', $current_user->id, $fnum) || EmundusHelperAccess::asAdministratorAccessLevel($current_user->id)) {
			$user    = $m_profile->getEmundusUser($student_id);
			$fnums[] = $fnum;
		}
		else {
			return false;
		}

		$chemin       = EMUNDUS_PATH_ABS;
		$post         = $this->input->getArray();
		$attachments  = $post['attachment'];
		$descriptions = $post['description'];

		if (isset($post['required_desc']) && $post['required_desc'] == 1 && empty(trim($descriptions))) {
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> empty description', Log::ERROR, 'com_emundus');
			$errorInfo = JText::_("COM_EMUNDUS_ERROR_DESCRIPTION_REQUIRED");

			if ($format == "raw") {
				echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
			}
			else {
				$this->app->enqueueMessage($errorInfo . "\n", 'error');
			}
			$this->setRedirect('index.php?option=com_emundus&view=checklist&Itemid=' . $itemid);

			return false;
		}

		$labels = $post['label'];

		if (!empty($_FILES)) {
			$files = array($_FILES["file"]);
		}
		else {
			$error = Uri::getInstance() . ' :: USER ID : ' . $user->id;
			Log::add($error, Log::ERROR, 'com_emundus');

			if ($format == "raw") {
				echo '{"aid":"0","status":false,"message":"' . $error . ' -> empty $_FILES" }';
			}

			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_ATTACHMENTS_ERROR_FILE_TOO_BIG'), 'error');
			$this->setRedirect('index.php?option=com_emundus&view=checklist&Itemid=' . $itemid);

			return false;
		}

		$query = '';
		$nb    = 0;

		if (!file_exists(EMUNDUS_PATH_ABS . $user->id)) {
			// An error would occur when the index.html file was missing, the 'Unable to create user file' error appeared yet the folder was created.
			if (!file_exists(EMUNDUS_PATH_ABS . 'index.html')) {
				touch(EMUNDUS_PATH_ABS . 'index.html');
			}

			if (!mkdir(EMUNDUS_PATH_ABS . $user->id) || !copy(EMUNDUS_PATH_ABS . 'index.html', EMUNDUS_PATH_ABS . $user->id . DS . 'index.html')) {
				$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> Unable to create user file';
				Log::add($error, Log::ERROR, 'com_emundus');

				if ($format == "raw") {
					echo '{"aid":"0","status":false,"message":"' . $error . '" }';
				}
				else {
					JError::raiseWarning(500, 'Unable to create user file');
				}

				return false;
			}
		}
		chmod(EMUNDUS_PATH_ABS . $user->id, 0755);

		if (isset($layout)) {
			$url = 'index.php?option=com_emundus&view=checklist&layout=attachments&sid=' . $user->id . '&tmpl=component&Itemid=' . $itemid . '#a' . $attachments;
		}
		else {
			$url = 'index.php?option=com_emundus&view=checklist&Itemid=' . $itemid . '#a' . $attachments;
		}

		if (!class_exists('HtmlSanitizerSingleton')) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}
		$htmlSanitizer = HtmlSanitizerSingleton::getInstance();
		foreach ($fnums as $fnum) {
			foreach ($files as $key => $file) {
				$files[$key]['name'] = $htmlSanitizer->sanitize($file['name']);
				$local_filename = $file['name'];

				$pageCount = 0;
				if (empty($file['name'])) {
					$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> try to upload empty file';
					Log::add($error, Log::ERROR, 'com_emundus');
					$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_EMPTYFILE");

					if ($format == "raw") {
						echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
					}
					else {
						$this->app->enqueueMessage($errorInfo . "\n", 'error');
					}

					return false;
				}

				try {
					$query_ext = 'SELECT UPPER(allowed_types) as allowed_types, nbmax, min_pages_pdf, max_pages_pdf, max_filesize FROM #__emundus_setup_attachments WHERE id = '.(int)$attachments;
					$this->_db->setQuery($query_ext);
					$attachment = $this->_db->loadAssoc();

					try {
						$query_cpt = 'SELECT count(id) FROM #__emundus_uploads WHERE user_id=' . $user->id . ' AND attachment_id=' . (int) $attachments . ' AND fnum like ' . $this->_db->Quote($fnum);
						$this->_db->setQuery($query_cpt);
						$cpt = $this->_db->loadResult();

						if ($cpt >= $attachment['nbmax']) {
							$error = JText::_('COM_EMUNDUS_ATTACHMENTS_MAX_ALLOWED') . $attachment['nbmax'];
							if ($format == "raw") {
								Log::add($error, Log::ERROR, 'com_emundus.errors');
							}
							else {
								$this->app->enqueueMessage($error, 'error');
								$this->setRedirect($url);
							}

							continue;
						}

						if (!empty($attachment['max_filesize'])) {
							$bytes = $attachment['max_filesize'] * 1024 * 1024;

							if ($file['size'] > $bytes) {
								$error = JText::_('COM_EMUNDUS_ATTACHMENTS_ERROR_FILE_TOO_BIG');

								if ($format == "raw") {
									echo '{"aid":"0","status":false,"message":"'.$error.'" }';
								} else {
									JFactory::getApplication()->enqueueMessage($error, 'error');
									$this->setRedirect($url);
								}

								return false;
							}
						}
					}
					catch (Exception $e) {
						$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $e->getMessage();
						Log::add($error, Log::ERROR, 'com_emundus');
						$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_SQL");

						if ($format == "raw") {
							echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
						}
						else {
							$this->app->enqueueMessage($errorInfo, 'error');
							$this->setRedirect($url);
						}

						continue;
					}
				}
				catch (Exception $e) {
					$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $e->getMessage();
					Log::add($error, Log::ERROR, 'com_emundus');
					$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_SQL");

					if ($format == "raw") {
						echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
					}
					else {
						$this->app->enqueueMessage($errorInfo, 'error');
						$this->setRedirect($url);
					}

					continue;
				}

				$file_array = explode('.', $file['name']);
				$file_ext   = end($file_array);
				$pos        = strpos($attachment['allowed_types'], strtoupper($file_ext));

				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mtype = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);

				if (!empty($mtype)) {
					if($mtype == 'application/zip') {
						// Check if the file is a zip file, check if the file type is application/x-zip-compressed for windows users
						if($file['type'] !== $mtype && $file['type'] !== 'application/x-zip-compressed') {
							$pos = false;
						}
					}
					elseif($file['type'] == 'application/msword' || $file['type'] == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
						// Check if the file is a zip file, check if the file type is application/x-zip-compressed for windows users
						if($file['type'] !== $mtype && !in_array($file['type'],['application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
							$pos = false;
						}
					}
					elseif($file['type'] !== $mtype) {
						$pos = false;
					}
				}

				if ($pos === false) {
					$error     = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' ' . $file_ext . ' -> type is not allowed, please send a doc with type : ' . $attachment['allowed_types'];
					$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_FILETYPE");

					if ($format == "raw") {
						echo '{"aid":"0","status":false,"message":"' . $errorInfo . $attachment['allowed_types'] . '" }';
					}
					else {
						$this->app->enqueueMessage($errorInfo . $attachment['allowed_types'], 'error');
						$this->setRedirect($url);
					}

					return false;
				}

				// If svg we have to sanitize it
				if($mtype == 'image/svg+xml') {
					$sanitizer = new Sanitizer();

					$svg_file = file_get_contents($file['tmp_name']);
					$cleaned_svg = $sanitizer->sanitize($svg_file);

					file_put_contents($file['tmp_name'], $cleaned_svg);
				}

				// Remove exif data from jpeg files
				if($mtype == 'image/jpeg') {
					$img = imagecreatefromjpeg($file['tmp_name']);
					imagejpeg($img, $file['tmp_name'], 100);
					imagedestroy($img);
				}

				//size > 0
				if (($file['size']) == 0) {
					$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> size is not allowed, please check out your filesize : ' . $file['size'];
					Log::add($error, Log::ERROR, 'com_emundus');
					$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_FILESIZE");

					if ($format == "raw") {
						echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
					}
					else {
						$this->app->enqueueMessage($errorInfo, 'error');
						$this->setRedirect($url);
					}

					return false;
				}

				// If encrypted pdf files are not allowed
				if ($can_submit_encrypted == 0 && strtoupper($file_ext) === "PDF") {
					// Check if file is readable
					if (!is_readable($file['tmp_name'])) {
						$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> this file cannot be opened, please check if it is corrupt';
						Log::add($error, Log::ERROR, 'com_emundus');
						$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_UNREADABLE");


						if ($format == "raw") {
							echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
						}
						else {
							$this->app->enqueueMessage($errorInfo, 'error');
							$this->setRedirect($url);
						}

						return false;
					}

					// Encrpyted pdf files are readable but require a password to be opened, this checks for this use-case
					if (EmundusHelperExport::isEncrypted($file['tmp_name'])) {
						$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> encrypted pdf files are not allowed, please remove protection and try again';
						Log::add($error, Log::ERROR, 'com_emundus');
						$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_ENCRYPTED");

						if ($format == "raw") {
							echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
						}
						else {
							$this->app->enqueueMessage($errorInfo, 'error');
							$this->setRedirect($url);
						}

						return false;
					}
				}

				// Check if pdf and if a max or min number of pages is defined
				if (($attachment['min_pages_pdf'] > 0 || $attachment['max_pages_pdf'] > 0) && strtoupper($file_ext) === "PDF") {
					require_once(JPATH_LIBRARIES . DS . 'emundus/fpdi.php');

					$pdf = new Fpdi();

					$pageCount = $pdf->setSourceFile($file['tmp_name']);

					if ($attachment['min_pages_pdf'] > 0 && $pageCount < $attachment['min_pages_pdf']) {
						$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> encrypted pdf files are not allowed, please remove protection and try again';
						Log::add($error, Log::ERROR, 'com_emundus');
						$errorInfo  = JText::_("COM_EMUNDUS_ATTACHMENTS_ERROR_MIN_PAGES_PDF");
						$errorInfo2 = JText::_("COM_EMUNDUS_ATTACHMENTS_PAGES");

						if ($format == "raw") {
							echo '{"aid":"0","status":false,"message":"' . $errorInfo . $attachment['min_pages_pdf'] . $errorInfo2 . '" }';
						}
						else {
							$this->app->enqueueMessage($errorInfo . $attachment['min_pages_pdf'] . $errorInfo2, 'error');
							$this->setRedirect($url);
						}

						return false;
					}

					if ($attachment['max_pages_pdf'] > 0 && $pageCount > $attachment['max_pages_pdf']) {
						$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> encrypted pdf files are not allowed, please remove protection and try again';
						Log::add($error, Log::ERROR, 'com_emundus');
						$errorInfo  = JText::_("COM_EMUNDUS_ATTACHMENTS_ERROR_MAX_PAGES_PDF");
						$errorInfo2 = JText::_("COM_EMUNDUS_ATTACHMENTS_PAGES");

						if ($format == "raw") {
							echo '{"aid":"0","status":false,"message":"' . $errorInfo . $attachment['max_pages_pdf'] . $errorInfo2 . '" }';
						}
						else {
							$this->app->enqueueMessage($errorInfo . $attachment['max_pages_pdf'] . $errorInfo2, 'error');
							$this->setRedirect($url);
						}

						return false;
					}
				}

				if (!empty($file['error'])) {

					switch ($file['error']) {
						case 1:
							$error     = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> file error type : ' . JText::_("File ") . $file['name'] . JText::_(" is bigger than the authorized size!");
							$errorInfo = JText::_("FILE") . $file['name'] . JText::_("COM_EMUNDUS_ERROR_INFO_MAX_ALLOWED_SIZE");
							$this->app->enqueueMessage($errorInfo, 'error');
							break;
						case 2:
							$error     = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> file error type : ' . JText::_("File ") . $file['name'] . JText::_(" is too big!\n");
							$errorInfo = JText::_("FILE") . $file['name'] . JText::_("COM_EMUNDUS_ERROR_INFO_TOO_BIG");
							$this->app->enqueueMessage($errorInfo, 'error');
							break;
						case 3:
							$error     = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> file error type : ' . JText::_("File ") . $file['name'] . JText::_(" upload has been interrupted.\n");
							$errorInfo = JText::_("FILE") . $file['name'] . JText::_("COM_EMUNDUS_ERROR_INFO_INTERRUPTED");
							$this->app->enqueueMessage($errorInfo, 'error');
							break;
						case 4:
							$error     = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> file error type : ' . JText::_("File ") . $file['name'] . JText::_(" is not correct.\n");
							$errorInfo = JText::_("FILE") . $file['name'] . JText::_("COM_EMUNDUS_ERROR_INFO_INCORRECT");
							$this->app->enqueueMessage($errorInfo, 'error');
							break;
						default:
					}

					Log::add($error, Log::ERROR, 'com_emundus');
					if ($format == "raw") {
						echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
					}
					else {
						$this->app->enqueueMessage($errorInfo, 'error');
						$this->setRedirect($url);
					}

					return false;

				}
				elseif (isset($file['name']) && $file['error'] == UPLOAD_ERR_OK) {
					$fnumInfos = $m_files->getFnumInfos($fnum);
					$paths     = $h_checklist->setAttachmentName($file['name'], $labels, $fnumInfos);

					if (copy($file['tmp_name'], $chemin . $user->id . DS . $paths)) {
						$can_be_deleted = $post['can_be_deleted_' . $attachments] != '' ? $post['can_be_deleted_' . $attachments] : $this->input->get('can_be_deleted', 1, 'POST');
						$can_be_viewed  = $post['can_be_viewed_' . $attachments] != '' ? $post['can_be_viewed_' . $attachments] : $this->input->get('can_be_viewed', 1, 'POST');

						$now = EmundusHelperDate::getNow();

						$query .= '(' . $user->id . ', ' . $attachments . ', \'' . $paths . '\', ' . $this->_db->Quote($descriptions) . ', ' . $can_be_deleted . ', ' . $can_be_viewed . ', ' . $fnumInfos['id'] . ', ' . $this->_db->Quote($fnum) . ', ' . $pageCount . ', ' . $this->_db->quote($local_filename) . ', ' . $this->_db->quote($now) . ', ' . $this->_db->quote($now) . ', ' . $this->_db->quote($file['size']) . '),';
						$nb++;
					}
					else {
						$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> Cannot move file : ' . $file['tmp_name'] . ' to ' . $chemin . $user->id . DS . $paths;
						Log::add($error, Log::ERROR, 'com_emundus');
						$errorInfo = JText::_("COM_EMUNDUS_ERROR_CANNOT_MOVE") . $file['name'];

						if ($format == "raw") {
							echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
						}
						else {
							$this->app->enqueueMessage($errorInfo, 'error');
							$this->setRedirect($url);
						}

						return false;
					}

					if ($labels == "_photo") {

						$checkdouble_query = 'SELECT count(user_id)
                        FROM #__emundus_uploads
                        WHERE attachment_id=
                                (SELECT id
                                    FROM #__emundus_setup_attachments
                                    WHERE lbl like "_photo"
                                )
                                AND user_id=' . $user->id . '
                                AND fnum like ' . $this->_db->Quote($fnum);

						try {
							$this->_db->setQuery($checkdouble_query);
							$cpt = $this->_db->loadResult();
						}
						catch (Exception $e) {
							$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $e->getMessage();
							Log::add($error, Log::ERROR, 'com_emundus');
							$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_SQL");

							if ($format == "raw") {
								echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
							}
							else {
								$this->app->enqueueMessage($errorInfo, 'error');
								$this->setRedirect($url);
							}

							return false;
						}

						if ($cpt) {
							$query = '';

							return false;
						}
						else {
							$pathToThumbs = EMUNDUS_PATH_ABS . $user->id . DS . $paths;
							$file_src     = EMUNDUS_PATH_ABS . $user->id . DS . $paths;
							//$img = imagecreatefromjpeg(EMUNDUS_PATH_ABS.$user->id.DS.$paths);
							list($w_src, $h_src, $type) = getimagesize($file_src);  // create new dimensions, keeping aspect ratio
							//$ratio = $w_src/$h_src;
							//if ($w_dst/$h_dst > $ratio) {$w_dst = floor($h_dst*$ratio);} else {$h_dst = floor($w_dst/$ratio);}

							switch ($type) {
								case 1:   //   gif -> jpg
									$img = imagecreatefromgif($file_src);
									break;
								case 2:   //   jpeg -> jpg
									$img = imagecreatefromjpeg($file_src);
									break;
								case 3:  //   png -> jpg
									$img = imagecreatefrompng($file_src);
									break;
								default:
									$img = imagecreatefromjpeg($file_src);
									break;
							}
							//$width = imagesx( $img );
							//$height = imagesy( $img );
							$new_width  = 200;
							$new_height = floor($h_src * ($new_width / $w_src));
							$tmp_img    = imagecreatetruecolor($new_width, $new_height);
							imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $w_src, $h_src);
							imagejpeg($tmp_img, $chemin . $user->id . DS . 'tn_' . $paths);
							$user->avatar = $paths;
						}
					}
				}
			}
		}

		/// resize image
		$_upload_file_type = $file['type'];

		if (strpos($_upload_file_type, 'image') !== false) {
			$file_src = EMUNDUS_PATH_ABS . $user->id . DS . $paths;
			list($w_src, $h_src, $type) = getimagesize($file_src);

			// get min_resolution, max_resolution from jos_emundus_setup_attachments (param::attachments)
			$image_resolution_query = "SELECT min_width,max_width,min_height,max_height FROM #__emundus_setup_attachments WHERE #__emundus_setup_attachments.id = " . (int) $attachments;
			$this->_db->setQuery($image_resolution_query);
			$image_resolution = $this->_db->loadObject();

			if ((!empty($image_resolution->max_width) && !empty($image_resolution->max_height)) && ($w_src * $h_src > (int) $image_resolution->max_width * (int) $image_resolution->max_height)) {

				if ($w_src > $h_src) {
					$ratio = $h_src / $w_src;

					$new_width  = max((int) $image_resolution->max_width, (int) $image_resolution->max_height);
					$new_height = round($new_width * $ratio);

				}
				else if ($w_src < $h_src) {
					$ratio = $w_src / $h_src;

					$new_height = max((int) $image_resolution->max_width, (int) $image_resolution->max_height);
					$new_width  = round($new_height * $ratio);

				}
				else {
					$new_height = min((int) $image_resolution->max_width, (int) $image_resolution->max_height);
					$new_width  = min((int) $image_resolution->max_width, (int) $image_resolution->max_height);
				}

				switch ($type) {
					case 1:   // gif
						$original_img = imagecreatefromgif($file_src);
						break;
					case 2: // jpeg
						$original_img = imagecreatefromjpeg($file_src);
						break;
					case 3: // png
						$original_img = imagecreatefrompng($file_src);
						break;
					default:    // jpg
						$original_img = imagecreatefromjpeg($file_src);
						break;
				}

				/* $new_width = (int)$image_resolution->max_width;
                $new_height = (int)$image_resolution->max_height; */

				$resized_img = imagecreatetruecolor($new_width, $new_height);

				// copy resample
				imagecopyresampled($resized_img, $original_img, 0, 0, 0, 0, $new_width, $new_height, $w_src, $h_src);

				// export new image to jpeg
				imagejpeg($resized_img, $chemin . $user->id . DS . 'tn_' . $paths);

				/// remove old image only if resize was successful
				if ($resized_img !== false) {
					unlink($file_src);
				}

				/// change name the resize image
				rename($chemin . $user->id . DS . 'tn_' . $paths, $file_src);
			}
			else if ((!empty($image_resolution->min_width) && !empty($image_resolution->min_height)) && ($w_src * $h_src < (int) $image_resolution->min_width * (int) $image_resolution->min_height)) {
				$errorInfo = "COM_EMUNDUS_ERROR_IMAGE_TOO_SMALL";
				echo '{"aid":"0","status":false,"message":"' . JText::_('COM_EMUNDUS_ERROR_IMAGE_TOO_SMALL') . " " . (int) $image_resolution->min_width . 'px x ' . (int) $image_resolution->min_height . 'px' . '"}';
				unlink($file_src);          /// remove uploaded file

				return false;
			}
		}

		// delete temp uploaded file
		unlink($file['tmp_name']);

		if (!empty($query)) {
			$query = 'INSERT INTO #__emundus_uploads (user_id, attachment_id, filename, description, can_be_deleted, can_be_viewed, campaign_id, fnum, pdf_pages_count, local_filename, timedate, modified, size)
                        VALUES ' . substr($query, 0, -1);

			try {
				$this->_db->setQuery($query);
				$this->_db->execute();
				$id = $this->_db->insertid();

				if (!empty($query_updating_file)) {
					$this->_db->setQuery($query_updating_file);
					$this->_db->execute();
				}

				// TODO: onAfterAttachmentUpload event appeared after onAfterUploadFile creation on this branch. move treatment to use onAfterAttachmentUpload

				JPluginHelper::importPlugin('emundus', 'sync_file');
				$this->app->triggerEvent('onAfterUploadFile', [['upload_id' => $id]]);


				$this->app->triggerEvent('onAfterAttachmentUpload', [$fnum, (int) $attachments, $paths]);
				$this->app->triggerEvent('onCallEventHandler', ['onAfterAttachmentUpload', ['fnum' => $fnum, 'attachment_id' => (int) $attachments, 'file' => $paths]]);

				if ($format == "raw") {
					echo '{"id":"' . $id . '","status":true, "message":"' . JText::_('COM_EMUNDUS_ACTIONS_DELETE') . '"}';
				}
				else {
					$this->app->enqueueMessage($nb . ($nb > 1 ? ' ' . JText::_("FILES_BEEN_UPLOADED") : ' ' . JText::_("FILE_BEEN_UPLOADED")), 'message');
					$this->setRedirect($url);
				}
			}
			catch (Exception $e) {
				$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $e->getMessage();
				Log::add($error, Log::ERROR, 'com_emundus');
				$errorInfo = JText::_("COM_EMUNDUS_ERROR_INFO_SQL");

				if ($format == "raw") {
					echo '{"aid":"0","status":false,"message":"' . $errorInfo . '" }';
				}
				else {
					$this->app->enqueueMessage($errorInfo, 'error');
					$this->setRedirect($url);
				}
			}
		}

		require_once(JPATH_SITE . DS . 'components/com_emundus/models/logs.php');
		$user = JFactory::getSession()->get('emundusUser');     # looged user #

		require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
		$mFile        = $this->getModel('Files');
		$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

		// stock the attachments name //
		$logsStd = new stdClass();

		/* get attachment type */
		$attachmentTpe = $m_application->getAttachmentByID($attachments)['value'];

		$logsStd->element = '[' . $attachmentTpe . ']';
		$logsStd->details = $_FILES['file']['name'];

		// stock all logs into an array
		$logsParams = array('created' => [$logsStd]);

		EmundusModelLogs::log($user->id, $applicant_id, $fnum, 4, 'c', 'COM_EMUNDUS_ACCESS_ATTACHMENT_CREATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));

		return true;
	}

	/***********************************
	 ** Update profile for Applicants
	 ***********************************/
	function updateprofile()
	{
		$user = $this->app->getSession()->get('emundusUser');
		if (!EmundusHelperAccess::isAdministrator($user->id) && !EmundusHelperAccess::isCoordinator($user->id)) {
			$this->setRedirect(JRoute::_('index.php'), JText::_('Only administrator can access this function.'), 'error');

			return;
		}
		$attachment_id          = $this->input->post->get('aid', array(), 'array');
		$attachment_selected    = $this->input->post->get('as', array(), 'array');
		$attachment_displayed   = $this->input->post->get('ad', array(), 'array');
		$attachment_required    = $this->input->post->get('ar', array(), 'array');
		$attachment_bank_needed = $this->input->post->get('ab', array(), 'array');
		$profile_id             = $this->input->post->getInt('pid', 0);
		if ($profile_id != $this->input->getInt('rowid', 0) || !is_numeric($profile_id) || floor($profile_id) != $profile_id || $profile_id <= 0) {
			$this->setRedirect('index.php', 'Error', 'error');

			return;
		}

		$attachments = array();
		if (!empty($attachment_selected)) {

			foreach ($attachment_id as $id) {
				$a                = new stdClass();
				$a->selected      = in_array($id, $attachment_selected);
				$a->displayed     = in_array($id, $attachment_displayed);
				$a->required      = in_array($id, $attachment_required);
				$a->bank_needed   = in_array($id, $attachment_bank_needed);
				$attachments[$id] = $a;
				unset($a);
			}

		}


		// ATTACHMENTS
		$query = $this->_db->getQuery(true);
		$query->delete($this->_db->quoteName('#__emundus_setup_attachment_profiles'))->where($this->_db->quoteName('profile_id') . ' = ' . $profile_id);
		$this->_db->setQuery($query);
		$this->_db->execute();

		if (isset($attachments)) {
			foreach ($attachments as $id => $attachment) {
				if($attachment->selected) {
					$inserted = [
						'profile_id'    => $profile_id,
						'attachment_id' => $id,
						'displayed'     => $attachment->displayed ? 1 : 0,
						'mandatory'     => $attachment->required ? 1 : 0,
						'bank_needed'   => $attachment->bank_needed ? 1 : 0,
						'ordering'      => 0,
					];
					$inserted = (object) $inserted;
					$this->_db->insertObject('#__emundus_setup_attachment_profiles', $inserted);
				}
			}
		}
// FORMS
		$Itemid = $this->input->get('Itemid', null, 'POST', 'none', 0);
		$this->setRedirect('index.php?option=com_emundus&view=profile&rowid=' . $profile_id . '&Itemid=' . $Itemid, '', '');
	}


	/**
	 * Get application form elements to display in CSV file
	 */
	function send_elements_csv()
	{
		$view = $this->input->get('v', null, 'GET');

		// Starting a session.
		$session      = $this->app->getSession();
		$cid          = $session->get('uid');
		$quick_search = $session->get('quick_search');
		$user         = $session->get('emundusUser');

		$menu   = $this->app->getMenu()->getActive();
		$access = !empty($menu) ? $menu->access : 0;
		if (!EmundusHelperAccess::isAllowedAccessLevel($user->id, $access)) {
			die(JText::_('ACCESS_DENIED'));
		}

		require_once(JPATH_ROOT . '/libraries/emundus/export_csv/csv_' . $view . '.php');
		$elements = $this->input->get('ud', null, 'POST', 'array', 0);

		export_csv($cid, $elements);
	}

	function transfert_view($reqids = array())
	{

		$view = $this->input->get('v', null, 'GET');

		$profile       = $this->input->get('profile', null, 'POST', 'none', 0);
		$finalgrade    = $this->input->get('finalgrade', null, 'POST', 'none', 0);
		$quick_search  = $this->input->get('s', null, 'POST', 'none', 0);
		$gid           = $this->input->get('groups', null, 'POST', 'none', 0);
		$evaluator     = $this->input->get('user', null, 'POST', 'none', 0);
		$engaged       = $this->input->get('engaged', null, 'POST', 'none', 0);
		$schoolyears   = $this->input->get('schoolyears', null, 'POST', 'none', 0);
		$itemid        = $this->input->get('Itemid', null, 'GET', 'none', 0);
		$miss_doc      = $this->input->get('missing_doc', null, 'POST', 'none', 0);
		$search        = $this->input->get('elements', null, 'POST', 'array', 0);
		$search_values = $this->input->get('elements_values', null, 'POST', 'array', 0);
		$comments      = $this->input->get('comments', null, 'POST', 'none', 0);
		$complete      = $this->input->get('complete', null, 'POST', 'none', 0);
		$validate      = $this->input->get('validate', null, 'POST', 'none', 0);
		$cid           = $this->input->get('ud', null, 'POST', 'array', 0);


		// Starting a session.
		$session = $this->app->getSession();
		if ($cid) {
			$session->set('uid', $cid);
		}
		if ($profile) {
			$session->set('profile', $profile);
		}
		if ($finalgrade) {
			$session->set('finalgrade', $finalgrade);
		}
		if ($quick_search) {
			$session->set('quick_search', $quick_search);
		}
		if ($gid) {
			$session->set('groups', $gid);
		}
		if ($evaluator) {
			$session->set('evaluator', $evaluator);
		}
		if ($engaged) {
			$session->set('engaged', $engaged);
		}
		if ($schoolyears) {
			$session->set('schoolyears', $schoolyears);
		}
		if ($miss_doc) {
			$session->set('missing_doc', $miss_doc);
		}
		if ($search) {
			$session->set('s_elements', $search);
		}
		if ($search_values) {
			$session->set('s_elements_values', $search_values);
		}
		if ($comments) {
			$session->set('comments', $comments);
		}
		if ($complete) {
			$session->set('complete', $complete);
		}
		if ($validate) {
			$session->set('validate', $validate);
		}

		$this->setRedirect('index.php?option=com_emundus&view=export_select_columns&v=' . $view . '&Itemid=' . $itemid);
	}

	function get_mime_type($filename)
	{
		$mime_types = array(
			'txt'  => 'text/plain',
			'htm'  => 'text/html',
			'html' => 'text/html',
			'php'  => 'text/html',
			'css'  => 'text/css',
			'js'   => 'application/javascript',
			'json' => 'application/json',
			'xml'  => 'application/xml',
			'swf'  => 'application/x-shockwave-flash',
			'flv'  => 'video/x-flv',

			// images
			'png'  => 'image/png',
			'jpe'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'ico'  => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff',
			'svg'  => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip'  => 'application/zip',
			'rar'  => 'application/x-rar-compressed',
			'exe'  => 'application/x-msdownload',
			'msi'  => 'application/x-msdownload',
			'cab'  => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3'  => 'audio/mpeg',
			'qt'   => 'video/quicktime',
			'mov'  => 'video/quicktime',

			// adobe
			'pdf'  => 'application/pdf',
			'psd'  => 'image/vnd.adobe.photoshop',
			'ai'   => 'application/postscript',
			'eps'  => 'application/postscript',
			'ps'   => 'application/postscript',

			// ms office
			'doc'  => 'application/msword',
			'rtf'  => 'application/rtf',
			'xls'  => 'application/vnd.ms-excel',
			'ppt'  => 'application/vnd.ms-powerpoint',

			// open office
			'odt'  => 'application/vnd.oasis.opendocument.text',
			'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$exploded_filename = explode('.', $filename);
		$ext               = strtolower(array_pop($exploded_filename));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}
		elseif (function_exists('finfo_open')) {
			$finfo    = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);

			return $mimetype;
		}
		else return 'application/octet-stream';
	}

	/**
	 * Check if user can or not open PDF file
	 */
	function getfile()
	{

		// Get the filename and user ID from the URL.

		$url = $this->input->get->get('u', null, 'RAW');

		$eMConfig             = JComponentHelper::getParams('com_emundus');
		$applicant_files_path = $eMConfig->get('applicant_files_path', 'images/emundus/files/');
		if (strpos($url, $applicant_files_path) !== 0 && strpos($url, 'tmp/') !== 0) {
			die (JText::_('ACCESS_DENIED'));
		}

		$urltab = explode('/', $url);

		// Split the URL into different parts.
		$cpt = count($urltab);
		$uid = (int) $urltab[$cpt - 2];
		if (empty($uid)) {
			// Manage subdirectories
			$uid = (int) $urltab[$cpt - 3];
		}
		$file = $urltab[$cpt - 1];

		$current_user = $this->app->getSession()->get('emundusUser');

		$fnum = '';
		if($current_user->id == $uid && !empty($current_user->fnum)) {
			$fnum = $current_user->fnum;
		}
		$fnums = [];
		if (!empty($current_user->fnums)) {
			$fnums = array_keys($current_user->fnums);
		}


		// This query checks if the file can actually be viewed by the user, in the case a file uploaded to his file by a coordniator is opened.
		if (!empty(JFactory::getUser($uid)->id)) {
            $query = 'SELECT can_be_viewed, fnum, local_filename FROM #__emundus_uploads';

			if (EmundusHelperAccess::isApplicant($current_user->id) && !empty($fnums)) {
				$query .= " WHERE fnum IN (" . implode(',', $this->_db->quote($fnums)) . ')';
				$query .= " AND filename like " . $this->_db->Quote($file);
			}
			else {
				if (!empty($fnum)) {
					$query .= " WHERE fnum like " . $this->_db->quote($fnum);
				}
				else {
					$query .= " WHERE user_id = " . $uid;
				}
				// TODO: adapt for all cases, KIT needs OR filename (ROAD-918)
				$query .= " AND filename like " . $this->_db->Quote($file);
			}
			$this->_db->setQuery($query);
			$fileInfo = $this->_db->loadObject();

			$first_part_of_filename = explode('_', $file)[0];
			if (empty($fileInfo) && is_numeric($first_part_of_filename) && strlen($first_part_of_filename) === 28) {
				$fileInfo                = new stdClass();
				$fileInfo->fnum          = $first_part_of_filename;
				$fileInfo->can_be_viewed = 1;
			}
		}

		// Check if the user is an applicant and it is his file.
		if (!EmundusHelperAccess::isFnumMine($fnum, $current_user->id) && !EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) {
			if ($fileInfo->can_be_viewed != 1 && !empty($fileInfo)) {
				die (JText::_('ACCESS_DENIED'));
			}
		}
		// If the user has the rights to open attachments, or to create a PDF export (he needs to be able to open it, even if he can't access the documents).
		elseif (!empty($fileInfo) && (!EmundusHelperAccess::asAccessAction(4, 'r', $current_user->id, $fileInfo->fnum) && !EmundusHelperAccess::asAccessAction(8, 'c', $current_user->id, $fileInfo->fnum))) {
			die (JText::_('ACCESS_DENIED'));
		}
		elseif (empty($fileInfo) && (!EmundusHelperAccess::asAccessAction(4, 'r') && !EmundusHelperAccess::asAccessAction(8, 'c'))) {
			die (JText::_('ACCESS_DENIED'));
		}

		// Otherwise, open the file if it exists.
		$file = JPATH_BASE . DS . $url;
		if (is_file($file)) {
			$mime_type = $this->get_mime_type($file);
            $fileName = $file;

            if (!empty($fileInfo->local_filename)) {
                $keep_original_file_name = $eMConfig->get('keep_original_file_name', 0);

                if ($keep_original_file_name) {
                    $fileName = $fileInfo->local_filename;
                }
            }

			//TODO If data ara anonimized remove metadata
			header('Content-type: ' . $mime_type);
			header('Content-Disposition: inline; filename=' . basename($fileName));
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Pragma: anytextexeptno-cache', true);
			header('Cache-control: private');
			header('Expires: 0');

			ob_clean();
			ob_end_flush();
			readfile($file);
			exit;
		}
		else {
			JError::raiseWarning(500, JText::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND') . ' ' . $url);
		}
	}

	/**
	 * Check if referent can or not open PDF file
	 */
	function getfilereferent()
	{

		// Get the filename and user ID from the URL.

		$url = $this->input->get->get('u', null, 'RAW');

		$eMConfig             = JComponentHelper::getParams('com_emundus');
		$applicant_files_path = $eMConfig->get('applicant_files_path', 'images/emundus/files/');
		if (strpos($url, $applicant_files_path) !== 0 && strpos($url, 'tmp/') !== 0) {
			die (JText::_('ACCESS_DENIED'));
		}

		$urltab = explode('/', $url);

		// Split the URL into different parts.
		$cpt = count($urltab);
		$uid = (int) $urltab[$cpt - 2];
		if (empty($uid)) {
			// Manage subdirectories
			$uid = (int) $urltab[$cpt - 3];
		}
		$file = $urltab[$cpt - 1];

		// Check if there is an awaiting file request with this keyid and fnum from less than 6 months ago
		$keyid = $this->input->get('keyid', null);
		if (!empty($keyid)) {
			$fnum = $this->input->get->get('fnum', null);
			if (!empty($fnum)) {
				// Can't use helper date here because we need to get now - 6 months
				$now      = new DateTime();
				$now      = $now->setTimezone(new DateTimeZone('UTC'));
				$deadline = $now->sub(new DateInterval('P6M'));
				$deadline = $deadline->format('Y-m-d H:i:s');


				$query = $this->_db->getQuery(true);

				$query->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__emundus_files_request'))
					->where($this->_db->quoteName('keyid') . ' LIKE ' . $this->_db->quote($keyid))
					->andWhere($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum))
					->andWhere($this->_db->quoteName('uploaded') . ' = 0')
					->andWhere($this->_db->quoteName('time_date') . ' > ' . $this->_db->quote($deadline));
				$this->_db->setQuery($query);
				$fileRequest = $this->_db->loadResult();
			}
			else {
				die (JText::_('ACCESS_DENIED'));
			}
		}
		else {
			die (JText::_('ACCESS_DENIED'));
		}

		if (!empty($fileRequest)) {
			// If there is an open file request, open the file
			$file = JPATH_BASE . DS . $url;
			if (is_file($file)) {
				$mime_type = $this->get_mime_type($file);

				if ($mime_type === 'application/pdf') {

					require_once(JPATH_LIBRARIES . DS . 'emundus/fpdi.php');
					$pdf = new ConcatPdf();
					$pdf->setFiles([$file]);
					$pdf->concat();
					$pdf->Output();
					exit;

				}
				else {
					header('Content-type: ' . $mime_type);
					header('Content-Disposition: inline; filename=' . basename($file));
					header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
					header('Cache-Control: no-store, no-cache, must-revalidate');
					header('Cache-Control: pre-check=0, post-check=0, max-age=0');
					header('Pragma: anytextexeptno-cache', true);
					header('Cache-control: private');
					header('Expires: 0');

					ob_clean();
					ob_end_flush();
					readfile($file);
					exit;
				}
			}
			else {
				JError::raiseWarning(500, JText::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND') . ' ' . $url);
			}
		}
		else {
			die (JText::_('ACCESS_DENIED'));
		}
	}


	function sendmail_applicant()
	{
		$itemid      = $this->input->get('Itemid', null, 'GET', 'none', 0);
		$sid         = $this->input->get('mail_to', null, 'POST', 'INT', 0);
		$campaign_id = $this->input->get('campaign_id', null, 'POST', 'INT', 0);
		$m_emails    = $this->getModel('emails');
		$email       = $m_emails->sendmail();

		$m_campaign = $this->getModel('campaign');
		$email      = $m_campaign->setResultLetterSent($sid, $campaign_id);


		$this->setRedirect('index.php?option=com_emundus&view=application&Itemid=' . $itemid . '&sid=' . $sid . '&tmpl=component');
	}

	function sendmail_expert()
	{
		$response = array('status' => false, 'msg' => JText::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {

			$itemid = $this->input->getInt('Itemid', null);
			$sid    = $this->input->getInt('sid', null);
			$fnum   = $this->input->getString('fnum', null);


			if (!empty($fnum)) {
				$m_emails = $this->getModel('Emails');
				$email    = $m_emails->sendMail('expert', $fnum);

				$response['status'] = true;
				$response['msg']    = JText::_('SUCCESS');
			}
			else {
				$response['msg'] = JText::_('MISSING_PARAMS');
			}
		}

		echo json_encode($response);
		exit;
	}

	/*
    ** @description Validate / Unvalidate a column from table (used in administrative validation). Ajax request
    ** @return string HTML to display in page for action block indexed by user ID.
    */
	function ajax_validation()
	{
		$user = JFactory::getSession()->get('emundusUser');
		if (!EmundusHelperAccess::isAdministrator($user->id) && !EmundusHelperAccess::isCoordinator($user->id)) {
			die(JText::_('ACCESS_DENIED'));
		}
		$uid      = $this->input->get('uid', null, 'GET', 'INT');
		$validate = $this->input->get('validate', null, 'GET', 'INT');
		$cible    = $this->input->get('cible', null, 'GET', 'CMD');
		$data     = explode('.', $cible);

		if (count($data) > 3) {
			$and = ' AND `campaign_id`=' . $data[3];
		}
		else {
			$and = '';
		}
		if ($data[0] == "jos_emundus_final_grade") {
			$column = "student_id";
		}
		else {
			$column = 'user';
		}

		if (!empty($uid) && is_numeric($uid)) {
			$value = abs((int) $validate - 1);
			$query = 'UPDATE `' . $data[0] . '` SET `' . $data[1] . '`=' . $this->_db->Quote($value) . ' WHERE `' . $column . '` = ' . $this->_db->Quote((int) $uid) . $and;
			$this->_db->setQuery($query);
			$this->_db->execute();
			if ($value > 0) {
				$img = 'tick.png';
				$btn = 'unvalidate|' . $uid;
				$alt = JText::_('COM_EMUNDUS_FORMS_VALIDATED') . '::' . JText::_('COM_EMUNDUS_FORMS_VALIDATED_NOTE');
			}
			else {
				$img = 'publish_x.png';
				$btn = 'validate|' . $uid;
				$alt = JText::_('COM_EMUNDUS_FORMS_UNVALIDATED') . '::' . JText::_('COM_EMUNDUS_FORMS_UNVALIDATED_NOTE');
			}
			echo '<span class="hasTip" title="' . $alt . '">
                    <input type="image" src="media/com_emundus/images/icones/' . $img . '" onclick="validation(' . $uid . ', \'' . $value . '\', \'' . $cible . '\');" ></span> ';
		}
		else {
			echo JText::_('ERROR');
		}
	}

	// export_fiche_synthese
	public function export_fiche_synthese()
	{
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		$user = JFactory::getSession()->get('emundusUser');


		/// get valid fnum
		$fnums_post  = $this->input->getRaw('checkInput');
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		$validFnums = array();
		foreach ($fnums_array as $fnum) {
			if (EmundusHelperAccess::asAccessAction(35, 'c', $user->id, $fnum) && $fnum != 'em-check-all-all' && $fnum != 'em-check-all')
				$validFnums[] = $fnum;
		}

		require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
		$m_files   = $this->getModel('Files');
		$fnumsInfo = $m_files->getFnumsInfos($validFnums);

		/// set params
		$file      = $this->input->getRaw('file', null);
		$totalfile = count($validFnums);
		$model     = $this->input->getRaw('model', null);
		$start     = 0;
		$limit     = 2;

		$forms = 0;

		/// from model --> get all model params
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/export.php');
		$h_files      = new EmundusHelperFiles;
		$export_model = $h_files->getExportPdfFilterById($model);

		// from export_model --> build fiche de synthese
		$pdf_elements = array();

		$profiles    = json_decode($export_model->constraints)->pdffilter->profiles;           /// type Array
		$formulaires = json_decode($export_model->constraints)->pdffilter->tables;
		$groups      = json_decode($export_model->constraints)->pdffilter->groups;
		$elements    = json_decode($export_model->constraints)->pdffilter->elements;

		// attachments
		$attachments = json_decode($export_model->constraints)->pdffilter->attachments;

		/// is_assessment, is_admission, is_decision
		$assessment = json_decode($export_model->constraints)->pdffilter->assessment;
		$admission  = json_decode($export_model->constraints)->pdffilter->admission;
		$decision   = json_decode($export_model->constraints)->pdffilter->decision;

		if (empty($profiles) and empty($formulaires) and empty($groups) and empty($elements)) {
			$forms = 0;
		}
		else {
			$forms = 1;
		}

		$options = json_decode($export_model->constraints)->pdffilter->headers;

		foreach ($profiles as $key => $value) {
			$pdf_elements[$value] = array('fids' => $formulaires, 'gids' => $groups, 'eids' => $elements);
		}

		/// from pdf elements --> build pdf
		$files = JPATH_LIBRARIES . DS . 'emundus/pdf.php';

		if (!function_exists('application_form_pdf')) {
			require_once($files);
		}

		//// pour chaque fnum --> appeler la fonction helpers/export.php/buildFormPDF
		///
		if (file_exists(JPATH_ROOT . '/tmp' . DS . $files)) {
			$files_list = array(JPATH_ROOT . '/tmp' . DS . $files);
		}
		else {
			$files_list = array();
		}

		for ($i = $start; $i <= $totalfile; $i++) {
			$fnum = $validFnums[$i];

			if (is_numeric($fnum) && !empty($fnum)) {
				require_once(JPATH_SITE . DS . 'components/com_emundus/models/profile.php');
				$m_profile   = $this->getModel('Profile');
				$infos       = $m_profile->getFnumDetails($fnum);
				$campaign_id = $infos['campaign_id'];

				/// build pdf for forms
				if (!empty($pdf_elements) and array_keys($pdf_elements)[0] != "") {
					$files_list[] = EmundusHelperExport::buildFormPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $forms, null, $options, null, $pdf_elements);
				}

				/// build pdf for attachments
				if (!empty($attachments) and $attachments[0] != "") {
					$tmpArray = array();
					require_once(JPATH_SITE . DS . 'components/com_emundus/models/application.php');
					$m_application        = $this->getModel('Application');
					$attachment_to_export = array();
					foreach ($attachments as $key => $aids) {
						$detail = explode("|", $aids);
						if ((!empty($detail[1]) && $detail[1] == $fnumsInfo[$fnum]['training']) && ($detail[2] == $fnumsInfo[$fnum]['campaign_id'] || $detail[2] == "0")) {
							$attachment_to_export[] = $detail[0];
						}
					}
					if ($attachments || !empty($attachment_to_export)) {
						$files = $m_application->getAttachmentsByFnum($fnum, null, $attachment_to_export);
						if ($options[0] != "0") {
							$files_list[] = EmundusHelperExport::buildHeaderPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $options);
						}
						$files_export = EmundusHelperExport::getAttachmentPDF($files_list, $tmpArray, $files, $fnumsInfo[$fnum]['applicant_id']);
					}
				}

				if ($assessment == 1)
					$files_list[] = EmundusHelperExport::getEvalPDF($fnum, $options);
				if ($decision == 1)
					$files_list[] = EmundusHelperExport::getDecisionPDF($fnum, $options);
				if ($admission == 1)
					$files_list[] = EmundusHelperExport::getAdmissionPDF($fnum, $options);

				if (array_keys($pdf_elements)[0] == "" and $attachments[0] == "" and ($assessment != 1) and ($decision != 1) and ($admission != 1) and ($options[0] != "0")) {
					$files_list[] = EmundusHelperExport::buildHeaderPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $options);
				}
			}
		}

		if (count($files_list) > 0) {
			require_once(JPATH_LIBRARIES . DS . 'emundus/fpdi.php');

			$pdf = new ConcatPdf();

			$pdf->setFiles($files_list);

			$pdf->concat();

			if (isset($tmpArray)) {
				foreach ($tmpArray as $fn) {
					unlink($fn);
				}
			}
			$pdf->Output(JPATH_ROOT . '/tmp' . DS . $file, 'F');

			$result = array('status' => true, 'file' => $file, 'msg' => JText::_('COM_EMUNDUS_EXPORTS_FILES_ADDED'), 'path' => Uri::base());
		}
		else {
			$result = array('status' => false, 'msg' => JText::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND'));
		}

		echo json_encode((object) $result);
		exit();
	}

	/**
	 * unregisterevent
	 *
	 * @return void
	 */
	function unregisterevent()
	{


		$fnum = $this->input->get('fnum', null);

		require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
		include_once(JPATH_SITE . DS . 'components/com_emundus/controllers/messages.php');
		$m_files    = $this->getModel('Files');
		$m_emails   = $this->getModel('Emails');
		$c_messages = new EmundusControllerMessages();

		$query = $this->_db->getQuery(true);

		if (in_array($fnum, array_keys($this->_user->fnums))) {
			$user = $this->_user;

			$query->select('cc.eb_registration,sc.event,sc.training,sc.label')
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->_db->quoteName('sc.id') . ' = ' . $this->_db->quoteName('cc.campaign_id'))
				->where($this->_db->quoteName('cc.fnum') . ' = ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);
			$registration = $this->_db->loadObject();

			$query->clear()
				->delete('#__eb_registrants')
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($registration->eb_registration));
			$this->_db->setQuery($query);
			$this->_db->execute();

			$m_files->updateState((array) $fnum, 3);
			$m_emails->sendEmailTrigger(3, (array) $registration->training, '0,1', $this->_user);

			$query->clear()
				->select('u.email,u.id')
				->from($this->_db->quoteName('#__emundus_configuration_activites_repeat_eb_activities', 'car'))
				->leftJoin($this->_db->quoteName('#__emundus_configuration_activites', 'ca') . ' ON ' . $this->_db->quoteName('ca.id') . ' = ' . $this->_db->quoteName('car.parent_id'))
				->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('ca.eb_referent'))
				->where($this->_db->quoteName('car.eb_activities') . ' = ' . $this->_db->quote($registration->event));
			$this->_db->setQuery($query);
			$referent_email = $this->_db->loadObject();

			if (!empty($referent_email)) {
				$post = array(
					'CAMPAIGN_LABEL' => $registration->label
				);
				$c_messages->sendEmailNoFnum($referent_email->email, 82, $post, $referent_email->id);
			}
		}
		else {
			JError::raiseError(500, JText::_('ACCESS_DENIED'));
			echo 'false';
		}

		unset($this->_user->fnums[$fnum]);

		if (in_array($user->fnum, array_keys($user->fnums))) {
			echo 'true';
		}
		else {
			array_shift($this->_user->fnums);
			echo 'true';
		}

		exit();
	}
}
