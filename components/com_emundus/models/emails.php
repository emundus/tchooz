<?php
/**
 * Profile Model for eMundus Component
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Registry\Registry;
use Tchooz\Entities\Emails\TagEntity;
use Tchooz\Entities\Messages\TriggerEntity;
use Tchooz\Enums\Emails\TagType;

class EmundusModelEmails extends JModelList
{
	private $app;

	protected $_db;
	private $_em_user;
	private $_user;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	public function __construct()
	{
		parent::__construct();

		require_once JPATH_SITE . '/components/com_emundus/helpers/emails.php';

		$this->app = Factory::getApplication();

		$this->_db      = Factory::getContainer()->get('DatabaseDriver');
		$this->_em_user = $this->app->getSession()->get('emundusUser');
		$this->_user    = $this->app->getIdentity();

		Log::addLogger(['text_file' => 'com_emundus.email.error.php'], Log::ERROR);
	}

	/**
	 * Get email template by code
	 *
	 * @param    $lbl string the email code
	 *
	 * @return   object|bool  The email template object
	 *
	 * @since version v6
	 */
	public function getEmail($lbl)
	{
		$email = null;

		if (!empty($lbl)) {
			$query = $this->_db->getQuery(true);
			$query->select('se.*, et.Template')
				->from('#__emundus_setup_emails AS se')
				->leftJoin('#__emundus_email_templates AS et ON et.id = se.email_tmpl')
				->where('se.lbl like ' . $this->_db->quote($lbl));

			try {
				$this->_db->setQuery($query);
				$email = $this->_db->loadObject();
			}
			catch (Exception $e) {
				error_log($e->getMessage(), 0);
				Log::add($query, Log::ERROR, 'com_emundus.email');
			}
		}

		return $email;
	}

	/**
	 * Get email template by ID
	 *
	 * @param   $id  int The email template ID
	 * @param   $lbl string the email code
	 *
	 * @return  object  The email template object
	 *
	 * @since version v6
	 */
	public function getEmailById($id)
	{
		$email = new stdClass();

		if (!empty($id)) {
			$query = $this->_db->getQuery(true);

			$query->select('ese.*, et.Template')
				->from('#__emundus_setup_emails AS ese')
				->leftJoin('#__emundus_email_templates AS et ON et.id = ese.email_tmpl')
				->where('ese.id = ' . $this->_db->quote($id));

			try {
				$this->_db->setQuery($query);
				$email = $this->_db->loadObject();
			}
			catch (Exception $e) {
				Log::add('Failed to get email by id ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $email;
	}

	/**
	 * Get email definition to trigger on Status changes
	 *
	 * @param   $step           INT The status of application
	 * @param   $code           array of programme code
	 * @param   $to_applicant   int define if trigger concern selected fnum from list or not. Can be 0, 1
	 *
	 * @return  array           Emails templates and recipient to trigger
	 *
	 * @since version v6
	 */
	public function getEmailTrigger($step, $codes, $to_applicant = 0, $to_current_user = null, $student = null) {
		$emails_triggers = array();

		if (isset($step) && !empty($codes)) {
			$query = $this->_db->getQuery(true);
			$query->select('eset.id as trigger_id, eset.step, ese.*, eset.to_current_user, eset.to_applicant, eserp.programme_id, GROUP_CONCAT(DISTINCT esp.code) as code_prog, GROUP_CONCAT(DISTINCT esp.label) as label_prog, GROUP_CONCAT(DISTINCT eser.profile_id) as profile_id, GROUP_CONCAT(DISTINCT eserg.group_id) as group_id, GROUP_CONCAT(DISTINCT eseru.user_id) as user_id, et.Template, GROUP_CONCAT(ert.tags) as tags, GROUP_CONCAT(erca.candidate_attachment) as attachments, GROUP_CONCAT(erla.letter_attachment) as letter_attachments, GROUP_CONCAT(err1.receivers) as cc, GROUP_CONCAT(err2.receivers) as bcc, eset.all_program')
				->from($this->_db->quoteName('#__emundus_setup_emails_trigger', 'eset'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails','ese').' ON '.$this->_db->quoteName('ese.id').' = '.$this->_db->quoteName('eset.email_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_programme_id','eserp').' ON '.$this->_db->quoteName('eserp.parent_id').' = '.$this->_db->quoteName('eset.id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_programmes','esp').' ON '.$this->_db->quoteName('esp.id').' = '.$this->_db->quoteName('eserp.programme_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id','eser').' ON '.$this->_db->quoteName('eser.parent_id').' = '.$this->_db->quoteName('eset.id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_group_id','eserg').' ON '.$this->_db->quoteName('eserg.parent_id').' = '.$this->_db->quoteName('eset.id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_user_id','eseru').' ON '.$this->_db->quoteName('eseru.parent_id').' = '.$this->_db->quoteName('eset.id'))
				->leftJoin($this->_db->quoteName('#__emundus_email_templates','et').' ON '.$this->_db->quoteName('et.id').' = '.$this->_db->quoteName('ese.email_tmpl'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_tags','ert').' ON '.$this->_db->quoteName('ert.parent_id').' = '.$this->_db->quoteName('eset.email_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment','erca').' ON '.$this->_db->quoteName('erca.parent_id').' = '.$this->_db->quoteName('eset.email_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment','erla').' ON '.$this->_db->quoteName('erla.parent_id').' = '.$this->_db->quoteName('eset.email_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers','err1').' ON '.$this->_db->quoteName('err1.parent_id').' = '.$this->_db->quoteName('eset.email_id').' AND '.$this->_db->quoteName('err1.type').' = '.$this->_db->quote('receiver_cc_email'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers','err2').' ON '.$this->_db->quoteName('err2.parent_id').' = '.$this->_db->quoteName('eset.email_id').' AND '.$this->_db->quoteName('err2.type').' = '.$this->_db->quote('receiver_bcc_email'))
				->where($this->_db->quoteName('eset.step').' = '.$this->_db->quote($step))
				->andWhere($this->_db->quoteName('eset.to_applicant').' IN ('.$to_applicant .')')
				->andWhere('eset.email_id > 0');

			if (!is_null($to_current_user)) {
				$query->andWhere($this->_db->quoteName('eset.to_current_user') . ' IN (' . $to_current_user . ')');
			}
			$query->andWhere('(' . $this->_db->quoteName('esp.code').' IN ('.implode(',', $this->_db->quote($codes)) .') OR ' . $this->_db->quoteName('eset.all_program') . ' = 1)')
				->group('eset.id');
			try {
				$this->_db->setQuery($query);
				$results = $this->_db->loadObjectList();
			}
			catch (Exception $e) {
				JLog::add('Error when get emails triggers with query : ' . $query->__toString(), JLog::ERROR, 'com_emundus');
			}

			$triggers = array_filter($results, function($obj){
				if (empty($obj->trigger_id)) { return false; }
				return true;
			});

			if (!empty($triggers) && !empty($triggers[0]->trigger_id)) {
				$all_codes = [];
				$query->clear()
					->select('DISTINCT esp.code')
					->from($this->_db->quoteName('#__emundus_setup_programmes','esp'))
					->where('published = 1');

				try {
					$this->_db->setQuery($query);
					$all_codes = $this->_db->loadColumn();
				}
				catch (Exception $e) {
					JLog::add('Error when get all codes with query : ' . $query->__toString(), JLog::ERROR, 'com_emundus');
				}

				foreach ($triggers as $trigger) {
					$codes_prog = explode(',',$trigger->code_prog);
					if ($trigger->all_program == 1)
					{
						$codes_prog = $all_codes;
					}

					// We separate by program code so that, for each trigger, we still know for what programs they must be used. Instead of sending every trigger for every file updated
					foreach($codes_prog as $code_prog) {
						// email tmpl
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['subject'] = $trigger->subject;
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['emailfrom'] = $trigger->emailfrom;
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['message'] = $trigger->message;
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['name'] = $trigger->name;
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['tags'] = $trigger->tags;
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['attachments'] = $trigger->attachments;
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['letter_attachment'] = $trigger->letter_attachments;
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['button'] = $trigger->button;

						// This is the email template model, the HTML structure that makes the email look good.
						$emails_triggers[$trigger->trigger_id][$code_prog]['tmpl']['template'] = $trigger->Template;

						// default recipients
						if (!empty($trigger->profile_id)) {
							$emails_triggers[$trigger->trigger_id][$code_prog]['to']['profile'] = explode(',', $trigger->profile_id);
						}

						if (!empty($trigger->group_id)) {
							$emails_triggers[$trigger->trigger_id][$code_prog]['to']['group'] = explode(',', $trigger->group_id);
						}

						if (!empty($trigger->user_id)) {
							$emails_triggers[$trigger->trigger_id][$code_prog]['to']['user'] = explode(',', $trigger->user_id);
						}

						$emails_triggers[$trigger->trigger_id][$code_prog]['to']['to_applicant'] = $trigger->to_applicant;
						$emails_triggers[$trigger->trigger_id][$code_prog]['to']['to_current_user'] = $trigger->to_current_user;
						$emails_triggers[$trigger->trigger_id][$code_prog]['to']['cc'] = $trigger->cc;
						$emails_triggers[$trigger->trigger_id][$code_prog]['to']['bcc'] = $trigger->bcc;
					}
				}

				// generate list of default recipient email + name
				foreach ($emails_triggers as $et_key => $codes) {
					$trigger_id = $et_key;

					foreach ($codes as $key => $tmpl) {
						$code = $key;
						$recipients = array();
						$as_where = false;
						$where = '';

						if (isset($tmpl['to']['profile'])) {
							if (count($tmpl['to']['profile']) > 0) {
								$where = ' (eu.profile IN ('.implode(',', $tmpl['to']['profile']).') OR eup.profile_id IN ('.implode(',', $tmpl['to']['profile']).'))';
								$as_where = true;
							}
						}

						if (isset($tmpl['to']['group'])) {
							if (count($tmpl['to']['group']) > 0) {
								$where .= $as_where?' OR ':'';
								$where .= ' eg.group_id IN ('.implode(',', $tmpl['to']['group']).')';
								$as_where = true;
							}
						}

						if (isset($tmpl['to']['user'])) {
							if (count(@$tmpl['to']['user']) > 0) {
								$where .= $as_where?' OR ':'';
								$where .= 'u.block=0 AND u.id IN ('.implode(',', $tmpl['to']['user']).')';
								$as_where = true;
							}
						}

						if ($as_where) {
							$query = 'SELECT DISTINCT u.id, u.name, u.email, eu.university_id
                                    FROM #__users as u
                                    LEFT JOIN #__emundus_users as eu on eu.user_id=u.id
                                    LEFT JOIN #__emundus_groups as eg on eg.user_id=u.id
                                    LEFT JOIN #__emundus_users_profiles as eup on eup.user_id=eu.user_id
                                    WHERE '.$where.'
                                    GROUP BY u.id';
							$this->_db->setQuery( $query );
							$users = $this->_db->loadObjectList();

							foreach ($users as $user) {
								$recipients[$user->id] = array('id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'university_id' => $user->university_id);
							}
						}

						// add applicant to recipients if this parameter is active and if the action was done by the applicant (in that case, $student is not empty)
						if ($tmpl['to']['to_current_user'] == 1 && !empty($student)) {
							$recipients[$student->id] = array('id' => $student->id, 'name' => $student->name, 'email' => $student->email, 'university_id' => $student->university_id);
						}

						$emails_triggers[$trigger_id][$code]['to']['recipients'] = $recipients;
					}
				}
			}
		}

		return $emails_triggers;
	}

	/**
	 * Send email triggered for Status
	 *
	 * @param   $step           int The status of application
	 * @param   $code           array of programme code
	 * @param   $to_applicant   int define if trigger concern selected fnum or not
	 * @param   $student        Object Joomla user
	 *
	 * @return  bool           Emails templates and recipient to trigger
	 *
	 * @throws Exception
	 * @since version v6
	 */
	public function sendEmailTrigger($step, $code, $to_applicant = 0, $student = null, $to_current_user = null, $trigger_emails = null): array
	{
		$emails_sent = [];
		$app = Factory::getApplication();
		$config = $app->getConfig();

		// Get default mail sender info
		$mail_from_sys = $config->get('mailfrom');
		$mail_from_sys_name = $config->get('fromname');

		jimport('joomla.log.log');
		JLog::addLogger(array('text_file' => 'com_emundus.email.php'), JLog::ALL, array('com_emundus.email'));

		if (empty($trigger_emails)) {
			$trigger_emails = $this->getEmailTrigger($step, $code, $to_applicant, $to_current_user, $student);
		}

		if (count($trigger_emails) > 0) {
			// get current applicant course
			include_once(JPATH_SITE.'/components/com_emundus/models/campaign.php');
			$m_campaign = new EmundusModelCampaign;
			$campaign = $m_campaign->getCampaignByID($student->campaign_id);
			$post = array(
				'APPLICANT_ID' => $student->id,
				'DEADLINE' => JHTML::_('date', $campaign['end_date'], Text::_('DATE_FORMAT_LC2'), null),
				'APPLICANTS_LIST' => '',
				'EVAL_CRITERIAS' => '',
				'EVAL_PERIOD' => '',
				'CAMPAIGN_LABEL' => $campaign['label'],
				'CAMPAIGN_YEAR' => $campaign['year'],
				'CAMPAIGN_START' => JHTML::_('date', $campaign['start_date'], Text::_('DATE_FORMAT_LC2'), null),
				'CAMPAIGN_END' => JHTML::_('date', $campaign['end_date'], Text::_('DATE_FORMAT_LC2'), null),
				'CAMPAIGN_CODE' => $campaign['training'],
				'FNUM' => $student->fnum,
				'COURSE_NAME' => $campaign['label']
			);

			require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/emails.php');
			$h_access = new EmundusHelperAccess();
			$h_emails = new EmundusHelperEmails();
			$m_files = new EmundusModelFiles();

			foreach ($trigger_emails as $trigger_id => $trigger_email) {
				// Add applicant if e-mail is to be sent to the applicant
				if ($trigger_email[$student->code]['to']['to_applicant'] == 1) {
					$trigger_email[$student->code]['to']['recipients'][$student->id] = array('id' => $student->id, 'name' => $student->name, 'email' => $student->email, 'university_id' => $student->university_id);
				}

				$post['BUTTON_TEXT'] = $trigger_email[$student->code]['tmpl']['button'];

				$tags = $this->setTags($student->id, $post, $student->fnum, '', $trigger_email[$student->code]['tmpl']['emailfrom'].$trigger_email[$student->code]['tmpl']['name'].$trigger_email[$student->code]['tmpl']['subject'].$trigger_email[$student->code]['tmpl']['message']);

				// If no mail sender info is provided, we use the system global config.
				if(!empty($trigger_email[$student->code]['tmpl']['emailfrom'])) {
					$mail_from = preg_replace($tags['patterns'], $tags['replacements'], $trigger_email[$student->code]['tmpl']['emailfrom']);
				} else {
					$mail_from = $mail_from_sys;
				}
				if(!empty($trigger_email[$student->code]['tmpl']['name'])){
					$mail_from_name = preg_replace($tags['patterns'], $tags['replacements'], $trigger_email[$student->code]['tmpl']['name']);
				} else {
					$mail_from_name = $mail_from_sys_name;
				}

				$mail_from_address = $mail_from_sys;

				foreach ($trigger_email[$student->code]['to']['recipients'] as $recipient) {
					// Check if the user has access to the file
					if (!$h_access->isUserAllowedToAccessFnum($recipient['id'],$student->fnum) || !$h_emails->assertCanSendMailToUser($recipient['id'])) {
						continue;
					}

					$mailer = JFactory::getMailer();

					$to = $recipient['email'];
					$to_id = $recipient['id'];
					$subject = preg_replace($tags['patterns'], $tags['replacements'], $trigger_email[$student->code]['tmpl']['subject']);

					$body = $trigger_email[$student->code]['tmpl']['message'];
					if ($trigger_email[$student->code]['tmpl']['template']) {
						$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $trigger_email[$student->code]['tmpl']['template']);
					}
					$body = preg_replace($tags['patterns'], $tags['replacements'], $body);
					$body = $this->setTagsFabrik($body, array($student->fnum));

					$toAttach= [];
					if(!empty($trigger_email[$student->code]['tmpl']['letter_attachment'])){
						include_once(JPATH_SITE . '/components/com_emundus/models/evaluation.php');
						$m_eval = new EmundusModelEvaluation();
						$letters = $m_eval->generateLetters($student->fnum, explode(',', $trigger_email[$student->code]['tmpl']['letter_attachment']), 1, 0, 0);

						foreach($letters->files as $filename){
							if(!empty($filename['filename'])) {
								$toAttach[] = EMUNDUS_PATH_ABS . $student->id . '/' . $filename['filename'];
							}
						}
					}
					if(!empty($trigger_email[$student->code]['tmpl']['attachments'])){
						require_once (JPATH_SITE . '/components/com_emundus/models/application.php');
						$m_application = new EmundusModelApplication();
						$attachments = $m_application->getAttachmentsByFnum($student->fnum,null, explode(',', $trigger_email[$student->code]['tmpl']['attachments']));

						foreach ($attachments as $attachment) {
							if(!empty($attachment->filename)) {
								$toAttach[] = EMUNDUS_PATH_ABS . $student->id . '/' . $attachment->filename;
							}
						}
					}

					if (!empty($trigger_email[$student->code]['to']['cc'])) {
						$cc = explode(',',$trigger_email[$student->code]['to']['cc']);
						$mailer->addCc($cc);
					}
					if (!empty($trigger_email[$student->code]['to']['bcc'])) {
						$bcc = explode(',',$trigger_email[$student->code]['to']['bcc']);
						$mailer->addBCC($bcc);
					}

					$mailer->setSender([$mail_from_address, $mail_from_name]);
					$mailer->addReplyTo($mail_from, $mail_from_name);
					$mailer->addRecipient($to);
					$mailer->addAttachment($toAttach);
					$mailer->setSubject($subject);
					$mailer->isHTML(true);
					$mailer->Encoding = 'base64';
					$mailer->setBody($body);

					$custom_email_tag = EmundusHelperEmails::getCustomHeader();
					if(!empty($custom_email_tag))
					{
						$mailer->addCustomHeader($custom_email_tag);
					}

					try {
						$sent = $mailer->Send();
					} catch (Exception $e) {
						$sent = false;
						JLog::add('eMundus Triggers - PHP Mailer send failed ' . $e->getMessage(), JLog::ERROR, 'com_emundus.email');
					}

					if ($sent !== true) {
						// echo 'Error sending email: ' . $sent;
						JLog::add($sent, JLog::ERROR, 'com_emundus.email');
					} else {
						$from_id = !empty(JFactory::getUser()->id) ? JFactory::getUser()->id : 62;

						if(!empty($trigger_email[$student->code]['tmpl']['tags'])) {
							$tags = array_filter(explode(',',$trigger_email[$student->code]['tmpl']['tags']));

							if(!empty($tags))
							{
								$m_files->tagFile([$student->fnum], $tags, $from_id);
							}
						}

						$emails_sent[] = $to;
						$message = array(
							'user_id_from' => $from_id,
							'user_id_to' => $to_id,
							'subject' => $subject,
							'message' => $body,
							'email_id' => $trigger_email[$student->code]['tmpl']['email_id'],
							'email_to' => $to
						);
						$this->logEmail($message, $student->fnum);
					}
				}
			}
		}

		return $emails_sent;
	}

	/**
	 * @description    replace body message tags [constant] by value
	 *
	 * @param          $user           Object      user object
	 * @param          $str            String      string with tags
	 * @param          $passwd         String      user password
	 *
	 * @return string $strval         String      str with tags replace by value
	 * @since          version v6
	 */
	public function setBody($user, $str, $passwd = '', $fnum = null)
	{
		$body = '';

		if (!empty($user) && !empty($user->id)) {
			$constants = $this->setConstants($user->id, null, $passwd, $fnum);

			if (!empty($constants['patterns']) && !empty($constants['replacements'])) {
				$body      = html_entity_decode(preg_replace($constants['patterns'], $constants['replacements'], $str), ENT_QUOTES);
			}
		}

		return $body;
	}

	public function replace($replacement, $str)
	{
		return preg_replace($replacement['patterns'], $replacement['replacements'], $str);
	}

	/**
	 * @description    Replace all accented characters by something else
	 *
	 * @param          $str              string
	 *
	 * @return         string            String with accents stripped
	 * @since          version v6
	 */
	public function stripAccents($str)
	{
		$unwanted_array = [
			'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
			'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
			'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
			'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
		];

		return strtr($str, $unwanted_array);
	}

	/**
	 * @description    get tags with Fabrik elementd IDs
	 *
	 * @param          $body           string
	 *
	 * @return         array           array of application file elements IDs
	 * @since          version v6
	 */
	public function getFabrikElementIDs($body)
	{
		preg_match_all('/\{(.*?)\}/', $body, $element_ids);

		return $element_ids;
	}

	/**
	 * @description    replace tags like {fabrik_element_id} by the application form value for current application file
	 *
	 * @param          $fnum           string  application file number
	 * @param          $element_ids    array   Fabrik element ID
	 *
	 * @return         array           array of application file elements values
	 * @since          version v6
	 */
	public function getFabrikElementValues($fnum, $element_ids)
	{
		require_once(JPATH_BASE . DS . 'components/com_emundus/helpers/list.php');
		$db = JFactory::getDBO();

		$element_details = @EmundusHelperList::getElementsDetailsByID('"' . implode('","', $element_ids) . '"');

		$element_values = array();
		foreach ($element_details as $value) {
			$query = 'SELECT ' . $value->element_name . ' FROM ' . $value->tab_name . ' WHERE fnum like ' . $db->Quote($fnum);
			$db->setQuery($query);
			$element_values[$value->element_id] = $db->loadResult();
		}

		return $element_values;
	}

	/**
	 * @description    replace tags like {fabrik_element_id} by the applicaiton form value in text
	 *
	 * @param          $body               string  source containing tags like {fabrik_element_id}
	 * @param          $element_values     array   Array of values index by Fabrik elements IDs
	 *
	 * @return         string              String with values
	 * @since          version v6
	 */
	public function setElementValues($body, $element_values)
	{
		foreach ($element_values as $key => $value) {
			$body = str_replace('{' . $key . '}', $value, $body);
		}

		return $body;
	}

	/**
	 * @param $user_id
	 * @param $post
	 * @param $passwd
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since version v6
	 */
	public function setConstants(?int $user_id, ?array $post = null, string $passwd = '', ?string $fnum = null, string $content = ''): array
	{
		$patterns = array();
		$replacements = array();
		$app          = Factory::getApplication();

		if ($app->getName() === 'cli') {
			return array('patterns' => $patterns, 'replacements' => $replacements);
		}

		$current_user = $app->getIdentity();
		if (!empty($current_user)) {
			if(!empty($user_id))
			{
				$user = $current_user->id == $user_id ? $current_user : Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
			}
			else {
				$user = $current_user;
			}
		}
		else {
			$user = !empty($user) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id) : null;
		}
		$config = $app->getConfig();

		if (!empty($user) && !empty($user->id)) {
			$logo = EmundusHelperEmails::getLogo();

			$sitename = $config->get('sitename');
			$siteurl = $config->get('live_site');

			$base_url = Uri::base();
			if ($app->isClient('administrator')) {
				$base_url = Uri::root();
			}

			$activation = $user->get('activation');

			$patterns     = array(
				'/\[ID\]/', '/\[NAME\]/', '/\[EMAIL\]/', '/\[SENDER_MAIL\]/', '/\[USERNAME\]/', '/\[USER_ID\]/', '/\[USER_NAME\]/', '/\[USER_EMAIL\]/', '/\n/', '/\[USER_USERNAME\]/', '/\[PASSWORD\]/',
				'/\[ACTIVATION_URL\]/', '/\[ACTIVATION_URL_RELATIVE\]/', '/\[SITE_URL\]/', '/\[SITE_NAME\]/',
				'/\[APPLICANT_ID\]/', '/\[APPLICANT_NAME\]/', '/\[APPLICANT_EMAIL\]/', '/\[APPLICANT_USERNAME\]/', '/\[CURRENT_DATE\]/', '/\[LOGO\]/'
			);
			$replacements = array(
				$user->id, $user->name, $user->email, $current_user->email, $user->username, $current_user->id, $current_user->name, $current_user->email, ' ', $current_user->username, $passwd,
				$base_url . "index.php?option=com_users&task=registration.activate&token=" . $activation, "index.php?option=com_users&task=registration.activate&token=" . $activation, $base_url, $sitename,
				$user->id, $user->name, $user->email, $user->username, Factory::getDate('now')->format(Text::_('DATE_FORMAT_LC3')), $logo
			);

			if (!empty($fnum)) {
				$ccid = EmundusHelperFiles::getIdFromFnum($fnum);
				$patterns[] = '/\[FNUM\]/';
				$replacements[] = $fnum;

				require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
				$m_files = new EmundusModelFiles();

				// Replace APPLICANT tags with applicant information
				$fnumInfos = $m_files->getFnumInfos($fnum);
				$applicant_id_key = array_search('/\[APPLICANT_ID\]/', $patterns);
				$replacements[$applicant_id_key] = $fnumInfos['applicant_id'];
				$applicant_name_key = array_search('/\[APPLICANT_NAME\]/', $patterns);
				$replacements[$applicant_name_key] = $fnumInfos['name'];
				$applicant_email_key = array_search('/\[APPLICANT_EMAIL\]/', $patterns);
				$replacements[$applicant_email_key] = $fnumInfos['email'];
				$applicant_username_key = array_search('/\[APPLICANT_USERNAME\]/', $patterns);
				$replacements[$applicant_username_key] = $fnumInfos['username'];

				$status  = $m_files->getStatusByFnums([$fnum]);

				$patterns[]     = '/\[APPLICATION_STATUS\]/';
				$replacements[] = $status[$fnum]['value'];

				$tags       = $m_files->getTagsByFnum([$fnum]);
				$tags_label = [];
				foreach ($tags as $tag) {
					$tags_label[] = $tag['label'];
				}
				$patterns[]     = '/\[APPLICATION_TAGS\]/';
				$replacements[] = implode(',', $tags_label);
				$patterns[] = '/\[CAMPAIGN_LABEL\]/';
				$replacements[] = $fnumInfos['label'];
				$patterns[] = '/\[CAMPAIGN_YEAR\]/';
				$replacements[] = $fnumInfos['year'];
				$patterns[] = '/\[CAMPAIGN_START\]/';
				$replacements[] = EmundusHelperDate::displayDate($fnumInfos['start_date']);
				$patterns[] = '/\[CAMPAIGN_END\]/';
				$replacements[] = EmundusHelperDate::displayDate($fnumInfos['end_date']);
				$patterns[] = '/\[CAMPAIGN_CODE\]/';
				$replacements[] = $fnumInfos['training'];

				if (!class_exists('EmundusModelCampaign')) {
					require_once(JPATH_SITE . '/components/com_emundus/models/campaign.php');
				}
				$m_campaign = new EmundusModelCampaign();
				$programme = $m_campaign->getProgrammeByTraining($fnumInfos['training']);
				$patterns[]     = '/\[TRAINING_CODE\]/';
				$replacements[] = $fnumInfos['training'];
				$patterns[]     = '/\[TRAINING_PROGRAMME\]/';
				$replacements[] = $programme->label;

				// Add booking tags if the booking is enabled
				try
				{
					if (!class_exists('EmundusModelEvents')) {
						require_once(JPATH_SITE . '/components/com_emundus/models/events.php');
					}
					$m_events = new EmundusModelEvents();
					$registration = $m_events->getMyBookingsInformations($fnumInfos['applicant_id'],[],$fnumInfos['ccid']);

					if(!empty($registration))
					{
						$registration = $registration[0];
						if (!class_exists('EmundusHelperDate')) {
							require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
						}
						$start_date = \EmundusHelperDate::displayDate($registration->start, 'DATE_FORMAT_LC1');
						$start_hour = \EmundusHelperDate::displayDate($registration->start, 'H:i');
						$end_hour = \EmundusHelperDate::displayDate($registration->end, 'H:i');

						$location_link = $registration->link_registrant;
						if(empty($location_link)) {
							if(strpos($location_link, 'http') === false && !empty($registration->address)) {
								$location_link = 'https://www.google.com/maps?q='.urlencode($registration->address);
							}
						}
						$booking_tags = [
							'BOOKING_START_DATE'           => $start_date,
							'BOOKING_START_HOUR'           => $start_hour,
							'BOOKING_END_HOUR'             => $end_hour,
							'BOOKING_LOCATION'             => $registration->name_location,
							'BOOKING_LOCATION_LINK'        => $location_link,
						];
						foreach ($booking_tags as $tag => $value) {
							$patterns[] = '/\['.$tag.'\]/';
							$replacements[] = $value;
						}
					}
				}
				catch (Exception $e)
				{
					Log::add('Error when try to set constants for bookin module',Log::ERROR,'com_emundus.email');
				}

				if (!empty($content) && (str_contains($content, '[LAST_COMMENT]') || str_contains($content, '[LAST_COMMENT_DATE]') || str_contains($content, '[LAST_COMMENT_AUTHOR]') || str_contains($content, '[LAST_COMMENT_TARGET]')))
				{
					$patterns[] = '/\[LAST_COMMENT\]/';
					$patterns[] = '/\[LAST_COMMENT_DATE\]/';
					$patterns[] = '/\[LAST_COMMENT_AUTHOR\]/';
					$patterns[] = '/\[LAST_COMMENT_TARGET\]/';
					if (!class_exists('EmundusModelComments'))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/models/comments.php');
					}
					$m_comments   = new EmundusModelComments();
					$last_comment = $m_comments->getComments($ccid, $current_user, false, [], null, null, 1);
					if (!empty($last_comment[0]))
					{
						$replacements[] = $last_comment[0]['comment_body'];
						$replacements[] = $last_comment[0]['date'];
						$replacements[] = $last_comment[0]['username'];
						$replacements[] = $m_comments->getCommentTarget($last_comment[0]);
					}
					else
					{
						$replacements[] = '';
						$replacements[] = '';
						$replacements[] = '';
						$replacements[] = '';
					}
				}
			}
		}

		if (isset($post)) {
			foreach ($post as $key => $value) {
				$constant_key = array_search('/\[' . $key . '\]/', $patterns);
				if ($constant_key !== false) {
					$replacements[$constant_key] = $value;
				}
				else {
					$patterns[]     = '/\[' . $key . '\]/';
					$replacements[] = $value;
				}
			}
		}

		return array('patterns' => $patterns, 'replacements' => $replacements);
	}

	/**
	 * Define replacement values for tags
	 *
	 * @param   int     $user_id
	 * @param   array   $post     custom tags define from context
	 * @param   string  $fnum     used to get fabrik tags ids from applicant file
	 * @param   string  $passwd   used set password if needed
	 * @param   string  $content  string containing tags to replace, ATTENTION : if empty all tags are computing
	 * @param   bool  $base64  if true, image will be converted to base64
	 * @param   bool  $check_content  if true, only tags in $content will be computed
	 *
	 * @return array[]
	 */
	public function setTags($user_id, $post = null, $fnum = null, $passwd = '', $content = '', $base64 = false, $check_content = false)
	{
		require_once(JPATH_SITE . '/components/com_emundus/helpers/tags.php');
		$h_tags = new EmundusHelperTags();

		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);
		$query->select('tag, description, request')
			->from($db->quoteName('#__emundus_setup_tags', 't'))
			->where($db->quoteName('t.published') . ' = 1');

		if (!empty($content)) {
			$tags_content = $h_tags->getVariables($content, 'SQUARE');

			if (!empty($tags_content)) {
				$tags_content = array_unique($tags_content);
				$query->andWhere('t.tag IN ("' . implode('","', $tags_content) . '")');
			}
			elseif ($check_content) {
				return array('patterns' => array(), 'replacements' => array());
			}
		}

		try {
			$db->setQuery($query);
			$tags = $db->loadAssocList();
		}
		catch (Exception $e) {
			Log::add('Error getting tags model/emails/setTags at query : ' . $query->__toString(), Log::ERROR, 'com_emundus.email');

			return array('patterns' => array(), 'replacements' => array());
		}

		$constants = $this->setConstants($user_id, $post, $passwd, $fnum, $content);

		$patterns     = $constants['patterns'];
		$replacements = $constants['replacements'];

		foreach ($tags as $tag) {
			$tagEntity = new TagEntity($tag['tag'], $tag['description']);
			$request      = preg_replace($constants['patterns'], $constants['replacements'], $tag['request']);

			// If fnum is set, we call setTagsFabrik to replace tags like {fabrik_element_id} by the application form value
			if(str_contains($request, 'php|') && !empty($fnum))
			{
				$request = str_replace('php|', '', $request);
				$request     = $this->setTagsFabrik($request, array($fnum));

				$request = 'php|' . $request;
			}

			$tagEntity->setRequest($request);
			$tagEntity->calculateValue($user_id, $base64);

			$patterns[] = $tagEntity->getFullPatternName();
			$replacements[] = $tagEntity->getValue();
		}
		
		// Check modifiers tags
		if(!empty($content)) {
			foreach ($tags_content as $tag)
			{
				$tagEntity = new TagEntity($tag);

				if(!empty($tagEntity->getModifiers()))
				{
					// Search value of base tag in replacements to avoid to call the database again
					$patternKey = array_search($tagEntity->getPatternName(), $patterns);
					if($patternKey !== false && !empty($replacements[$patternKey]))
					{
						$tagEntity->setValue($replacements[$patternKey]);
					}
					else
					{
						$query->clear()
							->select('request')
							->from($db->quoteName('#__emundus_setup_tags'))
							->where($db->quoteName('tag') . ' = ' . $db->quote($tagEntity->getName()));
						try {
							$db->setQuery($query);
							$request = $db->loadResult();
						}
						catch (Exception $e) {
							Log::add('Error getting tag request for tag : ' . $tagEntity->getName() . '. Message : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
							$request = '';
						}

						$request      = preg_replace($constants['patterns'], $constants['replacements'], $request);

						// If fnum is set, we call setTagsFabrik to replace tags like {fabrik_element_id} by the application form value
						if(str_contains($request, 'php|') && !empty($fnum))
						{
							$request = str_replace('php|', '', $request);
							$request     = $this->setTagsFabrik($request, array($fnum));

							$request = 'php|' . $request;
						}

						$tagEntity->setRequest($request);
						$tagEntity->calculateValue($user_id, $base64);
					}

					// Add tag with modifier to patterns and replacements
					$patterns[] = $tagEntity->getFullPatternName();
					$replacements[] = $tagEntity->getValueModified();
				}
			}
		}

		return array('patterns' => $patterns, 'replacements' => $replacements);
	}

	
	public function setTagsWord(?int $user_id, ?array $post = null, ?string $fnum = null, ?string $passwd = ''): array
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);
		$query->select('tag, request')
			->from($db->quoteName('#__emundus_setup_tags'));
		$db->setQuery($query);
		$tags = $db->loadAssocList();

		$constants = $this->setConstants($user_id, $post, $passwd, $fnum);

		$patterns     = array();
		$replacements = array();
		foreach ($tags as $tag) {
			$tagEntity = new TagEntity($tag['tag'], $tag['description']);

			// If fnum is set, we call setTagsFabrik to replace tags like {fabrik_element_id} by the application form value
			$request      = preg_replace($constants['patterns'], $constants['replacements'], $tag['request']);
			if(str_contains($request, 'php|') && !empty($fnum))
			{
				$request = str_replace('php|', '', $request);
				$request     = $this->setTagsFabrik($request, array($fnum));

				$request = 'php|' . $request;
			}
			$tagEntity->setRequest($request);
			$tagEntity->calculateValue($user_id);

			$patterns[] = $tagEntity->getFullName();
			$replacements[] = $tagEntity->getValue();
		}

		return array('patterns' => $patterns, 'replacements' => $replacements);
	}

	public function setTagsFabrik(string $str, array $fnums = array(), bool $raw = false)
	{
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
		$m_files = new EmundusModelFiles();

		$jinput = Factory::getApplication()->input;

		if (count($fnums) == 0) {
			$fnums      = $jinput->get('fnums', null, 'RAW');
			$fnumsArray = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);
		}
		else {
			$fnumsArray = $fnums;
		}

		$tags = $m_files->getVariables($str);

		if(!class_exists('EmundusHelperFabrik'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');
		}
		$fabrik_aliases = EmundusHelperFabrik::getAllFabrikAliases();

		$idFabrik = [];
		$fabrikTags = [];
		$aliasFabrik  = [];

		if (count($tags) > 0) {
			foreach ($tags as $val) {
				$tag = new TagEntity($val, '', [], TagType::FABRIK);

				if (!empty($tag->getName()) && is_numeric($tag->getName())) {
					$idFabrik[] = $tag->getName();
					$fabrikTags[] = $tag;
				}
				elseif (in_array($tag->getName(), $fabrik_aliases))
				{
					$elt = EmundusHelperFabrik::getElementsByAlias($tag->getName());

					if(!empty($elt[0]))
					{
						$idFabrik[] = $elt[0]->id;
						$aliasFabrik[$tag->getName()] = $elt[0]->id;
						$fabrikTags[] = $tag;
					}
				}
			}
		}

		if (!empty($idFabrik)) {
			$fabrikElts   = $m_files->getValueFabrikByIds($idFabrik);
			$fabrikValues = array();

			foreach ($fabrikElts as $elt) {
				$params         = json_decode($elt['params']);
				$groupParams    = json_decode($elt['group_params']);
				$isDate         = (in_array($elt['plugin'],['date','jdate']));
				$isDatabaseJoin = ($elt['plugin'] === 'databasejoin');

				if ($groupParams->repeat_group_button == 1 || $isDatabaseJoin) {
					$fabrikValues[$elt['id']] = $m_files->getFabrikValueRepeat($elt, $fnumsArray, $params, @$groupParams->repeat_group_button == 1);


					if (empty($fabrikValues[$elt['id']])) {
						$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name']);
					}


				}
				else {
					if ($isDate) {
						if($elt['plugin'] == 'jdate') {
							$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name'], $params->jdate_form_format);
						} else {
							$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name'], $params->date_form_format);
						}
					}
					else {
						$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name']);
					}
				}

				if ($elt['plugin'] == "checkbox" || $elt['plugin'] == "dropdown" || $elt['plugin'] == "radiobutton") {
					foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
						$params = json_decode($elt['params']);
						$elm    = array();
						if ($elt['plugin'] == "checkbox") {
							$index = array_intersect(json_decode($val["val"]), $params->sub_options->sub_values);
						}
						else {
							$index = array_intersect((array) $val["val"], $params->sub_options->sub_values);
						}
						foreach ($index as $value) {
							$key   = array_search($value, $params->sub_options->sub_values);
							$elm[] = !$raw ? Text::_($params->sub_options->sub_labels[$key]) : $value;
						}
						$fabrikValues[$elt['id']][$fnum]['val'] = implode(", ", $elm);
					}
				}

				if ($elt['plugin'] == "birthday") {
					foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
						$val = explode(',', $val['val']);
						foreach ($val as $k => $v) {
							$val[$k] = date($params->details_date_format, strtotime($v));
						}
						$fabrikValues[$elt['id']][$fnum]['val'] = implode(",", $val);
					}
				}

				if ($elt['plugin'] == 'cascadingdropdown') {
					foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
						$fabrikValues[$elt['id']][$fnum]['val'] = $this->getCddLabel($elt, $val['val']);
					}
				}
				if ($elt['plugin'] == 'textarea') {
					foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
						$fabrikValues[$elt['id']][$fnum]['val'] = htmlentities($val['val'], ENT_QUOTES);
					}
				}
				if ($elt['plugin'] == 'emundus_phonenumber') {
					foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
						$fabrikValues[$elt['id']][$fnum]['val'] = substr($val['val'], 2, strlen($val['val']));
					}
				}
				if ($elt['plugin'] == 'yesno'){
					foreach ($fabrikValues[$elt['id']] as $fnum => $val)
					{
						$fabrikValues[$elt['id']][$fnum]['val'] = $val['val'] ? Text::_('JYES') : Text::_('JNO');
					}
				}
			}
			
			$preg = array('patterns' => array(), 'replacements' => array());
			
			foreach ($fnumsArray as $fnum) {
				foreach ($idFabrik as $id) {
					$preg['patterns'][] = '/\$\{' . $id . '\}/';
					if (isset($fabrikValues[$id][$fnum])) {
						$preg['replacements'][] = Text::_($fabrikValues[$id][$fnum]['val']);
					}
					else {
						$preg['replacements'][] = '';
					}
				}
				
				foreach ($aliasFabrik as $alias => $id) {
					$preg['patterns'][] = '/\$\{' . $alias . '\}/';
					if (isset($fabrikValues[$id][$fnum])) {
						$preg['replacements'][] = Text::_($fabrikValues[$id][$fnum]['val']);
					}
					else {
						$preg['replacements'][] = '';
					}
				}
				
				foreach ($fabrikTags as $fabrikTag) {
					if(!empty($fabrikTag->getModifiers()))
					{
						$patternKey = array_search($fabrikTag->getPatternName(), $preg['patterns']);
						if($patternKey !== false && !empty($preg['replacements'][$patternKey]))
						{
							$fabrikTag->setValue($preg['replacements'][$patternKey]);

							$preg['patterns'][] = $fabrikTag->getFullPatternName();
							$preg['replacements'][] = $fabrikTag->getValueModified();
						}
					}
				}
			}

			return $this->replace($preg, $str);
		}
		else {
			return $str;
		}
	}


	/**
	 * Gets the label of a CascadingDropdown element based on the value.
	 *
	 * @param $elt array the cascadingdropdown element.
	 * @param $val string the value of the element to be used for retrieving the label.
	 *
	 * @return mixed|string
	 * @since version v6
	 */
	public function getCddLabel($elt, $val)
	{
		$attribs = json_decode($elt['params']);
		$id      = $attribs->cascadingdropdown_id;
		$r1      = explode('___', $id);
		$label   = $attribs->cascadingdropdown_label;
		$r2      = explode('___', $label);
		$select  = !empty($attribs->cascadingdropdown_label_concat) ? str_replace('{shortlang}', substr(JFactory::getLanguage()->getTag(), 0, 2), str_replace('{thistable}', $r2[0], "CONCAT(" . $attribs->cascadingdropdown_label_concat . ")")) : $r2[1];

		$query = $this->_db->getQuery(true);
		$query->select($select)
			->from($this->_db->quoteName($r2[0]))
			->where($this->_db->quoteName($r1[1]) . ' LIKE ' . $this->_db->quote($val));
		$this->_db->setQuery($query);

		try {
			$ret = $this->_db->loadResult();
			if (empty($ret)) {
				return $val;
			}

			return $ret;
		}
		catch (Exception $e) {
			Log::add('Error getting cascadingdropdown label in model/emails/getCddLabel at query : ' . $query->__toString(), Log::ERROR, 'com_emundus');

			return $val;
		}
	}

	/**
	 * Find all variables like ${var} in string.
	 *
	 * @param   string  $str
	 *
	 * @return string[]
	 * @since v6
	 */
	private function getVariables($str)
	{
		preg_match_all('/\$\{(.*?)}/i', $str, $matches);

		return $matches[1];
	}


	/**
	 * @param $type
	 * @param $fnum
	 *
	 * @return array|bool
	 *
	 * @throws Exception
	 * @since version v6
	 */
	public function sendMail($type = null, $fnum = null)
	{

		$jinput    = JFactory::getApplication()->input;
		$mail_type = $jinput->get('mail_type', null, 'CMD');

		if ($fnum != null) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			$m_files    = new EmundusModelFiles();
			$fnum_infos = $m_files->getFnumInfos($fnum);

			$student_id  = $fnum_infos['applicant_id'];
			$campaign_id = $fnum_infos['campaign_id'];
		}
		else {
			$student_id  = $jinput->getInt('student_id', null);
			$campaign_id = $jinput->getInt('campaign_id', null);
		}

		$student = JFactory::getUser($student_id);

		if (!isset($type)) {
			$type = $mail_type;
		}

		if ($type == "evaluation_result") {

			$mode = 1; // HTML

			$mail_cc      = null;
			$mail_subject = $jinput->get('mail_subject', null, 'STRING');

			$mail_from_name = $this->_em_user->name;
			$mail_from      = $this->_em_user->email;

			$mail_to_id = $jinput->get('mail_to', null, 'STRING');
			$student    = JFactory::getUser($mail_to_id);
			$mail_to    = $student->email;

			$mail_body        = $this->setBody($student, JFactory::getApplication()->input->get('mail_body', null, 'POST'), '', $fnum);
			$mail_attachments = $jinput->get('mail_attachments', null, 'STRING');

			if (!empty($mail_attachments)) {
				$mail_attachments = explode(',', $mail_attachments);
			}

			$message = [
				'user_id_from' => $this->_em_user->id,
				'user_id_to'   => $mail_to_id,
				'subject'      => $mail_subject,
				'message'       => $mail_body,
				'email_to'      => $mail_to
			];
			$this->logEmail($message);

		}
		elseif ($type == 'expert') {

			require_once(JPATH_ROOT . '/components/com_emundus/helpers/filters.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
			$eMConfig   = JComponentHelper::getParams('com_emundus');
			$formid     = json_decode($eMConfig->get('expert_fabrikformid', '{"accepted":169, "refused":328}'));
			$documentid = $eMConfig->get('expert_document_id', '36');

			$mail_cc      = null;
			$mail_subject = $jinput->get('mail_subject', null, 'STRING');

			$mail_from_name = $jinput->get('mail_from_name', null, 'STRING');
			$mail_from      = $jinput->get('mail_from', null, 'STRING');
			$mail_to        = $jinput->get('mail_to', null, 'STRING');
			$mail_body      = $jinput->get('mail_body', null, 'RAW');
			$tags           = $this->setTags($this->_em_user->id, null, null, '', $mail_from_name . $mail_from . $mail_to);
			$mail_from_name = preg_replace($tags['patterns'], $tags['replacements'], $mail_from_name);
			$mail_from      = preg_replace($tags['patterns'], $tags['replacements'], $mail_from);
			$mail_to        = explode(',', $mail_to);
			$mail_body      = $this->setBody($student, $mail_body, '', $fnum);

			//
			// Replacement
			//
			$campaign  = @EmundusHelperfilters::getCampaignByID($campaign_id);
			$post      = [
				'TRAINING_PROGRAMME' => $campaign['label'],
				'CAMPAIGN_START'     => $campaign['start_date'],
				'CAMPAIGN_END'       => $campaign['end_date'],
				'EVAL_DEADLINE'      => date("d/M/Y", mktime(0, 0, 0, date("m") + 2, date("d"), date("Y")))
			];
			$tags      = $this->setTags($student_id, $post, $fnum, '', $mail_body);
			$mail_body = preg_replace($tags['patterns'], $tags['replacements'], $mail_body);

			//tags from Fabrik ID
			$element_ids = $this->getFabrikElementIDs($mail_body);
			if (count(@$element_ids[0]) > 0) {
				$element_values = $this->getFabrikElementValues($fnum, $element_ids[1]);
			}

			$mail_attachments  = $jinput->get('mail_attachments', null, 'STRING');
			$delete_attachment = $jinput->get('delete_attachment', null, 'INT');

			if (!empty($mail_attachments)) {
				$mail_attachments = explode(',', $mail_attachments);
			}

			$sent          = array();
			$failed        = array();
			$print_message = '';

			$query = $this->_db->getQuery(true);
			foreach ($mail_to as $m_to) {

				$key1 = md5($this->rand_string(20) . time());
				$m_to = trim($m_to);

				// 2. MAJ de la table emundus_files_request
				$attachment_id = $documentid; // document avec clause de confidentialité
				$query->clear();
				$query->insert('#__emundus_files_request')
					->columns('time_date, student_id, keyid, attachment_id, campaign_id, email, fnum')
					->values($this->_db->quote(gmdate('Y-m-d H:i:s')) . ', ' . $student_id . ', "' . $key1 . '", "' . $attachment_id . '", ' . $campaign_id . ', ' . $this->_db->quote($m_to) . ', ' . $this->_db->quote($fnum));

				try {
					$this->_db->setQuery($query);
					$this->_db->query();
				}
				catch (Exception $e) {
					Log::add('Error trying to insert emundus files request (fnum ' . $fnum . ') : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}

				// 3. Envoi du lien vers lequel le professeur va pouvoir uploader la lettre de référence
				$link_accept        = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid->accepted . '&keyid=' . $key1 . '&sid=' . $student_id . '&email=' . $m_to . '&cid=' . $campaign_id;
				$link_refuse        = 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid->refused . '&keyid=' . $key1 . '&sid=' . $student_id . '&email=' . $m_to . '&cid=' . $campaign_id . '&usekey=keyid&rowid=' . $key1;
				$link_accept_noform = 'index.php?option=com_fabrik&c=form&view=form&keyid=' . $key1 . '&sid=' . $student_id . '&email=' . $m_to . '&cid=' . $campaign_id;
				$link_refuse_noform = 'index.php?option=com_fabrik&c=form&view=form&keyid=' . $key1 . '&sid=' . $student_id . '&email=' . $m_to . '&cid=' . $campaign_id . '&usekey=keyid&rowid=' . $key1;

				$post = array(
					'EXPERT_ACCEPT_LINK'                 => JURI::base() . $link_accept,
					'EXPERT_REFUSE_LINK'                 => JURI::base() . $link_refuse,
					'EXPERT_ACCEPT_LINK_RELATIVE'        => $link_accept,
					'EXPERT_REFUSE_LINK_RELATIVE'        => $link_refuse,
					'EXPERT_ACCEPT_LINK_NOFORM'          => JURI::base() . $link_accept_noform,
					'EXPERT_REFUSE_LINK_NOFORM'          => JURI::base() . $link_refuse_noform,
					'EXPERT_ACCEPT_LINK_RELATIVE_NOFORM' => $link_accept_noform,
					'EXPERT_REFUSE_LINK_RELATIVE_NOFORM' => $link_refuse_noform
				);

				$tags = $this->setTags($student_id, $post, $fnum);

				$body = preg_replace($tags['patterns'], $tags['replacements'], $mail_body);
				$body = $this->setTagsFabrik($body, array($fnum));

				// If we have an email sender in our jinput then we use that, else we use the default system sender.
				$app            = JFactory::getApplication();
				$email_from_sys = $app->getCfg('mailfrom');

				// If the email sender has the same domain as the system sender address.
				if (!empty($mail_from) && substr(strrchr($mail_from, "@"), 1) === substr(strrchr($email_from_sys, "@"), 1)) {
					$mail_from_address = $mail_from;
				}
				else {
					$mail_from_address = $email_from_sys;
				}

				// Set sender
				$sender = [
					$mail_from_address,
					$mail_from_name
				];

				$mailer = JFactory::getMailer();
				$mailer->setSender($sender);
				$mailer->addReplyTo($mail_from, $mail_from_name);
				$mailer->addRecipient($m_to);
				$mailer->setSubject($mail_subject);
				$mailer->isHTML(true);
				$mailer->Encoding = 'base64';
				$mailer->setBody($body);
				if (is_array($mail_attachments) && count($mail_attachments) > 0) {
					foreach ($mail_attachments as $attachment) {
						$mailer->addAttachment($attachment);
					}
				}

				require_once JPATH_ROOT . '/components/com_emundus/helpers/emails.php';
				$custom_email_tag = EmundusHelperEmails::getCustomHeader();
				if (!empty($custom_email_tag)) {
					$mailer->addCustomHeader($custom_email_tag);
				}

				$send = $mailer->Send();

				if ($send !== true) {
					$failed[]      = $m_to;
					$row           = [
						'applicant_id' => $student_id,
						'user_id'      => $this->_em_user->id,
						'reason'       => Text::_('COM_EMUNDUS_EXPERTS_INFORM_EXPERTS'),
						'comment_body' => Text::_('ERROR') . ' ' . Text::_('MESSAGE') . ' ' . Text::_('COM_EMUNDUS_APPLICATION_NOT_SENT') . ' ' . Text::_('COM_EMUNDUS_TO') . ' ' . $m_to,
						'fnum'         => $fnum
					];
					$print_message .= '<hr>Error sending email: ' . $send->__toString();

				}
				else {
					$sent[] = $m_to;
					$row    = [
						'applicant_id' => $student_id,
						'user_id'      => $this->_em_user->id,
						'reason'       => Text::_('COM_EMUNDUS_EXPERTS_INFORM_EXPERTS'),
						'comment_body' => Text::_('MESSAGE') . ' ' . Text::_('COM_EMUNDUS_APPLICATION_SENT') . ' ' . Text::_('COM_EMUNDUS_TO') . ' ' . $m_to,
						'fnum'         => $fnum
					];

					$query = 'SELECT id FROM #__users WHERE email like ' . $this->_db->Quote($m_to);
					$this->_db->setQuery($query);
					$user_id_to = $this->_db->loadResult();

					if ($user_id_to > 0) {
						$message = [
							'user_id_from' => $this->_em_user->id,
							'user_id_to'   => $user_id_to,
							'subject'      => $mail_subject,
							'message'       => $body,
							'email_to'      => $m_to
						];
						$this->logEmail($message);
					}

					$print_message .= '<hr>' . Text::_('COM_EMUNDUS_MAILS_EMAIL_SENT') . ' : ' . $m_to;
					$print_message .= '<hr>' . Text::_('COM_EMUNDUS_EMAILS_SUBJECT') . ' : ' . $mail_subject;
					$print_message .= '<hr>' . $body;
				}

				$m_application = new EmundusModelApplication;
				$m_application->addComment($row);
			}

			// delete attached files
			if (is_array($mail_attachments) && count($mail_attachments) > 0 && $delete_attachment == 1) {
				foreach ($mail_attachments as $attachment) {

					$filename = explode(DS, $attachment);
					$query    = 'DELETE FROM #__emundus_uploads
                                WHERE user_id=' . $student_id . '
                                    AND campaign_id=' . $campaign_id . '
                                    AND fnum like ' . $this->_db->Quote($fnum) . '
                                    AND filename LIKE "' . $filename[count($filename) - 1] . '"';
					$this->_db->setQuery($query);
					$this->_db->query();

					@unlink(EMUNDUS_PATH_ABS . $student_id . DS . $filename[count($filename) - 1]);

				}
			}

			return array('sent' => $sent, 'failed' => $failed, 'message' => $print_message);

		}
		else {
			return false;
		}

		JFactory::getApplication()->enqueueMessage(Text::_('COM_EMUNDUS_MAILS_EMAIL_SENT'), 'message');

		return true;
	}

	/**
	 * Used for sending the expert invitation email with the link to the form.
	 *
	 * @param $fnums array
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since version v6
	 */
	public function sendExpertMail(array $fnums, int $sender_id, string $mail_subject, string $mail_from_name, string $mail_from, array $mail_to, string $mail_body): array
	{
		$sent          = [];
		$failed        = [];
		$print_message = '';

		if (!empty($fnums) && !empty($mail_to) && !empty($mail_subject) && !empty($mail_body)) {
			require_once(JPATH_SITE . DS . 'components/com_emundus/helpers/filters.php');
			require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
			PluginHelper::importPlugin('emundus');

			$h_filters = new EmundusHelperFilters();
			$m_files   = new EmundusModelFiles();

			Log::addLogger(['text_file' => 'com_emundus.inviteExpert.error.php'], Log::ALL, 'com_emundus');

			$eMConfig   = ComponentHelper::getParams('com_emundus');
			$formid = json_decode($eMConfig->get('expert_fabrikformid', '{"accepted":169, "refused":328, "agreement": 0}'));
			$documentid = $eMConfig->get('expert_document_id', '36');

			$app            = Factory::getApplication();
			$email_from_sys = $app->getConfig()->get('mailfrom');

			// We are using the first fnum for things like setting tags and getting campaign info.
			// ! This means that we should NOT PUT TAGS RELATING TO PERSONAL INFO IN THE EMAIL.
			$example_fnum    = $fnums[0];
			$campaign_id     = (int) substr($example_fnum, 14, 7);
			$campaign        = $h_filters->getCampaignByID($campaign_id);
			$example_user_id = (int) substr($example_fnum, -7);
			$example_user    = $app->getIdentity($example_user_id);

			if (!empty($sender_id)) {
				$this->setTags($sender_id);
			} else if (!empty($this->_em_user)) {
				$tags = $this->setTags($this->_em_user->id);
			}

			if (!empty($tags)) {
				$mail_from_name = preg_replace($tags['patterns'], $tags['replacements'], $mail_from_name);
				$mail_from      = preg_replace($tags['patterns'], $tags['replacements'], $mail_from);
			}
			$mail_tmpl = $this->getEmail('confirm_post');

			if (!empty($mail_to)) {
				$mail_body = $this->setBody($example_user, $mail_body);

				// Build an HTML list to stick in the email body.
				$fnums_infos = $m_files->getFnumsInfos($fnums);
				$fnums_html  = '<ul>';
				foreach ($fnums_infos as $fnum) {
					$fnums_html .= '<li>' . $fnum['name'] . ' (' . $fnum['fnum'] . ')</li>';
				}
				$fnums_html .= '</ul>';

				// Replacement
				$post      = [
					'CAMPAIGN_LABEL'     => $campaign['label'],
					'TRAINING_PROGRAMME' => $campaign['label'],
					'CAMPAIGN_START'     => $campaign['start_date'],
					'CAMPAIGN_END'       => $campaign['end_date'],
					'EVAL_DEADLINE'      => date("d/M/Y", mktime(0, 0, 0, date("m") + 2, date("d"), date("Y"))),
					'FNUMS'              => $fnums_html
				];
				$tags      = $this->setTags($example_user_id, $post, $example_fnum);
				$mail_body = preg_replace($tags['patterns'], $tags['replacements'], $mail_body);

				// Tags from Fabrik ID
				$element_ids = $this->getFabrikElementIDs($mail_body);
				if (!empty($element_ids) && !empty($element_ids[0])) {
					$element_values = $this->getFabrikElementValues($example_fnum, $element_ids[1]);
				}

				$sent          = array();
				$failed        = array();
				$print_message = '';

				foreach ($mail_to as $m_to) {
					$key1 = md5($this->rand_string(20) . time());
					$m_to = trim($m_to);


					// 2. MAJ de la table emundus_files_request
					$attachment_id = $documentid; // document avec clause de confidentialité

					// Build multiline insert, 1 key can accept for multiple files.
					$query = $this->_db->getQuery(true);
					$query->insert($this->_db->quoteName('#__emundus_files_request'))
						->columns($this->_db->quoteName(['time_date', 'student_id', 'keyid', 'attachment_id', 'campaign_id', 'email', 'fnum']));

					foreach ($fnums_infos as $fnum_info) {
						$query->values('NOW(), ' . $fnum_info['applicant_id'] . ', "' . $key1 . '", "' . $attachment_id . '", ' . $fnum_info['campaign_id'] . ', ' . $this->_db->quote($m_to) . ', ' . $this->_db->quote($fnum_info['fnum']));
					}

					$this->_db->setQuery($query);
					try {
						$this->_db->execute();
					}
					catch (Exception $e) {
						$failed[]      = $m_to;
						$print_message .= '<hr>Error inviting expert ' . $m_to;
						Log::add('Error inserting file requests for expert invitations ' . $m_to . ' : ' . $e->getMessage() . ' with query : ' . $query->__toString(), Log::ERROR, 'com_emundus');
						continue;
					}

					$this->_db->setQuery('show tables');
					$existingTables = $this->_db->loadColumn();
					if (in_array('jos_emundus_files_request_1614_repeat', $existingTables)) {
						$parent_id = 0;

						foreach ($fnums_infos as $fnum) {
							try {
								$query->clear()
									->select($this->_db->quoteName(['id', 'fnum', 'student_id']))
									->from($this->_db->quoteName('#__emundus_files_request'))
									->where($this->_db->quoteName('email') . ' LIKE ' . $this->_db->Quote($m_to) . ' AND ' . $this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->Quote($fnum['fnum']) . ' AND ' . $this->_db->quoteName('keyid') . ' LIKE ' . $this->_db->Quote($key1));
								$this->_db->setQuery($query);
								$files_request = $this->_db->loadObject();

								if (empty($parent_id)) {
									$parent_id = $files_request->id;
								}

								$query->clear()
									->select($this->_db->quoteName('name'))
									->from($this->_db->quoteName('#__users'))
									->where($this->_db->quoteName('id') . ' = ' . $files_request->student_id);
								$this->_db->setQuery($query);
								$student_name = $this->_db->loadResult();

								$query->clear()
									->insert($this->_db->quoteName('#__emundus_files_request_1614_repeat'))
									->set($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($parent_id))
									->set($this->_db->quoteName('nom_candidat_expertise') . ' = ' . $this->_db->quote($student_name))
									->set($this->_db->quoteName('fnum_expertise') . '=' . $this->_db->quote($fnum['fnum']));
								$this->_db->setQuery($query);
								$this->_db->execute();
							}
							catch (Exception $e) {
								$failed[]      = $m_to . '  ' . $fnum['fnum'];
								$print_message .= '<hr>Error associating expert ' . $m_to . ' to fnum ' . $fnum['fnum'];
								Log::add('Error inserting file requests for expert invitations ' . $m_to . ' and fnum ' . $fnum['fnum'] . ' : ' . $e->getMessage() . ' with query : ' . $query->__toString(), Log::ERROR, 'com_emundus');
								continue;
							}
						}
					}

					// 3. Envoi du lien vers lequel le professeur va pouvoir uploader la lettre de référence
					$link_accept = !empty($formid->accepted) ? 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid->accepted . '&keyid=' . $key1 . '&cid=' . $campaign_id : '';
					$link_refuse = !empty($formid->refused) ? 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid->refused . '&keyid=' . $key1 . '&cid=' . $campaign_id . '&usekey=keyid&rowid=' . $key1 : '';
					$link_accept_noform = 'index.php?option=com_fabrik&c=form&view=form&keyid=' . $key1 . '&sid=' . $fnum_info['applicant_id'] . '&email=' . $m_to . '&cid=' . $campaign_id;
					$link_refuse_noform = 'index.php?option=com_fabrik&c=form&view=form&keyid=' . $key1 . '&sid=' . $fnum_info['applicant_id'] . '&email=' . $m_to . '&cid=' . $campaign_id . '&usekey=keyid&rowid=' . $key1;

					$post = array(
						'EXPERT_ACCEPT_LINK'                 => JURI::base() . $link_accept,
						'EXPERT_REFUSE_LINK'                 => JURI::base() . $link_refuse,
						'EXPERT_ACCEPT_LINK_RELATIVE'        => $link_accept,
						'EXPERT_REFUSE_LINK_RELATIVE'        => $link_refuse,
						'EXPERT_ACCEPT_LINK_NOFORM'          => JURI::base() . $link_accept_noform,
						'EXPERT_REFUSE_LINK_NOFORM'          => JURI::base() . $link_refuse_noform,
						'EXPERT_ACCEPT_LINK_RELATIVE_NOFORM' => $link_accept_noform,
						'EXPERT_REFUSE_LINK_RELATIVE_NOFORM' => $link_refuse_noform
					);

					if (!empty($formid->agreement)) {
						$post['EXPERT_KEY_ID'] = $key1;
						$post['EXPERT_AGREEMENT_LINK'] = JURI::base() . 'index.php?option=com_fabrik&c=form&view=form&formid=' . $formid->agreement . '&keyid=' . $key1;
					}

					$tags = $this->setTags($example_user_id, $post, $example_fnum);

					$message = $this->setTagsFabrik($mail_body, [$example_fnum]);
					$subject = $this->setTagsFabrik($mail_subject, [$example_fnum]);

					// Tags are replaced with their corresponding values using the PHP preg_replace function.
					$subject = !empty($tags['patterns']) && !empty($tags['replacements']) && !empty($subject) ? preg_replace($tags['patterns'], $tags['replacements'], $subject) : $subject;
					$body    = $message;
					if ($mail_tmpl) {
						$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $mail_tmpl->Template);
					}
					$body = !empty($tags['patterns']) && !empty($tags['replacements']) ? preg_replace($tags['patterns'], $tags['replacements'], $body) : $body;

					// If the email sender has the same domain as the system sender address.
					$mail_from_address = $email_from_sys;
					if(empty($mail_from)) {
						$mail_from = $email_from_sys;
					}

					// Set sender
					$sender = [
						$mail_from_address,
						$mail_from_name
					];

					$mailer = JFactory::getMailer();
					$mailer->setSender($sender);
					$mailer->addReplyTo($mail_from, $mail_from_name);
					$mailer->addRecipient($m_to);
					$mailer->setSubject($mail_subject);
					$mailer->isHTML(true);
					$mailer->Encoding = 'base64';
					$mailer->setBody($body);

					require_once JPATH_ROOT . '/components/com_emundus/helpers/emails.php';
					$custom_email_tag = EmundusHelperEmails::getCustomHeader();
					if (!empty($custom_email_tag)) {
						$mailer->addCustomHeader($custom_email_tag);
					}

					$send = $mailer->Send();

					if ($send !== true) {
						$failed[]      = $m_to;
						$print_message .= '<hr>Error sending email: ' . $send;
					}
					else {
						$sent[] = $m_to;

						$query = $this->_db->getQuery(true);
						$query->select($this->_db->quoteName('id'))
							->from($this->_db->quoteName('#__users'))
							->where($this->_db->quoteName('email') . ' LIKE ' . $this->_db->Quote($m_to));
						$this->_db->setQuery($query);

						try {
							$user_id_to = $this->_db->loadResult();

							if ($user_id_to > 0) {
								$message = [
									'user_id_from' => $this->_em_user->id,
									'user_id_to'   => $user_id_to,
									'subject'      => $mail_subject,
									'message'       => $body,
									'email_to'      => $m_to
								];
								$this->logEmail($message);
							}
						}
						catch (Exception $e) {
							Log::add('Could not get user by email : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
						}

						$print_message .= '<hr>' . Text::_('COM_EMUNDUS_MAILS_EMAIL_SENT') . ' : ' . $m_to;
						$print_message .= '<hr>' . Text::_('COM_EMUNDUS_EMAILS_SUBJECT') . ' : ' . $mail_subject;
						$print_message .= '<hr>' . $body;
					}

					Factory::getApplication()->triggerEvent('onCallEventHandler', ['onSendExpertRequest', [
						'keyid' => $key1,
						'fnums' => $fnums,
						'mail_to' => $m_to
					]]);
				}
				unset($key1);
			}
		}

		return [
			'sent'    => $sent,
			'failed'  => $failed,
			'message' => $print_message
		];
	}

	/**
	 * @param $row
	 *
	 *
	 * @since version v6
	 */
	public function logEmail($row, $fnum = null)
	{
		$logged = false;

		// log email to admin user if user_id_from is empty
		$row['user_id_from'] = !empty($row['user_id_from']) ? $row['user_id_from'] : 62;
		$row['email_cc']     = !empty($row['email_cc']) ? $row['email_cc'] : '';
		$row['email_to'] = !empty($row['email_to']) ? $row['email_to'] : '';

		require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
		$now    = EmundusHelperDate::getNow();

		$query = $this->_db->getQuery(true);

		$columns = ['user_id_from', 'user_id_to', 'date_time', 'subject', 'message', 'email_cc', 'email_to'];
		$values  = [$row['user_id_from'], $row['user_id_to'], $this->_db->quote($now), $this->_db->quote($row['subject']), $this->_db->quote($row['message']), $this->_db->quote($row['email_cc']), $this->_db->quote($row['email_to'])];

		// If we are logging the email type as well, this allows us to put them in separate folders.
		if (isset($row['type']) && !empty($row['type'])) {
			$columns[] = 'folder_id';
			$values[]  = $row['type'];
		}

		$query->insert($this->_db->quoteName('#__messages'))
			->columns($this->_db->quoteName($columns))
			->values(implode(',', $values));

		try {
			$this->_db->setQuery($query);
			$logged = $this->_db->execute();

			if ($logged && !empty($fnum)) {
				$message_id = $this->_db->insertid();

				// check user_id_to is the applicant user id, before logging in file
				$query->clear()
					->select($this->_db->quoteName('applicant_id'))
					->from($this->_db->quoteName('#__emundus_campaign_candidature'))
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));

				$this->_db->setQuery($query);
				$applicant_id = $this->_db->loadResult();
				if ($applicant_id == $row['user_id_to']) {
					$email_id = isset($row['email_id']) ? $row['email_id'] : 0;

					include_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
					if (class_exists('EmundusModelLogs')) {
						$m_logs = new EmundusModelLogs();
						$m_logs->log($row['user_id_from'], $row['user_id_to'], $fnum, 9, 'c', 'COM_EMUNDUS_LOGS_EMAIL_SENT', json_encode(['email_id' => $email_id, 'message_id' => $message_id, 'created' => [$row['subject']]], JSON_UNESCAPED_UNICODE));
					}
				}
			}
		}
		catch (Exception $e) {
			Log::add('Error logging email in model/emails with error '.$e->getMessage().' : ' . preg_replace("/[\r\n]/", " ", $query->__toString()) . ' data : ' . json_encode($row), Log::ERROR, 'com_emundus.email.error');
		}

		return $logged;
	}

	//////////////////////////  SET FILES REQUEST  /////////////////////////////
	//
	// Génération de l'id du prochain fichier qui devra être ajouté par le referent

	// 1. Génération aléatoire de l'ID
	public function rand_string($len, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
	{
		$string = '';
		for ($i = 0; $i < $len; $i++) {
			$pos    = rand(0, strlen($chars) - 1);
			$string .= $chars[$pos];
		}

		return $string;
	}

	/**
	 * Gets all emails sent to or from the User id.
	 *
	 * @param   Int user ID
	 *
	 * @return Mixed Array
	 * @since v6
	 */
	public function get_messages_to_from_user($user_id) {
		$messages = [];

		if (!empty($user_id)) {
			$query = $this->_db->getQuery(true);

			try {
				// First we get all messages sent to the user
				$query->select('*')
					->from($this->_db->quoteName('#__messages'))
					->where($this->_db->quoteName('user_id_to') . ' = ' . $user_id . ' AND ' . $this->_db->quoteName('folder_id') . ' <> 2')
					->order($this->_db->quoteName('date_time') . ' DESC');
				$this->_db->setquery($query);
				$messages = $this->_db->loadObjectList();

				if(!empty($messages))
				{

					// Then we get all messages from emundus_logs
					$query->clear()
						->select('el.params,el.fnum_to')
						->from($this->_db->quoteName('#__emundus_logs', 'el'))
						->where($this->_db->quoteName('el.user_id_to') . ' = ' . $user_id)
						->andWhere($this->_db->quoteName('el.action_id') . ' = 9')
						->andWhere($this->_db->quoteName('el.message') . ' = ' . $this->_db->quote('COM_EMUNDUS_LOGS_EMAIL_SENT'));
					$this->_db->setQuery($query);
					$messages_fnums = $this->_db->loadObjectList();

					$messages_fnums_by_id = [];
					foreach ($messages_fnums as $message)
					{
						$params                                    = json_decode($message->params);
						$messages_fnums_by_id[$params->message_id] = $message;
					}

					// Finally we filter the messages to add the fnum_to field
					foreach ($messages as $message)
					{
						$message->fnum_to = '';
						if (in_array($message->message_id, array_keys($messages_fnums_by_id)))
						{
							$message->fnum_to = $messages_fnums_by_id[$message->message_id]->fnum_to;
						}
					}
				}
			} catch (Exception $e) {
				Log::add('Error getting messages sent to or from user: '.$user_id.' at query: '.$query, Log::ERROR, 'com_emundus.error');
			}
		}

		return $messages;
	}

	/**
	 * @param   int    $email
	 * @param   array  $groups
	 * @param   array  $attachments
	 *
	 * @return bool
	 * @since v6
	 */
	public function sendEmailToGroup(int $email, array $groups, array $attachments = []): bool
	{

		if (empty($email) || empty($groups)) {
			Log::add('No user or group found in sendEmailToGroup function: ', Log::ERROR, 'com_emundus');

			return false;
		}

		require_once(JPATH_SITE . DS . 'components/com_emundus/models/messages.php');
		$m_messages = new EmundusModelMessages();
		$template   = $m_messages->getEmail($email);

		require_once(JPATH_SITE . DS . 'components/com_emundus/models/groups.php');
		$m_groups = new EmundusModelGroups();
		$users    = $m_groups->getUsersByGroups($groups);

		foreach ($users as $user) {
			try {
				$this->sendEmailFromPlatform($user["user_id"], $template, $attachments);
			}
			catch (Exception $e) {
				Log::add('Error sending an email via the platform: ', Log::ERROR, 'com_emundus');

				return false;
			}
		}
		Log::add(sizeof($users) . ' emails sent to the following groups: ' . implode(",", $groups), Log::ERROR, 'com_emundus');

		return true;
	}

	/**
	 * @param   int     $user
	 * @param   object  $template
	 * @param   array   $attachments
	 *
	 * @return void
	 * @since v6
	 */
	public function sendEmailFromPlatform(int $user, object $template, array $attachments): void
	{
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/logs.php');
		$current_user = JFactory::getUser();
		$user         = JFactory::getUser($user);
		$toAttach     = [];

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/emails.php');
		$h_emails = new EmundusHelperEmails();

		if ($h_emails->assertCanSendMailToUser($user->id)) {
			// Tags are replaced with their corresponding values using the PHP preg_replace function.
			$tags = $this->setTags($user->id);

			$subject = preg_replace($tags['patterns'], $tags['replacements'], $template->subject);
			$body    = $template->message;
			if ($template) {
				$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);
			}
			$body = preg_replace($tags['patterns'], $tags['replacements'], $body);

			$config = JFactory::getConfig();
			// Get default mail sender info
			$mail_from_sys      = $config->get('mailfrom');
			$mail_from_sys_name = $config->get('fromname');
			// Set sender
			$sender = [
				$mail_from_sys,
				$mail_from_sys_name
			];

			// Configure email sender
			$mailer = JFactory::getMailer();
			$mailer->setSender($sender);
			$mailer->addReplyTo($mail_from_sys, $mail_from_sys_name);
			$mailer->addRecipient($user->email);
			$mailer->setSubject($subject);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$mailer->setBody($body);

			$files = '';
			// Files uploaded from the frontend.
			if (!empty($attachments)) {
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

			// Send and log the email.
			$send = $mailer->Send();
			if ($send !== true) {
				$failed[] = $user->email;
				echo 'Error sending email: ' . $send->__toString();
				Log::add($send->__toString(), Log::ERROR, 'com_emundus');
			}
			else {
				$sent[] = $user->email;
				$log    = [
					'user_id_from' => $current_user->id,
					'user_id_to'   => $user->id,
					'subject'      => $subject,
					'message' => $body . $files,
					'type' => !empty($template)?$template->type:'',
					'email_to' => $user->email
				];
				$this->logEmail($log);
				// Log the email in the eMundus logging system.
				$logsParams = array('created' => [$subject]);
				EmundusModelLogs::log($current_user->id, $user->id, '', 9, 'c', 'COM_EMUNDUS_ACCESS_MAIL_APPLICANT_CREATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
			}
		}
	}

	/**
	 * @param $lim
	 * @param $page
	 * @param $filter
	 * @param $sort
	 * @param $recherche
	 *
	 * @return array
	 *
	 * @since version 1.0
	 */
	function getAllEmails($lim, $page, $filter, $sort, $recherche, $category = '', $order_by = 'se.id')
	{
		$query = $this->_db->getQuery(true);

		if (empty($lim) || $lim == 'all') {
			$limit = '';
		}
		else {
			$limit = $lim;
		}

		if (empty($page) || empty($limit)) {
			$offset = 0;
		}
		else {
			$offset = ($page - 1) * $limit;
		}

		if (empty($sort)) {
			$sort = 'DESC';
		}
		$sortDb = 'se.id ';

		if ($filter == 'Unpublish') {
			$filterDate = $this->_db->quoteName('se.published') . ' = 0';
		}
		else {
			$filterDate = $this->_db->quoteName('se.published') . ' = 1';
		}

		if (empty($recherche)) {
			$fullRecherche = 1;
		}
		else {
			$rechercheSubject  = $this->_db->quoteName('se.subject') . ' LIKE ' . $this->_db->quote('%' . $recherche . '%');
			$rechercheMessage  = $this->_db->quoteName('se.message') . ' LIKE ' . $this->_db->quote('%' . $recherche . '%');
			$rechercheEmail    = $this->_db->quoteName('se.emailfrom') . ' LIKE ' . $this->_db->quote('%' . $recherche . '%');
			$rechercheType     = $this->_db->quoteName('se.type') . ' LIKE ' . $this->_db->quote('%' . $recherche . '%');
			$rechercheCategory = $this->_db->quoteName('se.category') . ' LIKE ' . $this->_db->quote('%' . $recherche . '%');
			$fullRecherche     = $rechercheSubject . ' OR ' . $rechercheMessage . ' OR ' . $rechercheEmail . ' OR ' . $rechercheType . ' OR ' . $rechercheCategory;
		}

		$query->select('*')
			->from($this->_db->quoteName('#__emundus_setup_emails', 'se'))
			->where($filterDate)
			->andWhere($fullRecherche);

		if (!empty($category)) {
			$query->andWhere($this->_db->quoteName('se.category') . ' = ' . $this->_db->quote($category));
		}

		$query->group('se.id')
			->order($order_by . ' ' . $sort);

		try {
			$this->_db->setQuery($query);
			$count_emails = sizeof($this->_db->loadObjectList());
			$this->_db->setQuery($query, $offset, $limit);

			$emails = $this->_db->loadObjectList();
			if (!empty($emails)) {
				foreach ($emails as $key => $email) {
					$email->label = ['fr' => $email->subject, 'en' => $email->subject];

					if (!empty($email->category)) {
						$email->additional_columns = [
							[
								'key'     => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'),
								'value'   => $email->category,
								'classes' => 'em-p-5-12 em-font-weight-600 em-bg-neutral-200 em-text-neutral-900 em-font-size-14 label',
								'display' => 'all'
							],
						];
					}
					else {
						$email->additional_columns = [['key' => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'), 'value' => '', 'classes' => '', 'display' => 'all']];
					}

					$emails[$key] = $email;
				}
			}

			return array('datas' => $emails, 'count' => $count_emails);
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Error when try to get emails : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return [];
		}
	}

	/**
	 * @param $data
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function deleteEmail($ids)
	{
		$deleted = false;
		$query   = $this->_db->getQuery(true);

		if (!empty($ids)) {
			if(!is_array($ids)) {
				$ids = [$ids];
			}

			try {
				$query->delete($this->_db->quoteName('#__emundus_setup_emails'))
					->where($this->_db->quoteName('id') . ' IN (' . implode(', ', $this->_db->quote($ids)) . ')')
					->andWhere($this->_db->quoteName('type') . ' != 1');
				$this->_db->setQuery($query);
				$this->_db->execute();

				// check if the emails were deleted, cannot just check db->execute() because it returns true even if no rows were deleted (e.g. if the email was a system email)
				$query->clear();
				$query->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__emundus_setup_emails'))
					->where($this->_db->quoteName('id') . ' IN (' . implode(', ', $this->_db->quote($ids)) . ')');
				$this->_db->setQuery($query);
				if (empty($this->_db->loadColumn())) {
					$deleted = true;
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Cannot delete emails: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $deleted;
	}

	/**
	 * @param $data
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function unpublishEmail($data)
	{
		$query = $this->_db->getQuery(true);

		if (!empty($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = htmlspecialchars($data[$key]);
			}

			try {
				$fields        = array(
					$this->_db->quoteName('published') . ' = 0'
				);
				$se_conditions = array(
					$this->_db->quoteName('id') . ' IN (' . implode(", ", array_values($data)) . ')',
				);

				$query->update($this->_db->quoteName('#__emundus_setup_emails'))
					->set($fields)
					->where($se_conditions);

				$this->_db->setQuery($query);

				return $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Cannot unpublish emails: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}
		else {
			return false;
		}
	}

	/**
	 * @param $data
	 *
	 * @return false|string
	 *
	 * @since version 1.0
	 */
	public function publishEmail($data)
	{
		$query = $this->_db->getQuery(true);

		if (!empty($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = htmlspecialchars($data[$key]);
			}

			try {
				$fields        = array(
					$this->_db->quoteName('published') . ' = 1'
				);
				$se_conditions = array(
					$this->_db->quoteName('id') . ' IN (' . implode(", ", array_values($data)) . ')',
				);

				$query->update($this->_db->quoteName('#__emundus_setup_emails'))
					->set($fields)
					->where($se_conditions);

				$this->_db->setQuery($query);

				return $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Cannot publish emails: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return $e->getMessage();
			}
		}
		else {
			return false;
		}
	}

	/**
	 * @param $data
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function duplicateEmail($data)
	{
		$query = $this->_db->getQuery(true);

		if (!empty($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = htmlspecialchars($data[$key]);
			}

			try {
				$columns = array_keys($this->_db->getTableColumns('#__emundus_setup_emails'));

				$columns = array_filter($columns, function ($k) {
					return ($k != 'id' && $k != 'date_time');
				});

				foreach ($data as $id) {
					$query->clear()
						->select(implode(',', $this->_db->qn($columns)))
						->from($this->_db->quoteName('#__emundus_setup_emails'))
						->where($this->_db->quoteName('id') . ' = ' . $id);

					$this->_db->setQuery($query);
					$values[] = implode(', ', $this->_db->quote($this->_db->loadRow()));
				}


				$query->clear()
					->insert($this->_db->quoteName('#__emundus_setup_emails'))
					->columns(
						implode(',', $this->_db->quoteName($columns))
					)
					->values($values);

				$this->_db->setQuery($query);

				return $this->_db->execute();

			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Cannot duplicate emails: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}
		else {
			return false;
		}
	}

	/**
	 * @param $id
	 *
	 * @return array|false
	 *
	 * @since version 1.0
	 */
	public function getAdvancedEmailById($id)
	{
		$query = $this->_db->getQuery(true);

		if (empty($id)) {
			return false;
		}

		$query->select('*')
			->from($this->_db->quoteName('#__emundus_setup_emails'))
			->where($this->_db->quoteName('id') . ' = ' . $id);

		$this->_db->setQuery($query);

		try {
			$this->_db->setQuery($query);
			$email_Info = $this->_db->loadObject();           /// get email info

			/// count records of #emundus_setup_emails_repeat_receivers
			$query->clear()->select('COUNT(*)')->from($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' = ' . (int) $id);

			$this->_db->setQuery($query);
			$receiver_count = $this->_db->loadResult();
			$receiver_Info  = array();

			if ($receiver_count > 0) {
				$query->clear()->select('#__emundus_setup_emails_repeat_receivers.*')->from($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' = ' . (int) $id);

				$this->_db->setQuery($query);
				$receiver_Info = $this->_db->loadObjectList();         /// get receivers info (empty or not)
			}

			/// get associated email template (jos_emundus_email_template)
			$query->clear()
				->select('#__emundus_email_templates.*')
				->from($this->_db->quoteName('#__emundus_email_templates'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails') . ' ON ' . $this->_db->quoteName('#__emundus_email_templates.id') . ' = ' . $this->_db->quoteName('#__emundus_setup_emails.email_tmpl'))
				->where($this->_db->quoteName('#__emundus_setup_emails.id') . ' = ' . (int) $id);

			$this->_db->setQuery($query);
			$template_Info = $this->_db->loadObjectList();

			/// get associated letters
			$query->clear()
				->select('esa.*')
				->from($this->_db->quoteName('#__emundus_setup_attachments', 'esa'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_letters', 'esl') . ' ON ' . $this->_db->quoteName('esl.attachment_id') . ' = ' . $this->_db->quoteName('esa.id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment', 'eslr') . ' ON ' . $this->_db->quoteName('esl.attachment_id') . ' = ' . $this->_db->quoteName('eslr.letter_attachment'))
				->where($this->_db->quoteName('eslr.parent_id') . ' = ' . (int) $id)
				->group('esa.id');
			$this->_db->setQuery($query);
			$letter_Info = $this->_db->loadObjectList();         /// get attachment info

			/// get associated candidate attachments
			$query->clear()
				->select('#__emundus_setup_attachments.*')
				->from($this->_db->quoteName('#__emundus_setup_attachments'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment') . ' ON ' . $this->_db->quoteName('#__emundus_setup_attachments.id') . ' = ' . $this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.candidate_attachment'))
				->where($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.parent_id') . ' = ' . (int) $id);

			$this->_db->setQuery($query);
			$attachments_Info = $this->_db->loadObjectList();         /// get attachment info

			/// get associated tags
			$query->clear()
				->select('#__emundus_setup_action_tag.*')
				->from($this->_db->quoteName('#__emundus_setup_action_tag'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_repeat_tags') . ' ON ' . $this->_db->quoteName('#__emundus_setup_action_tag.id') . ' = ' . $this->_db->quoteName('#__emundus_setup_emails_repeat_tags.tags'))
				->where($this->_db->quoteName('#__emundus_setup_emails_repeat_tags.parent_id') . ' = ' . (int) $id);

			$this->_db->setQuery($query);
			$tags_Info = $this->_db->loadObjectList();         /// get attachment info

			return array('email' => $email_Info, 'receivers' => $receiver_Info, 'template' => $template_Info, 'letter_attachment' => $letter_Info, 'candidate_attachment' => $attachments_Info, 'tags' => $tags_Info);
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Cannot get the email by id ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * @param $data
	 * @param $receiver_cc
	 * @param $receiver_bcc
	 * @param $letters
	 * @param $documents
	 * @param $tags
	 *
	 * @return bool
	 *
	 * @since version 1.0
	 */
	public function createEmail($data, $receiver_cc = null, $receiver_bcc = null, $letters = null, $documents = null, $tags = null)
	{
		$created = false;
		$query   = $this->_db->getQuery(true);

		// set regular expression for fabrik elem
		$fabrik_pattern = '/\${(.+[0-9])\}/';

		if (!empty($data)) {
			$query->insert($this->_db->quoteName('#__emundus_setup_emails'))
				->columns($this->_db->quoteName(array_keys($data)))
				->values(implode(',', $this->_db->quote(array_values($data))));

			try {
				$this->_db->setQuery($query);
				$inserted = $this->_db->execute();

				if ($inserted) {
					$newemail = $this->_db->insertid();
					$created  = $newemail;

					$query->clear()
						->update($this->_db->quoteName('#__emundus_setup_emails'))
						->set($this->_db->quoteName('lbl') . ' = ' . $this->_db->quote('custom_' . date('YmdhHis')))
						->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($newemail));
					$this->_db->setQuery($query);
					$this->_db->execute();

					// add cc for new email
					if (!empty($receiver_cc)) {
						foreach ($receiver_cc as $key => $receiver) {
							$is_fabrik_tag = (bool) preg_match_all($fabrik_pattern, $receiver);
							if ($is_fabrik_tag == true) {
								$query->clear()
									->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $newemail)
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_cc_fabrik'));

								$this->_db->setQuery($query);
								$this->_db->execute();
							}
							else if (filter_var($receiver, FILTER_VALIDATE_EMAIL) !== false) {
								$query->clear()
									->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $newemail)
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_cc_email'));

								$this->_db->setQuery($query);
								$this->_db->execute();
							}
						}
					}

					// add bcc for new email
					if (!empty($receiver_bcc)) {
						foreach ($receiver_bcc as $key => $receiver) {
							$is_fabrik_tag = (bool) preg_match_all($fabrik_pattern, $receiver);
							if ($is_fabrik_tag == true) {
								$query->clear()
									->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $newemail)
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_bcc_fabrik'));

								$this->_db->setQuery($query);
								$this->_db->execute();
							}
							else if (filter_var($receiver, FILTER_VALIDATE_EMAIL) !== false) {
								$query->clear()
									->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $newemail)
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
									->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_bcc_email'));

								$this->_db->setQuery($query);
								$this->_db->execute();
							}
						}
					}

					// add letter attachment to table #jos_emundus_setup_emails_repeat_letter_attachment
					if (!empty($letters)) {
						foreach ($letters as $key => $letter) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment'))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.parent_id') . ' =  ' . (int) $newemail)
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.letter_attachment') . ' = ' . (int) $letter);

							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}

					// add candidate attachment to table #jos_emundus_setup_emails_repeat_candidate_attachment
					if (!empty($documents)) {
						foreach ($documents as $key => $document) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment'))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.parent_id') . ' =  ' . (int) $newemail)
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.candidate_attachment') . ' = ' . (int) $document);

							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}

					// add tag to table #jos_emundus_setup_emails_repeat_tags
					if (!empty($tags)) {
						foreach ($tags as $key => $tag) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_tags'))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_tags.parent_id') . ' =  ' . (int) $newemail)
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_tags.tags') . ' = ' . (int) $tag);

							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Cannot create an email: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
				$created = false;
			}
		}

		return $created;
	}

	/**
	 * @param $id
	 * @param $data
	 * @param $receiver_cc
	 * @param $receiver_bcc
	 * @param $letters
	 * @param $documents
	 * @param $tags
	 *
	 * @return bool
	 *
	 * @since version 1.0
	 */
	public function updateEmail($id, $data, $receiver_cc = null, $receiver_bcc = null, $letters = null, $documents = null, $tags = null)
	{
		$updated = false;
		$query   = $this->_db->getQuery(true);

		// set regular expression for fabrik elem
		$fabrik_pattern = '/\${(.+[0-9])\}/';

		if (!empty($data)) {

			$fields = [];

			foreach ($data as $key => $val) {
				if (!is_null($val)) {
					$insert   = $this->_db->quoteName($key) . ' = ' . $this->_db->quote($val);
					$fields[] = $insert;
				}
			}

			$query->update($this->_db->quoteName('#__emundus_setup_emails'))->set($fields)->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($id));

			try {
				$this->_db->setQuery($query);
				$this->_db->execute();

				/// remove and update new documents for an email
				if (!empty($letters)) {
					$query->clear()->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.parent_id') . '=' . (int) $id);

					$this->_db->setQuery($query);
					$this->_db->execute();

					foreach ($letters as $letter) {
						$query->clear()
							->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment'))
							->set($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.parent_id') . ' =  ' . (int) $id)
							->set($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.letter_attachment') . ' = ' . (int) $letter);

						$this->_db->setQuery($query);
						$this->_db->execute();
					}
				}
				else {
					$query->clear()->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.parent_id') . '=' . (int) $id);

					$this->_db->setQuery($query);
					$this->_db->execute();
				}

				if (!empty($receiver_cc)) {
					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
						->where($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . '=' . (int) $id)
						->andWhere($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' LIKE ' . $this->_db->quote('receiver_cc_%'));

					$this->_db->setQuery($query);
					$this->_db->execute();

					foreach ($receiver_cc as $receiver) {
						$is_fabrik_tag = (bool) preg_match_all($fabrik_pattern, $receiver);
						if ($is_fabrik_tag == true) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $id)
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_cc_fabrik'));
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
						else if (filter_var($receiver, FILTER_VALIDATE_EMAIL) !== false) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $id)
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_cc_email'));
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}
				}
				else {
					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
						->where($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . '=' . (int) $id)
						->andWhere($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' LIKE ' . $this->_db->quote('receiver_cc_%'));

					$this->_db->setQuery($query);
					$this->_db->execute();
				}

				/// update bcc
				if (!empty($receiver_bcc)) {
					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
						->where($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . '=' . (int) $id)
						->andWhere($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' LIKE ' . $this->_db->quote('receiver_bcc_%'));

					$this->_db->setQuery($query);
					$this->_db->execute();

					foreach ($receiver_bcc as $receiver) {
						$is_fabrik_tag = (bool) preg_match_all($fabrik_pattern, $receiver);
						if ($is_fabrik_tag) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $id)
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_bcc_fabrik'));
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
						else if (filter_var($receiver, FILTER_VALIDATE_EMAIL) !== false) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . ' =  ' . (int) $id)
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.receivers') . ' = ' . $this->_db->quote($receiver))
								->set($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' = ' . $this->_db->quote('receiver_bcc_email'));
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}
				}
				else {
					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers'))
						->where($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.parent_id') . '=' . (int) $id)
						->andWhere($this->_db->quoteName('#__emundus_setup_emails_repeat_receivers.type') . ' LIKE ' . $this->_db->quote('receiver_bcc_%'));

					$this->_db->setQuery($query);
					$this->_db->execute();
				}

				if (!empty($documents)) {
					$query->clear()->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.parent_id') . '=' . (int) $id);

					$this->_db->setQuery($query);
					$this->_db->execute();

					foreach ($documents as $document) {
						$query->clear()
							->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment'))
							->set($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.parent_id') . ' =  ' . (int) $id)
							->set($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.candidate_attachment') . ' = ' . (int) $document);

						$this->_db->setQuery($query);
						$this->_db->execute();
					}
				}
				else {
					$query->clear()->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment.parent_id') . '=' . (int) $id);

					$this->_db->setQuery($query);
					$this->_db->execute();
				}

				if (!empty($tags)) {
					$query->clear()->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_tags'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_tags.parent_id') . '=' . (int) $id);

					$this->_db->setQuery($query);
					$this->_db->execute();

					foreach ($tags as $tag) {
						$query->clear()
							->insert($this->_db->quoteName('#__emundus_setup_emails_repeat_tags'))
							->set($this->_db->quoteName('#__emundus_setup_emails_repeat_tags.parent_id') . ' =  ' . (int) $id)
							->set($this->_db->quoteName('#__emundus_setup_emails_repeat_tags.tags') . ' = ' . (int) $tag);

						$this->_db->setQuery($query);
						$this->_db->execute();
					}
				}
				else {
					$query->clear()->delete($this->_db->quoteName('#__emundus_setup_emails_repeat_tags'))->where($this->_db->quoteName('#__emundus_setup_emails_repeat_tags.parent_id') . '=' . (int) $id);

					$this->_db->setQuery($query);
					$this->_db->execute();
				}


				$updated = true;
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Cannot update the email ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}

		}

		return $updated;
	}

	/**
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function getEmailTypes()
	{
		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT(type)')
			->from($this->_db->quoteName('#__emundus_setup_emails'))
			->order('id DESC');

		try {
			$this->_db->setQuery($query);

			return $this->_db->loadColumn();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Cannot get emails types : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	public function getEmailCategories()
	{
		$categories = [];

		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT(category)')
			->from($this->_db->quoteName('#__emundus_setup_emails'))
			->where($this->_db->quoteName('category') . ' <> ""')
			->order('id DESC');

		try {
			$this->_db->setQuery($query);
			$categories = $this->_db->loadColumn();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Cannot get emails categories : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $categories;
	}

	/**
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	function getStatus()
	{
		$query = $this->_db->getQuery(true);

		$query->select('*')
			->from($this->_db->quoteName('#__emundus_setup_status'))
			->order('step ASC');

		try {
			$this->_db->setQuery($query);

			return $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Cannot get status : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * @param $pid
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	function getTriggersByProgramId($pid)
	{
		$triggers = [];

		if (!empty($pid)) {

			$lang = JFactory::getApplication()->getLanguage();
			$lid  = 2;
			if ($lang->getTag() != 'fr-FR') {
				$lid = 1;
			}

			$query = $this->_db->getQuery(true);

			$query->select(['DISTINCT(et.id) AS trigger_id', 'se.subject AS subject', 'ss.step AS status', 'ep.profile_id AS profile', 'et.to_current_user AS candidate', 'et.to_applicant AS manual'])
				->from($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_programme_id', 'etrp'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger', 'et') . ' ON ' . $this->_db->quoteName('etrp.parent_id') . ' = ' . $this->_db->quoteName('et.id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails', 'se') . ' ON ' . $this->_db->quoteName('et.email_id') . ' = ' . $this->_db->quoteName('se.id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_status', 'ss') . ' ON ' . $this->_db->quoteName('et.step') . ' = ' . $this->_db->quoteName('ss.step'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id', 'ep') . ' ON ' . $this->_db->quoteName('et.id') . ' = ' . $this->_db->quoteName('ep.parent_id'))
				->where($this->_db->quoteName('etrp.programme_id') . ' = ' . $this->_db->quote($pid) . ' OR ' . $this->_db->quoteName('et.all_program') . ' = 1');

			try {
				$this->_db->setQuery($query);
				$triggers = $this->_db->loadObjectList();

				foreach ($triggers as $trigger) {
					$query->clear()
						->select('value')
						->from($this->_db->quoteName('#__falang_content'))
						->where($this->_db->quoteName('reference_id') . ' = ' . $this->_db->quote($trigger->status))
						->andWhere($this->_db->quoteName('reference_table') . ' = ' . $this->_db->quote('emundus_setup_status'))
						->andWhere($this->_db->quoteName('reference_field') . ' = ' . $this->_db->quote('value'))
						->andWhere($this->_db->quoteName('language_id') . ' = ' . $this->_db->quote($lid));
					$this->_db->setQuery($query);
					$translated_status = $this->_db->loadResult();

					if (empty($translated_status)) {
						$query->clear()
							->select('value')
							->from('#__emundus_setup_status')
							->where('step = ' . $trigger->status);

						$this->_db->setQuery($query);
						$trigger->status = $this->_db->loadResult();
					} else {
						$trigger->status = $translated_status;
					}

					$query->clear()
						->select(['us.firstname', 'us.lastname'])
						->from($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_user_id', 'tu'))
						->leftJoin($this->_db->quoteName('#__emundus_users', 'us') . ' ON ' . $this->_db->quoteName('tu.user_id') . ' = ' . $this->_db->quoteName('us.user_id'))
						->where($this->_db->quoteName('tu.parent_id') . ' = ' . $this->_db->quote($trigger->trigger_id));

					$this->_db->setQuery($query);
					$trigger->users = array_values($this->_db->loadObjectList());
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Error at getting triggers by program id ' . $pid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $triggers;
	}

	/**
	 * @param $tid
	 *
	 * @return object | null
	 *
	 * @since version 1.0
	 */
	function getTriggerById($tid)
	{
		$trigger = null;

		if (!empty($tid)) {
			$query = $this->_db->getQuery(true);
			$query->select(['DISTINCT(et.id) AS trigger_id', 'et.step AS status', 'et.email_id AS model', 'et.all_program',  'ep.profile_id AS target', 'et.to_current_user', 'et.to_applicant', 'et.email_id as email_id', 'et.sms_id as sms_id', 'GROUP_CONCAT(DISTINCT programmes.programme_id) AS program_ids', 'GROUP_CONCAT(DISTINCT profiles.profile_id) AS profile_ids', 'GROUP_CONCAT(DISTINCT groups.group_id) AS group_ids', 'GROUP_CONCAT(DISTINCT us.user_id) AS user_ids'])
				->from($this->_db->quoteName('#__emundus_setup_emails_trigger', 'et'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id', 'ep') . ' ON ' . $this->_db->quoteName('et.id') . ' = ' . $this->_db->quoteName('ep.parent_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_programme_id', 'programmes') . ' ON ' . $this->_db->quoteName('et.id') . ' = ' . $this->_db->quoteName('programmes.parent_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id', 'profiles') . ' ON ' . $this->_db->quoteName('et.id') . ' = ' . $this->_db->quoteName('profiles.parent_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_group_id', 'groups') . ' ON ' . $this->_db->quoteName('et.id') . ' = ' . $this->_db->quoteName('groups.parent_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_user_id', 'us') . ' ON ' . $this->_db->quoteName('et.id') . ' = ' . $this->_db->quoteName('us.parent_id'))
				->where($this->_db->quoteName('et.id') . ' = ' . $this->_db->quote($tid));

			try {
				$this->_db->setQuery($query);
				$trigger = $this->_db->loadObject();
				$trigger->program_ids = !empty($trigger->program_ids) ? array_map('intval', explode(',', $trigger->program_ids)) : [];
				$trigger->profile_ids = !empty($trigger->profile_ids) ? array_map('intval', explode(',', $trigger->profile_ids)) : [];
				$trigger->group_ids = !empty($trigger->group_ids) ? array_map('intval', explode(',', $trigger->group_ids)) : [];
				$trigger->user_ids = !empty($trigger->user_ids) ? array_map('intval', explode(',', $trigger->user_ids)) : [];
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Error at getting trigger ' . $tid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $trigger;
	}

	/**
	 * @param $trigger
	 * @param $user
	 *
	 * @return boolean
	 *
	 * @since version 1.0
	 */
	function createTrigger($trigger, $user)
	{
		$created = false;

		if (!empty($user->id) && !empty($trigger['model']) && isset($trigger['status'])) {
			$email = $this->getEmailById($trigger['model']);

			if (!empty($email) && !empty($email->id)) {
				$query = $this->_db->getQuery(true);

				$to_current_user = 0;
				$to_applicant    = 0;

				if ($trigger['action_status'] == 'to_current_user') {
					$to_current_user = 1;
				}
				elseif ($trigger['action_status'] == 'to_applicant') {
					$to_applicant = 1;
				}

				try {
					$query->insert($this->_db->quoteName('#__emundus_setup_emails_trigger'))
						->set($this->_db->quoteName('user') . ' = ' . $this->_db->quote($user->id))
						->set($this->_db->quoteName('step') . ' = ' . $this->_db->quote($trigger['status']))
						->set($this->_db->quoteName('email_id') . ' = ' . $this->_db->quote($trigger['model']))
						->set($this->_db->quoteName('to_current_user') . ' = ' . $this->_db->quote($to_current_user))
						->set($this->_db->quoteName('to_applicant') . ' = ' . $this->_db->quote($to_applicant));


					$this->_db->setQuery($query);
					$this->_db->execute();
					$trigger_id = $this->_db->insertid();

					if (!empty($trigger_id)) {
						if ($trigger['target'] == 5 || $trigger['target'] == 6) {
							$query->clear()
								->insert($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id'))
								->set($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($trigger_id))
								->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($trigger['target']));
							$this->_db->setQuery($query);
							$this->_db->execute();
						}

						$query->clear()
							->insert($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_programme_id'))
							->set($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($trigger_id))
							->set($this->_db->quoteName('programme_id') . ' = ' . $this->_db->quote($trigger['program']));

						$this->_db->setQuery($query);
						$created = $this->_db->execute();
					}
				}
				catch (Exception $e) {
					Log::add('component/com_emundus/models/email | Cannot create a trigger : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');
				}
			}
		}

		return $created;
	}

	/**
	 * @param $tid
	 * @param $trigger
	 *
	 * @return false|void
	 *
	 * @since version 1.0
	 */
	function updateTrigger($tid, $trigger)
	{
		$updated = false;

		if (!empty($tid) && !empty($trigger)) {
			$query = $this->_db->getQuery(true);

			$to_current_user = 0;
			$to_applicant    = 0;

			if ($trigger['action_status'] == 'to_current_user') {
				$to_current_user = 1;
			}
			elseif ($trigger['action_status'] == 'to_applicant') {
				$to_applicant = 1;
			}

			$query->update($this->_db->quoteName('#__emundus_setup_emails_trigger'))
				->set($this->_db->quoteName('step') . ' = ' . $this->_db->quote($trigger['status']))
				->set($this->_db->quoteName('email_id') . ' = ' . $this->_db->quote($trigger['model']))
				->set($this->_db->quoteName('to_current_user') . ' = ' . $this->_db->quote($to_current_user))
				->set($this->_db->quoteName('to_applicant') . ' = ' . $this->_db->quote($to_applicant))
				->where($this->_db->quoteName('id') . ' = ' . $tid);

			try {
				$this->_db->setQuery($query);
				$updated = $this->_db->execute();

				if ($trigger['target'] == 5 || $trigger['target'] == 6) {
					$query->clear()
						->select('id')
						->from($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id'))
						->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($tid));

					$this->_db->setQuery($query);
					$row_id = $this->_db->loadResult();

					if (!empty($row_id)) {
						$query->clear()
							->update($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id'))
							->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($trigger['target']))
							->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($row_id));

						try {
							$this->_db->setQuery($query);
							$updated = $this->_db->execute();
						}
						catch (Exception $e) {
							Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
						}
					} else {
						$query->clear()
							->insert($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id'))
							->set($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($tid))
							->set($this->_db->quoteName('profile_id') . ' = ' . $this->_db->quote($trigger['target']));

						try {
							$this->_db->setQuery($query);
							$updated = $this->_db->execute();
						}
						catch (Exception $e) {
							Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
						}
					}
				} else {
					$query->clear()
						->delete($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_profile_id'))
						->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($tid));

					$this->_db->setQuery($query);
					$updated = $this->_db->execute();
				}
			} catch (Exception $e) {
				Log::add('component/com_emundus/models/email | Cannot update the trigger ' . $tid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	/**
	 * @param $tid
	 *
	 * @return false
	 *
	 * @since version 1.0
	 */
	function removeTrigger($tid)
	{
		$query = $this->_db->getQuery(true);

		$query->delete($this->_db->quoteName('#__emundus_setup_emails_trigger'))
			->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($tid));

		try {
			$this->_db->setQuery($query);

			return $this->_db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Error at remove the trigger ' . $tid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * @param $ids
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since version 1.0
	 */
	public function getEmailsFromFabrikIds($ids, $fnum = null)
	{
		require_once(JPATH_SITE . DS . 'components/com_emundus/models/files.php');
		$m_files = new EmundusModelFiles;

		$output = [];

		$fabrik_results = $m_files->getValueFabrikByIds($ids);

		foreach ($fabrik_results as $fabrik) {
			$query = 'SELECT ' . $fabrik['db_table_name'] . '.' . $fabrik['name'] . ' FROM ' . $fabrik['db_table_name'] . ' WHERE ' . $fabrik['db_table_name'] . '.' . $fabrik['name'] . ' IS NOT NULL';
			if (!empty($fnum)) {
				$query .= ' AND ' . $fabrik['db_table_name'] . '.fnum LIKE ' . $fnum;
			}
			$this->_db->setQuery($query);
			$output[] = $this->_db->loadObjectList();
		}

		$array_reduce = (array) array_reduce($output, 'array_merge', array());

		$result = [];
		foreach ($array_reduce as $value) {
			foreach ((array) $value as $data) {
				$result[] = $data;
			}
		}

		return array_unique($result);
	}

	public function checkUnpublishedTags($content)
	{
		$tags = [];

		require_once(JPATH_SITE . DS . 'components/com_emundus/helpers/tags.php');
		$h_tags = new EmundusHelperTags();

		$db = JFactory::getDBO();

		if (!empty($content)) {
			$query = $db->getQuery(true);
			$query->select('tag')
				->from($db->quoteName('#__emundus_setup_tags', 't'))
				->where($db->quoteName('t.published') . ' = 0');

			$tags_content = $h_tags->getVariables($content, 'SQUARE');

			if (!empty($tags_content)) {
				$tags_content = array_unique($tags_content);
				$query->andWhere('t.tag IN ("' . implode('","', $tags_content) . '")');

				try {
					$db->setQuery($query);
					$tags = $db->loadColumn();
				}
				catch (Exception $e) {
					Log::add('Error checking unpublished tags model/emails/setTags at query : ' . $query->__toString(), Log::ERROR, 'com_emundus.email');

					return array('patterns' => array(), 'replacements' => array());
				}
			}
		}

		return $tags;
	}

	public function sendEmailNoFnum($email_address, $email, $post = null, $user_id = null, $attachments = [], $fnum = null, $log_email = true, $emails_cc = [], $user_id_from = null)
	{
		$sent = false;

		if (!empty($email_address) && !empty($email)) {
			$e_config   = JComponentHelper::getParams('com_emundus');

			
			if (empty($user_id_from)) {
				if (!empty($this->_user->id)) {
					$user_id_from = $this->_user->id;
				} else {
					$automated_task_user = $e_config->get('automated_task_user', 1); // if empty, 1 shoud be sysadmin
					$user_id_from = $automated_task_user;
				}
			}

			require_once (JPATH_ROOT.'/components/com_emundus/helpers/emails.php');
			$h_emails = new EmundusHelperEmails();
			$is_correct = $h_emails->correctEmail($email_address);

			if ($is_correct) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/messages.php');
				$m_messages = new EmundusModelMessages();
				$config = JFactory::getConfig();
				$template = $m_messages->getEmail($email);
				$body = $template->message;
				$subject = $template->subject;
				$button_text = $template->button;

				// Get default mail sender info
				$mail_from_sys = $config->get('mailfrom');
				$mail_from_sys_name = $config->get('fromname');

				// If no mail sender info is provided, we use the system global config.
				$mail_from = $mail_from_sys;
				if(!empty($template->emailfrom)){
					$mail_from = $template->emailfrom;
				}
				$mail_from_name = $mail_from_sys_name;
				if(!empty($template->name)){
					$mail_from_name = $template->name;
				}
				$mail_from_address = $mail_from_sys;

				$toAttach = array();
				if (!empty($attachments) && is_array($attachments)) {
					$toAttach = $attachments;
				}

				// In case no post value is supplied
				$default_post = [
					'SITE_URL'   => Uri::base(),
					'SITE_NAME' => $config->get('sitename'),
					'USER_EMAIL' => $email_address,
					'LOGO' => EmundusHelperEmails::getLogo(),
					'BUTTON_TEXT' => $button_text,
				];
				if(!empty($fnum)) {
					$default_post['FNUM'] = $fnum;
				}

				if (!empty($post)) {
					$post = array_merge($default_post, $post);
				} else {
					$post = $default_post;
				}

				$cc = [];
				$keys = [];

				if ($user_id != null) {
					include_once(JPATH_ROOT.'/components/com_emundus/models/users.php');
					$m_users = new EmundusModelUsers();
					$emundus_user = $m_users->getUserById($user_id)[0];
					if (!empty($emundus_user->email_cc)) {
						if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $emundus_user->email_cc) === 1) {
							$cc[] = $emundus_user->email_cc;
						}
					}

					$password = !empty($post['PASSWORD']) ? $post['PASSWORD'] : "";
					$post_tags = $this->setTags($user_id, $post, $fnum, $password, $mail_from_name.$mail_from.$template->subject.$template->message);

					if(!empty($post_tags))
					{
						// TODO: override $post_tags replacements by $post tags if a value is set for the pattern

						$mail_from_name = preg_replace($post_tags['patterns'], $post_tags['replacements'], $mail_from_name);
						$mail_from      = preg_replace($post_tags['patterns'], $post_tags['replacements'], $mail_from);
						$subject        = preg_replace($post_tags['patterns'], $post_tags['replacements'], $subject);
						$body           = preg_replace($post_tags['patterns'], $post_tags['replacements'], $body);
					}
				}

				foreach (array_keys($post) as $key) {
					$keys[] = '/\['.$key.'\]/';
				}
				$subject = preg_replace($keys, $post, $subject);
				$body = preg_replace($keys, $post, $body);

				if($fnum != null) {
					$body = $this->setTagsFabrik($body, array($fnum));
				}

				$body_raw = strip_tags($body);

				if (isset($template->Template)) {
					$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);

					if($user_id != null && !empty($post_tags)) {
						$body = preg_replace($post_tags['patterns'], $post_tags['replacements'], $body);
					}
					$body = preg_replace($keys, $post, $body);
				}

				$mailer = JFactory::getMailer();
				$mailer->setSender([$mail_from_address, $mail_from_name]);
				$mailer->addReplyTo($mail_from, $mail_from_name);
				$mailer->addRecipient($email_address);
				$mailer->setSubject($subject);
				$mailer->isHTML(true);
				$mailer->Encoding = 'base64';
				$mailer->setBody($body);
				$mailer->AltBody = $body_raw;

				$cc = array_merge($cc, $emails_cc);
				if (!empty($cc)) {
					$mailer->addCC($cc);
				}
				if (!empty($toAttach)) {
					$mailer->addAttachment($toAttach);
				}

				require_once(JPATH_ROOT . '/components/com_emundus/helpers/emails.php');
				$custom_email_tag = EmundusHelperEmails::getCustomHeader();
				if (!empty($custom_email_tag))
				{
					$mailer->addCustomHeader($custom_email_tag);
				}

				$send = $mailer->Send();
				if ($send !== true) {
					if ($send === false) {
						Log::add('Tried sending email with mailer disabled in site settings.', Log::ERROR, 'com_emundus');
					} else {
						Log::add($send->getMessage(), Log::ERROR, 'com_emundus');
					}
				} else {
					$user_id_to = !empty($user_id) ? $user_id : null;

					if ($user_id_to === null) {
						$db = JFactory::getDbo();
						$query = $db->getQuery(true);

						$query->select('id')
							->from($db->quoteName('#__users'))
							->where($db->quoteName('email') . ' LIKE ' . $db->quote($email_address));

						try {
							$db->setQuery($query);
							$user_id_to = $db->loadResult();
						} catch (Exception $e) {
							Log::add('error trying to find user_id_to ' . $e->getMessage(), Log::ERROR);
						}
					}

					if (!empty($user_id_to) && $log_email) {
						$log = [
							'user_id_from'  => $user_id_from,
							'user_id_to'    => $user_id_to,
							'subject'       => $subject,
							'message'       => $body,
							'type'          => $template->type,
							'email_to'      => $email_address
						];
						$this->logEmail($log);
					}

					$sent = true;
				}
			}
		}

		return $sent;
	}

	function sendEmail($fnum, $email_id, $post = null, $attachments = [], $bcc = false, $sender_id = null, $user = null) {
		$sent = false;

		if (!empty($fnum) && !empty($email_id)) {
			if (empty($user)) {
				$user   = JFactory::getUser();
			}

			require_once (JPATH_ROOT.'/components/com_emundus/helpers/emails.php');
			$h_emails = new EmundusHelperEmails();
			$can_send_mail = $h_emails->assertCanSendMailToUser(null, $fnum);
			if (!$can_send_mail) {
				return false;
			}

			require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/campaign.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/messages.php');
			$m_messages = new EmundusModelMessages();
			$m_files    = new EmundusModelFiles();
			$m_campaign = new EmundusModelCampaign();
			$m_users = new EmundusModelUsers();

			$config = JFactory::getConfig();

			// Get additional info for the fnums such as the user email.
			$fnum = $m_files->getFnumInfos($fnum);
			$template = $m_messages->getEmail($email_id);
			$programme = $m_campaign->getProgrammeByTraining($fnum['training']);

			// In case no post value is supplied
			$post['FNUM'] = !isset($post['FNUM']) ? $fnum['fnum'] : $post['FNUM'];
			$post['USER_NAME'] = !isset($post['USER_NAME']) ? $fnum['name'] : $post['USER_NAME'];
			$post['COURSE_LABEL'] = !isset($post['COURSE_LABEL']) ? $programme->label : $post['COURSE_LABEL'];
			$post['CAMPAIGN_LABEL'] = !isset($post['CAMPAIGN_LABEL']) ? $fnum['label'] : $post['CAMPAIGN_LABEL'];
			$post['SITE_URL'] = !isset($post['SITE_URL']) ? JURI::base() : $post['SITE_URL'];
			$post['USER_EMAIL'] = !isset($post['USER_EMAIL']) ? $fnum['email'] : $post['USER_EMAIL'];
			$post['BUTTON_TEXT'] = !isset($post['BUTTON_TEXT']) ? $template->button : $post['BUTTON_TEXT'];
			$tags = $this->setTags($fnum['applicant_id'], $post, $fnum['fnum'], '', $template->emailfrom.$template->name.$template->subject.$template->message);

			// Get default mail sender info
			$mail_from_sys = $config->get('mailfrom');
			$mail_from_sys_name = $config->get('fromname');

			// If no mail sender info is provided, we use the system global config.
			if(!empty($template->emailfrom)) {
				$mail_from = preg_replace($tags['patterns'], $tags['replacements'], $template->emailfrom);
			} else {
				$mail_from = $mail_from_sys;
			}
			if(!empty($template->name)){
				$mail_from_name = preg_replace($tags['patterns'], $tags['replacements'], $template->name);
			} else {
				$mail_from_name = $mail_from_sys_name;
			}

			$mail_from_address = $mail_from_sys;

			$toAttach = [];
			if (!empty($attachments)) {
				$toAttach = is_array($attachments) ? $attachments : [$attachments];
			}

			$message = $this->setTagsFabrik($template->message, [$fnum['fnum']]);
			$subject = $this->setTagsFabrik($template->subject, [$fnum['fnum']]);

			// Tags are replaced with their corresponding values using the PHP preg_replace function.
			$subject = preg_replace($tags['patterns'], $tags['replacements'], $subject);

			$body = $message;
			$body = preg_replace($tags['patterns'], $tags['replacements'], $body);
			$body_raw = strip_tags($body);

			if ($template) {
				$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $template->Template);
				$body = preg_replace($tags['patterns'], $tags['replacements'], $body);
			}

			// Check if user defined a cc address
			$cc = [];
			$emundus_user = $m_users->getUserById($fnum['applicant_id'])[0];
			if(!empty($emundus_user->email_cc)) {
				if (preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/', $emundus_user->email_cc) === 1) {
					$cc[] = $emundus_user->email_cc;
				}
			}

			// Configure email sender
			$mailer = JFactory::getMailer();
			if (!empty($cc)) {
				$mailer->addCc($cc);
			}
			if ($bcc) {
				$mailer->addBCC($user->email);
			}
			$mailer->setSender([$mail_from_address, $mail_from_name]);
			$mailer->addReplyTo($mail_from, $mail_from_name);
			$mailer->addRecipient($fnum['email']);
			$mailer->setSubject($subject);
			$mailer->isHTML(true);
			$mailer->Encoding = 'base64';
			$mailer->setBody($body);
			$mailer->AltBody = $body_raw;


			// Get any candidate files included in the message.
			if (!empty($template->candidate_file)) {
				foreach ($template->candidate_file as $candidate_file) {

					$filename = $m_messages->get_upload($fnum['fnum'], $candidate_file);

					if ($filename) {

						// Build the path to the file we are searching for on the disk.
						$path = EMUNDUS_PATH_ABS.$fnum['applicant_id'].DS.$filename;

						if (file_exists($path)) {
							$toAttach[] = $path;
						}
					}
				}
			}

			if (!empty($template->letter_attachments)) {
				if (!class_exists('EmundusModelEvaluation')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
				}
				$m_evaluation = new EmundusModelEvaluation();
				$attachments = explode(',', $template->letter_attachments);
				$generatedLetters = $m_evaluation->generateLetters($fnum['fnum'], $attachments, 1);

				if ($generatedLetters->status && !empty($generatedLetters->files)) {
					foreach($generatedLetters->files as $file) {
						$toAttach[] = EMUNDUS_PATH_ABS . $fnum['applicant_id'] . DS . $file['filename'];
					}
				}
			}

			if (!empty($toAttach) && !empty($toAttach[0])) {
				$mailer->addAttachment($toAttach);
			}

			$custom_email_tag = EmundusHelperEmails::getCustomHeader();
			if (!empty($custom_email_tag))
			{
				$mailer->addCustomHeader($custom_email_tag);
			}

			// Send and log the email.
			$send = $mailer->Send();

			if ($send !== true) {
				Log::add($send, Log::WARNING, 'com_emundus.email');
			} else {
				// in cron task, the current user is the last logged user, so we use a sender_id given in parameter, or the current user id, or the default user_id if none is found.
				if (!empty($sender_id)) {
					$user_id = $sender_id;
				} else if (!empty($user)) {
					$user_id = $user->id;
				} else {
					$user_id = 62;
				}

				$log = [
					'user_id_from'  => $user_id,
					'user_id_to'    => $fnum['applicant_id'],
					'subject'       => $subject,
					'message'       => $body,
					'type'          => $template->type,
					'email_id'      => $email_id,
					'email_to'      => $fnum['email']
				];
				$this->logEmail($log, $fnum['fnum']);

				$sent = true;
			}
		}

		return $sent;
	}

	public function countEmailTriggers(int $campaign_id, int $program_id, string $search = '')
	{
		$count = 0;

		try {
			$query = $this->_db->createQuery();

			if (!empty($campaign_id))
			{
				$query->clear()
					->select('esp.id')
					->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'esp') . 'ON esp.code = esc.training')
					->where('esc.id = ' . $this->_db->quote($campaign_id));
				$this->_db->setQuery($query);
				$program_id = $this->_db->loadResult();
			}

			$query->clear()
				->select('COUNT(et.id)')
				->from($this->_db->quoteName('#__emundus_setup_emails_trigger', 'et'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails', 'email') . ' ON email.id = et.email_id')
				->leftJoin($this->_db->quoteName('#__emundus_setup_status', 'est') . ' ON est.step = et.step')
				->where('1=1');

			if (!empty($program_id)) {
				$query->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_programme_id', 'estrpi') . ' ON estrpi.parent_id = et.id')
					->where('(estrpi.programme_id = ' . $this->_db->quote($program_id) . ' OR et.all_program = 1)');
			}

			if (!empty($search)) {
				$query->where('(email.subject LIKE ' . $this->_db->quote('%' . $search . '%') . ' OR sms.label LIKE ' . $this->_db->quote('%' . $search . '%') .')');
			}

			$this->_db->setQuery($query);
			$count = $this->_db->loadResult();
		} catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Error at getting triggers by program id ' . $program_id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $count;
	}

	/**
	 * @param   int  $campaign_id
	 * @param   int  $program_id
	 *
	 * @return array
	 */
	public function getEmailTriggers(int $campaign_id, int $program_id, string $search = '', int $lim = 25, int $page = 1, string $order_by = '', string $sort = 'ASC'): array
	{
		$triggers = [];

		try {
			$query = $this->_db->createQuery();

			if (!empty($campaign_id))
			{
				$query->clear()
					->select('esp.id')
					->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'esp') . 'ON esp.code = esc.training')
					->where('esc.id = ' . $this->_db->quote($campaign_id));
				$this->_db->setQuery($query);
				$program_id = $this->_db->loadResult();
			}

			$query->clear()
				->select('et.id, email.id as email_id, et.sms_id as sms_id, sms.label as sms_label, email.subject as email_label, est.value as status, et.to_current_user, et.to_applicant')
				->from($this->_db->quoteName('#__emundus_setup_emails_trigger', 'et'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_emails', 'email') . ' ON email.id = et.email_id')
				->leftJoin($this->_db->quoteName('#__emundus_setup_status', 'est') . ' ON est.step = et.step')
				->leftJoin($this->_db->quoteName('#__emundus_setup_sms', 'sms') . ' ON sms.id = et.sms_id')
				->where('1=1');

			if (!empty($program_id)) {
				$query->leftJoin($this->_db->quoteName('#__emundus_setup_emails_trigger_repeat_programme_id', 'estrpi') . ' ON estrpi.parent_id = et.id')
					->andWhere('(estrpi.programme_id = ' . $this->_db->quote($program_id) . ' OR et.all_program = 1)');
			}

			if (!empty($search)) {
				$query->andWhere('(email.subject LIKE ' . $this->_db->quote('%' . $search . '%') . ' OR sms.label LIKE ' . $this->_db->quote('%' . $search . '%') . ')');
			}
			
			if (!empty($order_by)) {
				$query->order($this->_db->quoteName($order_by) . ' ' . $this->_db->escape($sort));
			} else {
				$query->order('et.id DESC');
			}

			$offset = ($page - 1) * $lim;

			$this->_db->setQuery($query, $offset, $lim);

			$triggers = $this->_db->loadAssocList();

			foreach($triggers as $key => $trigger) {

				if (!empty($trigger['email_id']) && !empty($trigger['sms_id'])) {
					$triggers[$key]['label'] = [
						'fr' => $trigger['email_label'] . ' / ' . $trigger['sms_label'],
						'en' => $trigger['email_label'] . ' / ' . $trigger['sms_label']
					];
				} else if (!empty($trigger['email_id'])) {
					$triggers[$key]['label'] = [
						'fr' => $trigger['email_label'],
						'en' => $trigger['email_label']
					];
				} else if (!empty($trigger['sms_id'])) {
					$triggers[$key]['label'] = [
						'fr' => $trigger['sms_label'],
						'en' => $trigger['sms_label']
					];
				}
			}
		} catch (Exception $e) {
			Log::add('component/com_emundus/models/email | Error at getting triggers by program id ' . $program_id . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $triggers;
	}

	public function saveTrigger(array $trigger, int $user_id): int
	{
		$trigger_id = 0;

		if (!empty($trigger)) {
			try {
				if (!class_exists('TriggerEntity')) {
					require_once(JPATH_ROOT .'/components/com_emundus/classes/Entities/Messages/TriggerEntity.php');
				}

				$program_ids = array_map(function ($program) {return $program['id'];}, $trigger['program_ids']);
				if (!empty($trigger['trigger_id'])) {
					$trigger_entity = new TriggerEntity($trigger['trigger_id']);
					$trigger_entity->step = $trigger['status'];
					$trigger_entity->email_id = (int)$trigger['email_id'] ?? 0;
					$trigger_entity->sms_id = (int)$trigger['sms_id'] ?? 0;
					$trigger_entity->program_ids = $program_ids;
					$trigger_entity->to_current_user = (int)$trigger['to_current_user'] ?? 0;
					$trigger_entity->to_applicant = (int)$trigger['to_applicant'] ?? 0;
					$trigger_entity->user_ids = $trigger['user_ids'] ?? [];
					$trigger_entity->role_ids = $trigger['profile_ids'] ?? [];
					$trigger_entity->group_ids = $trigger['group_ids'] ?? [];
					$trigger_entity->all_program = (int)$trigger['all_program'] ?? 0;
				} else {
					$trigger_entity = new TriggerEntity(
						0,
						$trigger['status'],
						$program_ids,
						(int)$trigger['email_id'] ?? 0,
						(int)$trigger['sms_id'] ?? 0,
						(int)$trigger['to_current_user'] ?? 0,
						(int)$trigger['to_applicant'] ?? 0,
						$trigger['user_ids'] ?? [],
						$trigger['profile_ids'] ?? [],
						$trigger['group_ids'] ?? [],
						(int)$trigger['all_program'] ?? 0
					);
				}

				$saved = $trigger_entity->save($user_id);

				if ($saved) {
					$trigger_id = $trigger_entity->getId();
				}
			} catch (Exception $e) {
				Log::add('Error at saving trigger : ' . $e->getMessage(), Log::ERROR, 'com_emundus.emails');
			}
		}

		return $trigger_id;
	}
}
?>
