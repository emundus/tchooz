<?php
/**
 * @version     2: emundusReferentLetter 2018-04-25 Hugo Moracchini
 * @package     Fabrik
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Redirection et chainage des formulaires suivant le profile de l'utilisateur
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */
class PlgFabrik_FormEmundusReferentLetter extends plgFabrik_Form
{
	/**
	 * Status field
	 *
	 * @var  string
	 */
	protected $URLfield = '';

	/**
	 * Get an element name
	 *
	 * @param   string  $pname  Params property name to look up
	 * @param   bool    $short  Short (true) or full (false) element name, default false/full
	 *
	 * @return    string    element full name
	 */
	public function getFieldName($pname, $short = false)
	{
		$params = $this->getParams();

		if ($params->get($pname) == '')
		{
			return '';
		}

		$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));

		return $short ? $elementModel->getElement()->name : $elementModel->getFullName();
	}

	/**
	 * Get the fields value regardless of whether its in joined data or no
	 *
	 * @param   string  $pname    Params property name to get the value for
	 * @param   mixed   $default  Default value
	 *
	 * @return  mixed  value
	 */
	public function getParam($pname, $default = '')
	{
		$params = $this->getParams();

		if ($params->get($pname) == '')
		{
			return $default;
		}

		return $params->get($pname);
	}

	public function onCanEditGroup($groupModel)
	{
		$can_be_update = true;

		$formModel = $this->getModel();
		$listModel = $formModel->getListModel();

		$table         = $listModel->getTable();
		$db_table_name = $table->db_table_name;

		$fnum = $formModel->data[$db_table_name . '___fnum'];

		if (is_array($groupModel))
		{
			$groupModel = $groupModel[0];
		}

		$referent_email = '';
		$emails         = explode(',', $this->getParam('emails', 'jos_emundus_references___Email_1,jos_emundus_references___Email_2,jos_emundus_references___Email_3,jos_emundus_references___Email_4'));

		if (!empty($emails) && !empty($formModel->data) && !empty($groupModel->getPublishedElements()))
		{
			foreach ($groupModel->getPublishedElements() as $element)
			{
				if (in_array($element->getFullName(), $emails))
				{
					$referent_email = $element->getValue($formModel->data);
				}
			}

			if (!empty($fnum) && !empty($referent_email))
			{
				$query = $this->_db->getQuery(true);

				$query->clear()
					->select('count(id)')
					->from($this->_db->quoteName('#__emundus_files_request'))
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum))
					->andWhere($this->_db->quoteName('email') . ' = ' . $this->_db->quote($referent_email))
					->andWhere($this->_db->quoteName('uploaded') . ' = 1');
				$this->_db->setQuery($query);
				$can_be_update = (int) $this->_db->loadResult() <= 0;
			}
		}

		return $can_be_update;
	}

	/**
	 * Main script.
	 *
	 * @return  bool
	 * @throws Exception
	 */
	public function onBeforeCalculations()
	{

		jimport('joomla.utilities.utility');
		jimport('joomla.log.log');
		JLog::addLogger(['text_file' => 'com_emundus.filerequest.php'], JLog::ALL, ['com_emundus']);

		include_once(JPATH_BASE . '/components/com_emundus/models/files.php');
		include_once(JPATH_BASE . '/components/com_emundus/models/emails.php');
		include_once(JPATH_BASE . '/components/com_emundus/models/profile.php');
		include_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');

		$query   = $this->_db->getQuery(true);
		$form_id = $this->app->getInput()->getInt('formid', 0);
		$user = $this->app->getIdentity();

		$offset   = $this->app->get('offset', 'UTC');
		$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
		$dateTime = $dateTime->setTimezone(new DateTimeZone($offset));
		$now      = $dateTime->format('Y-m-d H:i:s');

		$db_table_name = 'jos_emundus_references';
		if (!empty($form_id))
		{
			$query = $this->_db->getQuery(true);
			$query->select('db_table_name')
				->from($this->_db->quoteName('#__fabrik_lists'))
				->where($this->_db->quoteName('form_id') . ' = ' . $this->_db->quote($form_id));
			$this->_db->setQuery($query);
			$db_table_name = $this->_db->loadResult();
		}

		$emundusUser = $this->app->getSession()->get('emundusUser');
		$m_profiles = new EmundusModelProfile;
		$applicant_profiles   = $m_profiles->getApplicantsProfilesArray();

		// Check if the user is an applicant
		$is_applicant = true;
		if (!empty($emundusUser) && !empty($applicant_profiles))
		{
			$is_applicant = in_array($emundusUser->profile, $applicant_profiles);
		}

		if($is_applicant || !EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			$student_id = $user->id;
			$fnum = $emundusUser->fnum;
		}
		else {
			$student_id = $this->app->getInput()->get($db_table_name . '___user')[0];
			$fnum       = $this->app->getInput()->get($db_table_name . '___fnum');
		}


		$email_tmpls         = explode(',', $this->getParam('email_tmpl', 'referent_letter'));
		$emails              = explode(',', $this->getParam('emails', 'jos_emundus_references___Email_1,jos_emundus_references___Email_2,jos_emundus_references___Email_3,jos_emundus_references___Email_4'));
		$names               = explode(',', $this->getParam('names', 'jos_emundus_references___Last_Name_1,jos_emundus_references___Last_Name_2,jos_emundus_references___Last_Name_3,jos_emundus_references___Last_Name_4'));
		$firstnames          = explode(',', $this->getParam('firstnames', 'jos_emundus_references___First_Name_1,jos_emundus_references___First_Name_2,jos_emundus_references___First_Name_3,jos_emundus_references___First_Name_4'));
		$default_attachments = [4, 6, 21, 19];

		$recipients = array();
		foreach ($emails as $key => $email)
		{
			$email     = $this->app->getInput()->getString($email, '');
			$name      = Text::_('CIVILITY_MR') . '/' . Text::_('CIVILITY_MRS');
			$firstname = '';
			if (!empty($names[$key]))
			{
				$name = $this->app->getInput()->getString($names[$key], Text::_('CIVILITY_MR') . '/' . Text::_('CIVILITY_MRS'));
			}
			if (!empty($firstnames[$key]))
			{
				$firstname = $this->app->getInput()->getString($firstnames[$key], '');
			}

			if (is_array($email_tmpls))
			{
				$email_tmpl = !empty($email_tmpls[$key]) ? $email_tmpls[$key] : $email_tmpls[0];
			}
			else
			{
				$email_tmpl = $email_tmpls;
			}

			$recipients[] = array(
				'email_tmpl'    => $email_tmpl,
				'attachment_id' => $this->app->getInput()->get($db_table_name . '___attachment_id_' . ($key + 1), $default_attachments[$key]),
				'email'         => $email,
				'name'          => ucwords($name),
				'firstname'     => ucwords($firstname)
			);
		}

		$student = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(intval($student_id));

		$url        = $this->getParam('url', 'index.php?option=com_fabrik&c=form&view=form&formid=68&tableid=71');
		$sef_url    = $this->getParam('sef_url', false);
		$email_tmpl = $this->getParam('email_tmpl', 'referent_letter');

		$query->clear()
			->select('esp.reference_letter')
			->from($this->_db->quoteName('#__emundus_setup_profiles', 'esp'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.profile_id') . ' = ' . $this->_db->quoteName('esp.id'))
			->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('esc.id'))
			->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($fnum));
		$this->_db->setQuery($query);
		$obj_letter = $this->_db->loadRowList();

		$m_files     = new EmundusModelFiles;
		$fnum_detail = $m_files->getFnumInfos($fnum);
		$m_emails    = new EmundusModelEmails;

		// setup mail
		$email_from_sys = $this->app->get('mailfrom');

		$attachment = array();
		if (!empty($obj_letter[0][0]))
		{
			$attachment[] = JPATH_BASE . str_replace("\\", "/", $obj_letter[0][0]);
		}

		foreach ($recipients as $recipient)
		{
			if (!empty($recipient['email']) && !empty($recipient['email_tmpl']))
			{
				// Récupération des données du mail
				$query = $this->_db->getQuery(true);
				$query->select('est.id,est.subject, est.emailfrom, est.name, est.message, eet.Template')
					->from($this->_db->quoteName('#__emundus_setup_emails', 'est'))
					->leftJoin($this->_db->quoteName('#__emundus_email_templates', 'eet') . ' ON ' . $this->_db->quoteName('est.email_tmpl') . ' = ' . $this->_db->quoteName('eet.id'))
					->where($this->_db->quoteName('est.lbl') . ' LIKE ' . $this->_db->quote($recipient['email_tmpl']));
				$this->_db->setQuery($query);
				$obj = $this->_db->loadObjectList();

				$from     = $obj[0]->emailfrom;
				$fromname = $obj[0]->name;

				$sender = array(
					$email_from_sys,
					$fromname
				);

				$attachment_id = $recipient['attachment_id'];

				// TODO : Check if we already sent a file request today, merge this query with query uploaded. If a file request is sent today OR already uploaded we don't send this email
				$query->clear()
					->select('count(id)')
					->from($this->_db->quoteName('#__emundus_files_request', 'jefr'))
					->where($this->_db->quoteName('jefr.fnum') . ' LIKE ' . $this->_db->quote($fnum))
					->andWhere($this->_db->quoteName('jefr.email') . ' = ' . $this->_db->quote($recipient['email']))
					->andWhere($this->_db->quoteName('jefr.attachment_id') . ' = ' . $this->_db->quote($attachment_id))
					->andWhere($this->_db->quoteName('jefr.uploaded') . ' = 1');
				$this->_db->setQuery($query);
				$isSelectedReferent = (int) $this->_db->loadResult();

				if ($isSelectedReferent > 0)
				{
					continue;
				}
				else
				{
					$query->clear()
						->select('count(id)')
						->from($this->_db->quoteName('#__emundus_files_request'))
						->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum))
						->andWhere($this->_db->quoteName('student_id') . ' = ' . $this->_db->quote($student->id))
						->andWhere($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($attachment_id))
						->andWhere($this->_db->quoteName('uploaded') . ' = 1');
					$this->_db->setQuery($query);
					$is_uploaded = $this->_db->loadResult();

					if ($is_uploaded == 0)
					{
						$key = md5(date('Y-m-d h:m:i') . '::' . $fnum . '::' . $student_id . '::' . $attachment_id . '::' . rand());

						$query->clear()
							->insert($this->_db->quoteName('#__emundus_files_request'))
							->columns($this->_db->quoteName(['time_date', 'student_id', 'keyid', 'attachment_id', 'campaign_id', 'fnum', 'email']))
							->values(implode(',', $this->_db->quote([$now, $student->id, $key, $attachment_id, $fnum_detail['id'], $fnum, $recipient['email']])));
						$this->_db->setQuery($query);
						$this->_db->execute();
						$request_id = $this->_db->insertid();

						if ($sef_url === 'true')
						{
							$link_upload = Uri::base() . $url . '?keyid=' . $key . '&sid=' . $student->id;
						}
						else
						{
							$link_upload = Uri::base() . $url . '&keyid=' . $key . '&sid=' . $student->id;
						}

						$post    = [
							'ID'                  => $student->id,
							'NAME'                => $student->name,
							'EMAIL'               => $student->email,
							'UPLOAD_URL'          => $link_upload,
							'PROGRAMME_NAME'      => $fnum_detail['label'],
							'FNUM'                => $fnum,
							'USER_NAME'           => $fnum_detail['name'],
							'CAMPAIGN_LABEL'      => $fnum_detail['label'],
							'SITE_URL'            => Uri::base(),
							'USER_EMAIL'          => $fnum_detail['email'],
							'REFERENT_NAME'       => $recipient['name'],
							'REFERENT_FIRST_NAME' => $recipient['firstname']
						];
						$tags    = $m_emails->setTags($fnum_detail['applicant_id'], $post, $fnum, '', $obj[0]->subject . $obj[0]->message);
						$subject = preg_replace($tags['patterns'], $tags['replacements'], $obj[0]->subject);
						$subject = $m_emails->setTagsFabrik($subject, [$fnum_detail['fnum']]);

						$body = $obj[0]->message;
						if ($obj[0]->Template)
						{
							$body = preg_replace(["/\[EMAIL_SUBJECT\]/", "/\[EMAIL_BODY\]/"], [$subject, $body], $obj[0]->Template);
						}
						$body = preg_replace($tags['patterns'], $tags['replacements'], $body);
						$body = $m_emails->setTagsFabrik($body, array($student->fnum));

						$to = array($recipient['email']);

						$mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
						$mailer->setSender($sender);
						if (!empty($from) && !empty($fromname))
						{
							$mailer->addReplyTo($from, $fromname);
						}
						$mailer->addRecipient($to);
						$mailer->setSubject($subject);
						$mailer->isHTML(true);
						$mailer->Encoding = 'base64';
						$mailer->setBody($body);
						$mailer->addAttachment($attachment);

						if ($mailer->Send() !== true)
						{
							$this->app->enqueueMessage(Text::_('MESSAGE_NOT_SENT') . ' : ' . $recipient['email'], 'error');
							JLog::add('Cannot send email', JLog::ERROR, 'com_emundus');
						}
						else
						{
							$this->app->enqueueMessage(Text::_('MESSAGE_SENT') . ' : ' . $recipient['email'], 'message');
							$body = Text::_('SENT_TO') . ' ' . $recipient['email'] . '<br><a href="index.php?option=com_fabrik&view=details&formid=264&rowid=' . $request_id . '&listid=273" target="_blank">' . Text::_('INVITATION_LINK') . '</a><br>' . $body;

							$query->clear()
								->insert($this->_db->quoteName('#__messages'))
								->columns($this->_db->quoteName(['user_id_from', 'user_id_to', 'subject', 'message', 'date_time']))
								->values(implode(',', $this->_db->quote([$this->app->getIdentity()->id, $student_id, $subject, $body, $now])));
							$this->_db->setQuery($query);
							try
							{
								$this->_db->execute();
							}
							catch (Exception $e)
							{
								// catch any database errors.
							}
						}

						unset($replacements);
						unset($mailer);
					}
				}
			}
		}

		return true;
	}
}
