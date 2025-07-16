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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;

/**
 * Emundus Application Controller
 * @package     Emundus
 */
class EmundusControllerApplication extends BaseController
{
	/**
	 * User object.
	 *
	 * @var \Joomla\CMS\User\User|JUser|mixed|null
	 * @since version 1.0.0
	 */
	private $_user;

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

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');

		$this->_user = $this->app->getIdentity();
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @return  EmundusControllerApplication  This object to support chaining.
	 *
	 * @since   1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view')) {
			$default = 'application_form';
			$this->input->set('view', $default);
		}

		parent::display();

		return $this;
	}

	/**
	 * Delete applicant attachments
	 *
	 * @since version 1.0.0
	 */
	public function delete_attachments()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			die(Text::_("ACCESS_DENIED"));
		}

		$attachments = $this->input->get('attachments', null, 'POST', 'array', 0);
		$user_id     = $this->input->get('sid', null, 'POST');
		$view        = $this->input->get('view', null, 'POST');
		$tmpl        = $this->input->get('tmpl', null, 'POST');

		$url = !empty($tmpl) ? 'index.php?option=com_emundus&view=' . $view . '&sid=' . $user_id . '&tmpl=' . $tmpl . '#attachments' : 'index.php?option=com_emundus&view=' . $view . '&sid=' . $user_id . '&tmpl=component#attachments';
		ArrayHelper::toInteger($attachments, 0);

		if (count($attachments) == 0) {
			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_NO_ITEMS_SELECTED'), 'error');
			exit;
		}

		$m_application = $this->getModel('Application');

		foreach ($attachments as $id) {
			$upload     = $m_application->getUploadByID($id);
			$attachment = $m_application->getAttachmentByID($upload['attachment_id']);

			if (EmundusHelperAccess::asAccessAction(4, 'd', $this->_user->id, $upload['fnum'])) {
				$result = $m_application->deleteAttachment($id);

				if ($result != 1) {
					echo Text::_('ATTACHMENT_DELETE_ERROR') . ' : ' . $attachment['value'] . ' : ' . $upload['filename'];
				}
				else {
					$file = EMUNDUS_PATH_ABS . $user_id . DS . $upload['filename'];
					unlink($file);

					$row['applicant_id'] = $upload['user_id'];
					$row['user_id']      = $this->_user->id;
					$row['reason']       = Text::_('COM_EMUNDUS_ATTACHMENTS_DELETED');
					$row['comment_body'] = $attachment['value'] . ' : ' . $upload['filename'];
					$m_application->addComment($row);

					echo $result;
				}
			}
			else {
				echo Text::_('ACCESS_DENIED') . ' : ' . $attachment['value'] . ' : ' . $upload['filename'];
			}
		}

		$this->setRedirect($url, Text::_('DONE'), 'message');

		return;
	}

	/**
	 * Delete an applicant attachment by id (one by one)
	 *
	 * @since version 1.0.0
	 */
	public function delete_attachment()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			die(Text::_("ACCESS_DENIED"));
		}

		$id = $this->input->get('id', null, 'GET');

		$m_application = $this->getModel('Application');

		$upload     = $m_application->getUploadByID($id);
		$attachment = $m_application->getAttachmentByID($upload['attachment_id']);

		if (EmundusHelperAccess::asAccessAction(4, 'd', $this->_user->id, $upload['fnum'])) {
			$result = $m_application->deleteAttachment($id);

			if ($result != 1) {
				echo Text::_('ATTACHMENT_DELETE_ERROR');
			}
			else {
				$row['applicant_id'] = $upload['user_id'];
				$row['user_id']      = $this->_user->id;
				$row['reason']       = Text::_('COM_EMUNDUS_ATTACHMENTS_DELETED');
				$row['comment_body'] = $attachment['value'] . ' : ' . $upload['filename'];
				$m_application->addComment($row);

				echo($result);
			}
		}
		else {
			echo Text::_('ACCESS_DENIED') . ' : ' . $attachment['value'] . ' : ' . $upload['filename'];
		}

	}

	/**
	 * Upload an applicant attachment (one by one)
	 *
	 * @since version 1.0.0
	 */
	public function upload_attachment()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			die(Text::_("ACCESS_DENIED"));
		}

		$aid            = $this->input->get('attachment_id', null, 'POST');
		$uid            = $this->input->get('uid', null, 'POST');
		$filename       = $this->input->get('filename', null, 'POST');
		$campaign_id    = $this->input->get('campaign_id', null, 'POST');
		$can_be_viewed  = $this->input->get('can_be_viewed', null, 'POST');
		$can_be_deleted = $this->input->get('can_be_deleted', null, 'POST');

		$targetFolder = EMUNDUS_PATH_ABS . $uid;

		if (!empty($_FILES)) {
			$msg  = "";
			$data = "{";
			switch ($_FILES['filename']['error']) {

				case 0:
					$msg        .= Text::_("FILE_UPLOADED");
					$data       .= '"message":"' . $msg . '",';
					$tempFile   = $_FILES['filename']['tmp_name'];
					$targetPath = $targetFolder;

					// Validate the file type
					$fileTypes = array('jpg', 'jpeg', 'gif', 'png', 'pdf', 'doc', 'docx', 'odt', 'zip'); // File extensions
					$fileParts = pathinfo($_FILES['filename']['name']);

					if (in_array($fileParts['extension'], $fileTypes)) {
						$m_application   = $this->getModel('Application');
						$type_attachment = $m_application->getAttachmentByID($aid);

						$filename   = date('Y-m-d_H-i-s') . $type_attachment['lbl'] . '_' . $_FILES['filename']['name'];
						$fileURL    = EMUNDUS_PATH_REL . $uid . '/' . $filename;
						$targetFile = rtrim($targetPath, '/') . DS . $filename;

						move_uploaded_file($tempFile, $targetFile);

						$filesize = $_FILES['filename']['size'];

						$attachment["key"]   = array("user_id", "attachment_id", "filename", "description", "can_be_deleted", "can_be_viewed", "campaign_id");
						$attachment["value"] = array($uid, $aid, '"' . $filename . '"', '"' . date('Y-m-d H:i:s') . '"', $can_be_deleted, $can_be_viewed, $campaign_id);

						$id = $m_application->uploadAttachment($attachment);
					}
					else {
						$msg .= Text::_('COM_EMUNDUS_ATTACHMENTS_FILETYPE_INVALIDE');
					}

					$data .= '"message":"' . $msg . '",';
					$data .= '"url":"' . htmlentities($fileURL) . '",';
					$data .= '"id":"' . $id . '",';
					$data .= '"filesize":"' . $filesize . '",';
					$data .= '"name":"' . $type_attachment['value'] . '",';
					$data .= '"filename":"' . $filename . '",';
					$data .= '"path":"' . str_replace("\\", "\\\\", $targetPath) . '",';
					$data .= '"aid":"' . $aid . '",';
					$data .= '"uid":"' . $uid . '"';
					break;

				case 1:
					$msg  .= "The file is bigger than this PHP installation allows";
					$data .= '"message":"' . $msg . '"';
					break;

				case 2:
					$msg  .= "The file is bigger than this form allows";
					$data .= '"message":"' . $msg . '"';
					break;

				case 3:
					$msg  .= "Only part of the file was uploaded";
					$data .= '"message":"' . $msg . '"';
					break;

				case 4:
					$msg  .= "No file was uploaded";
					$data .= '"message":"' . $msg . '"';
					break;

				case 6:
					$msg  .= "Missing a temporary folder";
					$data .= '"message":"' . $msg . '"';
					break;

				case 7:
					$msg  .= "Failed to write file to disk";
					$data .= '"message":"' . $msg . '"';
					break;

				case 8:
					$msg  .= "File upload stopped by extension";
					$data .= '"message":"' . $msg . '"';
					break;

				default:
					$msg  .= "Unknown error " . $_FILES['filename']['error'];
					$data .= '"message":"' . $msg . '",';
					break;
			}
			$data .= "}";
			echo $data;
		}
	}

	/**
	 * Edit a comment
	 *
	 * @since version 1.0.0
	 */
	public function editcomment()
	{
		$comment_id    = $this->input->get('id', null);
		$comment_title = $this->input->get('title', null, 'string');
		$comment_text  = $this->input->get('text', null, 'string');

		$m_application = $this->getModel('Application');

		$comment = $m_application->getComment($comment_id);

		$uid = $comment['user_id'];

		if ($uid == $this->_user->id && EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $comment['fnum'])) {
			$result = $m_application->editComment($comment_id, $comment_title, $comment_text);

			if ($result)
				$msg = Text::_('COM_EMUNDUS_COMMENTS_COMMENT_EDITED');
			else
				$msg = Text::_('COM_EMUNDUS_ERROR_COMMENT_EDIT');

			$tab = array('status' => $result, 'msg' => $msg);
		}
		else {
			if (EmundusHelperAccess::asAccessAction(10, 'u', $this->_user->id, $comment['fnum'])) {
				$result = $m_application->editComment($comment_id, $comment_title, $comment_text);

				if ($result)
					$msg = Text::_('COM_EMUNDUS_COMMENTS_COMMENT_EDITED');
				else
					$msg = Text::_('COM_EMUNDUS_ERROR_COMMENT_EDIT');

				$tab = array('status' => $result, 'msg' => $msg);
			}
			else {
				$tab = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));
			}
		}


		echo json_encode((object) $tab);
		exit;
	}


	/**
	 * Delete a comment
	 *
	 * @since version 1.0.0
	 */
	public function deletecomment()
	{
		$comment_id = $this->input->get('comment_id', null, 'GET');

		$m_application = $this->getModel('Application');

		$comment = $m_application->getComment($comment_id);

		$uid = $comment['user_id'];

		if ($uid == $this->_user->id && EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $comment['fnum'])) {
			$result = $m_application->deleteComment($comment_id, $comment['fnum']);
			$tab    = array('status' => $result, 'msg' => Text::_('COM_EMUNDUS_COMMENTS_DELETED'));

		}
		else {
			if (EmundusHelperAccess::asAccessAction(10, 'd', $this->_user->id, $comment['fnum'])) {
				$result = $m_application->deleteComment($comment_id, $comment['fnum']);
				$tab    = array('status' => $result, 'msg' => Text::_('COM_EMUNDUS_COMMENTS_DELETED'));

			}
			else {
				$tab = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));

			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Delete an application tag
	 *
	 * @since version 1.0.0
	 */
	public function deletetag()
	{
		$response = array('status' => 0, 'msg' => Text::_('TAG_DELETE_ERROR'));

		if (empty($this->_user->id)) {
			$response['msg'] = Text::_('ACCESS_DENIED');
		}
		else {

			$id_tag = $this->input->getInt('id_tag', 0);
			$fnum   = $this->input->getString('fnum', '');

			if (!empty($fnum) && $id_tag > 0) {
				$m_application = $this->getModel('Application');
				$m_files       = $this->getModel('Files');

				$tags = $m_files->getTagsByIdFnumUser($id_tag, $fnum, $this->_user->id);

				if (EmundusHelperAccess::asAccessAction(14, 'd', $this->_user->id, $fnum)) {
					$result = $m_application->deleteTag($id_tag, $fnum);

					if ($result == 1 || $result) {
						$response = array('status' => $result, 'msg' => Text::_('COM_EMUNDUS_TAGS_DELETED'));
					}
				}
				else if ($tags) {
					$result = $m_application->deleteTag($id_tag, $fnum, $this->_user->id);

					if ($result == 1 || $result) {
						$response = array('status' => $result, 'msg' => Text::_('COM_EMUNDUS_TAGS_DELETED'));
					}
				}
				else {
					$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Delete training
	 *
	 * @since version 1.0.0
	 */
	public function deletetraining()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			die(Text::_("ACCESS_DENIED"));
		}

		$id    = $this->input->get('id', null, 'GET');
		$sid   = $this->input->get('sid', null, 'GET');
		$table = $this->input->get('t', null, 'GET');

		$m_application = $this->getModel('Application');
		$result        = $m_application->deleteData($id, $table);

		$row['applicant_id'] = $sid;
		$row['user_id']      = $this->_user->id;
		$row['reason']       = Text::_('COM_EMUNDUS_APPLICATION_DATA_DELETED');
		$row['comment_body'] = Text::_('COM_EMUNDUS_APPLICATION_LINE') . ' ' . $id . ' ' . Text::_('COM_EMUNDUS_APPLICATION_FROM') . ' ' . $table;
		$m_application->addComment($row);

		echo $result;
	}

	/**
	 * Get menus availables for an application file
	 *
	 * @since version 1.0.0
	 */
	public function getapplicationmenu()
	{
		$response = ['status' => false];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			die(Text::_('ACCESS_DENIED'));
		}

		$fnum = $this->input->get('fnum', null, 'STRING');
		$res           = false;

		if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
			$m_application = $this->getModel('Application');
			$menus         = $m_application->getApplicationMenu($this->_user->id, $fnum);
			$ccid = EmundusHelperFiles::getIdFromFnum($fnum);

			if ($menus !== false) {
				$res              = true;
				$menu_application = array();
				$i                = 0;

				foreach ($menus as $k => $menu) {
					$access             = false;
					$actions_for_access = explode(',', $menu['note']);

					foreach ($actions_for_access as $action_for_access) {
						$action    = explode('|', $action_for_access);
						$action_id = $action[0];

						if (EmundusHelperAccess::asAccessAction($action[0], $action[1], $this->_user->id, $fnum)) {
							$access = true;
							break;
						}
					}

					if ($access) {
						if ($action_id == 36) {
							$messenger = $this->getModel('Messenger');

							$notifications = $messenger->getNotificationsByFnum($fnum, $this->_user->id);
							if ($notifications > 0) {
								$menu['notifications'] = $notifications;
							}
						}
						if ($action_id == 10) {
							$m_comments                = $this->getModel('Comments');
							$notifications_comments = sizeof($m_comments->getComments($ccid, $this->_user->id, false, [], 0, 1));
							$menu['notifications']  = $notifications_comments;
						}

						$menu_application[] = $menu;
						if ((intval($menu['rgt']) - intval($menu['lft'])) == 1) {
							$menu_application[$i++]['hasSons'] = false;
						}
						else {
							$menu_application[$i++]['hasSons'] = true;
						}
					}

				}
			}
			$response = array('status' => $res, 'menus' => $menu_application);
		}
		else {
			$response['msg'] = Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS');
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Delete attachments by their ids and for a specific fnum
	 *
	 * @since version 1.0.0
	 */
	public function deleteattachement()
	{

		$fnum = $this->input->getString('fnum', null);
		$ids  = $this->input->getString('ids', null);
		$ids  = json_decode(stripslashes($ids));
		$res  = new stdClass();

		if (EmundusHelperAccess::asAccessAction(4, 'd', $this->_user->id, $fnum)) {
			$m_application = $this->getModel('Application');
			foreach ($ids as $id) {
				$m_application->deleteAttachment($id);
			}

			require_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
			$user = $this->app->getSession()->get('emundusUser');     # logged user #

			require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
			$mFile        = new EmundusModelFiles();

			$res->status = true;
		}
		else {
			$res->status = false;
			$res->msg    = Text::_("ACCESS_DENIED");
		}
		echo json_encode($res);
		exit();
	}

	/**
	 * Export an application file to PDF
	 *
	 * @since version 1.0.0
	 */
	public function exportpdf()
	{

		$fnum = $this->input->post->getString('fnum', null);

		if (!empty($fnum) && EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $fnum)) {
			$ids              = $this->input->post->getString('ids', null);
			$sid              = $this->input->post->getInt('student_id', null);
			$form_post        = $this->input->post->getVar('forms', null);
			$attachments_only = $this->input->post->getBool('attachments_only', false);

			$m_form    = $this->getModel('Form');
			$m_profile = $this->getModel('Profile');
			$m_files   = $this->getModel('Files');

			$fnumInfos = $m_files->getFnumInfos($fnum);
			$profile   = $m_profile->getProfileByCampaign($fnumInfos['campaign_id']);

			if (!$attachments_only) {
				if (empty($form_post)) {
					$form_post = array();

					$forms = $m_form->getFormsByProfileId($profile['profile_id']);
					foreach ($forms as $form) {
						if (!in_array($form->id, $form_post)) {
							$form_post[] = $form->id;
						}
					}
				}
			}

			if (empty($ids)) {
				$ids           = array();
				$m_application = $this->getModel('Application');

				$profile                = $m_profile->getProfileByCampaign($fnumInfos['campaign_id']);
				$attachments_by_profile = $m_form->getDocumentsByProfile($profile['profile_id']);
				$aids_allowed           = array();
				foreach ($attachments_by_profile as $attachment) {
					$aids_allowed[] = $attachment->attachment_id;
				}
				$attachments = $m_application->getAttachmentsByFnum($fnum);
				foreach ($attachments as $attachment) {
					if (in_array($attachment->attachment_id, $aids_allowed)) {
						$ids[] = $attachment->id;
					}
				}
			}

			$exports  = array();
			$tmpArray = array();

			if ($form_post) {
				$fnumInfos = $m_files->getFnumInfos($fnum);

				$exports[] = EmundusHelperExport::buildFormPDF($fnumInfos, $sid, $fnum, 1);
			}

			$m_application = $this->getModel('Application');
			$files         = $m_application->getAttachments($ids);

			$isNotOnlyApplicantionForms = EmundusHelperExport::getAttachmentPDF($exports, $tmpArray, $files, $sid);


			if (!$isNotOnlyApplicantionForms) {
				$res         = new stdClass();
				$res->status = false;
				$res->msg    = Text::_('COM_EMUNDUS_EXPORTS_CANNOT_EXPORT_FILETYPE');
			}
			elseif (!empty($exports)) {
				// TODO: Replace FPDI by Gotenberg
				require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'fpdi.php');
				require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');
				$pdf = new ConcatPdf();
				$pdf->setFiles($exports);
				$pdf->concat();
				foreach ($tmpArray as $fn) {
					unlink($fn);
				}
				$pdf->Output(EMUNDUS_PATH_ABS . $sid . DS . $fnum . '_attachments.pdf', 'F');
				$res         = new stdClass();
				$res->status = true;
				$res->link   = Uri::base() . EMUNDUS_PATH_REL . $sid . '/' . $fnum . '_attachments.pdf';

			}
			else {
				$res         = new stdClass();
				$res->status = false;
				$res->msg    = Text::_('COM_EMUNDUS_ATTACHMENTS_FILES_NOT_FOUND_IN_SERVER');
			}
		}
		else {
			$res         = new stdClass();
			$res->status = false;
			$res->msg    = Text::_('ACCESS_DENIED');
		}

		echo json_encode($res);
		exit();
	}

	/**
	 * Update access for an application file
	 *
	 * @since version 1.0.0
	 */
	public function updateaccess()
	{
		$fnum = $this->input->getString('fnum', null);

		if (EmundusHelperAccess::asAccessAction(11, 'u', $this->_user->id, $fnum)) {
			$state         = $this->input->getInt('state', null);
			$accessid      = explode('-', $this->input->getString('access_id', null));
			$type          = $this->input->getString('type', null);
			$m_application = $this->getModel('Application');
			$res           = new stdClass();
			if ($type == 'groups') {
				$res->status = $m_application->updateGroupAccess($fnum, $accessid[0], $accessid[1], $accessid[2], $state);
			}
			else {
				$res->status = $m_application->updateUserAccess($fnum, $accessid[0], $accessid[1], $accessid[2], $state);
			}
			echo json_encode($res);
			exit();
		}
		else {
			$res         = new stdClass();
			$res->status = false;
			$res->msg    = Text::_('YOU_ARE_NOT_ALLOWED_TO_DO_THAT');
			echo json_encode($res);
			exit();
		}
	}

	/**
	 * Remove access for an application file
	 *
	 * @since version 1.0.0
	 */
	public function deleteaccess()
	{
		$fnum = $this->input->getString('fnum', null);

		if (EmundusHelperAccess::asAccessAction(11, 'd', $this->_user->id, $fnum)) {
			$id            = $this->input->getString('id', null);
			$type          = $this->input->getString('type', null);
			$m_application = $this->getModel('Application');
			$res           = new stdClass();
			if ($type == 'groups') {
				$res->status = $m_application->deleteGroupAccess($fnum, $id);
			}
			else {
				$res->status = $m_application->deleteUserAccess($fnum, $id);
			}
			echo json_encode($res);
			exit();
		}
		else {
			$res         = new stdClass();
			$res->status = false;
			$res->msg    = Text::_('YOU_ARE_NOT_ALLOWED_TO_DO_THAT');
			echo (object) json_encode(array($res));
			exit();
		}
	}

	/**
	 * Update validation state of an attachment for an application file
	 *
	 * @since version 1.0.0
	 */
	public function attachment_validation()
	{
		$fnum          = $this->input->getString('fnum', null);
		$att_id        = $this->input->getInt('att_id', null);
		$state         = $this->input->getVar('state', null);
		$m_application = $this->getModel('Application');
		$res           = new stdClass();

		if (EmundusHelperAccess::asAccessAction(4, 'c', $this->_user->id, $fnum)) {
			$res->status = $m_application->attachment_validation($att_id, $state);
			echo json_encode($res);
			exit();
		}
		else {
			$res->msg = Text::_('YOU_ARE_NOT_ALLOWED_TO_DO_THAT');
			exit();
		}
	}

	/////////////////////////////////////////////////////////////
	/* used by VueJS com_emundus Attachments component */

	/**
	 * Get attachments for a specific fnum
	 *
	 * @since version 1.0.0
	 */
	public function getattachmentsbyfnum()
	{
		$response = ['msg' => Text::_('ACCESS_DENIED'), 'status' => false, 'code' => 403];
		$fnum     = $this->input->getString('fnum', '');

		if (!empty($fnum)) {
			if (EmundusHelperAccess::asAccessAction(4, 'r', $this->_user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->_user->id,$fnum)) {
				$m_application = $this->getModel('Application');
				$euser = $this->app->getSession()->get('emundusUser');

				if (!class_exists('EmundusModelFiles')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
				}

				$m_files = new EmundusModelFiles();
				$fnumInfos = $m_files->getFnumInfos($fnum);

				$response['attachments'] = $m_application->getUserAttachmentsByFnum($fnum,'',$fnumInfos['profile_id'],$euser->applicant == 1,$this->_user->id);
				$response['msg']         = Text::_('SUCCESS');
				$response['status']      = true;
				$response['code']        = 200;
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Update file of an attachment for a specific fnum
	 *
	 * @since version 1.0.0
	 */
	public function updateattachment()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		$fnum = $this->input->getString('fnum', '');

		if (!empty($fnum)) {
			$data = $this->input->post->getArray();
			$m_application = $this->getModel('Application');

			$upload = $m_application->getUploadByID($data['id']);

			if ($upload['fnum'] === $fnum) {
				if (EmundusHelperAccess::asAccessAction(4, 'u', $this->_user->id, $fnum)) {
					$data['user'] = $this->_user->id;

					if ($this->input->files->get('file')) {
						$data['file'] = $this->input->files->get('file');
					}

					$response['status'] = $m_application->updateAttachment($data);
				} else if (EmundusHelperAccess::isFnumMine($this->_user->id, $fnum)) {
					// only if attachment can_be_deleted is set to 1 and state is invalid
					if ($upload['is_validated'] == 0 && $upload['can_be_deleted'] == 1) {
						$data = [
							'id' => $data['id'],
							'is_validated' => -2, // reset validation state
							'fnum' => $fnum,
							'user' => $this->_user->id,
						];
						if ($this->input->files->get('file')) {
							$data['file'] = $this->input->files->get('file');
						}

						$response['status'] = $m_application->updateAttachment($data);
					}
				}

				if ($response['status']['update'] || $response['status']['update_file']) {
					$response['data'] = $m_application->getUploadByID($data['id']);
					$response['msg'] = Text::_('COM_EMUNDUS_ATTACHMENTS_UPDATED');
					$response['code'] = 200;
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_ATTACHMENTS_UPDATE_ERROR');
					$response['code'] = 500;
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Get datas of an application for a specific fnum, can be filtered by form (profile_id)
	 *
	 * @since version 1.0.0
	 */
	public function getform()
	{
		$result = array('status' => false, 'msg' => '', 'data' => null);

		$profile = $this->input->getInt('profile', 0);
		$user    = $this->input->getInt('user', 0);
		$fnum    = $this->input->getString('fnum', null);

		if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->_user->id,$fnum)) {
			$m_application = $this->getModel('Application');

			$form = $m_application->getForms($user, $fnum, $profile);
			if (!empty($form)) {
				$result['status'] = true;
				$result['msg']    = Text::_('FORM_RETRIEVED');
				$result['data']   = $form;
			}
			else {
				$result['msg'] = Text::_('FORM_NOT_RETRIEVED');
			}
		}
		else {
			$result['msg'] = Text::_('RESTRICTED_ACCESS');
		}

		echo json_encode($result);
		exit;
	}

	/**
	 * Load a preview of an attachment
	 *
	 * @since version 1.0.0
	 */
	public function getattachmentpreview()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		$upload_id      = $this->input->getInt('upload_id', null);

		if (!empty($upload_id)) {
			$m_application = $this->getModel('Application');
			$upload_details = $m_application->getUploadByID($upload_id);

			if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id) || (EmundusHelperAccess::isFnumMine($this->_user->id, $upload_details['fnum']) && $upload_details['can_be_viewed'] == 1)) {
				$user     = $this->input->getInt('user', 0);
				$filename = $this->input->getString('filename', '');

				if (!empty($filename) && !empty($upload_details['user_id'])) {
					$response = $m_application->getAttachmentPreview($user, $filename);
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Reorder applications of a user
	 *
	 * @since version 1.0.0
	 */
	public function reorderapplications()
	{
		$response = array('status' => false, 'msg' => JText::_('ACCESS_DENIED'));

		$current_user = $this->app->getIdentity();
		$emundusUser = $this->app->getSession()->get('emundusUser');
		$emundusUserFnums = array_keys($emundusUser->fnums);

		$fnum = $this->input->getString('fnum', '');
		$direction = $this->input->getString('direction', 'up');
		$order_column = $this->input->getString('order_column', 'ordering');
		$redirect = $this->input->getString('redirect', true);

		if (EmundusHelperAccess::asCoordinatorAccessLevel($current_user->id) || in_array($fnum, $emundusUserFnums)) {
			$m_application = new EmundusModelApplication();
			try {
				$reordered = $m_application->moveApplicationByColumn($fnum, $direction, $order_column);
				$response['status'] = $reordered;
				$response['msg'] =  $reordered ? Text::_('SUCCESS') : Text::_('FAILED');
			} catch (Exception $e) {
				$response['msg'] = $e->getMessage();
			}
		}

		if ($redirect) {
			$this->app->redirect('/index.php');
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Create a tab to group applications for the logged user
	 *
	 * @since version 1.0.0
	 */
	public function createtab()
	{
		$response = array('tab' => 0, 'msg' => Text::_('FAILED'));

		if (!empty($this->_user->id)) {
			$tab_name = $this->input->getString('name', '');

			if (!empty($tab_name)) {
				$m_application = $this->getModel('Application');
				$tab_created   = $m_application->createTab($tab_name, $this->_user->id);

				$response['tab'] = $tab_created;
				$response['msg'] = $tab_created ? Text::_('SUCCESS') : Text::_('FAILED');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Get tabs of the logged user
	 *
	 * @since version 1.0.0
	 */
	public function gettabs()
	{
		$response = array('tabs' => array());

		if (!empty($this->_user->id)) {
			$m_application    = $this->getModel('Application');
			$response['tabs'] = $m_application->getTabs($this->_user->id);
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Update tabs of the logged user
	 *
	 * @since version 1.0.0
	 */
	public function updatetabs()
	{
		$response = array('msg' => Text::_('FAILED'));

		if (!empty($this->_user->id)) {

			$tabs = $this->input->getRaw('tabs');
			$tabs = json_decode($tabs);

			if (!empty($tabs)) {
				$m_application       = $this->getModel('Application');
				$response['updated'] = $m_application->updateTabs($tabs, $this->_user->id);
				$response['msg']     = $response['updated'] ? Text::_('SUCCESS') : Text::_('FAILED');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Delete a tab of the logged user
	 *
	 * @since version 1.0.0
	 */
	public function deletetab()
	{
		$response = array('msg' => Text::_('FAILED'));

		if (!empty($this->_user->id)) {

			$tab = $this->input->getInt('tab');

			if (!empty($tab)) {
				$m_application       = $this->getModel('Application');
				$response['deleted'] = $m_application->deleteTab($tab, $this->_user->id);
				$response['msg']     = $response['deleted'] ? Text::_('SUCCESS') : Text::_('FAILED');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Copy a file of the logged user to another campaign
	 *
	 * @since version 1.0.0
	 */
	public function copyfile()
	{
		$response = array('status' => 0, 'msg' => '');

		$fnum     = $this->input->getString('fnum');
		$campaign = $this->input->getString('campaign');

		if (!empty($fnum) && !empty($campaign)) {
			$m_files   = $this->getModel('Files');
			$fnumInfos = $m_files->getFnumInfos($fnum);

			if ($fnumInfos['applicant_id'] !== $this->_user->id) {
				$response['msg'] = Text::_('ACCESS_DENIED');
			}
			else {
				$fnum_to = $m_files->createFile($campaign, $fnumInfos['applicant_id']);

				if (!empty($fnum_to)) {
					$m_application          = $this->getModel('Application');
					$response['status']     = $m_application->copyFile($fnum, $fnum_to);
					$response['first_page'] = 'index.php?option=com_emundus&task=openfile&fnum=' . $fnum_to;
				}
				$response['msg'] = $response['status'] ? Text::_('SUCCESS') : Text::_('FAILED');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Move an application file of the logged user to another tab
	 *
	 * @since version 1.0.0
	 */
	public function movetotab()
	{
		$response = array('status' => 0, 'msg' => '');

		$fnum = $this->input->getString('fnum');
		$tab  = $this->input->getString('tab');

		if (!empty($tab) && !empty($fnum)) {
			$m_files   = $this->getModel('Files');
			$fnumInfos = $m_files->getFnumInfos($fnum);

			if ($fnumInfos['applicant_id'] !== $this->_user->id) {
				$response['msg'] = Text::_('ACCESS_DENIED');
			}
			else {
				$m_application      = $this->getModel('Application');
				$response['status'] = $m_application->moveToTab($fnum, $tab);

				$response['msg'] = $response['status'] ? Text::_('SUCCESS') : Text::_('FAILED');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Rename an application file of the logged user
	 *
	 * @since version 1.0.0
	 */
	public function renamefile()
	{
		$response = array('status' => 0, 'msg' => '');

		$fnum     = $this->input->getString('fnum');
		$new_name = $this->input->getString('new_name');

		if (!empty($fnum)) {
			$m_files   = $this->getModel('Files');
			$fnumInfos = $m_files->getFnumInfos($fnum);

			if ($fnumInfos['applicant_id'] !== $this->_user->id) {
				$response['msg'] = Text::_('ACCESS_DENIED');
			}
			else {
				$m_application = $this->getModel('Application');
				try {
					$response['status'] = $m_application->renameFile($fnum, $new_name);
					$response['msg']    = $response['status'] ? Text::_('SUCCESS') : Text::_('FAILED');
				}
				catch (Exception $e) {
					$response['msg'] = $e->getMessage();
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Get campaigns available for copy for an application file of the logged user
	 *
	 * @since version 1.0.0
	 */
	public function getcampaignsavailableforcopy()
	{
		$response = array('status' => 0, 'msg' => '');

		$fnum = $this->input->getString('fnum');

		if (!empty($fnum)) {
			$m_files   = $this->getModel('Files');
			$fnumInfos = $m_files->getFnumInfos($fnum);

			if ($fnumInfos['applicant_id'] !== $this->_user->id) {
				$response['msg'] = Text::_('ACCESS_DENIED');
			}
			else {
				$m_application         = $this->getModel('Application');
				$response['campaigns'] = $m_application->getCampaignsAvailableForCopy($fnum);

				$response['msg'] = !empty($response['campaigns']) ? Text::_('SUCCESS') : Text::_('FAILED');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Filter applications by order by and filter by
	 *
	 * @since version 1.0.0
	 */
	public function filterapplications()
	{
		$response = array('status' => 1, 'msg' => Text::_('SUCCESS'));

		$type  = $this->input->getString('type');
		$value = $this->input->getString('value');

		if (!empty($type) && !empty($value) && in_array($type, ['applications_order_by', 'applications_filter_by'])) {
			$this->app->getSession()->set($type, $value);
		}
		elseif (empty($value)) {
			$this->app->getSession()->clear($type);
		}
		else {
			$response = array('status' => 0, 'msg' => Text::_('FAILED'));
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Execute a custom action on an application file of the logged user
	 *
	 * @since version 1.0.0
	 */
	public function applicantcustomaction()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::isApplicant($this->_user->id)) {
			$action        = $this->input->getString('action', '');
			$fnum          = $this->input->getString('fnum', '');
			$module_id     = $this->input->getInt('module_id', 0);
			$fnum_filtered = preg_replace('/[^0-9]/', '', $fnum);

			if ($fnum_filtered === $fnum) {
				if (!empty($action) && !empty($fnum)) {
					require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';
					$h_files            = new EmundusHelperFiles;
					$fnums              = $h_files->getApplicantFnums($this->_user->id);
					$current_user_fnums = array_keys($fnums);

					if (in_array($fnum, $current_user_fnums)) {
						$m_application      = $this->getModel('Application');
						$response['status'] = $m_application->applicantCustomAction($action, $fnum, $module_id);
						$response['code']   = 200;

						if ($response['status']) {
							$response['msg'] = Text::_('SUCCESS');
						}
						else {
							$response['msg'] = Text::_('FAILED');
						}
					}
					else {
						$response['msg']  = Text::_('INVALID_PARAMETERS');
						$response['code'] = 400;
					}
				}
			}
			else {
				// Log invalid fnum and ip address, to prevent brute force attacks
				$ip = $_SERVER['REMOTE_ADDR'];
				Log::add('Call to custom action on Invalid fnum: ' . $fnum . ' from ip: ' . $ip, Log::WARNING, 'com_emundus');
			}
		}

		header('Content-Type: application/json');
		header('HTTP/1.1 ' . $response['code'] . ' ' . $response['msg']);
		echo json_encode($response);
		exit;
	}

	/**
	 * Share an application file with an other person
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function sharefilewith()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (!$this->_user->guest) {
			$fnum = $this->input->getString('fnum', '');
			$e_user = $this->app->getSession()->get('emundusUser');

			if (!empty($fnum) && (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id) || in_array($fnum, array_keys($e_user->fnums)))) {
				$response['code'] = 500;
				$m_application      = $this->getModel('Application');
				$collaboration_url = $m_application->getCollaborationAcceptionLink();

				if (!empty($collaboration_url)) {
					$response['msg'] = Text::_('MISSING_PARAMETERS');
					$ccid = $this->input->getInt('ccid', 0);
					$emails = $this->input->getString('emails', '');

					if (!empty($emails) && !empty($ccid)) {
						$response['msg'] = Text::_('FAILED');
						$emails = explode(',', $emails);

						$response['data'] = $m_application->shareFileWith($emails, $ccid, Factory::getUser()->id);

						if ($response['data']['status']) {
							$response['code'] = 200;
							$response['msg'] = '';
							$response['status'] = true;

							require_once JPATH_ROOT . '/components/com_emundus/controllers/messages.php';
							$c_messages = new EmundusControllerMessages();

							$emails_not_sent = [];
							foreach ($response['data']['emails'] as $email => $key) {
								$post = [
									'COLLABORATE_URL' => $collaboration_url . $key,
									'COLLABORATE_BUTTON' => Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_BUTTON'),
								];

								$sent = $c_messages->sendEmailNoFnum($email, 'collaborate_invitation', $post, $e_user->id, [], $fnum);
								if (!$sent) {
									$response['data']['failed_emails'][] = $email;
								}
							}
						}
					}
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_LINK_NOT_CONFIGURED');
				}
			}
		}

		if ($response['code'] == 403) {
			header('HTTP/1.1 403 Forbidden');
			echo $response['msg'];
			exit;
		} else if ($response['code'] == 500) {
			header('HTTP/1.1 500 Internal Server Error');
			echo $response['msg'];
			exit;
		}
		echo json_encode($response);
		exit;
	}

	/**
	 * Remove a user from a shared application file
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function removeshareduser()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		$fnum = $this->input->getString('fnum','');
		$e_user = $this->app->getSession()->get('emundusUser');

		if(!empty($fnum) && (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id) || in_array($fnum, array_keys($e_user->fnums)))) {
			$ccid = $this->input->getInt('ccid',0);
			$request_id = $this->input->getInt('request_id',0);

			if(!empty($request_id) && !empty($ccid)) {
				PluginHelper::importPlugin('emundus', 'custom_event_handler');
				$this->app->triggerEvent('onCallEventHandler', ['onBeforeRemoveSharedUser', ['request_id' => $request_id, 'ccid' => $ccid, 'fnum' => $fnum]]);

				$m_application      = $this->getModel('Application');
				$response['status'] = $m_application->removeSharedUser($request_id, $ccid, $this->_user->id);

				if ($response['status']) {
					$response['msg'] = Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_USER_REMOVED_SUCCESFULLY');
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_USER_REMOVE_FAILED');
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Resend a new collaboration email to a user
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function sendnewcollaborationemail()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		$fnum = $this->input->getString('fnum','');
		$e_user = $this->app->getSession()->get('emundusUser');

		if(!empty($fnum) && (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id) || in_array($fnum, array_keys($e_user->fnums)))) {
			$ccid = $this->input->getInt('ccid',0);
			$request_id = $this->input->getInt('request_id',0);

			$ttl = $this->app->getSession()->get('ttl_send_email_'.$request_id);

			if($ttl && (time() - $ttl) < 900) {
				$response['msg'] = Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_EMAIL_TTL');
			}
			elseif(!empty($request_id) && !empty($ccid)) {
				$m_application      = $this->getModel('Application');
				$response['data'] = $m_application->regenerateKey($request_id, $ccid, $this->_user->id);

				if($response['data']['status']) {
					$response['status'] = true;

					$collaboration_url = $m_application->getCollaborationAcceptionLink();

					require_once JPATH_ROOT . '/components/com_emundus/controllers/messages.php';
					$c_messages = new EmundusControllerMessages();

					$post = [
						'COLLABORATE_URL' => $collaboration_url . $response['data']['key'],
						'COLLABORATE_BUTTON' => Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_BUTTON'),
					];

					$c_messages->sendEmailNoFnum($response['data']['email'],'collaborate_invitation', $post, $e_user->id, [], $fnum);

					$this->app->getSession()->set('ttl_send_email_'.$request_id, time());

					$response['msg'] = Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_EMAIL_SENT_SUCCESFULLY');
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Update right of a user on a shared application file
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function updateright()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		$fnum = $this->input->getString('fnum','');
		$e_user = $this->app->getSession()->get('emundusUser');

		if(!empty($fnum) && (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id) || in_array($fnum, array_keys($e_user->fnums)))) {
			$ccid = $this->input->getInt('ccid',0);
			$request_id = $this->input->getInt('request_id',0);
			$right = $this->input->getString('right',0);
			$value = $this->input->getString('value',0);
			$value = $value == 'true' ? 1 : 0;

			if(!empty($request_id) && !empty($ccid) && !empty($right)) {
				$m_application      = $this->getModel('Application');
				$response['status'] = $m_application->updateRight($request_id, $ccid, $right, $value);
				if($response['status']) {
					$response['msg'] = Text::_('COM_EMUNDUS_APPLICATIONS_COLLABORATE_RIGHT_UPDATED_SUCCESFULLY');
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Lock a Fabrik element for collaborators
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function lockelement()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		$element = $this->input->getString('element','');
		$fid = $this->input->getInt('form_id',0);
		$fnum = $this->input->getString('fnum','');
		$state = $this->input->getInt('state',0);

		$e_user = $this->app->getSession()->get('emundusUser');
		$fnumInfos = $e_user->fnums[$fnum];

		if(!empty($fnum) && $fnumInfos->applicant_id == $this->_user->id) {
			$m_application      = $this->getModel('Application');
			$response['status'] = $m_application->lockElement($element, $fid, $fnumInfos->id, $state);
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Save a form session
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function saveformsession()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if(!$this->_user->guest)
		{
			$element = $this->input->getString('element', '');
			$fid     = $this->input->getInt('form_id', 0);
			$value   = $this->input->getString('value', '');

			$e_user = $this->app->getSession()->get('emundusUser');
			$fnum   = $e_user->fnum;

			if (!empty($fnum) && !empty($element) && !empty($fid))
			{
				$m_application      = $this->getModel('Application');
				$response['status'] = $m_application->saveFormSession($element, $fid, $value, $fnum);
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Clear a form session
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function clearformsession()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if(!$this->_user->guest)
		{
			$fid = $this->input->getInt('form_id', 0);

			$e_user = $this->app->getSession()->get('emundusUser');
			$fnum   = $e_user->fnum;

			if (!empty($fnum) && !empty($fid))
			{
				$m_application      = $this->getModel('Application');
				$response['status'] = $m_application->clearFormSession($fid, $fnum);
			}
		}

		echo json_encode($response);
		exit;
	}
}
