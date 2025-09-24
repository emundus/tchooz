<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      James Dean
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Tchooz\Traits\TraitResponse;

/**
 * Emundus Messenger Controller
 * @package     Emundus
 */
class EmundusControllerMessenger extends BaseController
{
	use TraitResponse;

	/**
	 * @var EmundusModelMessenger
	 * @since version 1.0.0
	 */
	private $m_messenger;

	/**
	 * @var \Joomla\CMS\User\User
	 * @since version 1.40.0
	 */
	private $user;

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

		$this->m_messenger = $this->getModel('messenger');
		$this->user		= $this->app->getIdentity();
	}

	/**
	 * Get campaigns by fnums of current user
	 */
	public function getfilesbyuser()
	{
		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			$files = $this->m_messenger->getFilesByUser($this->user->id, false);
		} else {
			$files = $this->m_messenger->getFilesByUser($this->user->id);
		}

		$data  = array('data' => $files, 'current_user' => $this->user->id);

		echo json_encode((object) $data);
		exit;
	}

	public function getchatroomsbyuser()
	{
		$chatrooms = $this->m_messenger->getChatroomsByUser($this->user->id);

		$data = array('data' => $chatrooms);

		echo json_encode((object) $data);
		exit;
	}
	
	public function getchatroomsbyfnum()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$fnum = $this->input->getString('fnum');
		
		if (!empty($fnum)) {
			$response['msg'] = Text::_('ACCESS_DENIED');

			if (EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				$response['data']   = $this->m_messenger->getChatroomsByFnum($fnum);
				$response['msg']    = Text::_('SUCCESS');
				$response['status'] = true;
				$response['code']   = 200;
			}
		}
		
		echo json_encode((object) $response);
		exit;
	}

	public function createchatroom()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$fnum = $this->input->getString('fnum');

		if (!empty($fnum)) {
			$response['msg'] = Text::_('ACCESS_DENIED');

			if (EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				$response['data']   = $this->m_messenger->createChatroom($fnum);
				$response['msg']    = Text::_('SUCCESS');
				$response['status'] = true;
				$response['code']   = 200;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function closechatroom()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$fnum = $this->input->getString('fnum');

		if (!empty($fnum)) {
			$response['msg'] = Text::_('ACCESS_DENIED');

			if (EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				$response['status']   = $this->m_messenger->closeChatroom($fnum);
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function openchatroom()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$fnum = $this->input->getString('fnum');

		if (!empty($fnum)) {
			$response['msg'] = Text::_('ACCESS_DENIED');

			if (EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				$response['status']   = $this->m_messenger->openChatroom($fnum);
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getmessagesbyfnum()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$fnum         = $this->input->getString('fnum');

		if (!empty($fnum) && !empty($this->user->id)) {
			$response['msg']    = Text::_('ACCESS_DENIED');

			if (EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				$offset = $this->input->getString('offset', 0);

				$response['data'] = $this->m_messenger->getMessagesByFnum($fnum, $offset, $this->user->id);
				if (!empty($response['data'])) {
					$response['status'] = true;
					$response['msg']  = Text::_('SUCCESS');
					$response['code'] = 200;
				}
				else {
					$response['msg']  = Text::_('FAIL');
					$response['code'] = 500;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function sendmessage()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$message = $this->input->getString('message');
		$fnum    = $this->input->getString('fnum');

		if (!empty($fnum) && !empty($message)) {
			$response['msg'] = Text::_('ACCESS_DENIED');

			if (EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				$response['data'] = $this->m_messenger->sendMessage($message, $fnum);

				if (!empty($response['data']->message_id)) {
					$response['status'] = true;
					$response['msg']    = Text::_('SUCCESS');
					$response['code']   = 200;
				}
				else {
					$response['msg']  = Text::_('FAIL');
					$response['code'] = 500;
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getnotifications()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$user = $this->input->getInt('user');

		if (!empty($user)) {
			$response['msg'] = Text::_('ACCESS_DENIED');

			if ($this->user->id == $user) {
				$response['data']   = $this->m_messenger->getNotifications($user);
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
				$response['status'] = true;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getnotificationsbyfnum()
	{
		$response = ['data' => null, 'status' => false, 'msg' => Text::_('BAD_REQUEST'), 'code' => 403];

		$fnum = $this->input->getString('fnum');

		if (!empty($fnum)) {
			$response['msg'] = Text::_('ACCESS_DENIED');

			if (EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum)) {
				$response['data']   = $this->m_messenger->getNotificationsByFnum($fnum, $this->user->id);
				$response['code']   = 200;
				$response['status'] = true;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function markasread()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('BAD_REQUEST'), 'data' => null];

		$chatroom_id = $this->input->getInt('chatroom_id', 0);

		if(!empty($chatroom_id))
		{
			$response['msg'] = Text::_('ACCESS_DENIED');

			$messages_readed = $this->m_messenger->markAsRead($chatroom_id, $this->user->id);
			$response['data'] = $messages_readed;
			$response['code']   = 200;
			$response['status'] = true;
		}

		echo json_encode((object) $response);
		exit;
	}

	public function uploaddocument()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('BAD_REQUEST'), 'data' => null];

		$file = $this->input->files->get('file');

		if (!empty($file)) {
			$fnum = $this->input->get('fnum');

			if (!empty($fnum)) {
				$response['msg'] = Text::_('ACCESS_DENIED');
				$applicant       = $this->input->getBool('applicant');
				$attachment      = $this->input->getInt('attachment');

				require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
				$m_files      = $this->getModel('Files');
				$fnumInfos    = $m_files->getFnumInfos($fnum);
				$applicant_id = $fnumInfos['applicant_id'];

				if (($this->user->id == $applicant_id || EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum)) && isset($file)) {
					$path     = $file['name'];
					$ext      = pathinfo($path, PATHINFO_EXTENSION);
					$filename = pathinfo($path, PATHINFO_FILENAME);

					$target_root = 'images/emundus/files/';
					$target_dir  = $target_root . $applicant_id . '/';
					if (!file_exists($target_root)) {
						mkdir($target_root);
					}
					if (!file_exists($target_dir)) {
						mkdir($target_dir);
					}

					if ($applicant && !empty($attachment)) {
						$db    = JFactory::getDbo();
						$query = $db->getQuery(true);

						$query->select('lbl')
							->from($db->quoteName('#__emundus_setup_attachments'))
							->where($db->quoteName('id') . ' = ' . $attachment);
						$db->setQuery($query);
						$lbl = $db->loadResult();
					}

					do {
						if ($applicant && !empty($attachment)) {
							$filesrc = $fnumInfos['applicant_id'] . '-' . $fnumInfos['id'] . '-' . trim($lbl, ' _') . '-' . rand() . '.' . $ext;
						}
						else {
							$filesrc = $fnum . '_' . rand(1000, 90000) . '.' . $ext;
						}
						$target_file = $target_dir . $filesrc;
					} while (file_exists($target_file));

					if (move_uploaded_file($file["tmp_name"], $target_file)) {
						$message     = '<a href="' . $target_file . '" download class="tw-flex tw-items-center tw-gap-2"><span class="material-icons-outlined">file_download</span>' . $filename . '</a>';
						$new_message = $this->m_messenger->sendMessage($message, $fnum, $this->user->id, false);
						if ($applicant) {
							$upload_emundus = $this->m_messenger->moveToUploadedFile($fnumInfos, $attachment, $filesrc, $target_file);
						}
						$response['msg']    = $upload_emundus;
						$response['data']   = $new_message;
						$response['status'] = true;
						$response['code']   = 200;

					}
					else {
						$response['msg']    = Text::_('ERROR_WHILE_UPLOADING_YOUR_DOCUMENT');
						$response['status'] = false;
						$response['code']   = 500;
					}
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getdocumentsbycampaign()
	{
		$fnum      = $this->input->getString('fnum');
		$applicant = $this->input->getVar('applicant');

		$messages_readed = $this->m_messenger->getDocumentsByCampaign($fnum, $applicant);

		$data = array('data' => $messages_readed);

		echo json_encode((object) $data);
		exit;
	}

	public function askattachment()
	{
		$fnum       = $this->input->getString('fnum');
		$attachment = $this->input->getString('attachment');
		$message    = $this->input->getString('message');

		$new_message = $this->m_messenger->askAttachment($fnum, $attachment, $message);

		$data = array('data' => $new_message);

		echo json_encode((object) $data);
		exit;
	}

	/**
	 * Allow to close a chatroom
	 *
	 * @since version 1.40.0
	 */
	public function closeMessenger(){
		require_once (JPATH_ROOT . '/components/com_emundus/models/messages.php');
		$m_message = new EmundusModelMessages();

		$fnum = $this->input->getString('fnum');

		if(!empty($fnum) && (EmundusHelperAccess::isFnumMine($this->user->id, $fnum) || EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum))) {
			$m_message->closeMessenger($fnum);
		}
	}

	public function gotofile() {
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'route' => '');

		$fnum = $this->input->getString('fnum');

		if(!empty($fnum) && EmundusHelperAccess::asAccessAction(36, 'c', $this->user->id, $fnum)) {
			if(!class_exists('EmundusModelProfile')) {
				require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
			}
			$m_profile = new EmundusModelProfile();
			$current_profile = $m_profile->getProfileById($this->app->getSession()->get('emundusUser')->profile);

			$menu  = $this->app->getMenu();
			$items = $menu->getItems(['link','menutype'], ['index.php?option=com_emundus&view=files',$current_profile['menutype']], true);
			if(empty($items)) {
				$items = $menu->getItems(['link','menutype'], ['index.php?option=com_emundus&view=evaluation',$current_profile['menutype']], true);
			}

			if (is_array($items))
			{
				$redirect_item = $items[0];
				foreach ($items as $item)
				{
					if ($item->menutype == $current_profile['menutype'])
					{
						$redirect_item = $item;
					}
				}
			}
			else
			{
				$redirect_item = $items;
			}

			if (!empty($redirect_item))
			{
				$response['status'] = true;
				$response['msg'] = Text::_('SUCCESS');
				$response['route'] = '/' . $redirect_item->route.'#'.$fnum;
			}
			else
			{
				$response['msg'] = Text::_('NO_FILES_VIEW_AVAILABLE');
			}
		}

		echo json_encode($response);
		exit;
	}
}
