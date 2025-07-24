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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Emundus Email Controller
 * @package     Emundus
 */
class EmundusControllerEmail extends BaseController
{
	/**
	 * @var object|mixed
	 * @since version 1.0.0
	 */
	private $_em_user;

	/**
	 * @var \Joomla\CMS\User\User|JUser|mixed|null
	 * @since version 1.0.0
	 */
	private $_user;

	/**
	 * @var EmundusModelEmails
	 * @since version 1.0.0
	 */
	private $m_emails;

	private HtmlSanitizerSingleton $sanitizer;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'filters.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');

		if (!class_exists('HtmlSanitizerSingleton')) {
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

		$this->app      = Factory::getApplication();
		$this->_em_user = $this->app->getSession()->get('emundusUser');
		$this->_user    = $this->app->getIdentity();
		$this->m_emails = $this->getModel('emails');
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *
	 * @return  EmundusControllerEmail  This object to support chaining.
	 *
	 * @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @since      1.0.0
	 */
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

	function clear()
	{
		EmundusHelperFiles::clear();

		$itemid           = $this->app->getMenu()->getActive()->id;
		$limitstart       = $this->input->get('limitstart', null, 'POST');
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);

		$this->setRedirect('index.php?option=com_emundus&view=' . $this->input->get('view') . '&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir . '&Itemid=' . $itemid);
	}

	function applicantEmail()
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		EmundusHelperEmails::sendApplicantEmail();
	}

	function getTemplate()
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		EmundusHelperEmails::getTemplate();
	}

	function sendmail_expert()
	{
		$response = ['status' => false, 'sent' => null, 'failed' => true, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id) || EmundusHelperAccess::asAccessAction(18, 'c', $this->_user->id))
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

			if (!empty($fnums) && !empty($mail_subject) && !empty($mail_to) && !empty($mail_body))
			{
				$mail_to = explode(',', $mail_to);
				$email    = $this->m_emails->sendExpertMail((array) $fnums, $this->_user->id, $mail_subject, $mail_from_name, $mail_from, $mail_to, $mail_body);

				$response = ['status' => true, 'sent' => $email['sent'], 'failed' => $email['failed'], 'message' => $email['message']];
			}
			else
			{
				$response = ['status' => false, 'sent' => null, 'failed' => true, 'message' => Text::_('MISSING_PARAMS')];
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Get emails filtered
	 */
	public function getallemail()
	{
		$tab = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{

			$filter    = $this->input->getString('filter') ? $this->input->getString('filter') : 'Publish';
			$sort      = $this->input->getString('sort', '');
			$recherche = $this->input->getString('recherche', '');
			$lim       = $this->input->getInt('lim', 0);
			$page      = $this->input->getInt('page', 0);
			$category  = $this->input->getString('category', '');
			$order_by  = $this->input->getString('order_by', 'se.id');
			$order_by  = $order_by == 'label' ? 'se.subject' : $order_by;

			$emails = $this->m_emails->getAllEmails($lim, $page, $filter, $sort, $recherche, $category, $order_by);

			if (count($emails) > 0)
			{
				$tab = array('status' => true, 'msg' => Text::_('EMAIL_RETRIEVED'), 'data' => $emails);
			}
			else
			{
				$tab = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_EMAIL'), 'data' => $emails);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function deleteemail()
	{
		$response    = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_DELETE_EMAIL'), 'data' => false);

			$data = $this->input->getInt('id',0);
			if(empty($data)) {
				$data = $this->input->getString('ids');
				$data = explode(',', $data);
			}

			if (!empty($data)) {
				$deleted = $this->m_emails->deleteEmail($data);

				if ($deleted) {
					$response = array('status' => 1, 'msg' => Text::_('EMAIL_DELETED'), 'data' => $deleted);
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function unpublishemail()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$data = $this->input->getInt('id');

			$emails = $this->m_emails->unpublishEmail($data);

			if ($emails)
			{
				$tab = array('status' => 1, 'msg' => Text::_('EMAIL_UNPUBLISHED'), 'data' => $emails);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_UNPUBLISH_EMAIL'), 'data' => $emails);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function publishemail()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$data = $this->input->getInt('id');

			$emails = $this->m_emails->publishEmail($data);

			if ($emails)
			{
				$tab = array('status' => 1, 'msg' => Text::_('EMAIL_PUBLISHED'), 'data' => $emails);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_PUBLISH_EMAIL'), 'data' => $emails);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function duplicateemail()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$data = $this->input->getInt('id');

			$email = $this->m_emails->duplicateEmail($data);

			if ($email)
			{
				$this->getallemail();
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_DUPLICATE_EMAIL'), 'data' => $email);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function createemail()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$htmlSanitizer      = HtmlSanitizerSingleton::getInstance();

			$data               = [];
			$data['lbl']        = $this->input->getString('lbl', '');
			$data['subject']    = $this->input->getString('subject', '');
			$data['emailfrom']  = $this->input->getString('emailfrom', '');
			$data['message']    = $this->input->getRaw('message', '');
			$data['message']    = $this->sanitizer->sanitize($data['message']);

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
			$result = $this->m_emails->createEmail($data, $cc_list, $bcc_list, $letter_list, $document_list, $tag_list);

			if ($result)
			{
				$response = array('status' => 1, 'msg' => Text::_('EMAIL_ADDED'), 'data' => $result);
			}
			else
			{
				$response = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_ADD_EMAIL'), 'data' => $result);
			}
		}

		echo json_encode((object) $response);
		exit;
	}


	public function updateemail()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
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

			$data['message']       = $this->sanitizer->sanitize($data['message']);

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
			if ($result)
			{
				$response = array('status' => true, 'msg' => Text::_('EMAIL_UPDATED'), 'data' => $result);
			}
			else
			{
				$response['msg'] = Text::_('EMAIL');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getemailbyid()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$id = $this->input->getInt('id');

			$email = $this->m_emails->getAdvancedEmailById($id);

			if (!empty($email))
			{
				$tab = array('status' => 1, 'msg' => Text::_('EMAIL_RETRIEVED'), 'data' => $email);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_EMAIL'), 'data' => $email);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getemailcategories()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$categories = $this->m_emails->getEmailCategories();
			$response   = array('status' => true, 'msg' => Text::_('EMAIL_CATEGORIES_RETRIEVED'), 'data' => $categories);
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getemailtypes()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$email = $this->m_emails->getEmailTypes();

			if (!empty($email))
			{
				$tab = array('status' => 1, 'msg' => Text::_('EMAIL_RETRIEVED'), 'data' => $email);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_EMAIL'), 'data' => $email);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getstatus()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$status = $this->m_emails->getStatus();

			if (!empty($status))
			{
				$tab = array('status' => 1, 'msg' => Text::_('STATUS_RETRIEVED'), 'data' => $status);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_STATUS'), 'data' => $status);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function gettriggersbyprogram()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$pid      = $this->input->getInt('pid');
			$triggers = $this->m_emails->getTriggersByProgramId($pid);

			if (!empty($triggers))
			{
				$response = array('status' => 1, 'msg' => Text::_('TRIGGERS_RETRIEVED'), 'data' => $triggers);
			}
			else
			{
				$response['msg']  = Text::_('ERROR_CANNOT_RETRIEVE_TRIGGERS');
				$response['data'] = $triggers;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function gettriggerbyid()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$tid = $this->input->getInt('tid', 0);

			if (!empty($tid))
			{
				$trigger = $this->m_emails->getTriggerById($tid);

				if (!empty($trigger))
				{
					$response = array('status' => true, 'msg' => Text::_('TRIGGER_RETRIEVED'), 'data' => $trigger);
				}
				else
				{
					$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_TRIGGER');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function createtrigger()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$trigger = $this->input->getRaw('trigger', '');
			$trigger = json_decode($trigger, true);

			if (!empty($trigger))
			{
				$created = $this->m_emails->createTrigger($trigger, $this->_user);
				if ($created)
				{
					$response = array('status' => 1, 'msg' => Text::_('TRIGGER_CREATED'), 'data' => $created);
				}
				else
				{
					$response['msg'] = Text::_('ERROR_CANNOT_CREATE_TRIGGER');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}
		echo json_encode((object) $response);
		exit;
	}

	public function updatetrigger()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$tid     = $this->input->getInt('tid', 0);
			$trigger = $this->input->getRaw('trigger', '');
			$trigger = json_decode($trigger, true);

			if (!empty($tid) && !empty($trigger))
			{
				$updated = $this->m_emails->updateTrigger($tid, $trigger);

				if ($updated)
				{
					$response = array('status' => true, 'msg' => Text::_('TRIGGER_UPDATED'), 'data' => $updated);
				}
				else
				{
					$response['msg'] = Text::_('ERROR_CANNOT_CREATE_TRIGGER');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}


		echo json_encode((object) $response);
		exit;
	}

	public function removetrigger()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$tid = $this->input->getInt('id');

			$status = $this->m_emails->removeTrigger($tid);

			if (!empty($status))
			{
				$tab = array('status' => 1, 'msg' => Text::_('TRIGGER_CREATED'), 'data' => $status);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_CREATE_TRIGGER'), 'data' => $status);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getemailcontent()
	{
		$template = $this->input->getString('tmp');

		$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_EMAIL'), 'data' => []);

		switch ($template)
		{
			case 8 : // referent_letter
				if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
				{
					$result['msg'] = Text::_('ACCESS_DENIED');
				}
				else
				{
					require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
					$m_files = $this->getModel('Files');

					$fnum  = $this->input->getString('fnum');
					$keyid = $this->input->getString('keyid');

					if (!empty($fnum) && !empty($keyid))
					{
						$email = $this->m_emails->getEmail('referent_letter');

						if (!empty($email))
						{
							$result['status'] = true;
							$result['msg']    = Text::_('EMAIL_RETRIEVED');

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

							$result['data'] = $data;
						}
					}
				}
				break;
		}

		echo json_encode((object) $result);
		exit;
	}

	public function getexpertconfig()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$config       = [];
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

			if (!empty($config))
			{
				$response = array('status' => true, 'msg' => Text::_('CONFIG_RETRIEVED'), 'data' => $config);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_CONFIG');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getexpertslist()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/evaluation.php';
			$m_evaluation = new EmundusModelEvaluation();
			$experts      = $m_evaluation->getExperts();


			if (is_array($experts))
			{
				$response = array('status' => true, 'msg' => Text::_('EXPERTS_RETRIEVED'), 'data' => $experts);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_EXPERTS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getemailtriggers()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$campaign_id = $this->input->getInt('campaign_id', 0);
			$program_id = $this->input->getInt('program_id', 0);
			$lim = $this->input->getInt('lim', 0);
			$page = $this->input->getInt('page', 1);
			$order_by = $this->input->getString('order_by', '');
			$order_by = $order_by == 'label' ? 'email.subject' : $order_by;
			$sort = $this->input->getString('sort', 'ASC');
			$recherche = $this->input->getString('recherche', '');

			$data = [
				'count' => $this->m_emails->countEmailTriggers($campaign_id, $program_id, $recherche),
				'datas' => $this->m_emails->getEmailTriggers($campaign_id, $program_id, $recherche, $lim, $page, $order_by, $sort)
			];

			foreach($data['datas'] as $key => $trigger) {
				$type_values = [];

				if (!empty($trigger['email_id']) && !empty($trigger['sms_id'])) {
					$type_values[] = [
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
						'value'   => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE_EMAIL_AND_SMS'),
						'classes' => 'em-p-5-12 em-font-weight-600 em-bg-main-100 em-text-neutral-900 em-font-size-14 label',
					];
				} else if (!empty($trigger['email_id'])) {
					$type_values[] = [
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
						'value'   => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE_EMAIL'),
						'classes' => 'em-p-5-12 em-font-weight-600 em-bg-main-100 em-text-neutral-900 em-font-size-14 label',
					];
				} else if (!empty($trigger['sms_id'])) {
					$type_values[] = [
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
						'value'   => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE_SMS'),
						'classes' => 'em-p-5-12 em-font-weight-600 em-bg-main-100 em-text-neutral-900 em-font-size-14 label',
					];
				}


				$trigger['additional_columns'] = [
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_TYPE'),
						'values'   => $type_values,
						'type'    => 'tags',
						'display' => 'all',
					],
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_TRIGGER_STEP'),
						'value'   => $trigger['status'],
						'classes' => '',
						'display' => 'all',
						'order_by' => 'est.value'
					]
				];

				$data['datas'][$key] = $trigger;
			}

			$response = array('status' => true, 'msg' => Text::_('TRIGGERS_RETRIEVED'), 'data' => $data);
		}

		echo json_encode((object) $response);
		exit;
	}

	public function savetrigger() {
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$trigger = $this->input->getRaw('trigger', '');
			$trigger = json_decode($trigger, true);

			if (!empty($trigger)) {
				if (empty($trigger['sms_id']) && empty($trigger['email_id'])) {
					$response['msg'] = Text::_('COM_EMUNDUS_TRIGGER_NO_MESSAGE_SELECTED');
				} else {
					$trigger_id = $this->m_emails->saveTrigger($trigger, $this->_user->id);

					if (!empty($trigger_id)) {
						$response = array('status' => 1, 'msg' => Text::_('TRIGGER_SAVED'), 'id' => $trigger_id);
					} else {
						$response['msg'] = Text::_('COM_EMUNDUS_TRIGGER_FAILED_TO_SAVE');
					}
				}
			} else {
				$response['msg'] = Text::_('COM_EMUNDUS_TRIGGER_MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}
}
