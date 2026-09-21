<?php
/**
 * Messages controller used for the creation and emission of messages from the platform.
 *
 * @package    Joomla
 * @subpackage Emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Hugo Moracchini
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Services\FileSecurityService;
use Tchooz\Controller\EmundusController;

/**
 * eMundus Component Controller
 *
 * @package    Joomla.eMundus
 * @subpackage Components
 */
class EmundusControllerMessages extends EmundusController
{
	protected $app;

	private $_user;

	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'messages.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');

		$this->app = Factory::getApplication();
		$this->_user = $this->app->getIdentity();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function setcategory(): void
	{
		$response = ['status' => false, 'msg' => Text::_('NO_EMAIL_FOUND')];

		$category = $this->input->get->getString('category', 'all');

		$m_messages = $this->getModel('Messages');
		$templates = $m_messages->getEmailsByCategory($category);

		if ($templates) {
			$response = (['status'    => true, 'templates' => $templates]);
		}

		echo json_encode((object) $response);
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function uploadfiletosend(): void
	{
		$result = ['status' => false, 'file_name' => '', 'file_path' => '', 'msg' => ''];

		if (empty($this->_user->id)) {
			$result['msg'] = Text::_('ACCESS_DENIED');
			echo json_encode($result);
			exit;
		}

		$filetype = $this->input->post->get('filetype', null);
		$file = $this->input->files->get('file');
		$user = $this->input->post->get('user');
		$fnum = $this->input->post->get('fnum');

		try {
			if (!isset($file['error']) || is_array($file['error'])) {
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_OCCURED'));
			}

			// Sanitize filename.
			$file['name'] = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $file['name']);
			$file['name'] = preg_replace("([\.]{2,})", '', $file['name']);
			$file['name'] = str_replace(array('(', ')'), '', $file['name']);

			// Check if file name is alphanumeric
			if (!preg_match("`^[-0-9A-Z_\.]+$`i", $file['name'])) {
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_INVALID_FILENAME'));
			}

			// Check if file name is not too long.
			if (mb_strlen($file['name'], "UTF-8") > 225) {
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_FILENAME_TOO_LONG'));
			}

			// If we specifically are uploading a PDF, check the MIME type.
			if ($filetype == 'pdf' && $file['type'] != 'application/pdf') {
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_INVALID_FILETYPE'));
			}

			if (!FileSecurityService::isAllowedUploadExtension($file['name'])) {
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_INVALID_FILETYPE'));
			}
			// Check if the message attachments directory exists.
			if (!is_dir('images' . DS . 'emundus' . DS . 'files' . DS . $user . DS . $fnum)) {
				mkdir('images' . DS . 'emundus' . DS . 'files' . DS . $user . DS . $fnum, 0777, true);
			}

			// Move the uploaded file to the server directory.
			if (!empty($user) && empty($fnum)) {
				$target = 'images' . DS . 'emundus' . DS . 'files' . DS . $user . DS . $fnum . DS . $file['name'];
			}
			else {
				$target = 'images' . DS . 'emundus' . DS . 'files' . DS . $file['name'];
			}

			if (file_exists($target)) {
				unlink($target);
			}

			move_uploaded_file($file['tmp_name'], $target);
		}
		catch (Exception $e) {
			$result['msg'] = $e->getMessage();
			echo json_encode($result);
			exit;
		}

		// Send back the info to the frontend.
		echo json_encode(['status' => true, 'file_name' => $file['name'], 'file_path' => $target]);
		exit;

	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function previewemail(): void
	{
		if (!EmundusHelperAccess::asAccessAction(9, 'c', $this->_user->id)) {
			die(Text::_("ACCESS_DENIED"));
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');

		$m_messages = $this->getModel('Messages');
		$m_emails   = $this->getModel('Emails');
		$m_files    = $this->getModel('Files');
		$m_campaign = $this->getModel('Campaign');

		$config = $this->app->getConfig();

		// Get default mail sender info
		$mail_from_sys      = $config->get('mailfrom');
		$mail_from_sys_name = $config->get('fromname');
		$reply_to = $config->get('replyto', $mail_from_sys);
		$reply_to_name = $config->get('replytoname', $mail_from_sys_name);;

		$fnums         = explode(',', $this->input->post->get('recipients', null, null));
		$nb_recipients = count($fnums);

		// If no mail sender info is provided, we use the system global config.
		$mail_from_name = $this->input->post->getString('mail_from_name', $mail_from_sys_name);
		$mail_from      = $this->input->post->getString('mail_from', $mail_from_sys);
		$reply_to_from  = $this->input->post->getString('reply_to_from', null, null);
		if (!empty($reply_to_from) && is_array($reply_to_from)) {
			foreach ($reply_to_from as $key => $reply_to_to_test) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $reply_to_to_test) !== 1) {
					unset($reply_to_from[$key]);
				}
			}
			$reply_to_from = array_values(array_unique($reply_to_from));
		}
		else {
			$reply_to_from = [];
		}

		if (empty($reply_to_from)) {
			$reply_to_from = [$reply_to];
		}

		$mail_subject = $this->input->post->getString('mail_subject', 'No Subject');
		$template_id  = $this->input->post->getInt('template', null);
		$mail_message = $this->input->post->get('message', null, 'RAW');
		$attachments  = $this->input->post->get('attachments', null, null);

		// Check tags unpublished
		$unpublished_tags = $m_emails->checkUnpublishedTags($mail_from . $mail_from_name . $mail_subject . $mail_message);

		$html = '';
		if (!empty($unpublished_tags)) {
			$html = '<div style="color: #c91212"><p style="color: #c91212">' . Text::_('COM_EMUNDUS_EMAIL_WARNING_UNPUBLISHED_TAGS') . '</p><ul>';
			foreach ($unpublished_tags as $unpublished_tag) {
				$html .= '<li>' . $unpublished_tag . '</li>';
			}
			$html .= '</ul></div>';
		}

		if ($nb_recipients > 1) {
			$html .= '<h2>' . Text::sprintf('COM_EMUNDUS_EMAIL_ABOUT_TO_SEND', $nb_recipients) . '</h2>';
		}


		// Here we filter out any CC or BCC emails that have been entered that do not match the regex.
		$cc  = $this->input->post->getString('cc');
		$bcc = $this->input->post->getString('bcc');

		if (!empty($bcc)) {
			if (!is_array($bcc)) {
				$bcc = [];
			}

			$bcc_html = '';
			foreach ($bcc as $key => $bcc_to_test) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $bcc_to_test) !== 1) {
					unset($bcc[$key]);
				}
				else {
					$bcc_html .= '<li>' . $bcc_to_test . '</li>';
				}
			}
		}

		if (!empty($cc)) {
			if (!is_array($cc)) {
				$cc = [];
			}

			$cc_html = '';
			foreach ($cc as $key => $cc_to_test) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $cc_to_test) !== 1) {
					unset($cc[$key]);
				}
				else {
					$cc_html .= '<li>' . $cc_to_test . '</li>';
				}
			}
		}

		if (isset($cc_html) || isset($bcc_html)) {

			$html .= '<div class="well">';

			if (isset($bcc_html)) {
				$html .= '<strong>' . Text::_('COM_EMUNDUS_EMAIL_PEOPLE_BCC') . '</strong> <ul>' . $bcc_html . '</ul>';
			}

			if (isset($cc_html)) {
				$html .= '<strong>' . Text::_('COM_EMUNDUS_EMAIL_PEOPLE_CC') . '</strong> <ul>' . $cc_html . '</ul>';
			}

			if ($nb_recipients > 1) {
				$html .= '<span class="alert alert-info">' . Text::sprintf('COM_EMUNDUS_EMAIL_CC_BCC_WILL_RECEIVE', $nb_recipients) . '</span>';
			}

			$html .= '</div>';
		}

		// Get additional info for only the first fnum.
		$fnum = $m_files->getFnumsInfos([$fnums[0]], 'object')[$fnums[0]];

		// Loading the message template is not used for getting the message text as that can be modified on the frontend by the user before sending.
		$template  = $m_messages->getEmail($template_id);
		$programme = $m_campaign->getProgrammeByTraining($fnum->training);

		$toAttach = [];
		$post     = [
			'FNUM'           => $fnum->fnum,
			'USER_NAME'      => $fnum->name,
			'COURSE_LABEL'   => $programme->label,
			'CAMPAIGN_LABEL' => $fnum->label,
			'CAMPAIGN_YEAR'  => $fnum->year,
			'CAMPAIGN_START' => HTMLHelper::_('date', $fnum->start_date, Text::_('DATE_FORMAT_LC2'), null),
			'CAMPAIGN_END'   => HTMLHelper::_('date', $fnum->end_date, Text::_('DATE_FORMAT_LC2'), null),
			'DEADLINE'       => HTMLHelper::_('date', $fnum->end_date, Text::_('DATE_FORMAT_LC2'), null),
			'SITE_URL'       => Uri::base(),
			'USER_EMAIL'     => $fnum->email,
			'BUTTON_TEXT'    => $template->button
		];

		$tags    = $m_emails->setTags($fnum->applicant_id, $post, $fnum->fnum, '', $mail_from . $mail_from_name . $mail_subject . $mail_message);
		$message = $m_emails->setTagsFabrik($mail_message, [$fnum->fnum]);
		$subject = $m_emails->setTagsFabrik($mail_subject, [$fnum->fnum]);

		// Tags are replaced with their corresponding values.
		if (empty($template) || empty($template->Template)) {
			if(empty($template)) {
				$template = new stdClass();
			}

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select($db->quoteName('Template'))
				->from($db->quoteName('#__emundus_email_templates'))
				->where($db->quoteName('id') . ' = 1')
				->orWhere($db->quoteName('lbl').' LIKE '.$db->quote('default'));
			$db->setQuery($query);

			$template->Template = $db->loadResult();
		}

		$body    = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $message], $template->Template);
		$subject = preg_replace($tags['patterns'], $tags['replacements'], $subject);
		$body    = preg_replace($tags['patterns'], $tags['replacements'], $body);


		// Get Sender and reply to addresses.
		$mail_from      = preg_replace($tags['patterns'], $tags['replacements'], $mail_from);
		$mail_from_name = preg_replace($tags['patterns'], $tags['replacements'], $mail_from_name);

		$mail_from_address = $mail_from_sys;

		$sender = $mail_from_name . ' &lt;' . $mail_from_address . '&gt;';

		// Build message preview.
		$html .= '</hr><div class="email-info">
                    <strong>' . Text::_('COM_EMUNDUS_EMAILS_FROM') . '</strong> ' . $sender . ' </br>';

		if (!empty($reply_to_from)) {
			$html .= '<strong>' . Text::_('COM_EMUNDUS_EMAILS_REPLY_TO') . '</strong> ' . implode(', ', $reply_to_from) . ' </br>';
		}


		if ($fnum->is_anonym == 1 || $fnum->anonymous == 1) {
			$html .= '<strong>' . Text::_('COM_EMUNDUS_EMAILS_TO') . '</strong> ' . Text::_('COM_EMUNDUS_ANONYM_EMAIL') . ' </br>';

			$html .= '<strong>' . Text::_('COM_EMUNDUS_EMAILS_SUBJECT') . '</strong> ' . $subject . ' </br>' .
				'<strong>' . Text::_('COM_EMUNDUS_EMAILS_BODY') . '</strong>
			</div>
			<div class="well">' . Text::_('COM_EMUNDUS_ANONYM_EMAIL_MESSAGE') . '</div>';
		} else {
			$html .= '<strong>' . Text::_('COM_EMUNDUS_EMAILS_TO') . '</strong> ' . $fnum->email . ' </br>';
			$html .= '<strong>' . Text::_('COM_EMUNDUS_EMAILS_SUBJECT') . '</strong> ' . $subject . ' </br>' .
				'<strong>' . Text::_('COM_EMUNDUS_EMAILS_BODY') . '</strong>
			</div>
			<div class="well">' . $body . '</div>';
		}


		// Retrieve and build a list of the files that will be attached to the mail.

		// Files uploaded from the frontend.
		if (!empty($attachments['upload'])) {
			// In the case of an uploaded file, just add it to the email.
			foreach ($attachments['upload'] as $upload) {
				if (file_exists(JPATH_SITE . DS . $upload)) {
					$toAttach['upload'][] = pathinfo($upload)['basename'];
				}
			}
		}

		// Files gotten from candidate files, requires attachment read rights.
		if (EmundusHelperAccess::asAccessAction(4, 'r', $this->_user->id) && !empty($attachments['candidate_file'])) {

			// Get from DB by fnum.
			foreach ($attachments['candidate_file'] as $candidate_file) {

				$filename = $m_messages->get_filename($candidate_file);

				if ($filename) {
					$toAttach['candidate_file'][] = $filename;
				}
			}
		}

		// Files generated using the Letters system. Requires attachment creation and doc generation rights.
		if (EmundusHelperAccess::asAccessAction(4, 'c', $this->_user->id) && EmundusHelperAccess::asAccessAction(27, 'c', $this->_user->id) && !empty($attachments['setup_letters'])) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			// Get from DB and generate using the tags.
			foreach ($attachments['setup_letters'] as $setup_letter) {
				/// get letter from attachment id distinctly --> note that : in this case, since in dropdown menu, we already show all letter model --> (with its id)
				/*$query->clear()
					->select('distinct #__emundus_setup_letters.*')
					->from($db->quoteName('#__emundus_setup_letters'))
					->where($db->quoteName('#__emundus_setup_letters.attachment_id') . ' = ' . $setup_letter);**/

				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
				$m_files = $this->getModel('Files');

				$aids                 = $m_files->getSetupAttachmentsById(array($setup_letter));
				$_letter              = reset($aids);
				$toAttach['letter'][] = $_letter['value'];
			}
		}


		$files = '';
		if (!empty($toAttach)) {

			$files .= '<div class="well"><h3>' . Text::_('COM_EMUNDUS_EMAILS_ATTACHMENTS') . '</h3>';

			if (!empty($toAttach['upload'])) {

				$files .= '<strong>' . Text::_('COM_EMUNDUS_UPLOAD') . '</strong>';

				$files .= '<ul>';
				foreach ($toAttach['upload'] as $attach) {
					$files .= '<li>' . $attach . '</li>';
				}
				$files .= '</ul>';
			}


			if (!empty($toAttach['candidate_file'])) {

				$files .= '<strong>' . Text::_('COM_EMUNDUS_EMAILS_CANDIDATE_FILE') . '</strong>';

				$files .= '<ul>';
				foreach ($toAttach['candidate_file'] as $attach) {
					$files .= '<li>' . $attach . '</li>';
				}
				$files .= '</ul>';
			}


			if (!empty($toAttach['letter'])) {

				$files .= '<strong>' . Text::_('COM_EMUNDUS_EMAILS_SETUP_LETTERS_ATTACH') . '</strong><ul>';
				foreach ($toAttach['letter'] as $attach) {
					$files .= '<li>' . $attach . '</li>';
				}
				$files .= '</ul>';
			}
			$files .= '</div>';
		}

		$html .= $files;

		echo json_encode(['status' => true, 'html' => $html]);
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function applicantemail()
	{
		if (!EmundusHelperAccess::asAccessAction(9, 'c', $this->_user->id)) {
			die(Text::_("ACCESS_DENIED"));
		}

		$db = Factory::getContainer()->get('DatabaseDriver');

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'evaluation.php');

		$m_messages = $this->getModel('Messages');
		$m_emails   = $this->getModel('Emails');
		$m_users    = $this->getModel('Users');
		$m_files    = $this->getModel('Files');
		$m_campaign = $this->getModel('Campaign');
		$m_eval     = $this->getModel('Evaluation');

		$user = $this->app->getIdentity();


		// Get default mail sender info
		$mail_from_sys      = $this->app->get('mailfrom');
		$mail_from_sys_name = $this->app->get('fromname');
		$reply_to = $this->app->get('replyto', $mail_from_sys);
		$reply_to_name = $this->app->get('replytoname', $mail_from_sys_name);

		$fnums = explode(',', $this->input->post->get('recipients', null, null));

		// If no mail sender info is provided, we use the system global config.
		$mail_from_name = $this->input->post->getString('mail_from_name', $mail_from_sys_name);
		$mail_from      = $this->input->post->getString('mail_from', $mail_from_sys);
		$reply_to_from  = $this->input->post->getString('reply_to_from', null, null);
		if (!empty($reply_to_from) && is_array($reply_to_from)) {
			foreach ($reply_to_from as $key => $reply_to_to_test) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $reply_to_to_test) !== 1) {
					unset($reply_to_from[$key]);
				}
			}
			$reply_to_from = array_values(array_unique($reply_to_from));
		}
		else {
			$reply_to_from = [];
		}

		if (empty($reply_to_from)) {
			$reply_to_from = [$reply_to];
		}

		$mail_subject = $this->input->post->getString('mail_subject', 'No Subject');
		$template_id  = $this->input->post->getInt('template', null);
		$mail_message = $this->input->post->get('message', null, 'RAW');
		$attachments  = $this->input->post->get('attachments', null, null);
		$tags_str     = $this->input->post->getString('tags', null, null);
		$cc           = $this->input->post->getString('cc', null, null);
		$bcc          = $this->input->post->getString('bcc', null, null);

		if (!empty($cc) && is_array($cc)) {
			foreach ($cc as $key => $cc_to_test) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $cc_to_test) !== 1) {
					unset($cc[$key]);
				}
			}
			$cc = array_unique($cc);
		}
		else {
			$cc = [];
		}


		if (!empty($bcc) && is_array($bcc)) {
			foreach ($bcc as $key => $bcc_to_test) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $bcc_to_test) !== 1) {
					unset($bcc[$key]);
				}
			}
			$bcc = array_unique($bcc);
		}
		else {
			$bcc = [];
		}

		// Get additional info for the fnums such as the user email.
		$fnums = $m_files->getFnumsInfos($fnums, 'object');

		// This will be filled with the email adresses of successfully sent emails, used to give feedback to front end.
		$sent   = [];
		$failed = [];

		// Loading the message template is not used for getting the message text as that can be modified on the frontend by the user before sending.
		$template = $m_messages->getEmail($template_id);

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/emails.php');
		$h_emails = new EmundusHelperEmails();

		foreach ($fnums as $fnum) {
			$can_send_mail = $h_emails->assertCanSendMailToUser($fnum->applicant_id, $fnum->fnum);
			if (!$can_send_mail) {
				continue;
			}

			$programme = $m_campaign->getProgrammeByTraining($fnum->training);

			$cc_final     = $cc;
			$emundus_user = $m_users->getUserById($fnum->applicant_id)[0];
			if (!empty($emundus_user->email_cc)) {
				if (!in_array($emundus_user->email_cc, $cc_final) && preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $emundus_user->email_cc) === 1) {
					$cc_final[] = $emundus_user->email_cc;
				}
			}

			$toAttach = [];
			$post     = [
				'FNUM'           => $fnum->fnum,
				'USER_NAME'      => $fnum->name,
				'COURSE_LABEL'   => $programme->label,
				'CAMPAIGN_LABEL' => $fnum->label,
				'CAMPAIGN_YEAR'  => $fnum->year,
				'CAMPAIGN_START' => HTMLHelper::_('date', $fnum->start_date, Text::_('DATE_FORMAT_LC2'), null),
				'CAMPAIGN_END'   => HTMLHelper::_('date', $fnum->end_date, Text::_('DATE_FORMAT_LC2'), null),
				'DEADLINE'       => HTMLHelper::_('date', $fnum->end_date, Text::_('DATE_FORMAT_LC2'), null),
				'SITE_URL'       => Uri::base(),
				'USER_EMAIL'     => $fnum->email,
				'BUTTON_TEXT'    => $template->button
			];

			$tags    = $m_emails->setTags($fnum->applicant_id, $post, $fnum->fnum, '', $mail_from . $mail_from_name . $mail_subject . $mail_message);
			$body    = $m_emails->setTagsFabrik($mail_message, [$fnum->fnum]);
			$subject = $m_emails->setTagsFabrik($mail_subject, [$fnum->fnum]);

			$subject = preg_replace($tags['patterns'], $tags['replacements'], $subject);

			if (empty($template) || empty($template->Template)) {
				if(empty($template)) {
					$template = new stdClass();
				}

				$query = $db->getQuery(true);

				$query->select($db->quoteName('Template'))
					->from($db->quoteName('#__emundus_email_templates'))
					->where($db->quoteName('id') . ' = 1')
					->orWhere($db->quoteName('lbl').' LIKE '.$db->quote('default'));
				$db->setQuery($query);

				$template->Template = $db->loadResult();
			}

			$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);
			$body = preg_replace($tags['patterns'], $tags['replacements'], $body);

			$mail_from         = preg_replace($tags['patterns'], $tags['replacements'], $mail_from);
			$mail_from_name    = preg_replace($tags['patterns'], $tags['replacements'], $mail_from_name);
			$mail_from_address = $mail_from_sys;

			$sender = [
				$mail_from_address,
				$mail_from_name
			];

			// Configure email sender
			$mailer = Factory::getContainer()->get(Mail\MailerFactoryInterface::class)->createMailer();
			$mailer->setSender($sender);
			if (!empty($reply_to_from)) {
				$mailer->addReplyTo($reply_to_from);
			}
			$mailer->addRecipient($fnum->email);
			$mailer->setSubject($subject);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$mailer->setBody($body);

			if (!empty($cc_final)) {
				$mailer->addCc($cc_final);
			}

			if (!empty($bcc)) {
				$mailer->addBcc($bcc);
			}

			// Files uploaded from the frontend.
			if (!empty($attachments['upload'])) {
				// In the case of an uploaded file, just add it to the email.
				foreach ($attachments['upload'] as $upload) {
					if (file_exists(JPATH_SITE . DS . $upload)) {
						$toAttach[] = JPATH_SITE . DS . $upload;
					}
				}
			}

			// Files generated using the Letters system. Requires attachment creation and doc generation rights.
			if (EmundusHelperAccess::asAccessAction(4, 'c', $this->_user->id) && EmundusHelperAccess::asAccessAction(27, 'c', $this->_user->id) && !empty($attachments['setup_letters'])) {
				foreach ($attachments['setup_letters'] as $setup_letter) {
					$_letter = $m_eval->getLetterTemplateForFnum($fnum->fnum, [$setup_letter]);

					if (!empty($_letter)) {
						$res = $m_eval->generateLetters($fnum->fnum, [$setup_letter], 0, 0, 0);

						$folder_id = current($m_files->getFnumsInfos(array($fnum->fnum)))['applicant_id'];

						foreach ($res->files as $f) {
							$path       = EMUNDUS_PATH_ABS . $folder_id . DS . $f['filename'];
							$toAttach[] = $path;
							break;
						}
					}
				}
			}

			// Files gotten from candidate files, requires attachment read rights.
			if (EmundusHelperAccess::asAccessAction(4, 'r', $this->_user->id) && !empty($attachments['candidate_file'])) {
				// Get from DB by fnum.
				foreach ($attachments['candidate_file'] as $candidate_file) {

					$filename = $m_messages->get_upload($fnum->fnum, $candidate_file);

					if ($filename != false) {
						// Build the path to the file we are searching for on the disk.
						$path = EMUNDUS_PATH_ABS . $fnum->applicant_id . DS . $filename;

						if (file_exists($path)) {
							$toAttach[] = $path;
						}
					}
				}
			}

			$files = '';

			if (!empty($toAttach)) {
				$files = '<ul>';
				if (!empty($attachments['upload'])) {
					foreach ($attachments['upload'] as $attach) {
						$filesName = basename($attach);
						$files     .= '<li>' . $filesName . '</li>';
					}
				}

				if (!empty($attachments['candidate_file'])) {
					foreach ($attachments['candidate_file'] as $attach) {
						$raw      = $m_eval->getAttachmentByIds([$attach]);
						$nameType = current($raw)['value'];

						$files .= '<li>' . $nameType . '</li>';
					}
				}
				if (!empty($attachments['setup_letters'])) {
					foreach ($attachments['setup_letters'] as $attach) {
						$raw      = $m_eval->getAttachmentByIds([$attach]);
						$nameType = current($raw)['value'];

						$files .= '<li>' . $nameType . '</li>';
					}
				}
			}

			$files .= '</ul>';

			$mailer->addAttachment(array_unique($toAttach));

			$custom_email_tag = EmundusHelperEmails::getCustomHeader();
			if (!empty($custom_email_tag)) {
				$mailer->addCustomHeader($custom_email_tag);
			}

			// Send and log the email.
			$send = $mailer->Send();
			if ($send !== true) {
				$failed[] = $fnum->is_anonym  == 1 ? $fnum->fnum : $fnum->email;
				echo 'Error sending email: ' . $send->__toString();
				Log::add($send->__toString(), Log::ERROR, 'com_emundus');
			}
			else {
				// Assoc tags if email has been sent
				if($tags_str != null || !empty($template->tags)) {
					$tags = array_filter(array_merge(explode(',',$tags_str),explode(',',$template->tags)));

					if(!empty($tags))
					{
						$m_files->tagFile([$fnum->fnum], $tags, $user->id);
					}
				}

				// Log email
				$sent[] = $fnum->is_anonym  == 1 ? $fnum->fnum : $fnum->email;
				$log    = [
					'user_id_from' => $user->id,
					'user_id_to'   => $fnum->applicant_id,
					'subject'      => $subject,
					'message' => $body . $files,
					'type'         => (empty($template->type)) ? '' : $template->type,
					'email_id' => $template_id,
					'email_to' => $fnum->email
				];
				if (!empty($cc_final)) {
					$log['email_cc'] = implode(', ', $cc_final);
				}
				$m_emails->logEmail($log, $fnum->fnum);
			}

			// Due to mailtrap now limiting emails sent to fast, we add a long sleep.
			if ($this->app->get('smtphost') === 'smtp.mailtrap.io') {
				sleep(15);
			}
		}

		echo json_encode(['status' => true, 'sent' => $sent, 'failed' => $failed]);
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function useremail(): void
	{
		if (!EmundusHelperAccess::asAccessAction(9, 'c', $this->_user->id)) {
			die(Text::_("ACCESS_DENIED"));
		}

		$app = Factory::getApplication();

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');

		$m_messages = $this->getModel('Messages');
		$m_emails   = $this->getModel('Emails');
		$m_users    = $this->getModel('Users');

		$current_user = $this->app->getIdentity();
		$config       = $this->app->getConfig();

		// Get default mail sender info
		$mail_from_sys      = $config->get('mailfrom');
		$mail_from_sys_name = $config->get('fromname');

		$uids = explode(',', $this->input->post->get('recipients', null, null));
		$bcc  = $this->input->post->getString('Bcc', false);

		// If no mail sender info is provided, we use the system global config.
		$mail_from_name = $this->input->post->getString('mail_from_name', $mail_from_sys_name);
		$mail_from      = $this->input->post->getString('mail_from', $mail_from_sys);

		$mail_subject = $this->input->getString('mail_subject', 'No Subject');
		$template_id  = $this->input->getInt('template',0);
		$mail_message = $this->input->getRaw('message');
		$attachments  = $this->input->getString('attachments');

		$users = $m_users->getUsersByIds($uids);

		// This will be filled with the email adresses of successfully sent emails, used to give feedback to front end.
		$sent   = [];
		$failed = [];

		$db    = Factory::getContainer()->get('DatabaseDriver');
		if (!empty($template_id)) {
			// Loading the message template is not used for getting the message text as that can be modified on the frontend by the user before sending.
			$template = $m_messages->getEmail($template_id);
		}
		else {
			$query = $db->getQuery(true);

			$query->clear()
				->select($db->quoteName('Template'))
				->from($db->quoteName('#__emundus_email_templates'))
				->where($db->quoteName('id') . ' = 1')
				->orWhere($db->quoteName('lbl').' LIKE '.$db->quote('default'));
			$db->setQuery($query);
			$template = $db->loadObject();
		}

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/emails.php');
		$h_emails = new EmundusHelperEmails();
		foreach ($users as $user) {
			$can_send_mail = $h_emails->assertCanSendMailToUser($user->id);
			if (!$can_send_mail) {
				$failed[] = $user->email;
				continue;
			}

			$toAttach = [];
			$post     = [
				'USER_NAME'  => $user->name,
				'SITE_URL'   => Uri::base(),
				'USER_EMAIL' => $user->email,
				'BUTTON_TEXT'    => $template->button ?? ''
			];

			if(isset($template->lbl) && $template->lbl === 'new_account')
			{
				$baseUrl = Uri::base();
				if($app->isClient('api'))
				{
					// Remove /api from base URL
					$baseUrl = rtrim(str_ireplace('/api', '', $baseUrl), '/').'/';
				}

				$token       = ApplicationHelper::getHash(UserHelper::genRandomPassword());
				$hashedToken = UserHelper::hashPassword($token);

				$update = (object)[
					'activation' => $hashedToken,
					'id'         => $user->id
				];
				$db->updateObject('#__users', $update, 'id');

				$siteApplication = Factory::getContainer()->get(SiteApplication::class);
				$menu_item = $siteApplication->getMenu()->getItems('link', 'index.php?option=com_users&view=reset', true);
				$account_menu_id = ComponentHelper::getComponent('com_emundus')->getParams()->get('account_creation_link',0);
				if(!empty($account_menu_id)) {
					$menu_item = $siteApplication->getMenu()->getItem($account_menu_id);
				}

				if(!empty($menu_item)) {
					$link = $menu_item->alias.'?layout=confirm&token=' . $token . '&username=' . $user->username;
				}
				else {
					$link = 'index.php?option=com_users&view=reset&layout=confirm&token=' . $token . '&username=' . $user->username . '&new_account=1';
				}

				$link = str_replace('+', '%2B', $link);

				$post['ACCOUNT_CREATION_URL'] = $baseUrl . $link;
			}

			$tags = $m_emails->setTags($user->id, $post, null, '', $mail_from . $mail_from_name . $mail_subject . $mail_message);

			// Tags are replaced with their corresponding values using the PHP preg_replace function.
			$subject = preg_replace($tags['patterns'], $tags['replacements'], $mail_subject);
			$body    = $mail_message;
			if (!empty($template->Template)) {
				$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);
			}
			$body = preg_replace($tags['patterns'], $tags['replacements'], $body);

			$mail_from      = preg_replace($tags['patterns'], $tags['replacements'], $mail_from);
			$mail_from_name = preg_replace($tags['patterns'], $tags['replacements'], $mail_from_name);
			
			$mail_from_address = $mail_from_sys;
			
			$sender = [
				$mail_from_address,
				$mail_from_name
			];

			// Configure email sender
			$mailer = Factory::getContainer()->get(Mail\MailerFactoryInterface::class)->createMailer();
			$mailer->setSender($sender);
			$mailer->addReplyTo($mail_from, $mail_from_name);
			$mailer->addRecipient($user->email);
			$mailer->setSubject($subject);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$mailer->setBody($body);

			if ($bcc === 'true') {
				$mailer->addBCC($current_user->email);
			}

			$files = '';
			// Files uploaded from the frontend.
			if (!empty($attachments)) {

				$attachments = explode(',', $attachments);
				// Here we also build the HTML being logged to show which files were attached to the email.
				$files = '<ul>';
				foreach ($attachments as $upload) {
					if (file_exists(JPATH_SITE . DS . $upload)) {
						$toAttach[] = JPATH_SITE . DS . $upload;
						$files      .= '<li>' . basename($upload) . '</li>';
					}
				}
				$files .= '</ul>';

			}

			$mailer->addAttachment($toAttach);

			$custom_email_tag = EmundusHelperEmails::getCustomHeader();
			if (!empty($custom_email_tag)) {
				$mailer->addCustomHeader($custom_email_tag);
			}

			// Send and log the email.
			$send = $mailer->Send();

			if ($send !== true) {
				$failed[] = $user->is_anonym != 1 ? $user->email : Text::_('COM_EMUNDUS_ANONYM_ACCOUNT') . ' ' . $user->id;
				echo 'Error sending email: ' . $send->__toString();
				Log::add($send->__toString(), Log::ERROR, 'com_emundus');
			}
			else {
				$sent[] = $user->is_anonym != 1 ? $user->email : Text::_('COM_EMUNDUS_ANONYM_ACCOUNT') . ' ' . $user->id;
				$log    = [
					'user_id_from' => $current_user->id,
					'user_id_to'   => $user->id,
					'subject'      => $subject,
					'message' => $body . $files,
					'type' => !empty($template->type)?$template->type:'',
					'email_to' => $user->email
				];
				$m_emails->logEmail($log);
				// Log the email in the eMundus logging system.
				$logsParams = array('created' => [$subject]);
				EmundusModelLogs::log($current_user->id, $user->id, '', 9, 'c', 'COM_EMUNDUS_ACCESS_MAIL_APPLICANT_CREATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
			}

		}
		echo json_encode(['status' => true, 'sent' => $sent, 'failed' => $failed]);
		exit;
	}

	function sendEmail($fnum, $email_id, $post = null, $attachments = [], $bcc = false, $sender_id = null) {
		$sent = false;
		$user = $this->app->getIdentity();

		if (!empty($fnum) && !empty($email_id)) {
			if (!class_exists('EmundusModelEmails')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
			}
			$m_emails = new EmundusModelEmails();
			$sent = $m_emails->sendEmail($fnum, $email_id, $post, $attachments, $bcc, $sender_id, $user);
		}

		return $sent;
	}

	/**
	 * @deprecated
	 * The generic function used for sending emails outside of emundus.
	 *
	 * @param   string      $email_address
	 * @param   string|int   $email  If a numeric ID is provided, use that, if a string is provided, get the email with that label.
	 * @param   ?array      $post
	 * @param   ?int        $user_id
	 * @param   array       $attachments
	 * @param   string      $fnum   If we need to replace fabrik tags
	 *
	 * @return bool
	 */
	function sendEmailNoFnum($email_address, $email, $post = null, $user_id = null, $attachments = [], $fnum = null, $log_email = true)
	{
		$sent = false;

		if (!empty($email_address) && !empty($email)) {
			if (!class_exists('EmundusModelEmails')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
			}
			$m_emails = new EmundusModelEmails();
			$sent = $m_emails->sendEmailNoFnum($email_address, $email, $post, $user_id, $attachments, $fnum, $log_email);
		}

		return $sent;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getalldocumentsletters(): void
	{
		$_mMessages = $this->getModel('Messages');
		$_documents = $_mMessages->getAllDocumentsLetters();

		if ($_documents) {
			echo json_encode(['status' => true, 'documents' => $_documents]);
		}
		else {
			echo json_encode(['status' => false, 'documents' => null]);
		}
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getattachmentsbyprofiles(): void
	{
		$response = ['status' => false, 'attachments' => null];

		$fnums = explode(',', $this->input->post->getRaw('fnums'));
		$_mMessages = $this->getModel('Messages');
		$_results   = $_mMessages->getAttachmentsByProfiles($fnums);
		if ($_results) {
			$response = ['status' => true, 'attachments' => $_results];
		}

		echo json_encode($response);
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getallattachments(): void
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		$m_messages = $this->getModel('Messages');
		$_documents = $m_messages->getAllAttachments();

		if ($_documents) {
			$response = ['status' => true, 'attachments' => $_documents];
		} else {
			$response = ['status' => false, 'attachments' => null];
		}

		echo json_encode($response);
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getAllCategories(): void
	{
		$res = ['status' => true, 'data' => []];
		if (!EmundusHelperAccess::asAccessAction(9, 'c', $this->_user->id)) {
			$res['status'] = false;
			echo json_encode($res);
			exit;
		}

		$_mMessages  = $this->getModel('Messages');
		$res['data'] = $_mMessages->getAllCategories();

		echo json_encode($res);
		exit;
	}
}
