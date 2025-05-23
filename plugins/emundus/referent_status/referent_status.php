<?php
/**
 * @package	eMundus
 * @version	6.6.5
 * @author	eMundus.fr
 * @copyright (C) 2019 eMundus SOFTWARE. All rights reserved.
 * @license	GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

/**
 * A cron task to email records to a give set of users (incomplete application)
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.emundusrecall
 * @since       3.0
 */

class PlgEmundusReferent_status extends CMSPlugin {

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);

		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_emundus.emundusreferent_status.php'), Log::ALL, array('com_emundus'));
	}


	function onAfterStatusChange($fnum,$state) {

		$referent_mail_id = $this->params->get('referent_status_mail_id', '');
		$status_to_check = $this->params->get('referent_status_step', '');
		$fabrik_elts = $this->params->get('referent_status_fabrik_elements', '');
		$attachments_id = $this->params->get('referent_status_attachments_letters', '');
		$campaigns = $this->params->get('referent_status_campaigns', '');
		$fabrik_form = $this->params->get('referent_status_fabrik_form', '68');

		if ($status_to_check != $state) {
			return false;
		}

		$this->log = '';

		// Get list of applicants to notify
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('DISTINCT u.id, u.email, eu.firstname, eu.lastname, ecc.fnum, ecc.campaign_id, ecc.applicant_id, esc.start_date, esc.end_date, esc.label, DATEDIFF( esc.end_date , now()) as left_days')
			->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
			->leftJoin($db->quoteName('#__users', 'u') . ' ON u.id=ecc.applicant_id')
			->leftJoin($db->quoteName('#__emundus_users', 'eu') . ' ON eu.user_id=u.id')
			->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.id=ecc.campaign_id')
			->where('ecc.fnum = '.$db->quote($fnum))
			->where('ecc.campaign_id IN ('.$campaigns.')')
			->where('ecc.status IN('.$status_to_check.')');
		$db->setQuery($query);
		$applicant = $db->loadObject();

		// Generate emails from template and store it in message table
		if (!empty($applicant)) {
			include_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
			$m_emails = new EmundusModelEmails;

			if (!empty($referent_mail_id)) {
				$email = $m_emails->getEmailById($referent_mail_id);
			} else {
				$email = $m_emails->getEmail('referent_letter');
			}

			// Récupération de la pièce jointe : modele de lettre
			$query->clear()
				->select('esp.reference_letter')
				->from($db->quoteName('#__emundus_setup_profiles', 'esp'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.profile_id = esp.id')
				->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.campaign_id = esc.id')
				->where('ecc.fnum = '.$db->quote($fnum));
			$db->setQuery($query);
			$obj_letter = $db->loadRowList();

			$attachment = array();
			if (!empty($obj_letter[0][0])) {
				$attachment[] = JPATH_BASE.str_replace("\\", "/", $obj_letter[0][0]);
			}

			if ($this->getFilesExist($applicant->fnum, $attachments_id) != sizeof(explode(',', $attachments_id))) {
				$mailer = Factory::getMailer();
				$mailer->SMTPDebug = true;

				$baseurl = Uri::root();

				$referents_emails = array();
				$elts = explode(',', $fabrik_elts);

				foreach ($elts as $elt) {
					$table = explode('__', $elt)[0];
					$field = explode('__', $elt)[1];

					$query->clear()
						->select($field)
						->from($db->quoteName('#__' . $table))
						->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($applicant->fnum));

					$db->setQuery($query);
					$referents_emails[] = $db->loadResult();
				}


				foreach ($referents_emails as $key => $referentEmail) {
					$query->clear()
						->select('COUNT(id)')
						->from($db->quoteName('#__emundus_files_request'))
						->where($db->quoteName('email') . ' LIKE ' . $db->quote($referentEmail))
						->andWhere($db->quoteName('uploaded') . ' = 1 ')
						->andWhere($db->quoteName('fnum') . ' LIKE '. $db->quote($applicant->fnum));
					$db->setQuery($query);
					$uploaded = $db->loadResult();

					if (empty($uploaded)) {
						$referent_key = md5($this->rand_string(20) . time());
						$link_upload = $baseurl . 'index.php?option=com_fabrik&c=form&view=form&formid=' . $fabrik_form . '&tableid=71&keyid=' . $referent_key . '&sid=' . $applicant->applicant_id;

						$post = array(
							'FNUM' => $applicant->fnum,
							'DEADLINE' => HTMLHelper::_('date', $applicant->end_date, Text::_('DATE_FORMAT_LC2'), null),
							'CAMPAIGN_LABEL' => $applicant->label,
							'CAMPAIGN_START' => HTMLHelper::_('date', $applicant->start_date, Text::_('DATE_FORMAT_LC2'), null),
							'CAMPAIGN_END' => HTMLHelper::_('date', $applicant->end_date, Text::_('DATE_FORMAT_LC2'), null),
							'FIRSTNAME' => $applicant->firstname,
							'LASTNAME' => strtoupper($applicant->lastname),
							'UPLOAD_URL' => $link_upload,
							'SITE_URL' => Uri::base(),
						);
						$tags = $m_emails->setTags($applicant->id, $post, null, '', $email->emailfrom.$email->name.$email->subject.$email->message);

						$from = preg_replace($tags['patterns'], $tags['replacements'], $email->emailfrom);
						$from_id = 62;
						$fromname = preg_replace($tags['patterns'], $tags['replacements'], $email->name);
						$to = $referentEmail;
						$to_id = $applicant->id;
						$subject = preg_replace($tags['patterns'], $tags['replacements'], $email->subject);
						$body = preg_replace($tags['patterns'], $tags['replacements'], $email->message);
						$body = $m_emails->setTagsFabrik($body, [$applicant->fnum]);


						$config = Factory::getConfig();

						$email_from_sys = $config->get('mailfrom');
						$email_from = $from;

						// If the email sender has the same domain as the system sender address.
						if (!empty($email_from) && substr(strrchr($email_from, "@"), 1) === substr(strrchr($email_from_sys, "@"), 1)) {
							$mail_from_address = $email_from;
						} else {
							$mail_from_address = $email_from_sys;
						}

						// Set sender
						$sender = [
							$mail_from_address,
							$fromname
						];

						$mailer->setSender($sender);
						$mailer->addRecipient($to);
						$mailer->setSubject($subject);
						$mailer->isHTML(true);
						$mailer->Encoding = 'base64';
						$mailer->setBody($body);
						if(!empty($attachment)){
							$mailer->addAttachment($attachment);
						}

						// Send emails
						$send = $mailer->Send();

						$mailer->clearAddresses();
						$mailer->clearAllRecipients();
						$mailer->smtpClose();

						if ($send !== true) {
							$this->log .= "\n Error sending email : " . $to;
						} else {

							$attachments = explode(',', $attachments_id);
							$this->setEmailSend($applicant->fnum, $attachments[$key], $applicant->id, $referentEmail, $applicant->campaign_id, $referent_key);
							$message = array(
								'user_id_from' => $from_id,
								'user_id_to' => $to_id,
								'subject' => $subject,
								'message' => $body,
								'email_to' => $to
							);
							$m_emails->logEmail($message);
							$this->log .= '\n' . Text::_('MESSAGE') . ' ' . Text::_('SENT') . ' ' . Text::_('TO') . ' ' . $to . ' :: ' . $body;
						}

						// to avoid being considered as a spam process or DDoS
						sleep(5);

					}
				}
			}
		}
		return true;
	}


	public function getFilesRequest($fnum, $attachment_id) {

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		Log::addLogger(['text_file' => 'com_emundus.cron.referentStatus.error.php'], Log::ERROR, 'com_emundus');

		$query->select('COUNT(id)')
			->from($db->quoteName('#__emundus_files_request'))
			->where($db->quoteName('attachment_id') . ' IN (' . $db->quote($attachment_id).') AND '.$db->quoteName('fnum').' LIKE '.$db->quote($fnum));

		$db->setQuery($query);
		try {
			return $db->loadResult();
		} catch (Exception $e) {
			Log::add('Error getting emails : '.$e->getMessage(), Log::ERROR, 'com_emundus');
			return null;
		}
	}


	private function getFilesExist($fnum, $attachments_id) {

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		Log::addLogger(['text_file' => 'com_emundus.cron.referentStatus.error.php'], Log::ERROR, 'com_emundus');

		$query->select('COUNT(id)')
			->from($db->quoteName('#__emundus_uploads'))
			->where($db->quoteName('attachment_id') . ' IN (' . $db->quote($attachments_id) .') AND'. $db->quoteName('fnum') .' LIKE '. $db->quote($fnum));

		$db->setQuery($query);
		try {
			return $db->loadResult();
		} catch (Exception $e) {
			Log::add('Error getting uploads : '.$e->getMessage(), Log::ERROR, 'com_emundus');
			return null;
		}
	}

	private function setEmailSend($fnum,$attachment_id,$applicant_id,$referent_email,$campaign_id,$referent_key){
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		Log::addLogger(['text_file' => 'com_emundus.cron.referentStatus.error.php'], Log::ERROR, 'com_emundus');
		try {
			$query->clear()
				->insert($db->quoteName('#__emundus_files_request'));
			$query->set($db->quoteName('time_date') . ' = ' . $db->quote(date('Y-m-d H:i:s')))
				->set($db->quoteName('student_id') . ' = ' . $db->quote($applicant_id))
				->set($db->quoteName('fnum') . ' = ' . $db->quote($fnum))
				->set($db->quoteName('keyid') . ' = ' . $db->quote($referent_key))
				->set($db->quoteName('attachment_id') . ' = ' . $db->quote($attachment_id))
				->set($db->quoteName('campaign_id') . ' = ' . $db->quote($campaign_id))
				->set($db->quoteName('email') . ' = ' . $db->quote($referent_email));
			$db->setQuery($query);
			$db->execute();
			return true;
		} catch (Exception $e) {
			Log::add('Error getting uploads : '.$e->getMessage(), Log::ERROR, 'com_emundus');
			return null;
		}
	}

	private function rand_string($len, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789') {
		$string = '';
		for ($i = 0; $i < $len; $i++) {
			$pos = rand(0, strlen($chars)-1);
			$string .= $chars[$pos];
		}
		return $string;
	}
}
