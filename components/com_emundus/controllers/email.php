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

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;

class EmundusControllerEmail extends EmundusController
{
	private mixed $_em_user;

	private $m_emails;

	private HtmlSanitizerSingleton $sanitizer;

	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . '/components/com_emundus/helpers/filters.php');
		require_once(JPATH_BASE . '/components/com_emundus/helpers/export.php');

		if (!class_exists('HtmlSanitizerSingleton'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}

		$config = (new HtmlSanitizerConfig())
			->allowSafeElements()
			->allowElement('a', ['href', 'title', 'target'])
			->allowElement('img', '*')
			->allowElement('p', 'style')
			->allowAttribute('img', ['src', 'style', 'alt', 'title', 'width', 'height', 'draggable'])
			->allowAttribute('*', 'style')
			->allowRelativeLinks(true)
			->allowRelativeMedias(true)
			->forceHttpsUrls(true);

		$this->sanitizer = HtmlSanitizerSingleton::getInstance($config);

		$this->_em_user = $this->app->getSession()->get('emundusUser');
		$this->m_emails = $this->getModel('emails');
	}

	function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view'))
		{
			$default = 'evaluation';
			$this->input->set('view', $default);
		}

		if (EmundusHelperAccess::asEvaluatorAccessLevel($this->_em_user->id))
		{
			parent::display();
		}
		else
		{
			echo Text::_('ACCESS_DENIED');
		}

		return $this;
	}

	function clear(): void
	{
		EmundusHelperFiles::clear();

		$itemid           = $this->app->getMenu()->getActive()->id;
		$limitstart       = $this->input->get('limitstart', null, 'POST');
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);

		$this->setRedirect('index.php?option=com_emundus&view=' . $this->input->get('view') . '&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir . '&Itemid=' . $itemid);
	}

	function applicantEmail(): void
	{
		require_once(JPATH_BASE . '/components/com_emundus/helpers/emails.php');
		EmundusHelperEmails::sendApplicantEmail();
	}

	function getTemplate(): void
	{
		require_once(JPATH_BASE . '/components/com_emundus/helpers/emails.php');
		EmundusHelperEmails::getTemplate();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 18, 'mode' => CrudEnum::CREATE]])]
	function sendmail_expert(): void
	{
		$fnums = $this->input->post->getString('fnums');
		if (empty($fnums))
		{
			$fnums = $this->app->getUserState('com_emundus.email.expert.fnums');
		}

		$mail_subject   = $this->input->post->getString('mail_subject', '');
		$mail_from_name = $this->input->post->getString('mail_from_name', '');
		$mail_from      = $this->input->post->getString('mail_from', '');
		$mail_to        = $this->input->post->getString('mail_to', '');
		$mail_body      = $this->input->post->getRaw('mail_body', '');
		$mail_id        = $this->input->post->getInt('mail_id', 0);

		if (!empty($fnums) && !empty($mail_subject) && !empty($mail_to) && !empty($mail_body))
		{
			$mail_to = explode(',', $mail_to);
			$email   = $this->m_emails->sendExpertMail((array) $fnums, $this->user->id, $mail_subject, $mail_from_name, $mail_from, $mail_to, $mail_body, $mail_id);

			$response = ['status' => true, 'sent' => $email['sent'], 'failed' => $email['failed'], 'message' => $email['message']];
		}
		else
		{
			$response = ['status' => false, 'sent' => null, 'failed' => true, 'message' => Text::_('MISSING_PARAMS')];
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getallemail(): EmundusResponse
	{
		$filter    = $this->input->getString('filter') ? $this->input->getString('filter') : 'Publish';
		$sort      = $this->input->getString('sort', '');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$category  = $this->input->getString('category', '');
		$order_by  = $this->input->getString('order_by', 'se.id');
		$order_by  = $order_by == 'label' ? 'se.subject' : $order_by;

		$actionRepository = new ActionRepository();
		$emailAction   = $actionRepository->getByName('email');
		$adminAccess = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asAdministratorAccessLevel($this->user->id);
		$emailAccess = $adminAccess || EmundusHelperAccess::asAccessAction($emailAction->getId(), 'r', $this->user->id);

		$emails = $this->m_emails->getAllEmails($lim, $page, $filter, $sort, $recherche, $category, $order_by, $this->user->id, $emailAccess, $adminAccess);

		return EmundusResponse::ok($emails);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'email', 'mode' => CrudEnum::DELETE]])]
	public function deleteemail(): EmundusResponse
	{
		$data = $this->input->getInt('id', 0);
		if (empty($data))
		{
			$data = $this->input->getString('ids');
			$data = explode(',', $data);
		}

		if (empty($data))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$deleted = $this->m_emails->deleteEmail($data);
		if (!$deleted)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DELETE_EMAIL'));
		}

		return EmundusResponse::ok($deleted, Text::_('EMAIL_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'email', 'mode' => CrudEnum::UPDATE]])]
	public function unpublishemail(): EmundusResponse
	{
		$data = $this->input->getInt('id');
		if (empty($data))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$emails = $this->m_emails->unpublishEmail($data);
		if (!$emails)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UNPUBLISH_EMAIL'));
		}

		return EmundusResponse::ok($emails, Text::_('EMAIL_UNPUBLISHED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'email', 'mode' => CrudEnum::UPDATE]])]
	public function publishemail(): EmundusResponse
	{
		$data = $this->input->getInt('id');
		if (empty($data))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$emails = $this->m_emails->publishEmail($data);
		if (!$emails)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_PUBLISH_EMAIL'));
		}

		return EmundusResponse::ok($emails, Text::_('EMAIL_PUBLISHED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'email', 'mode' => CrudEnum::CREATE]])]
	public function duplicateemail(): void
	{
		$data = $this->input->getInt('id');
		if (empty($data))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$email = $this->m_emails->duplicateEmail($data);
		if (!$email)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DUPLICATE_EMAIL'));
		}

		$this->getallemail();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'email', 'mode' => CrudEnum::CREATE]])]
	public function createemail(): EmundusResponse
	{
		$data              = [];
		$data['lbl']       = $this->input->getString('lbl', '');
		$data['subject']   = $this->input->getString('subject', '');
		$data['emailfrom'] = $this->input->getString('emailfrom', '');
		$data['message']   = $this->input->getRaw('message', '');
		$data['message']   = $this->sanitizer->sanitize($data['message']);

		$data['email_tmpl'] = $this->input->getInt('email_tmpl', 1);
		$data['category']   = $this->input->getString('category', '');
		$data['name']       = $this->input->getString('name', '');
		$data['button']     = $this->input->getString('button', '');

		// Additional data
		$receivers_cc          = $this->input->getRaw('selectedReceiversCC');
		$receivers_cc          = json_decode($receivers_cc, true);
		$receivers_bcc         = $this->input->getRaw('selectedReceiversBCC');
		$receivers_bcc         = json_decode($receivers_bcc, true);
		$letter_attachments    = $this->input->getRaw('selectedLetterAttachments');
		$letter_attachments    = json_decode($letter_attachments, true);
		$candidate_attachments = $this->input->getRaw('selectedCandidateAttachments');
		$candidate_attachments = json_decode($candidate_attachments, true);
		$tags                  = $this->input->getRaw('selectedTags');
		$tags                  = json_decode($tags, true);

		$cc_list       = [];
		$bcc_list      = [];
		$letter_list   = [];
		$document_list = [];
		$tag_list      = [];

		// get receiver cc from cc list
		if (!empty($receivers_cc))
		{
			foreach ($receivers_cc as $value)
			{
				if (!empty($value['email']) or !is_null($value['email']))
				{
					$cc_list[] = $value['email'];
				}
			}
		}

		// get receiver bcc from cc list
		if (!empty($receivers_bcc))
		{
			foreach ($receivers_bcc as $value)
			{
				if (!empty($value['email']) or !is_null($value['email']))
				{
					$bcc_list[] = $value['email'];
				}
			}
		}

		// get letters from $letter_attachments
		if (!empty($letter_attachments))
		{
			foreach ($letter_attachments as $value)
			{
				if (!empty($value['id']) or !is_null($value['id']))
				{
					$letter_list[] = $value['id'];
				}
			}
			$letter_list = array_unique($letter_list);
		}

		// get candidate attachments from $candidate_attachments
		if (!empty($candidate_attachments))
		{
			foreach ($candidate_attachments as $value)
			{
				if (!empty($value['id']) or !is_null($value['id']))
				{
					$document_list[] = $value['id'];
				}
			}
		}

		// get tags from $tags
		if (!empty($tags))
		{
			foreach ($tags as $value)
			{
				if (!empty($value['id']) or !is_null($value['id']))
				{
					$tag_list[] = $value['id'];
				}
			}
		}

		// call to createEmail model
		$result = $this->m_emails->createEmail($data, $cc_list, $bcc_list, $letter_list, $document_list, $tag_list, $this->user->id);
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_ADD_EMAIL'));
		}

		return EmundusResponse::ok($result, Text::_('EMAIL_ADDED'));
	}


	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'email', 'mode' => CrudEnum::UPDATE]])]
	public function updateemail(): EmundusResponse
	{
		// Main data
		$data               = [];
		$id                 = $this->input->getInt('id', 0);
		$data['lbl']        = $this->input->getString('lbl', '');
		$data['subject']    = $this->input->getString('subject', '');
		$data['emailfrom']  = $this->input->getString('emailfrom', '');
		$data['message']    = $this->input->getRaw('message', '');
		$data['email_tmpl'] = $this->input->getInt('email_tmpl', 1);
		$data['category']   = $this->input->getString('category', '');
		$data['name']       = $this->input->getString('name', '');
		$data['button']     = $this->input->getString('button', '');

		// Additional data
		$receivers_cc          = $this->input->getRaw('selectedReceiversCC');
		$receivers_cc          = json_decode($receivers_cc, true);
		$receivers_bcc         = $this->input->getRaw('selectedReceiversBCC');
		$receivers_bcc         = json_decode($receivers_bcc, true);
		$letter_attachments    = $this->input->getRaw('selectedLetterAttachments');
		$letter_attachments    = json_decode($letter_attachments, true);
		$candidate_attachments = $this->input->getRaw('selectedCandidateAttachments');
		$candidate_attachments = json_decode($candidate_attachments, true);
		$tags                  = $this->input->getRaw('selectedTags');
		$tags                  = json_decode($tags, true);

		$data['message'] = $this->sanitizer->sanitize($data['message']);

		$cc_list     = [];
		$bcc_list    = [];
		$letter_list = [];

		$document_list = [];
		$tag_list      = [];

		// get receiver cc from cc list
		if (!empty($receivers_cc))
		{
			foreach ($receivers_cc as $value)
			{
				if (!empty($value['email']) or !is_null($value['email']))
				{
					$cc_list[] = $value['email'];
				}
			}
		}

		// get receiver bcc from cc list
		if (!empty($receivers_bcc))
		{
			foreach ($receivers_bcc as $value)
			{
				if (!empty($value['email']) or !is_null($value['email']))
				{
					$bcc_list[] = $value['email'];
				}
			}
		}

		// get attachments from $letters
		if (!empty($letter_attachments))
		{
			foreach ($letter_attachments as $value)
			{
				if (!empty($value['id']) or !is_null($value['id']))
				{
					$letter_list[] = $value['id'];
				}
			}
		}

		// get candidate attachments from $candidate_attachments
		if (!empty($candidate_attachments))
		{
			foreach ($candidate_attachments as $value)
			{
				if (!empty($value['id']) or !is_null($value['id']))
				{
					$document_list[] = $value['id'];
				}
			}
		}

		// get tags from $tags
		if (!empty($tags))
		{
			foreach ($tags as $value)
			{
				if (!empty($value['id']) or !is_null($value['id']))
				{
					$tag_list[] = $value['id'];
				}
			}
		}

		$result = $this->m_emails->updateEmail($id, $data, $cc_list, $bcc_list, $letter_list, $document_list, $tag_list);
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UPDATE_EMAIL'));
		}

		return EmundusResponse::ok($result, Text::_('EMAIL_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'email', 'mode' => CrudEnum::READ]])]
	public function getemailbyid(): EmundusResponse
	{
		$id = $this->input->getInt('id');
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$email = $this->m_emails->getAdvancedEmailById($id);
		if (empty($email))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_EMAIL'));
		}

		return EmundusResponse::ok($email, Text::_('EMAIL_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getemailcategories(): EmundusResponse
	{
		$categories = $this->m_emails->getEmailCategories();

		return EmundusResponse::ok($categories, Text::_('EMAIL_CATEGORIES_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getemailtypes(): EmundusResponse
	{
		$email = $this->m_emails->getEmailTypes();
		if (empty($email))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_EMAIL_TYPES'));
		}

		return EmundusResponse::ok($email, Text::_('EMAIL_TYPES_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getstatus(): EmundusResponse
	{
		$status = $this->m_emails->getStatus();
		if (empty($status))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_STATUS'));
		}

		return EmundusResponse::ok($status, Text::_('STATUS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function gettriggersbyprogram(): EmundusResponse
	{
		$pid = $this->input->getInt('pid');
		if (empty($pid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$triggers = $this->m_emails->getTriggersByProgramId($pid);
		if (empty($triggers))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_TRIGGERS'));
		}

		return EmundusResponse::ok($triggers, Text::_('TRIGGER_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function gettriggerbyid(): EmundusResponse
	{
		$tid = $this->input->getInt('tid', 0);
		if (empty($tid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$trigger = $this->m_emails->getTriggerById($tid);
		if (empty($trigger))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_TRIGGER'));
		}

		return EmundusResponse::ok($trigger, Text::_('TRIGGER_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function createtrigger(): EmundusResponse
	{
		$trigger = $this->input->getRaw('trigger', '');
		$trigger = json_decode($trigger, true);
		if (empty($trigger))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$created = $this->m_emails->createTrigger($trigger, $this->user);
		if (!$created)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_CREATE_TRIGGER'));
		}

		return EmundusResponse::ok($created, Text::_('TRIGGER_CREATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function updatetrigger(): EmundusResponse
	{
		$tid     = $this->input->getInt('tid', 0);
		$trigger = $this->input->getRaw('trigger', '');
		$trigger = json_decode($trigger, true);
		if (empty($trigger) || empty($tid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$updated = $this->m_emails->updateTrigger($tid, $trigger);
		if (!$updated)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UPDATE_TRIGGER'));
		}

		return EmundusResponse::ok($updated, Text::_('TRIGGER_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function removetrigger(): EmundusResponse
	{
		$tid = $this->input->getInt('id');
		if (empty($tid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$status = $this->m_emails->removeTrigger($tid);
		if (!$status)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DELETE_TRIGGER'));
		}

		return EmundusResponse::ok($status, Text::_('TRIGGER_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getemailcontent(): EmundusResponse
	{
		$template = $this->input->getString('tmp');
		if (empty($template))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if ($template != 8)
		{
			throw new InvalidArgumentException(Text::_('INVALID_TEMPLATE'));
		}

		require_once(JPATH_SITE . DS . '/components/com_emundus/models/files.php');
		$m_files = $this->getModel('Files');

		$fnum  = $this->input->getString('fnum');
		$keyid = $this->input->getString('keyid');

		if (empty($fnum) || empty($keyid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$email = $this->m_emails->getEmail('referent_letter');
		if (empty($email))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_EMAIL'));
		}

		$referent_email = $m_files->getReferentEmail($keyid, $fnum);
		$fnum           = $m_files->getFnumInfos($fnum);

		$baseurl = Uri::base();
		//TODO: Via fnum : get referent form id
		$link_upload = $baseurl . 'index.php?option=com_fabrik&c=form&view=form&formid=68&tableid=71&keyid=' . $keyid . '&sid=' . $fnum['applicant_id'];

		$post = [
			'NAME'              => $fnum['name'],
			'EMAIL'             => $fnum['email'],
			'APPLICANT_PROGRAM' => $fnum['label'],
			'UPLOAD_URL'        => $link_upload
		];

		$tags = $this->m_emails->setTags($fnum['applicant_id'], $post, $fnum['fnum'], '', $email->message);

		$email->message = preg_replace($tags['patterns'], $tags['replacements'], $email->message);

		$translations = [
			'title'              => Text::_('COM_EMUNDUS_REFERENT'),
			'copy'               => Text::_('COM_EMUNDUS_EMAILS_CONTENT_COPY'),
			'close'              => Text::_('COM_EMUNDUS_ATTACHMENTS_CLOSE'),
			'emailContent'       => Text::_('COM_EMUNDUS_EMAILS_CONTENT'),
			'linkLabel'          => Text::_('COM_EMUNDUS_REFERENT_LINK'),
			'referentEmailLabel' => Text::_('COM_EMUNDUS_REFERENT_EMAIL'),
			'copied'             => Text::_('COM_EMUNDUS_EMAILS_CONTENT_COPIED'),
		];

		$data = [
			'message'        => $email->message,
			'link'           => $link_upload,
			'referent_email' => $referent_email,
			'translations'   => $translations
		];

		return EmundusResponse::ok($data, Text::_('EMAIL_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getexpertconfig(): EmundusResponse
	{
		$emConfig     = ComponentHelper::getParams('com_emundus');
		$default_tmpl = $emConfig->get('default_email_tmpl', 'expert');

		$config = $this->m_emails->getEmail($default_tmpl);
		if (empty($config->name))
		{
			$config->name = $this->app->get('fromname');
		}
		if (empty($config->emailfrom))
		{
			$config->emailfrom = $this->app->get('mailfrom');
		}

		if (empty($config))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_CONFIG'));
		}

		return EmundusResponse::ok($config, Text::_('CONFIG_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getexpertslist(): EmundusResponse
	{
		require_once JPATH_SITE . '/components/com_emundus/models/evaluation.php';
		$m_evaluation = new EmundusModelEvaluation();
		$experts      = $m_evaluation->getExperts();

		return EmundusResponse::ok($experts, Text::_('EXPERTS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getemailtriggers(): EmundusResponse
	{
		$campaign_id = $this->input->getInt('campaign_id', 0);
		$program_id  = $this->input->getInt('program_id', 0);
		$lim         = $this->input->getInt('lim', 0);
		$page        = $this->input->getInt('page', 1);
		$order_by    = $this->input->getString('order_by', '');
		$order_by    = $order_by == 'label' ? 'email.subject' : $order_by;
		$sort        = $this->input->getString('sort', 'ASC');
		$recherche   = $this->input->getString('recherche', '');

		$data = [
			'count' => $this->m_emails->countEmailTriggers($campaign_id, $program_id, $recherche),
			'datas' => $this->m_emails->getEmailTriggers($campaign_id, $program_id, $recherche, $lim, $page, $order_by, $sort)
		];

		foreach ($data['datas'] as $key => $trigger)
		{
			$type_values = [];

			if (!empty($trigger['email_id']) && !empty($trigger['sms_id']))
			{
				$type_values[] = [
					'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
					'value'   => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE_EMAIL_AND_SMS'),
					'classes' => 'em-p-5-12 em-font-weight-600 em-bg-main-100 em-text-neutral-900 em-font-size-14 label',
				];
			}
			else
			{
				if (!empty($trigger['email_id']))
				{
					$type_values[] = [
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
						'value'   => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE_EMAIL'),
						'classes' => 'em-p-5-12 em-font-weight-600 em-bg-main-100 em-text-neutral-900 em-font-size-14 label',
					];
				}
				else
				{
					if (!empty($trigger['sms_id']))
					{
						$type_values[] = [
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
							'value'   => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE_SMS'),
							'classes' => 'em-p-5-12 em-font-weight-600 em-bg-main-100 em-text-neutral-900 em-font-size-14 label',
						];
					}
				}
			}


			$trigger['additional_columns'] = [
				[
					'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
					'values'  => $type_values,
					'type'    => 'tags',
					'display' => 'all',
				],
				[
					'key'      => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_STEP'),
					'value'    => $trigger['status'],
					'classes'  => '',
					'display'  => 'all',
					'order_by' => 'est.value'
				]
			];

			$data['datas'][$key] = $trigger;
		}

		return EmundusResponse::ok($data, Text::_('TRIGGERS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function savetrigger(): void
	{
		$trigger = $this->input->getRaw('trigger', '');
		$trigger = json_decode($trigger, true);
		if(empty($trigger))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}
		if(empty($trigger['sms_id']) && empty($trigger['email_id']))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_TRIGGER_NO_MESSAGE_SELECTED'));
		}

		$trigger_id = $this->m_emails->saveTrigger($trigger, $this->user->id);
		if(empty($trigger_id))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_SAVE_TRIGGER'));
		}

		$response = array('code' => 200,'status' => 1, 'msg' => Text::_('TRIGGER_SAVED'), 'id' => $trigger_id);

		$this->sendJsonResponse($response);
	}
}
