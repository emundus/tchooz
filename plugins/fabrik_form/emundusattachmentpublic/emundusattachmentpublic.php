<?php
/**
 * @version     2: emunduscampaign 2019-04-11 Hugo Moracchini
 * @package     Fabrik
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Création de dossier de candidature automatique.
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.emundusattachment
 * @since       3.0
 */
class PlgFabrik_FormEmundusAttachmentPublic extends plgFabrik_Form
{

	public function onBeforeLoad()
	{
		$session                = $this->app->getSession();
		$emundus_user           = $session->get('emundusUser');

		require_once(JPATH_SITE .'/components/com_emundus/models/users.php');
		require_once(JPATH_SITE .'/components/com_emundus/helpers/menu.php');
		$m_users      = new EmundusModelUsers();
		$applicant_profiles     = $m_users->getApplicantProfiles();
		$current_user_profile   = $emundus_user->profile;
		$applicant_profiles_ids = array_map(function ($profile) {
			return $profile->id;
		}, $applicant_profiles);

		$is_applicant = in_array($current_user_profile, $applicant_profiles_ids) ? 1 : 0;

		if ($is_applicant) {
			$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$this->app->redirect(EmundusHelperMenu::getHomepageLink());
		}

		$query = $this->_db->getQuery(true);

		$key_id      = $this->app->getInput()->getString('keyid');
		$sid         = $this->app->getInput()->getInt('sid');
		$email       = $this->app->getInput()->getString('email', '');
		$campaign_id = $this->app->getInput()->getInt('cid', 0);
		$formid      = $this->app->getInput()->getInt('formid', 0);

		$article_id  = $this->getParams()->get('emundusattachmentpublic_articleid_already_uploaded',60);
		
		$eMConfig    = ComponentHelper::getParams('com_emundus');
		$referent_edit = $eMConfig->get('referent_can_edit_after_deadline');

		require_once(JPATH_BASE . '/components/com_emundus/models/files.php');
        require_once(JPATH_BASE . '/components/com_emundus/models/workflow.php');
		$m_files = new EmundusModelFiles();
        $m_workflow = new EmundusModelWorkflow();

		$query->select('*')
			->from($this->_db->quoteName('#__emundus_files_request'))
			->where($this->_db->quoteName('keyid') . ' = ' . $this->_db->quote($key_id))
			->where($this->_db->quoteName('student_id') . ' = ' . $this->_db->quote($sid))
			->where($this->_db->quoteName('uploaded') . ' = 0');
		$this->_db->setQuery($query);
		$obj = $this->_db->loadObject();

		if (isset($obj)) {
			$fnumInfos = $m_files->getFnumInfos($obj->fnum);

			$offset   = $this->app->get('offset', 'UTC');
			$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
			$dateTime = $dateTime->setTimezone(new DateTimeZone($offset));
			$now      = $dateTime->format('Y-m-d H:i:s');

            $current_phase = $m_workflow->getCurrentWorkflowStepFromFile($obj->fnum);
            $infinite_step = false;
            if (!empty($current_phase) && !empty($current_phase->id))
            {
                if ($current_phase->infinite)
                {
                    $infinite_step = true;
                }

                $current_end_date   = !empty($current_phase->end_date) ? $current_phase->end_date : $fnumInfos['end_date'];
                $current_start_date = !empty($current_phase->start_date) ? $current_phase->start_date : $fnumInfos['start_date'];
            }
            else
            {
                $current_end_date   = $fnumInfos['end_date'];
                $current_start_date = $fnumInfos['start_date'];
            }

			$is_campaign_started = strtotime(date($now)) >= strtotime($current_start_date);
            $is_dead_line_passed = !$infinite_step && strtotime(date($now)) > strtotime($current_end_date);

			if (!$is_campaign_started) {
				$this->app->enqueueMessage(Text::_('COM_EMUNDUS_REFERENT_PERIOD_NOT_STARTED'), 'error');
				$this->app->redirect('/');
			}
			elseif ($is_dead_line_passed && !$referent_edit) {
				$this->app->enqueueMessage(Text::_('COM_EMUNDUS_REFERENT_PERIOD_PASSED'), 'error');
				$this->app->redirect('/');
			}
			else {
				$s = $this->app->getInput()->getInt('s');
				if ($s != 1) {
					$query->clear()
						->select('id')
						->from($this->_db->quoteName('#__menu'))
						->where($this->_db->quoteName('link') . ' = ' . $this->_db->quote('index.php?option=com_fabrik&view=form&formid=' . $formid));
					$this->_db->setQuery($query);
					$item_id = $this->_db->loadResult();

					$link_upload = 'index.php?option=com_fabrik&view=form&formid=' . $formid . '&Itemid='.$item_id.'&jos_emundus_uploads___user_id=' . $sid . '&jos_emundus_uploads___attachment_id=' . $obj->attachment_id . '&jos_emundus_uploads___campaign_id=' . $obj->campaign_id . '&jos_emundus_uploads___fnum=' . $obj->fnum . '&sid=' . $sid . '&keyid=' . $key_id . '&email=' . $email . '&cid=' . $campaign_id . '&s=1';
					$this->app->redirect(Route::_($link_upload, false));
					exit();
				}
				else {
					$student_id    = $this->app->getInput()->getInt('jos_emundus_uploads___user_id', 0);
					$attachment_id = $this->app->getInput()->getInt('jos_emundus_uploads___attachment_id', 0);

					if (empty($student_id) || empty($key_id) || empty($attachment_id) || $attachment_id != $obj->attachment_id || !is_numeric($sid) || $sid != $student_id) {
						$this->app->enqueueMessage(Text::_('ERROR: please try again'), 'error');
						header('Location: ' . Uri::base());
						exit();
					}

					if (!empty($sid)) {
						$student = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($sid);
						echo '<h1>' . Text::_('SETUP_PUBLIC_UPLOAD_FILE') . '</h1><p>' . sprintf(Text::_('COM_EMUNDUS_REFERENT_ADD_LETTER_INTRO'), $student->name ) . '</p><br/>';
					}
				}
			}
		}
		else {
			$this->app->redirect(Route::_('index.php?option=com_content&view=article&id='.$article_id));
			exit();
		}
	}

	public function onBeforeCalculations()
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		require_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'checklist.php');

		$query = $this->_db->getQuery(true);

		$eMConfig = ComponentHelper::getParams('com_emundus');
		$alert_new_attachment = $eMConfig->get('alert_new_attachment');

		$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

		$m_files = new EmundusModelFiles();
		$h_checklist = new EmundusHelperChecklist();
		$m_emails   = new EmundusModelEmails();

		$files = $_FILES['jos_emundus_uploads___filename'];
		$key_id = $this->app->getInput()->get('keyid');
		$user_id = $this->app->getInput()->getInt('jos_emundus_uploads___user_id',0);
		$fnum = $this->app->getInput()->getString('jos_emundus_uploads___fnum','');
		$sid = $this->app->getInput()->getInt('sid',0);
		$attachment_id = $this->app->getInput()->getInt('jos_emundus_uploads___attachment_id',0);
		$formid      = $this->app->getInput()->getInt('formid', 0);

		$article_id  = $this->getParams()->get('emundusattachmentpublic_articleid',61);


		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.filerequest.php'], Log::ALL, ['com_emundus']);

		try {
			$query->select('student_id, attachment_id, keyid')
				->from($this->_db->quoteName('#__emundus_files_request'))
				->where($this->_db->quoteName('keyid') . ' = ' . $this->_db->quote($key_id));
			$this->_db->setQuery($query);
			$file_request = $this->_db->loadObject();

			if ($files['size'] == 0) {
				$query->clear()
					->select('id')
					->from($this->_db->quoteName('#__menu'))
					->where($this->_db->quoteName('link') . ' = ' . $this->_db->quote('index.php?option=com_fabrik&view=form&formid=' . $formid));
				$this->_db->setQuery($query);
				$item_id = $this->_db->loadResult();

				$link_upload = 'index.php?option=com_fabrik&view=form&formid='.$formid.'&Itemid='.$item_id.'&jos_emundus_uploads___user_id[value]='.$sid.'&jos_emundus_uploads___attachment_id[value]='.$file_request->attachment_id.'&sid='.$sid.'&keyid='.$key_id;

				if ($files['error'] == 4) {
					$this->app->enqueueMessage(Text::_('WARNING: No file selected, please select a file', 'error'));
				} else {
					$this->app->enqueueMessage(Text::_('WARNING: You just upload an empty file, please check out your file', 'error'));
				}

				$this->app->redirect(Route::_($link_upload));
			}

			if ($user_id != $file_request->student_id || $attachment_id != $file_request->attachment_id) {
				Log::add("PLUGIN emundus-attachment_public [".$key_id."]: ".Text::_("ERROR_ACCESS_DENIED"), Log::ERROR, 'com_emundus');
				$this->app->redirect(Uri::base());
				exit();
			}

			$student = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
			if (empty($student)) {
				Log::add("PLUGIN emundus-attachment_public [".$key_id."]: ".JText::_("ERROR_STUDENT_NOT_SET"), Log::ERROR, 'com_emundus');
				$this->app->redirect(Uri::base());
			}

			$query->clear()
				->select('profile')
				->from($this->_db->quoteName('#__emundus_users'))
				->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($user_id));
			$this->_db->setQuery($query);
			$profile = $this->_db->loadResult();

			$query->clear()
				->select('ap.displayed, attachment.lbl, attachment.value')
				->from($this->_db->quoteName('#__emundus_setup_attachments', 'attachment'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_attachment_profiles', 'ap') . ' ON attachment.id = ap.attachment_id AND ap.profile_id=' . $profile)
				->where($this->_db->quoteName('attachment.id') . ' =' . $attachment_id);
			$this->_db->setQuery($query);
			$attachement_params = $this->_db->loadObject();

			$query->clear()
				->select('id,filename')
				->from($this->_db->quoteName('#__emundus_uploads'))
				->where($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($attachment_id))
				->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($user_id))
				->order('id DESC');
			$this->_db->setQuery($query);
			$upload = $this->_db->loadObject();

			if (!empty($upload->id) == 0) {
				Log::add("PLUGIN emundus-attachment_public [".$key_id."]: ".Text::_("ERROR_FILE_NOT_FOUND"), Log::ERROR, 'com_emundus');
				die(Text::_("ERROR_FILE_NOT_FOUND"));
			}

			$fnumInfos = $m_files->getFnumInfos($fnum);
			$nom = $h_checklist->setAttachmentName($upload->filename, $attachement_params->lbl, $fnumInfos);

			if (!file_exists(EMUNDUS_PATH_ABS.$user_id) && (!mkdir(EMUNDUS_PATH_ABS.$user_id, 0777, true) || !copy(EMUNDUS_PATH_ABS.'index.html', EMUNDUS_PATH_ABS.$user_id.DS.'index.html'))) {
				Log::add("PLUGIN emundus-attachment_public [".$key_id."]: ".Text::_("ERROR_CANNOT_CREATE_USER_FILE"), Log::ERROR, 'com_emundus');
				die(Text::_('ERROR_CANNOT_CREATE_USER_FILE'));
			}

			if (!rename(JPATH_SITE.$upload->filename, EMUNDUS_PATH_ABS.$user_id.DS.$nom)) {
				Log::add("PLUGIN emundus-attachment_public [".$key_id."]: ".Text::_("ERROR_MOVING_UPLOAD_FILE"), Log::ERROR, 'com_emundus');
				die(Text::_("ERROR_MOVING_UPLOAD_FILE"));
			}

			$query->clear()
				->update($this->_db->quoteName('#__emundus_uploads'))
				->set($this->_db->quoteName('filename') . ' = ' . $this->_db->quote($nom))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($upload->id));
			$this->_db->setQuery($query);
			$this->_db->execute();

			$query->clear()
				->update($this->_db->quoteName('#__emundus_files_request'))
				->set($this->_db->quoteName('uploaded') . ' = 1')
				->set($this->_db->quoteName('filename') . ' = ' . $this->_db->quote($nom))
				->where($this->_db->quoteName('keyid') . ' = ' . $this->_db->quote($key_id));
			$this->_db->setQuery($query);
			$this->_db->execute();

			$query->clear()
				->select('se.id, se.subject, se.emailfrom, se.name, se.message, et.Template')
				->from($this->_db->quoteName('#__emundus_setup_emails', 'se'))
				->leftJoin($this->_db->quoteName('#__emundus_email_templates', 'et') . ' ON se.email_tmpl = et.id')
				->where($this->_db->quoteName('se.lbl') . ' = ' . $this->_db->quote('attachment'));
			$this->_db->setQuery($query);
			$obj = $this->_db->loadObject();

			$post = [
				'FNUM'           => $fnum,
				'USER_NAME'      => $student->name,
				'SITE_URL'       => Uri::base(),
				'USER_EMAIL'     => $student->email,
				'ID'             => $student->id,
				'NAME'           => $student->name,
			];
			$tags = $m_emails->setTags($student->id, $post, $fnum, '', $obj->subject.$obj->message);

			$patterns = array ('/\[ID\]/', '/\[NAME\]/', '/\[EMAIL\]/','/\n/');
			$replacements = array ($student->id, $student->name, $student->email, '<br />');

			$message = $m_emails->setTagsFabrik($obj->message, [$fnum]);
			$subject = $m_emails->setTagsFabrik($obj->subject, [$fnum]);

			// Mail au candidat
			$fileURL = Uri::base().'/'.EMUNDUS_PATH_REL.$upload->user_id.'/'.$nom;

			$recipient[] = $student->email;

			$subject = preg_replace($tags['patterns'], $tags['replacements'], $subject);
			$body = $message;

			$mail_from_address = $this->app->get('mailfrom');
			$from = $obj->emailfrom;
			$fromname = !empty($obj->name) ? $obj->name : $this->app->get('fromname');

			if ($obj->Template) {
				$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $obj->Template);
			}
			$body = preg_replace($tags['patterns'], $tags['replacements'], $body);


			$sender = [
				$mail_from_address,
				$fromname
			];

			$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
			$mailer->setSender($sender);
			if(!empty($from) && !empty($fromname)) {
				$mailer->addReplyTo($from, $fromname);
			}
			$mailer->addRecipient($recipient);
			$mailer->setSubject($subject);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$mailer->setBody($body);

			if ($mailer->Send() !== true) {
				Log::add("PLUGIN emundus-attachment_public [".$key_id."]: ".Text::_("ERROR_CANNOT_SEND_EMAIL"), Log::ERROR, 'com_emundus');
				echo 'Error sending email: '; die();
			} else {
				$query->clear()
					->insert($this->_db->quoteName('#__messages'))
					->columns($this->_db->quoteName(['user_id_from', 'user_id_to', 'subject', 'message', 'date_time']))
					->values('62, '.$student->id.', '.$this->_db->quote($subject).', '.$this->_db->quote($body).', NOW()');
				$this->_db->setQuery($query);
				$this->_db->execute();
			}
		}
		catch (Exception $e) {
			// catch any database errors.
			Log::add(Uri::getInstance(). '::' .$query, Log::ERROR, 'com_emundus');
		}

		$this->app->redirect(Route::_('index.php?option=com_content&view=article&id='.$article_id));
	}
}
