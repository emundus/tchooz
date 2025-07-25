<?php
/**
 * @package    Joomla.Site
 * @subpackage com_emundus
 *
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_emundus/models'); // call com_emundus model

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Tchooz\Enums\NumericSign\SignStatus;

/**
 * Emundus Component Application Model
 *
 * @since  1.0.0
 */
class EmundusModelApplication extends ListModel
{
	/**
	 * Application context
	 *
	 * @var \Joomla\CMS\Application\CMSApplication
	 * @since version 1.0.0
	 */
	private $_mainframe;

	/**
	 * Cache helper
	 *
	 * @var EmundusHelperCache
	 * @since version 1.0.0
	 */
	private $h_cache;

	/**
	 * @var mixed
	 * @since version 1.0.0
	 */
	private $_user;

	/**
	 * @var mixed
	 * @since version 1.0.0
	 */
	protected $_db;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		parent::__construct();

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'date.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'fabrik.php');

		$this->_mainframe = Factory::getApplication();
		$this->_db        = Factory::getContainer()->get('DatabaseDriver');
		$this->h_cache        = new EmundusHelperCache();

		$session  = $this->_mainframe->getSession();
		$language = $this->_mainframe->getLanguage();

		$this->_user   = $session->get('emundusUser');
		$this->locales = substr($language->getTag(), 0, 2);
	}

	public function getApplicantInfos($aid, $param)
	{
		$applicant_infos = [];

		if (!empty($aid) && !empty($param)) {
			$query = $this->_db->getQuery(true);

			if(!is_array($param)) {
				$param = array($param);
			}

			$query->select(implode(',', $this->_db->quoteName($param)))
				->from($this->_db->quoteName('#__users', 'u'))
				->leftJoin($this->_db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->_db->quoteName('eu.user_id') . ' = ' . $this->_db->quoteName('u.id'))
				->leftJoin($this->_db->quoteName('#__emundus_personal_detail', 'epd') . ' ON ' . $this->_db->quoteName('epd.user') . ' = ' . $this->_db->quoteName('u.id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->_db->quoteName('esp.id') . ' = ' . $this->_db->quoteName('eu.profile'))
				->leftJoin($this->_db->quoteName('#__emundus_uploads', 'euu') . ' ON ' . $this->_db->quoteName('euu.user_id') . ' = ' . $this->_db->quoteName('u.id') . ' AND ' . $this->_db->quoteName('euu.attachment_id') . ' = ' . $this->_db->quote(10))
				->where($this->_db->quoteName('u.id') . ' = ' . $this->_db->quote($aid));

			try {
				$this->_db->setQuery($query);
				$applicant_infos = $this->_db->loadAssoc();
			}
			catch (Exception $e) {
				Log::add("Failed to get applicant infos for user_id $aid " . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $applicant_infos;
	}

	public function getUserCampaigns($id, $cid = null, $published_only = true)
	{
		$user_campaigns = [];

		$query = $this->_db->getQuery(true);
		$query->select('esc.*,ecc.date_submitted,ecc.submitted,ecc.id as campaign_candidature_id,efg.result_sent,efg.date_result_sent,efg.final_grade,ecc.fnum,ess.class,ess.step,ess.value as step_value')
			->from($this->_db->quoteName('#__emundus_users','eu'))
			->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature','ecc').' ON '.$this->_db->quoteName('ecc.applicant_id').' = '.$this->_db->quoteName('eu.user_id'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns','esc').' ON '.$this->_db->quoteName('ecc.campaign_id').' = '.$this->_db->quoteName('esc.id'))
			->leftJoin($this->_db->quoteName('#__emundus_final_grade','efg').' ON '.$this->_db->quoteName('efg.campaign_id').' = '.$this->_db->quoteName('esc.id').' AND '.$this->_db->quoteName('efg.student_id').' = '.$this->_db->quoteName('eu.user_id'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_status','ess').' ON '.$this->_db->quoteName('ess.step').' = '.$this->_db->quoteName('ecc.status'))
			->where($this->_db->quoteName('eu.user_id').' = '.$this->_db->quote($id))
			->andWhere($this->_db->quoteName('ecc.published').' = '.$this->_db->quote(1));

		if ($cid === null) {
			if ($published_only) {
				$query->andWhere($this->_db->quoteName('esc.published') . ' = ' . $this->_db->quote(1));
			}

			$this->_db->setQuery($query);
			$user_campaigns = $this->_db->loadObjectList();
		} else {
			$query->andWhere($this->_db->quoteName('esc.id').' = '.$this->_db->quote($cid));

			$this->_db->setQuery($query);
			$user_campaigns =  $this->_db->loadObject();
		}

		return $user_campaigns;
	}

	public function getCampaignByFnum($fnum)
	{
		$campaigns = [];

		if(!empty($fnum))
		{
			try
			{
				$query = $this->_db->getQuery(true);

				$query->select('esc.*, ecc.date_submitted, ecc.submitted, ecc.id as campaign_candidature_id, efg.result_sent, efg.date_result_sent, efg.final_grade, ecc.fnum, ess.class, ess.step, ess.value as step_value')
					->from($this->_db->quoteName('#__emundus_users', 'eu'))
					->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.applicant_id') . ' = ' . $this->_db->quoteName('eu.user_id'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('esc.id'))
					->leftJoin($this->_db->quoteName('#__emundus_final_grade', 'efg') . ' ON ' . $this->_db->quoteName('efg.campaign_id') . ' = ' . $this->_db->quoteName('esc.id') . ' AND ' . $this->_db->quoteName('efg.student_id') . ' = ' . $this->_db->quoteName('eu.user_id'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_status', 'ess') . ' ON ' . $this->_db->quoteName('ess.step') . ' = ' . $this->_db->quoteName('ecc.status'))
					->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($fnum));
				$this->_db->setQuery($query);
				$campaigns = $this->_db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('Error getting campaign by fnum in model at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
			}

		}

		return $campaigns;
	}

	public function getUserAttachments($id)
	{
		$query = $this->_db->getQuery(true);

		$query->select('eu.id AS aid, esa.*, eu.filename, eu.description, eu.timedate, esc.label as campaign_label, esc.year, esc.training')
			->from($this->_db->quoteName('#__emundus_uploads', 'eu'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_attachments', 'esa') . ' ON ' . $this->_db->quoteName('eu.attachment_id') . ' = ' . $this->_db->quoteName('esa.id'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('eu.campaign_id'))
			->where($this->_db->quoteName('eu.user_id') . ' = ' . $this->_db->quote($id))
			->order($this->_db->quoteName('esa.category') . ', ' . $this->_db->quoteName('esa.ordering') . ', ' . $this->_db->quoteName('esa.value') . ' DESC');
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	function getUserAttachmentsByFnum($fnum, $search = '', $profile = null, $applicant = false, $user_id = null)
	{
		$attachments = [];
		$app = Factory::getApplication();

		if (!empty($fnum)) {
			if(empty($user_id))
			{
				$user_id = $this->_user->id;
			}

			if (!class_exists('EmundusHelperAccess')) {
				require_once JPATH_ROOT . '/components/com_emundus/helpers/access.php';
			}
			if(!class_exists('EmundusModelSettings')) {
				require_once JPATH_ROOT . '/components/com_emundus/models/settings.php';
			}
			if(!class_exists('EmundusModelSign')) {
				require_once JPATH_ROOT . '/components/com_emundus/models/sign.php';
			}
			$m_sign = new EmundusModelSign();
			$m_settings = new EmundusModelSettings();
			$sign_enabled = $m_settings->getAddonStatus('numeric_sign')['enabled'];
			if($sign_enabled && !$app->isClient('cli'))
			{
				$emundusUser      = $app->getSession()->get('emundusUser');
				$add_sign_url = $app->getMenu()->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=sign&layout=add', $emundusUser->menutype], 'true');
				if(empty($add_sign_url) && EmundusHelperAccess::asCoordinatorAccessLevel($user_id))
				{
					$add_sign_url = $app->getMenu()->getItems(['link', 'menutype'], ['index.php?option=com_emundus&view=sign&layout=add', 'onboardingmenu'], 'true');
				}
			}

			$eMConfig           = ComponentHelper::getParams('com_emundus');
			$expert_document_id = $eMConfig->get('expert_document_id', '36');

			$query = $this->_db->getQuery(true);

			$columns = [
				$this->_db->quoteName('eu.id','aid'),
				$this->_db->quoteName('eu.user_id'),
				$this->_db->quoteName('ecc.applicant_id'),
				$this->_db->quoteName('ecc.id','ccid'),
				'esa.*',
				$this->_db->quoteName('eu.attachment_id'),
				$this->_db->quoteName('eu.filename'),
				$this->_db->quoteName('eu.description','upload_description'),
				$this->_db->quoteName('eu.timedate'),
				$this->_db->quoteName('eu.can_be_deleted'),
				$this->_db->quoteName('eu.can_be_viewed'),
				$this->_db->quoteName('eu.is_validated'),
				$this->_db->quoteName('eu.modified'),
				$this->_db->quoteName('eu.modified_by'),
				$this->_db->quoteName('eu.signed_file'),
				$this->_db->quoteName('esc.label','campaign_label'),
				$this->_db->quoteName('esc.year'),
				$this->_db->quoteName('esc.training'),
				'CONCAT(u.firstname, " ", u.lastname) AS user_name',
				'CONCAT(u2.firstname, " ", u2.lastname) AS modified_user_name'
			];

			$query->from($this->_db->quoteName('#__emundus_uploads', 'eu'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_attachments', 'esa') . ' ON ' . $this->_db->quoteName('eu.attachment_id') . ' = ' . $this->_db->quoteName('esa.id'))
				->leftJoin($this->_db->quoteName('#__emundus_users', 'u') . ' ON ' . $this->_db->quoteName('u.user_id') . ' = ' . $this->_db->quoteName('eu.user_id'))
				->leftJoin($this->_db->quoteName('#__emundus_users', 'u2') . ' ON ' . $this->_db->quoteName('u2.user_id') . ' = ' . $this->_db->quoteName('eu.modified_by'));

			if (!empty($profile)) {
				$query->leftJoin($this->_db->quoteName('#__emundus_setup_attachment_profiles', 'esap') . ' ON ' . $this->_db->quoteName('esa.id') . ' = ' . $this->_db->quoteName('esap.attachment_id') . ' AND ' . $this->_db->quoteName('esap.profile_id') . ' = ' . $this->_db->quote($profile));
			} else {
				$query->leftJoin($this->_db->quoteName('#__emundus_setup_attachment_profiles', 'esap') . ' ON ' . $this->_db->quoteName('esa.id') . ' = ' . $this->_db->quoteName('esap.attachment_id'));
			}

			$query->select($columns)
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('eu.campaign_id'))
				->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.fnum') . ' = ' . $this->_db->quoteName('eu.fnum'))
				->where($this->_db->quoteName('eu.fnum') . ' LIKE ' . $this->_db->quote($fnum))
				->andWhere('esa.lbl NOT LIKE ' . $this->_db->quote('_application_form'));

			if (EmundusHelperAccess::isExpert($user_id)) {
				$query->andWhere($this->_db->quoteName('esa.id') . ' != ' . $expert_document_id);
			}

			if (!empty($search)) {
				$query->andWhere($this->_db->quoteName('esa.value') . ' LIKE ' . $this->_db->quote('%' . $search . '%')
					. ' OR ' . $this->_db->quoteName('esa.description') . ' LIKE ' . $this->_db->quote('%' . $search . '%')
					. ' OR ' . $this->_db->quoteName('eu.timedate') . ' LIKE ' . $this->_db->quote('%' . $search . '%'));
			}

			$query->order($this->_db->quoteName('esap.mandatory') . ' DESC, ' . $this->_db->quoteName('esap.ordering') . ', ' . $this->_db->quoteName('esa.value') . ' ASC');

			if($applicant) {
				$query->andWhere($this->_db->quoteName('eu.can_be_viewed') . ' = 1');
			}

			$query->group('eu.id');

			try {
				$this->_db->setQuery($query);
				$attachments = $this->_db->loadObjectList();
			}
			catch (Exception $e) {
				Log::add('Error getting user attachments in model at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
			}

			if (!empty($attachments)) {
				$allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs($user_id);
				if ($allowed_attachments !== true) {
					foreach ($attachments as $key => $attachment) {
						if (!in_array($attachment->id, $allowed_attachments)) {
							unset($attachments[$key]);
						}
					}
				}

				foreach ($attachments as $attachment) {
					if (!file_exists(EMUNDUS_PATH_ABS . $attachment->applicant_id . '/' . $attachment->filename)) {
						$attachment->existsOnServer = false;
					}
					else {
						$attachment->existsOnServer = true;
					}

					$query->clear()
						->select('profile_id')
						->from($this->_db->quoteName('#__emundus_setup_attachment_profiles'))
						->where($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($attachment->attachment_id));
					$this->_db->setQuery($query);
					$attachment->profiles = $this->_db->loadColumn();
                    $attachment->upload_description = empty($attachment->upload_description)? '' : $attachment->upload_description;
					$attachment->signers = 0;
					$attachment->original_upload_id = 0;
					$attachment->signed_upload_id = 0;

					if($sign_enabled)
					{
						$query->clear()
							->select('esrs.id, esrs.status, esr.signed_upload_id, esr.upload_id')
							->from($this->_db->quoteName('#__emundus_sign_requests','esr'))
							->leftJoin($this->_db->quoteName('#__emundus_sign_requests_signers','esrs') . ' ON ' . $this->_db->quoteName('esrs.request_id') . ' = ' . $this->_db->quoteName('esr.id'))
							->where($this->_db->quoteName('esr.fnum') . ' = ' . $this->_db->quote($fnum))
							// Remove cancellation requests
							->where($this->_db->quoteName('esr.status') . ' != ' . $this->_db->quote(SignStatus::CANCELLED->value));

						if($attachment->signed_file === 1)
						{
							$query->where($this->_db->quoteName('esr.signed_upload_id') . ' = ' . $this->_db->quote($attachment->aid));
						}
						else {
							$query->where($this->_db->quoteName('esr.upload_id') . ' = ' . $this->_db->quote($attachment->aid));
						}

						$this->_db->setQuery($query);
						$signers = $this->_db->loadObjectList();

						if(!empty($signers))
						{
							$signers_signed                 = 0;
							$attachment->original_upload_id = $signers[0]->upload_id;
							$attachment->signed_upload_id   = $signers[0]->signed_upload_id;
						}

						if(
							(empty($attachment->signed_upload_id) || $attachment->signed_file === 1)
							&& !empty($signers))
						{
							foreach ($signers as $signer)
							{
								if ($signer->status === SignStatus::SIGNED->value)
								{
									$signers_signed++;
								}
							}

							$icon       = $signers_signed === count($signers) ? 'check_circle' : 'do_not_disturb_on';
							$bg_color   = $signers_signed === count($signers) ? 'tw-bg-main-100' : 'tw-bg-blue-100';
							$text_color = $signers_signed === count($signers) ? 'tw-text-main-600' : 'tw-text-blue-600';

							$attachment->signers = '<div class="' . $bg_color . ' ' . $text_color . ' tw-rounded-full tw-w-fit tw-text-profile-full tw-flex tw-items-center tw-justify-center tw-font-semibold tw-px-2 tw-py-1 tw-gap-2 tw-m-auto"><span class="' . $text_color . ' material-symbols-outlined !tw-text-base">' . $icon . '</span><span class="' . $text_color . '">' . $signers_signed . '/' . count($signers) . '</span></div>';
						}
						else {
							// Display only for pdf files
							if(!empty($add_sign_url) && pathinfo($attachment->filename, PATHINFO_EXTENSION) === 'pdf' && EmundusHelperAccess::asAccessAction($m_sign->getSignActionId(), 'c', $user_id, $fnum)) {
								$url = $add_sign_url->route . '?ccid=' . $attachment->ccid . '&attachment=' . $attachment->id . '&upload=' . $attachment->aid;
								$attachment->signers = '<a href="'.$url.'" target="_blank" class="tw-w-fit tw-btn-primary tw-m-auto"><span class="material-symbols-outlined">stylus_note</span></a>';
							}
							else
							{
								$attachment->signers = '-';
							}
						}
					}
				}

				if ($attachments !== array_values($attachments)) {
					$attachments = array_values($attachments);
				}
			}
		}

		return $attachments;
	}

	public function getUsersComments($id)
	{
		$query = $this->_db->getQuery(true);

		$query->select('ec.id, ec.comment_body as comment, ec.reason, ec.date, u.name')
			->from($this->_db->quoteName('#__emundus_comments', 'ec'))
			->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('ec.user_id'))
			->where($this->_db->quoteName('ec.applicant_id') . ' = ' . $this->_db->quote($id))
			->order($this->_db->quoteName('ec.date') . ' DESC');
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}

	public function getComment($id)
	{
		$comment = [];
		$query = $this->_db->getQuery(true);

		if(!empty($id))
		{
			try
			{
				$query->select('*')
					->from($this->_db->quoteName('#__emundus_comments'))
					->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($id));
				$this->_db->setQuery($query);
				$comment = $this->_db->loadAssoc();
			}
			catch (Exception $e)
			{
				Log::add('Error getting comment in model at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $comment;
	}

	public function getTag($id)
	{
		$tag = [];

		if(!empty($id))
		{
			try
			{
				$query = $this->_db->getQuery(true);

				$query->select('*')
					->from($this->_db->quoteName('#__emundus_tag_assoc'))
					->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($id));
				$this->_db->setQuery($query);
				$tag = $this->_db->loadAssoc();
			}
			catch (Exception $e)
			{
				Log::add('Error getting tag in model at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $tag;
	}

	public function getFileComments($fnum)
	{
		$file_comments = [];

		if(!empty($fnum))
		{
			try
			{
				$query = $this->_db->getQuery(true);

				$query->select('ec.id, ec.comment_body as comment, ec.reason, ec.fnum, ec.user_id, ec.date, u.name')
					->from($this->_db->quoteName('#__emundus_comments', 'ec'))
					->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('ec.user_id'))
					->where($this->_db->quoteName('ec.fnum') . ' LIKE ' . $this->_db->quote($fnum))
					->order($this->_db->quoteName('ec.date') . ' ASC');
				$this->_db->setQuery($query);

				$file_comments = $this->_db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('Error getting file comments in model at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $file_comments;
	}

	public function getFileOwnComments($fnum, $user_id)
	{
		$file_own_comments = [];

		if(!empty($fnum) && !empty($user_id))
		{
			try
			{
				$query = $this->_db->getQuery(true);

				$query->select('ec.id, ec.comment_body as comment, ec.reason, ec.fnum, ec.user_id, ec.date, u.name')
					->from($this->_db->quoteName('#__emundus_comments', 'ec'))
					->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('ec.user_id'))
					->where($this->_db->quoteName('ec.fnum') . ' LIKE ' . $this->_db->quote($fnum))
					->andWhere($this->_db->quoteName('ec.user_id') . ' = ' . $this->_db->quote($user_id))
					->order($this->_db->quoteName('ec.date') . ' ASC');
				$this->_db->setQuery($query);

				$file_own_comments = $this->_db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('Error getting file own comments in model at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.error');
			}
		}

		return $file_own_comments;
	}

	public function editComment($id, $title, $text)
	{

		try {
			// Get the old comment content for the logging system
			$query = 'SELECT reason, comment_body FROM #__emundus_comments WHERE id =' . $this->_db->quote($id);
			$this->_db->setQuery($query);
			$old_comment = $this->_db->loadObject();

			// Update the comment content
			$query = 'UPDATE #__emundus_comments SET reason = ' . $this->_db->quote($title) . ', comment_body = ' . $this->_db->quote($text) . '  WHERE id = ' . $this->_db->quote($id);
			$this->_db->setQuery($query);
			$this->_db->execute();

			// Logging requires the fnum, we have to get this from the comment ID being edited.
			// Only get the fnum if logging is on and comments are in the list of actions to be logged.
			$eMConfig    = JComponentHelper::getParams('com_emundus');
			$log_actions = $eMConfig->get('log_action', null);
			if ($eMConfig->get('logs', 0) && (empty($log_actions) || in_array(10, explode(',', $log_actions)))) {

				$query = $this->_db->getQuery(true);
				$query->select($this->_db->quoteName('fnum'))
					->from($this->_db->quoteName('#__emundus_comments'))
					->where($this->_db->quoteName('id') . '=' . $id);

				$this->_db->setQuery($query);
				$fnum = $this->_db->loadResult();

				// Log the comment in the eMundus logging system.
				$logsParams = array('updated' => []);

				if (empty(trim($old_comment->reason))) {
					$old_comment->reason = Text::_('COM_EMUNDUS_COMMENT_NO_TITLE');
				}

				if (empty(trim($title))) {
					$title = Text::_('COM_EMUNDUS_COMMENT_NO_TITLE');
				}

				if ($old_comment->reason !== $title) {
					array_push($logsParams['updated'], ['description' => '<b>' . '[' . $old_comment->reason . ']' . '</b>', 'element' => '<span>' . Text::_('COM_EMUNDUS_EDIT_COMMENT_TITLE') . '</span>',
					                                    'old'         => $old_comment->reason,
					                                    'new'         => $title]);
				}

				/////////////
				if ($old_comment->comment_body !== $text) {
					array_push($logsParams['updated'], ['description' => '<b>' . '[' . $old_comment->reason . ']' . '</b>', 'element' => '<span>' . Text::_('COM_EMUNDUS_EDIT_COMMENT_BODY') . '</span>',
					                                    'old'         => $old_comment->comment_body,
					                                    'new'         => $text]);
				}

				if (!empty($logsParams['updated'])) {
                    if (!class_exists('EmundusModelFiles')) {
                        require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
                    }
                    $m_files = new EmundusModelFiles;
                    $fnumInfos = $m_files->getFnumInfos($fnum);

					$logsParams['updated'] = array_values($logsParams['updated']);
					EmundusModelLogs::log(JFactory::getUser()->id, (int)$fnumInfos['applicant_id'], $fnum, 10, 'u', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_UPDATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
				}
			}

			return true;

		}
		catch (Exception $e) {
			Log::add('Query: ' . $query . ' Error:' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

	}

	public function deleteComment($id, $fnum = null)
	{

		$query = $this->_db->getQuery(true);

		if (empty($fnum)) {
			$query->select($this->_db->quoteName('fnum'))
				->from($this->_db->quoteName('#__emundus_comments'))
				->where($this->_db->quoteName('id') . ' = ' . $id);
			$this->_db->setQuery($query);

			try {
				$this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Error getting fnum for comment id ' . $id . ' in m/application.', Log::ERROR, 'com_emundus');
			}
		}

		// Get the comment for logs
		$query->select($this->_db->quoteName('reason') . ',' . $this->_db->quoteName('comment_body'))
			->from($this->_db->quoteName('#__emundus_comments'))
			->where($this->_db->quoteName('id') . ' = ' . $id);
		$this->_db->setQuery($query);
		$deleted_comment = $this->_db->loadObject();

		// Delete comment
		$query->clear()->delete($this->_db->quoteName('#__emundus_comments'))
			->where($this->_db->quoteName('id') . ' = ' . $id);
		$this->_db->setQuery($query);

		// Log the comments in the eMundus logging system.
		$logsStd = new stdClass();

		try {
			$res = $this->_db->execute();
		}
		catch (Exception $e) {
			Log::add('Error deleting comment id ' . $id . ' in m/application. ERROR -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		if ($res && !empty($fnum)) {
			// Log the comment in the eMundus logging system
			// Log only the body if the comment had no title
			if (empty($deleted_comment->reason)) {
				$logsStd->element = '[' . Text::_('COM_EMUNDUS_COMMENT_NO_TITLE') . ']';
				$logsStd->details = $deleted_comment->comment_body;
			}
			else {
				$logsStd->element = "[" . $deleted_comment->reason . "]";
				$logsStd->details = $deleted_comment->comment_body;
			}

            if (!class_exists('EmundusModelFiles')) {
                require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
            }
            $m_files = new EmundusModelFiles;
            $fnumInfos = $m_files->getFnumInfos($fnum);

			$logsParams = array('deleted' => [$logsStd]);
			EmundusModelLogs::log(JFactory::getUser()->id, (int)$fnumInfos['applicant_id'], $fnum, 10, 'd', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_DELETE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
		}

		return $res;

	}

	public function deleteTag($id_tag, $fnum, $user_id = null, $user_to_log = null)
	{
		$query = $this->_db->getQuery(true);

		// Get the tag for logs
		$query->select($this->_db->quoteName('label'))
			->from($this->_db->quoteName('#__emundus_setup_action_tag'))
			->where($this->_db->quoteName('id') . ' = ' . $id_tag);
		$this->_db->setQuery($query);
		$deleted_tag = $this->_db->loadResult();

		$query->clear()
			->delete($this->_db->quoteName('#__emundus_tag_assoc'))
			->where($this->_db->quoteName('id_tag') . ' = ' . $id_tag)
			->andWhere($this->_db->quoteName('fnum') . ' like ' . $this->_db->Quote($fnum));

		if (!empty($user_id)) {
			$query->andWhere($this->_db->quoteName('user_id') . ' = ' . $user_id);
		}

		$this->_db->setQuery($query);
		$res = $this->_db->execute();

		if ($res) {
			// Log the tag in the eMundus logging system.
			$logsStd          = new stdClass();
			$logsStd->details = $deleted_tag;
			$logsParams       = array('deleted' => [$logsStd]);

			$user_id          = empty($user_to_log) ? Factory::getApplication()->getIdentity()->id : $user_to_log;
			$user_id          = empty($user_id) ? ComponentHelper::getParams('com_emundus')->get('automated_task_user', 62) : $user_id;

            if (!class_exists('EmundusModelFiles')) {
                require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
            }
            $m_files = new EmundusModelFiles;
            $fnumInfos = $m_files->getFnumInfos($fnum);
			EmundusModelLogs::log($user_id, (int)$fnumInfos['applicant_id'], $fnum, 14, 'd', 'COM_EMUNDUS_ACCESS_TAGS_DELETE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
		}

		return $res;
	}

	/**
	 * Add a new comment
	 *
	 * @param $row
	 * @return int new comment id
	 * @throws Exception
	 */
	public function addComment($row)
	{
		$comment_id = 0;

		$query = $this->_db->getQuery(true);

		if(!empty($row['fnum'])) {
			if (!class_exists('EmundusHelperFiles')) {
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
			}

			$ccid = EmundusHelperFiles::getIdFromFnum($row['fnum']);
		}

		if(!empty($row['fnum']) && !empty($ccid))
		{
			$query->insert($this->_db->quoteName('#__emundus_comments'))
				->columns('applicant_id, user_id, reason, date, comment_body, fnum, ccid')
				->values($row['applicant_id'] . ', ' . $row['user_id'] . ', ' . $this->_db->quote($row['reason']) . ', ' . $this->_db->quote(EmundusHelperDate::getNow()) . ', ' . $this->_db->quote($row['comment_body']) . ', ' . $this->_db->quote($row['fnum']) . ', ' . $ccid);

			try
			{
				$this->_db->setQuery($query);
				$inserted = $this->_db->execute();
				if ($inserted)
				{
					$comment_id = $this->_db->insertid();

					if (!empty($comment_id))
					{
						$logsStd          = new stdClass();
						$logsStd->element = empty($row['reason']) ? '[' . Text::_('COM_EMUNDUS_COMMENT_NO_TITLE') . ']' : '[' . $row['reason'] . ']';
						$logsStd->details = $row['comment_body'];
						$logsParams       = array('created' => [$logsStd]);
                        if (!class_exists('EmundusModelFiles')) {
                            require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
                        }
                        $m_files = new EmundusModelFiles;
                        $fnumInfos = $m_files->getFnumInfos($row['fnum']);
						EmundusModelLogs::log($this->_user->id, (int)$fnumInfos['applicant_id'], $row['fnum'], 10, 'c', 'COM_EMUNDUS_ACCESS_COMMENT_FILE_CREATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Failed to insert comment ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $comment_id;
	}

	public function deleteAttachment($id)
	{
		$deleted = false;

		if (!empty($id)) {
			$query = $this->_db->getQuery(true);
			try {
				$query->clear()
					->select('*')
					->from('#__emundus_uploads')
					->where('id = ' . $id);

				$this->_db->setQuery($query);
				$file = $this->_db->loadAssoc();
			} catch (Exception $e) {
				Log::add('Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
			}

			if (!empty($file)) {
				$query->clear()
					->select('applicant_id')
					->from($this->_db->quoteName('#__emundus_campaign_candidature'))
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($file['fnum']));
				$this->_db->setQuery($query);
				$applicant_id = $this->_db->loadResult();

				$f = EMUNDUS_PATH_ABS . $applicant_id . DS . $file['filename'];
				$deleted_file = unlink($f);

				if ($deleted_file) {
					try {
						$query->clear()
							->delete('#__emundus_uploads')
							->where('id = ' . $id);
						$this->_db->setQuery($query);
						$deleted = $this->_db->execute();

						if ($deleted) {
							$logsStd = new stdClass();
							$attachmentTpe = $this->getAttachmentByID($file['attachment_id']);

							$logsStd->element = '[' . $attachmentTpe['value'] . ']';
							$logsStd->details = $file['filename'];
							$logsParams = array('deleted' => [$logsStd]);

							EmundusModelLogs::log(JFactory::getUser()->id, (int)$applicant_id, $file['fnum'], 4, 'd', 'COM_EMUNDUS_ACCESS_ATTACHMENT_DELETE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
						}
					} catch (Exception $e) {
						Log::add('Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
					}
				}
			}
		}

		return $deleted;
	}

	public function uploadAttachment($data)
	{
		$upload_id = false;

		if (!empty($data) && !empty($data['key']) && !empty($data['value'])) {
			try {
				$values = implode(',', $this->_db->quote($data['value']));

				$query = $this->_db->getQuery(true);
				$query->insert($this->_db->quoteName('#__emundus_uploads'))
					->columns($data['key'])
					->values($values);

				$this->_db->setQuery($query);
				$this->_db->execute();
				$upload_id = $this->_db->insertid();
			}
			catch (RuntimeException $e) {
				JFactory::getApplication()->enqueueMessage($e->getMessage());
				Log::add('Error in model/application at query: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $upload_id;
	}

	public function getAttachmentByID($id)
	{
		$query = "SELECT * FROM #__emundus_setup_attachments WHERE id=" . $id;
		$this->_db->setQuery($query);

		return $this->_db->loadAssoc();
	}

	public function getAttachmentByLbl($label)
	{
		$query = "SELECT * FROM #__emundus_setup_attachments WHERE lbl LIKE" . $this->_db->Quote($label);
		$this->_db->setQuery($query);

		return $this->_db->loadAssoc();
	}

	public function getUploadByID(int $id): array
	{
		$upload = [];

		try {
			$query = $this->_db->createQuery();
			$query->select('*')
				->from($this->_db->quoteName('#__emundus_uploads'))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($id));
			$this->_db->setQuery($query);
			$upload = $this->_db->loadAssoc();
		} catch (\Exception $e) {
			Log::add('Error getting upload by ID in model/application at query: ' . $query->__toString() . ' - ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $upload;
	}

	/**
	 * Check if forms of a profile are filled, even partially
	 * @param $profile_id
	 * @param $fnum
	 *
	 * @return false
	 */
	public function isFormFilled($profile_id, $fnum)
	{
		$is_filled = false;

		if (!empty($profile_id) && !empty($fnum)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('jm.link')
				->from($db->quoteName('#__menu', 'jm'))
				->leftJoin($db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON esp.menutype = jm.menutype')
				->where('esp.id = ' . $profile_id)
				->andWhere('jm.type = ' . $db->quote('component'))
				->andWhere('jm.published = 1')
				->andWhere('jm.level > 1')
				->andWhere('jm.link LIKE ' . $db->quote('%index.php?option=com_fabrik&view=form&formid%'));

			try {
				$db->setQuery($query);
				$links = $db->loadColumn();

				if (!empty($links)) {
					$form_ids = [];
					foreach($links as $link) {
						$form_ids[] = preg_match('/formid=([0-9]+)/', $link, $matches) ? $matches[1] : null;
					}

					$query->clear()
						->select('db_table_name')
						->from($db->quoteName('#__fabrik_lists'))
						->where('form_id IN (' . implode(',', $form_ids) . ')');

					$db->setQuery($query);
					$tables = $db->loadColumn();

					while(!$is_filled && !empty($tables)) {
						$table = array_pop($tables);
						$query->clear()
							->select('COUNT(*)')
							->from($db->quoteName($table))
							->where('fnum = ' . $db->quote($fnum));

						$db->setQuery($query);
						$is_filled = $db->loadResult() > 0;
					}
				}
			}
			catch (Exception $e) {
				Log::add('Error trying to check if at least one of the form of the profile is filled: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $is_filled;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array|bool|false|float
	 *
	 * @since version
	 */
	public function getFormsProgress($fnum = "0", $euser = null, $use_session = 0)
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		$m_profile = new EmundusModelProfile;

		if (empty($fnum) || (!is_array($fnum) && !is_numeric($fnum))) {
			return false;
		}

		$current_user = $euser;
		if (empty($current_user)) {
			$session = Factory::getApplication()->getSession();

			$current_user = $session->get('emundusUser');
		}

		$query = $this->_db->getQuery(true);

		if (!is_array($fnum)) {
			$profile_by_status = $m_profile->getProfileByStatus($fnum,$use_session);

			if (empty($profile_by_status['profile'])) {
				$query->select('esc.profile_id AS profile_id, ecc.campaign_id AS campaign_id')
					->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
					->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('esc.id'))
					->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($fnum));
				$this->_db->setQuery($query);

				$profile_by_status = $this->_db->loadAssoc();
			}

			$profile    = !empty($profile_by_status["profile_id"]) ? $profile_by_status["profile_id"] : $profile_by_status["profile"];
			$profile_id = (!empty($current_user->fnums[$fnum]) && $current_user->profile != $profile && $current_user->applicant === 1) ? $current_user->profile : $profile;

			$forms = EmundusHelperMenu::getUserApplicationMenu($profile_id);
			$nb    = 0;

			if (empty($forms)) {
				return 100;
			}

			foreach ($forms as $form) {
				$query->clear()->select('count(*)')->from($this->_db->quoteName($form->db_table_name))->where($this->_db->quoteName('fnum') . ' like ' . $this->_db->quote($fnum));
				$this->_db->setQuery($query);
				$cpt = $this->_db->loadResult();

				if ($cpt > 0) {
					$nb++;
				}
			}

			$this->updateFormProgressByFnum(@floor(100 * $nb / count($forms)), $fnum);

			return @floor(100 * $nb / count($forms));

		}
		else {
			$result = array();

			foreach ($fnum as $f) {
				$profile_by_status = $m_profile->getProfileByStatus($f);

				if (empty($profile_by_status["profile"])) {
					$query->clear()
						->select('esc.profile_id AS profile_id, ecc.campaign_id AS campaign_id')
						->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
						->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('esc.id'))
						->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($f));
					$this->_db->setQuery($query);
					$profile_by_status = $this->_db->loadAssoc();
				}

				$profile_id = !empty($profile_by_status["profile_id"]) ? $profile_by_status["profile_id"] : $profile_by_status["profile"];

				$forms = EmundusHelperMenu::buildMenuQuery($profile_id);
				$nb    = 0;

				if (empty($forms)) {
					$result[$f] = 100;
				}
				else {
					foreach ($forms as $form) {
						$query->clear()->select('count(*)')->from($this->_db->quoteName($form->db_table_name))->where($this->_db->quoteName('fnum') . ' like ' . $this->_db->quote($f));
						$this->_db->setQuery($query);
						$cpt = $this->_db->loadResult();

						if ($cpt == 1) {
							$nb++;
						}
					}

					$this->updateFormProgressByFnum(@floor(100 * $nb / count($forms)), $f);
					$result[$f] = @floor(100 * $nb / count($forms));
				}
			}

			return $result;
		}
	}

	public function getFormsProgressWithProfile($fnum, $profile_id)
	{
		$forms = @EmundusHelperMenu::getUserApplicationMenu($profile_id);
		$nb    = 0;

		if (empty($forms)) {
			return 100;
		}

		foreach ($forms as $form) {
			$query = 'SELECT count(*) FROM ' . $form->db_table_name . ' WHERE fnum like ' . $this->_db->Quote($fnum);
			$this->_db->setQuery($query);
			$cpt = $this->_db->loadResult();
			if ($cpt == 1) {
				$nb++;
			}
		}

		$this->updateFormProgressByFnum(@floor(100 * $nb / count($forms)), $fnum);

		return @floor(100 * $nb / count($forms));
	}

	public function updateFormProgressByFnum($result, $fnum)
	{
		$query = $this->_db->getQuery(true);

		$query->update($this->_db->quoteName('#__emundus_campaign_candidature'))
			->set($this->_db->quoteName('form_progress') . ' = ' . $this->_db->quote($result))
			->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));
		$this->_db->setQuery($query);

		return $this->_db->execute();
	}


	public function getFilesProgress($fnum = null)
	{
		$result = ['attachments' => [], 'forms' => []];

		if (empty($fnum) || (!is_array($fnum) && !is_numeric($fnum))) {
			return $result;
		}

		$m_profile = new EmundusModelProfile();

		$query = $this->_db->getQuery(true);

		if (!is_array($fnum)) {
			$fnum = [$fnum];
		}

		foreach ($fnum as $f) {
			$current_profile = $m_profile->getProfileByFnum($f);
			$this->getFormsProgressWithProfile($f, $current_profile);

			$query->clear()
				->select('attachment_progress, form_progress')
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($f));

			try {
				$this->_db->setQuery($query);
				$progress = $this->_db->loadObject();

				$result['attachments'][$f] = $progress->attachment_progress;
				$result['forms'][$f]       = $progress->form_progress;
			} catch (Exception $e) {
				Log::add('Error trying to get progress for fnum ' . $f . ' in model/application.', Log::ERROR, 'com_emundus.error');
			}
		}

		return $result;
	}

	/**
	 * @param   null  $fnum
	 *
	 * @return array|bool|false|float
	 *
	 * @since version
	 */
	public function getAttachmentsProgress($fnums = null, $euser = null, $use_session = 0)
	{
		$progress     = 0.0;
		$return_array = true;

		if (empty($fnums) || (!is_array($fnums) && !is_numeric($fnums)))
		{
			$progress = 0.0;
		}
		else
		{
			$current_user = $euser;
			if (empty($current_user))
			{
				$session = Factory::getApplication()->getSession();

				$current_user = $session->get('emundusUser');
			}

			require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/checklist.php');
			$m_profile   = new EmundusModelProfile;
			$m_checklist = new EmundusModelChecklist;

			if (!is_array($fnums))
			{
				$fnums        = [$fnums];
				$return_array = false;
			}

			$query  = $this->_db->getQuery(true);
			$result = array();
			foreach ($fnums as $f)
			{
				$result[$f] = 0.0;

				$profile_by_status = $m_profile->getProfileByStatus($f, $use_session);

				if (empty($profile_by_status["profile"]))
				{
					$query->clear()
						->select('esc.profile_id AS profile_id, ecc.campaign_id AS campaign_id')
						->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
						->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.campaign_id') . ' = ' . $this->_db->quoteName('esc.id'))
						->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($f));
					$this->_db->setQuery($query);
					$profile_by_status = $this->_db->loadAssoc();
				}

				if (!empty($profile_by_status))
				{
					$profile_id  = !empty($profile_by_status["profile_id"]) ? $profile_by_status["profile_id"] : $profile_by_status["profile"];
					$campaign_id = $profile_by_status["campaign_id"];

					$attachments = $m_checklist->getAttachmentsForProfile($profile_id, $campaign_id);

					// verify
					// check how many attachments are completed
					$completion = 0;

					if (!empty($attachments))
					{
						$nb_mandatory_attachments = 0;
						foreach ($attachments as $attachment)
						{
							if ($attachment->mandatory == 1)
							{
								$nb_mandatory_attachments++;
							}
						}

						if ($nb_mandatory_attachments > 0)
						{
							foreach ($attachments as $attachment)
							{
								if ($attachment->mandatory == 1)
								{
									$query = $this->_db->getQuery(true);

									$query->select('count(*)')
										->from($this->_db->quoteName('#__emundus_uploads'))
										->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($f))
										->andWhere($this->_db->quoteName('attachment_id') . ' = ' . $attachment->id);
									$this->_db->setQuery($query);
									$nb = $this->_db->loadResult();

									if ($nb > 0)
									{
										$completion += 100 / $nb_mandatory_attachments;
									}
								}
							}
						}
						else
						{
							$completion = 100;
						}
					}
					else
					{
						$completion = 100;
					}


					$this->updateAttachmentProgressByFnum(floor($completion), $f);
					$result[$f] = floor($completion);
				}
			}
			$progress = $result;
		}

		if (!$return_array && count($progress) == 1)
		{
			$progress = $progress[$fnums[0]];
		}

		return $progress;
	}

	/**
	 * @param $fnum
	 *
	 * @return array|bool|false|float
	 *
	 * @since version 1.28.0
	 */
	public function getAttachmentsProgressWithProfile($fnum, $profile_id)
	{
		if (empty($fnum)) {
			return false;
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		$m_profile         = new EmundusModelProfile;
		$profile_by_status = $m_profile->getProfileByStatus($fnum);

		$query = 'SELECT COUNT(profiles.id)
            FROM #__emundus_setup_attachment_profiles AS profiles
            WHERE profiles.campaign_id = ' . intval($profile_by_status["campaign_id"]) . ' AND profiles.displayed = 1';

		$this->_db->setQuery($query);
		$attachments = $this->_db->loadResult();

		if (intval($attachments) == 0) {
			$query = 'SELECT IF(COUNT(profiles.attachment_id)=0, 100, 100*COUNT(uploads.attachment_id>0)/COUNT(profiles.attachment_id))
            FROM #__emundus_setup_attachment_profiles AS profiles
            LEFT JOIN #__emundus_uploads AS uploads ON uploads.attachment_id = profiles.attachment_id AND uploads.fnum like ' . $this->_db->Quote($fnum) . '
            WHERE profiles.profile_id = ' . $profile_id . ' AND profiles.displayed = 1 AND profiles.mandatory = 1';
		}
		else {
			$query = 'SELECT IF(COUNT(profiles.attachment_id)=0, 100, 100*COUNT(uploads.attachment_id>0)/COUNT(profiles.attachment_id))
            FROM #__emundus_setup_attachment_profiles AS profiles
            LEFT JOIN #__emundus_uploads AS uploads ON uploads.attachment_id = profiles.attachment_id AND uploads.fnum like ' . $this->_db->Quote($fnum) . '
            WHERE profiles.campaign_id = ' . $profile_by_status["campaign_id"] . ' AND profiles.displayed = 1 AND profiles.mandatory = 1';
		}

		$this->_db->setQuery($query);
		$doc_result = $this->_db->loadResult();
		$this->updateAttachmentProgressByFnum(floor($doc_result), $fnum);

		return floor($doc_result);
	}

	public function updateAttachmentProgressByFnum($result, $fnum)
	{
		$updated = false;

		if (!empty($fnum))
		{

			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__emundus_campaign_candidature'))
				->set($this->_db->quoteName('attachment_progress') . ' = ' . $this->_db->quote($result))
				->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);

			try {
				$updated = $this->_db->execute();
			} catch (Exception $e) {
				Log::add('Error in model/application at query: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	public function checkFabrikValidations($fnum, $redirect = false, $itemId = null)
	{
		$validate = true;

		if (!empty($fnum)) {
			require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
			$m_profile = new EmundusModelProfile;
			$profile   = $m_profile->getProfileByStatus($fnum);

			if (!empty($profile['profile'])) {
				require_once(JPATH_SITE . '/components/com_emundus/models/form.php');
				$m_form = new EmundusModelForm;
				$forms  = $m_form->getFormsByProfileId($profile['profile']);

				if (!empty($forms)) {
					$form_ids = array_map(function ($form) {
						return $form->id;
					}, $forms);

					$query = $this->_db->getQuery(true);
					$query->select('jfe.label, jfe.params, jff.form_id')
						->from('jos_fabrik_elements as jfe')
						->leftJoin('jos_fabrik_formgroup jff on jfe.group_id = jff.group_id')
						->where('jff.form_id IN (' . implode(',', $form_ids) . ')')
						->andWhere('jfe.plugin = ' . $this->_db->quote('emundus_fileupload'))
						->andWhere('jfe.published = 1')
						->andWhere('JSON_SEARCH(jfe.params, "one", "notempty")  != ""');

					try {
						$this->_db->setQuery($query);
						$elements_params = $this->_db->loadObjectList();
					}
					catch (Exception $e) {
						Log::add('Failed to check if emundus fileuploads fields are correctly filled ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					}

					if (!empty($elements_params)) {
						foreach ($elements_params as $element) {
							$params       = json_decode($element->params, true);
							$notempty_key = array_search('notempty', $params['validations']['plugin']);

							if ($params['validations']['plugin_published'][$notempty_key] == 1) {
								// check user uploaded file
								$query->clear()
									->select('id')
									->from('#__emundus_uploads')
									->where('fnum LIKE ' . $this->_db->quote($fnum))
									->andWhere('attachment_id = ' . $params['attachmentId']);

								try {
									$this->_db->setQuery($query);
									$is_uploaded = $this->_db->loadResult();
									if (empty($is_uploaded)) {
										$form_label = '';
										foreach ($forms as $form) {
											if ($form->id == $element->form_id) {
												$form_label = Text::_($form->label);
												break;
											}
										}

										$app = JFactory::getApplication();
										$app->enqueueMessage(sprintf(Text::_('COM_EMUNDUS_MISSING_MANDATORY_FILE_UPLOAD'), '<b>' . Text::_($element->label) . '</b>', '<b>' . $form_label . '</b>'), 'warning');
										if ($redirect && !empty($itemId)) {
											$app->redirect("index.php?option=com_fabrik&view=form&formid=" . $element->form_id . "&Itemid=$itemId&usekey=fnum&rowid=$fnum");
										}

										return false;
									}
								}
								catch (Exception $e) {
									Log::add('Failed to check if emundus fileuploads fields are correctly filled ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
								}
							}
						}
					}
				}
			}
		}

		return $validate;
	}

	/**
	 * @param $aid
	 *
	 * @return bool|mixed
	 *
	 * @since version
	 */
	public function getLogged($aid, $user = null)
	{
		if (empty($user)) {
			$user = Factory::getApplication()->getIdentity();
		}

		$query = $this->_db->getQuery(true);

		$query->select('s.time, s.client_id, u.id, u.name, u.username')
			->from($this->_db->quoteName('#__session', 's'))
			->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON s.userid = u.id')
			->where($this->_db->quoteName('u.id') . ' = ' . $this->_db->quote($aid));
		$this->_db->setQuery($query);
		$results = $this->_db->loadObjectList();

		foreach ($results as $k => $result) {
			$results[$k]->logoutLink = '';

			if ($user->authorise('core.manage', 'com_users')) {
				$results[$k]->editLink   = JRoute::_('index.php?option=com_emundus&view=users&edit=1&rowid=' . $result->id . '&tmpl=component');
				$results[$k]->logoutLink = JRoute::_('index.php?option=com_login&task=logout&uid=' . $result->id . '&' . JSession::getFormToken() . '=1');
			}
			$results[$k]->name = $results[$k]->username;
		}

		return $results;
	}


	/**
	 * @param        $formID
	 * @param        $aid
	 * @param   int  $fnum
	 *
	 * @return string|null
	 *
	 * @since version
	 */
	public function getFormByFabrikFormID($formID, $aid, $fnum = 0)
	{
		$h_access          = new EmundusHelperAccess;
		$eMConfig          = JComponentHelper::getParams('com_emundus');
		$show_empty_fields = $eMConfig->get('show_empty_fields', 1);

		$form = '';

		// Get table by form ID
		$query = 'SELECT fbtables.id AS table_id, fbtables.form_id, fbforms.label, fbtables.db_table_name
                    FROM #__fabrik_forms AS fbforms
                    LEFT JOIN #__fabrik_lists AS fbtables ON fbtables.form_id = fbforms.id
                    WHERE fbforms.id IN (' . implode(',', $formID) . ')
                    ORDER BY find_in_set(fbforms.id, "' . implode(',', $formID) . '")';

		try {
			$this->_db->setQuery($query);
			$table = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			return null;
		}


		for ($i = 0; $i < sizeof($table); $i++) {
			$form .= '<br><hr><div class="TitleAdmission"><h2>';

			$form  .= Text::_($table[$i]->label);

			$form .= '</h2>';
			if ($h_access->asAccessAction(1, 'u', $this->_user->id, $fnum) && $table[$i]->db_table_name != "#__emundus_training") {

				$query = 'SELECT count(id) FROM `' . $table[$i]->db_table_name . '` WHERE fnum like ' . $this->_db->Quote($fnum);
				try {

					$this->_db->setQuery($query);
					$cpt = $this->_db->loadResult();

				}
				catch (Exception $e) {
					return $e->getMessage();
				}


				$allowEmbed = $this->allowEmbed(JURI::base() . 'index.php?lang=en');
				if ($cpt > 0) {

					if ($allowEmbed) {
						$form .= '<button type="button" id="' . $table[$i]->form_id . '" class="btn btn btn-info btn-sm em-actions-form marginRightbutton" url="index.php?option=com_fabrik&view=form&formid=' . $table[$i]->form_id . '&usekey=fnum&rowid=' . $fnum . '&tmpl=component" title="' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '"><i> ' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '</i></button>';
					}
					else {
						$form .= ' <a id="' . $table[$i]->form_id . '" class="btn btn btn-info btn-sm marginRightbutton" href="index.php?option=com_fabrik&view=form&formid=' . $table[$i]->form_id . '&usekey=fnum&rowid=' . $fnum . '" title="' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '" target="_blank"><i> ' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '</i></a>';
					}

				}
				else {
					if ($allowEmbed) {
						$form .= '<button type="button" id="' . $table[$i]->form_id . '" class="btn btn-default btn-sm em-actions-form marginRightbutton" url="index.php?option=com_fabrik&view=form&formid=' . $table[$i]->form_id . '&' . $table[$i]->db_table_name . '___fnum=' . $fnum . '&' . $table[$i]->db_table_name . '___user_raw=' . $aid . '&' . $table[$i]->db_table_name . '___user=' . $aid . '&sid=' . $aid . '&tmpl=component" title="' . Text::_('COM_EMUNDUS_ADD') . '"><i> ' . Text::_('COM_EMUNDUS_ADD') . '</i></button>';
					}
					else {
						$form .= ' <a type="button" id="' . $table[$i]->form_id . '" class="btn btn-default btn-sm marginRightbutton" href="index.php?option=com_fabrik&view=form&formid=' . $table[$i]->form_id . '&' . $table[$i]->db_table_name . '___fnum=' . $fnum . '&' . $table[$i]->db_table_name . '___user_raw=' . $aid . '&' . $table[$i]->db_table_name . '___user=' . $aid . '&sid=' . $aid . '" title="' . Text::_('COM_EMUNDUS_ADD') . '" target="_blank"><i> ' . Text::_('COM_EMUNDUS_ADD') . '</i></a>';
					}
				}

			}
			$form .= '</div>';

			// liste des groupes pour le formulaire d'une table
			$query = 'SELECT ff.id, ff.group_id, fg.id, fg.label, fg.params
                      FROM #__fabrik_formgroup ff, #__fabrik_groups fg
                      WHERE ff.group_id = fg.id AND fg.published = 1 AND ff.form_id = ' . $table[$i]->form_id . '
                      ORDER BY ff.ordering';
			try {

				$this->_db->setQuery($query);
				$groupes = $this->_db->loadObjectList();

			}
			catch (Exception $e) {
				return $e->getMessage();
			}

			/*-- Liste des groupes -- */
			foreach ($groupes as $itemg) {

				$g_params = json_decode($itemg->params);

				if (!EmundusHelperAccess::isAllowedAccessLevel($this->_user->id, (int) $g_params->access)) {
					continue;
				}

				// liste des items par groupe
				$query = 'SELECT fe.id, fe.name, fe.label, fe.plugin, fe.params
                            FROM #__fabrik_elements fe
                            WHERE fe.published=1 AND fe.hidden=0 AND fe.group_id = "' . $itemg->group_id . '"
                            ORDER BY fe.ordering';

				try {
					$this->_db->setQuery($query);
					$elements = $this->_db->loadObjectList();
				}
				catch (Exception $e) {
					return $e->getMessage();
				}

				if (count($elements) > 0) {
					$form .= '<fieldset><legend class="legend">';
					$form .= Text::_($itemg->label);
					$form .= '</legend>';

					if ($itemg->group_id == 14) {

						foreach ($elements as &$element) {
							if (!empty($element->label) && $element->label != ' ') {

								if (in_array($element->plugin,['date','jdate']) && $element->content > 0) {
									if (!empty($element->content) && ($element->content != '0000-00-00 00:00:00' && $element->content != '0000-00-00')) {
										$elt = date(EmundusHelperFabrik::getFabrikDateParam($element, 'date_form_format'), strtotime($element->content));
									}
									else {
										$elt = '';
									}

								}
								elseif (($element->plugin == 'birthday' || $element->plugin == 'birthday_remove_slashes') && $element->content > 0) {
									preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $element->content, $matches);
									if (count($matches) == 0) {
										$elt = $element->content;
									}
									else {
										$format = json_decode($element->params)->list_date_format;

										$d = DateTime::createFromFormat($format, $element->content);
										if ($d && $d->format($format) == $element->content) {
											$elt = JHtml::_('date', $element->content, Text::_('DATE_FORMAT_LC'));
										}
										else {
											$elt = JHtml::_('date', $element->content, $format);
										}
									}

								}
								elseif ($element->plugin == 'databasejoin') {
									$params = json_decode($element->params);
									$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

									if ($params->database_join_display_type == 'checkbox') {

										$query = $this->_db->getQuery(true);

										$parent_id = strlen($element->content_id) > 0 ? $element->content_id : 0;
										$select    = $this->getSelectFromDBJoinElementParams($params);

										$query->select($select)
											->from($this->_db->quoteName($params->join_db_name . '_repeat_' . $element->name, 't'))
											->leftJoin($this->_db->quoteName($params->join_db_name, 'jd') . ' ON ' . $this->_db->quoteName('jd.' . $params->join_key_column) . ' = ' . $this->_db->quoteName('t.' . $element->name))
											->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($parent_id));

										try {
											$this->_db->setQuery($query);
											$res = $this->_db->loadColumn();
											$elt = implode(', ', $res);
										}
										catch (Exception $e) {
											Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
											throw $e;
										}
									}
									else {
										$from  = $params->join_db_name;
										$where = $params->join_key_column . '=' . $this->_db->Quote($element->content);
										$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
										$query = preg_replace('#{thistable}#', $from, $query);
										$query = preg_replace('#{shortlang}#', $this->locales, $query);
										$query = preg_replace('#{my->id}#', $aid, $query);

										try {

											$this->_db->setQuery($query);

											$elt = $this->_db->loadResult();

										}
										catch (Exception $e) {
											return $e->getMessage();
										}
									}

								}
								elseif ($element->plugin == 'checkbox') {

									$elt = implode(", ", json_decode(@$element->content));

								}
								else {
									$elt = $element->content;
								}

								$form .= '<b>' . Text::_($element->label) . ': </b>' . Text::_($elt) . '<br/>';
							}
						}

						// TABLEAU DE PLUSIEURS LIGNES
					}
					elseif ((int) $g_params->repeated === 1 || (int) $g_params->repeat_group_button === 1) {

						$form .= '<table class="table table-bordered table-striped">
                            <thead>
                            <tr> ';

						// Entrée du tableau
						$t_elt = array();

						foreach ($elements as &$element) {
							$t_elt[] = $element->name;
							$form    .= '<th scope="col">' . Text::_($element->label) . '</th>';
						}
						unset($element);

						$query = 'SELECT table_join FROM #__fabrik_joins WHERE group_id=' . $itemg->group_id . ' AND table_join_key like "parent_id"';

						try {

							$this->_db->setQuery($query);
							$r_table = $this->_db->loadResult();

						}
						catch (Exception $e) {
							return $e->getMessage();
						}

						$query = 'SELECT `' . implode("`,`", $t_elt) . '`, id FROM ' . $r_table . ' WHERE parent_id=(SELECT id FROM ' . $table[$i]->db_table_name . ' WHERE fnum like ' . $this->_db->Quote($fnum) . ')';


						try {

							$this->_db->setQuery($query);
							$repeated_elements = $this->_db->loadObjectList();

						}
						catch (Exception $e) {
							return $e->getMessage();
						}

						unset($t_elt);
						$form .= '</tr></thead>';

						// Ligne du tableau
						if (count($repeated_elements) > 0) {
							$form .= '<tbody>';

							foreach ($repeated_elements as $r_element) {
								$form .= '<tr>';
								$j    = 0;

								foreach ($r_element as $key => $r_elt) {
									if ($key != 'id' && $key != 'parent_id' && isset($elements[$j])) {

										if (in_array($elements[$j]->plugin,['date','jdate'])) {
											if (!empty($r_elt) && ($r_elt != '0000-00-00 00:00:00' && $r_elt != '0000-00-00')) {
												$elt = date(EmundusHelperFabrik::getFabrikDateParam($elements[$j], 'date_form_format'), strtotime($r_elt));
											}
											else {
												$elt = '';
											}

										}
										elseif (($elements[$j]->plugin == 'birthday' || $elements[$j]->plugin == 'birthday_remove_slashes') && $r_elt > 0) {
											preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $r_elt, $matches);
											if (count($matches) == 0) {
												$elt = $r_elt;
											}
											else {
												$format = json_decode($elements[$j]->params)->list_date_format;
												$d      = DateTime::createFromFormat($format, $r_elt);
												if ($d && $d->format($format) == $r_elt) {
													$elt = JHtml::_('date', $r_elt, Text::_('DATE_FORMAT_LC'));
												}
												else {
													$elt = JHtml::_('date', $r_elt, $format);
												}
											}

										}
										elseif ($elements[$j]->plugin == 'databasejoin') {

											$params = json_decode($elements[$j]->params);
											$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

											if ($params->database_join_display_type == 'checkbox') {

												$query = $this->_db->getQuery(true);

												$parent_id = strlen($elements[$j]->content_id) > 0 ? $elements[$j]->content_id : 0;
												$select    = $this->getSelectFromDBJoinElementParams($params);

												$query->select($select)
													->from($this->_db->quoteName($params->join_db_name . '_repeat_' . $elements[$j]->name, 't'))
													->leftJoin($this->_db->quoteName($params->join_db_name, 'jd') . ' ON ' . $this->_db->quoteName('jd.' . $params->join_key_column) . ' = ' . $this->_db->quoteName('t.' . $elements[$j]->name))
													->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($parent_id));

												try {
													$this->_db->setQuery($query);
													$res = $this->_db->loadColumn();
													$elt = implode(', ', $res);
												}
												catch (Exception $e) {
													Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
													throw $e;
												}
											}
											else {
												$from  = $params->join_db_name;
												$where = $params->join_key_column . '=' . $this->_db->Quote($r_elt);
												$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
												$query = preg_replace('#{thistable}#', $from, $query);
												$query = preg_replace('#{my->id}#', $aid, $query);
												$query = preg_replace('#{shortlang}#', $this->locales, $query);

												try {
													$this->_db->setQuery($query);
													$elt = $this->_db->loadResult();
												}
												catch (Exception $e) {
													return $e->getMessage();
												}
											}

										}
										elseif ($elements[$j]->plugin == 'checkbox') {

											$elt = implode(", ", json_decode(@$r_elt));

										}
										elseif ($elements[$j]->plugin == 'dropdown' || $elements[$j]->plugin == 'radiobutton') {

											$params = json_decode($elements[$j]->params);
											$index  = array_search($r_elt, $params->sub_options->sub_values);
											if (strlen($index) > 0) {
												$elt = Text::_($params->sub_options->sub_labels[$index]);
											}
											else {
												$elt = "";
											}

										}
										else {
											$elt = $r_elt;
										}

										$form .= '<td><div id="em_training_' . $r_element->id . '" class="course ' . $r_element->id . '"> ' . Text::_($elt) . '</div></td>';
									}
									$j++;
								}
								$form .= '</tr>';
							}
							$form .= '</tbody>';
						}
						$form .= '</table>';

						// AFFICHAGE EN LIGNE
					}
					else {
						$form   .= '<table class="em-personalDetail-table-inline">';
						$modulo = 0;
						foreach ($elements as &$element) {

							if (!empty($element->label) && $element->label != ' ') {
								$query = 'SELECT `id`, `' . $element->name . '` FROM `' . $table[$i]->db_table_name . '` WHERE fnum like ' . $this->_db->Quote($fnum);

								try {
									$this->_db->setQuery($query);
									$res = $this->_db->loadRow();
								}
								catch (Exception $e) {
									return $e->getMessage();
								}

								$element->content    = @$res[1];
								$element->content_id = @$res[0];

								// Do not display elements with no value inside them.
								if ($show_empty_fields == 0 && trim($element->content) == '') {
									continue;
								}
								if (in_array($element->plugin,['date','jdate']) && $element->content > 0) {
									if (!empty($element->content) && ($element->content != '0000-00-00 00:00:00' && $element->content != '0000-00-00')) {
										$elt = date(EmundusHelperFabrik::getFabrikDateParam($element, 'date_form_format'), strtotime($element->content));
									}
									else {
										$elt = '';
									}

								}
								elseif (($element->plugin == 'birthday' || $element->plugin == 'birthday_remove_slashes') && $element->content > 0) {
									preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $element->content, $matches);
									if (count($matches) == 0) {
										$elt = $element->content;
									}
									else {
										$format = json_decode($element->params)->list_date_format;

										$d = DateTime::createFromFormat($format, $element->content);
										if ($d && $d->format($format) == $element->content) {
											$elt = JHtml::_('date', $element->content, Text::_('DATE_FORMAT_LC'));
										}
										else {
											$elt = JHtml::_('date', $element->content, $format);
										}
									}
								}
								elseif ($element->plugin == 'databasejoin') {

									$params = json_decode($element->params);
									$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

									if ($params->database_join_display_type == 'checkbox') {

										$elt = implode(", ", json_decode(@$element->content));

									}
									else {

										$from  = $params->join_db_name;
										$where = $params->join_key_column . '=' . $this->_db->Quote($element->content);
										$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
										$query = preg_replace('#{thistable}#', $from, $query);
										$query = preg_replace('#{my->id}#', $aid, $query);
										$query = preg_replace('#{shortlang}#', $this->locales, $query);

										try {
											$this->_db->setQuery($query);
											$elt = $this->_db->loadResult();
										}
										catch (Exception $e) {
											return $e->getMessage();
										}
									}

								}
								elseif ($element->plugin == 'cascadingdropdown') {

									$params               = json_decode($element->params);
									$cascadingdropdown_id = $params->cascadingdropdown_id;

									$r1                      = explode('___', $cascadingdropdown_id);
									$cascadingdropdown_label = Text::_($params->cascadingdropdown_label);

									$r2 = explode('___', $cascadingdropdown_label);

									$select = !empty($params->cascadingdropdown_label_concat) ? "CONCAT(" . $params->cascadingdropdown_label_concat . ")" : $r2[1];
									$from   = $r2[0];
									$where  = $r1[1] . '=' . $this->_db->Quote($element->content);
									$query  = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
									$query  = preg_replace('#{thistable}#', $from, $query);
									$query  = preg_replace('#{my->id}#', $aid, $query);
									$query  = preg_replace('#{shortlang}#', $this->locales, $query);

									try {
										$this->_db->setQuery($query);
										$elt = $this->_db->loadResult();
									}
									catch (Exception $e) {
										return $e->getMessage();
									}

								}
								elseif ($element->plugin == 'checkbox') {

									$elt = implode(", ", json_decode(@$element->content));

								}
								elseif ($element->plugin == 'dropdown' || $element->plugin == 'radiobutton') {

									$params = json_decode($element->params);
									$index  = array_search($element->content, $params->sub_options->sub_values);
									if (strlen($index) > 0) {
										$elt = Text::_($params->sub_options->sub_labels[$index]);
									}
									else {
										$elt = "";
									}

								}
								else {

									$elt = $element->content;
								}

								// modulo for strips css
								if ($modulo % 2) {
									$form .= '<tr class="table-strip-1"><td style="padding-right:50px; padding-left: 0; border-bottom: 1px solid var(--neutral-400);"><b>' . Text::_($element->label) . '</b></td> <td> ' . Text::_($elt) . '</td></tr>';
								}
								else {
									$form .= '<tr class="table-strip-2 tw-bg-neutral-0"><td style="padding-right:50px; padding-left: 0; border-bottom: 1px solid var(--neutral-400);"><b>' . Text::_($element->label) . '</b></td> <td> ' . Text::_($elt) . '</td></tr>';
								}
								$modulo++;
							}
						}
					}
					$form .= '</table>';
					$form .= '</fieldset>';
				}
			}
		}

		return $form;
	}

	// Get form to display in application page layout view
	public function getForms($aid, $fnum = 0, $pid = 9)
	{
		$h_menu    = new EmundusHelperMenu;
		$h_access  = new EmundusHelperAccess;
		$tableuser = $h_menu->buildMenuQuery($pid);

		$eMConfig          = ComponentHelper::getParams('com_emundus');
		$show_empty_fields = $eMConfig->get('show_empty_fields', 1);

		$forms = '';

		try {

			if (isset($tableuser)) {
				if (!class_exists('HtmlSanitizerSingleton')) {
					require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
				}
				$html_sanitizer = HtmlSanitizerSingleton::getInstance();

				$allowed_groups      = EmundusHelperAccess::getUserFabrikGroups($this->_user->id);
				$allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs($this->_user->id);

				$allowEmbed = $this->allowEmbed(Uri::base() . 'index.php?lang=en');
				$can_comment = EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $fnum) && $this->_user->applicant != 1;

				if ($can_comment) {
					if (!class_exists('EmundusHelperFiles')) {
						require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
					}

					$ccid = EmundusHelperFiles::getIdFromFnum($fnum);
					require_once(JPATH_ROOT . '/components/com_emundus/models/comments.php');
					$m_comments = new EmundusModelComments();
					$file_comments = $m_comments->getComments($ccid, $this->_user->id);
				}

				foreach ($tableuser as $itemt) {

					$forms .= '<br><hr><div class="TitlePersonalInfo em-personalInfo em-mb-12 title-applicant-form">';
					$title = explode(' - ', Text::_($itemt->label));
					if (empty($title[1])) {
						$title = Text::_(trim($itemt->label));
					}
					else {
						$title = Text::_(trim($title[1]));
					}
					$forms       .= '<h2>' . $title . '</h2>';
					$form_params = json_decode($itemt->params);

					$forms .= '<div class="flex flex-row items-center">';

					if ($can_comment) {
						$comment_classes = 'comment-icon material-symbols-outlined tw-cursor-pointer tw-p-1 tw-h-fit tw-mr-2 ';
						foreach ($file_comments as $comment) {
							if ($comment['target_id'] == $itemt->form_id && $comment['target_type'] == 'forms') {
								$comment_classes .= ' has-comments tw-bg-main-500 tw-text-neutral-300 tw-rounded-full';
							}
						}

						$forms .= '<span class="' . $comment_classes . '" title="' . Text::_('COM_EMUNDUS_COMMENTS_ADD_COMMENT') . '" data-target-type="forms" data-target-id="' . $itemt->form_id . '">comment</span>';
					}

					if ($h_access->asAccessAction(1, 'u', $this->_user->id, $fnum) && $itemt->db_table_name != '#__emundus_training' && $this->_user->applicant != 1) {

						$query = $this->_db->getQuery(true);
						$query->select('id')
							->from($this->_db->quoteName($itemt->db_table_name))
							->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
						$this->_db->setQuery($query);
						$rowid = $this->_db->loadResult();

						$link = 'index.php?option=com_fabrik&view=form&formid=' . $itemt->form_id;
						$menu_item = Factory::getApplication()->getMenu()->getItems('link', $link, true);

						$url = $menu_item->route;
						$url .= '?fnum='.$fnum;
						if(!empty($rowid)) {
							$url .= '&rowid=' . $rowid;
						}

						if (!empty($rowid)) {
							if ($allowEmbed) {
								$forms .= ' <button type="button" id="' . $itemt->form_id . '" class="btn btn btn-info btn-sm em-actions-form" url="'.$url.'" title="' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '" target="_blank"><i> ' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '</i></button>';
							}
							else {
								$forms .= ' <a id="' . $itemt->form_id . '" class="tw-target-blank-links tw-text-profile-full visited:tw-text-profile-full hover:tw-text-profile-full visited:hover:tw-text-profile-full hover:tw-underline" href="'.$url.'" title="' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '" target="_blank"><span> ' . Text::_('COM_EMUNDUS_ACTIONS_EDIT') . '</span></a>';
							}
						}
						else {
							if ($allowEmbed) {
								$forms .= ' <button type="button" id="' . $itemt->form_id . '" class="btn btn-default btn-sm em-actions-form" url="'.$url.'" title="' . Text::_('COM_EMUNDUS_ADD') . '"><i> ' . Text::_('COM_EMUNDUS_ADD') . '</i></button>';
							}
							else {
								$forms .= ' <a type="button" id="' . $itemt->form_id . '" class="tw-target-blank-links tw-text-profile-full visited:tw-text-profile-full !tw-no-underline hover:!tw-underline hover:!tw-text-profile-full visited:hover:tw-text-profile-full" href="'.$url.'" title="' . Text::_('COM_EMUNDUS_ADD') . '" target="_blank"><span> ' . Text::_('COM_EMUNDUS_ADD') . '</span></a>';
							}
						}
					}
					$forms .= '</div></div>';

					// liste des groupes pour le formulaire d'une table
					$query = $this->_db->getQuery(true);
					$query->select('ff.id,ff.group_id,fg.id,fg.label,fg.params,fg.is_join')
						->from($this->_db->quoteName('#__fabrik_formgroup', 'ff'))
						->leftJoin($this->_db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $this->_db->quoteName('fg.id') . ' = ' . $this->_db->quoteName('ff.group_id'))
						->where($this->_db->quoteName('fg.published') . ' = 1')
						->where($this->_db->quoteName('ff.form_id') . ' = ' . $this->_db->quote($itemt->form_id))
						->order($this->_db->quoteName('ff.ordering'));
					$this->_db->setQuery($query);
					$groupes = $this->_db->loadObjectList();

					/*-- Liste des groupes -- */
					$hidden_group_param_values = [0, '-1', '-2'];
					foreach ($groupes as $itemg) {
						$g_params = json_decode($itemg->params);

						if (
							(($allowed_groups !== true && !in_array($itemg->group_id, $allowed_groups)) || !EmundusHelperAccess::isAllowedAccessLevel($this->_user->id, (int) $g_params->access)) &&
							!in_array($g_params->repeat_group_show_first, $hidden_group_param_values)
						) {
							$forms .= '<fieldset class="em-personalDetail">
											<h3 style="font-size: var(--em-coordinator-h3); font-weight: inherit; padding-left: 0;">' . Text::_($itemg->label) . '</h3>
											<table class="em-restricted-group">
												<thead><tr><td>' . Text::_('COM_EMUNDUS_CANNOT_SEE_GROUP') . '</td></tr></thead>
											</table>
										</fieldset>';
							continue;
						}

						$query = $this->_db->getQuery(true);
						$query->select('fe.id,fe.name,fe.label,fe.plugin,fe.params,fe.default,fe.eval')
							->from($this->_db->quoteName('#__fabrik_elements', 'fe'))
							->where($this->_db->quoteName('fe.published') . ' = 1')
							->where($this->_db->quoteName('fe.hidden') . ' = 0')
							->where($this->_db->quoteName('fe.group_id') . ' = ' . $this->_db->quote($itemg->group_id))
							->order($this->_db->quoteName('fe.ordering'));

						try {
							$this->_db->setQuery($query);
							$elements = $this->_db->loadObjectList();
						}
						catch (Exception $e) {
							Log::add('Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
							throw $e;
						}

						if (count($elements) > 0) {

							if ((int) $g_params->repeated === 1 || (int) $g_params->repeat_group_button === 1 || (int) $itemg->is_join === 1) {

								$query->clear()
									->select('table_join')
									->from($this->_db->quoteName('#__fabrik_joins'))
									->where($this->_db->quoteName('list_id') . ' = ' . $this->_db->quote($itemt->table_id))
									->where($this->_db->quoteName('group_id') . ' = ' . $this->_db->quote($itemg->group_id))
									->where($this->_db->quoteName('table_join_key') . ' = ' . $this->_db->quote('parent_id'));
								try {
									$this->_db->setQuery($query);
									$table = $this->_db->loadResult();
								}
								catch (Exception $e) {
									Log::add('Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
									throw $e;
								}

								$check_repeat_groups = $this->checkEmptyRepeatGroups($elements, $table, $itemt->db_table_name, $fnum);

								if ($check_repeat_groups) {
									// -- Entrée du tableau --
									$t_elt = array();
									foreach ($elements as &$element) {
										$t_elt[] = $element->name;
									}
									unset($element);

									$forms .= '<fieldset class="em-personalDetail !tw-overflow-y-hidden" style="scrollbar-width: auto;">';
									$forms .= '<div class="tw-flex tw-flex-row justify-between form-group-title tw-sticky tw-left-0">';
									$forms .= (!empty($itemg->label)) ? '<h3 style="font-size: var(--em-coordinator-h3); font-weight: inherit; padding-left: 0;">' . Text::_($itemg->label) . '</h3>' : '';

									if ($can_comment) {
										$comment_classes = 'comment-icon material-symbols-outlined tw-cursor-pointer tw-p-1 tw-h-fit ';
										foreach ($file_comments as $comment) {
											if ($comment['target_id'] == $itemg->group_id && $comment['target_type'] == 'groups') {
												$comment_classes .= ' has-comments tw-bg-main-500 tw-text-neutral-300 tw-rounded-full';
											}
										}

										$forms .= '<span class="' . $comment_classes . '" title="' . Text::_('COM_EMUNDUS_COMMENTS_ADD_COMMENT') . '" data-target-type="groups" data-target-id="' . $itemg->group_id . '">comment</span>';
									}

									$forms .= '</div>';

									$forms .= '<table class="em-mt-8 em-mb-16 table table-bordered table-striped em-personalDetail-table-multiplleLine tw-p-6 tw-shadow-card !tw-rounded-coordinator-cards tw-border-separate !tw-border tw-border-neutral-400 tw-bg-neutral-0"><thead><tr class="!tw-border-0"> ';

									$repeated_elements = [];

									if ($itemg->group_id == 174) {
										$query->clear()
											->select(implode(',', $this->_db->quoteName($t_elt)) . ', id')
											->from($this->_db->quoteName($table))
											->where($this->_db->quoteName('parent_id') . ' = (SELECT id FROM ' . $this->_db->quoteName($itemt->db_table_name) . ' WHERE fnum like ' . $this->_db->quote($fnum) . ')')
											->orWhere($this->_db->quoteName('applicant_id') . ' = ' . $this->_db->quote($aid));
									}
									else {
										$query->clear()
											->select(implode(',', $this->_db->quoteName($t_elt)) . ', id')
											->from($this->_db->quoteName($table))
											->where($this->_db->quoteName('parent_id') . ' = (SELECT id FROM ' . $this->_db->quoteName($itemt->db_table_name) . ' WHERE fnum like ' . $this->_db->quote($fnum) . ')');
									}

									try {
										$this->_db->setQuery($query);
										$repeated_elements = $this->_db->loadObjectList();
									}
									catch (Exception $e) {
										Log::Add('ERROR getting repeated elements in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
									}

									$visible_elements = [];

									foreach ($elements as $element)
									{
										if (in_array($element->plugin, ['id', 'panel'])) continue;

										if ($show_empty_fields == 1)
										{
											$visible_elements[] = $element;
										}
										else
										{
											$hasValue = false;
											foreach ($repeated_elements as $row)
											{
												if (isset($row->{$element->name}) && $row->{$element->name} !== '' && $row->{$element->name} !== null)
												{
													$hasValue = true;
													break;
												}
											}

											if ($hasValue)
											{
												$visible_elements[] = $element;
											}
										}
									}

									foreach ($visible_elements as $element) {
										$forms .= '<th scope="col" class="!tw-border-b !tw-border-b-neutral-400 !tw-h-auto">';
										$forms .= '<div class="tw-flex tw-flex-row tw-items-center tw-w-full"><span class="tw-font-bold tw-text-ellipsis tw-overflow-hidden tw-whitespace-nowrap">' . Text::_($element->label) . '</span>';
										if ($can_comment) {
											$comment_classes = 'comment-icon material-symbols-outlined tw-cursor-pointer tw-p-1 tw-h-fit tw-ml-2';
											foreach ($file_comments as $comment) {
												if ($comment['target_id'] == $element->id && $comment['target_type'] == 'elements') {
													$comment_classes .= ' has-comments tw-bg-main-500 tw-text-neutral-300 tw-rounded-full';
												}
											}
											$forms .= '<span class="' . $comment_classes . '" title="' . Text::_('COM_EMUNDUS_COMMENTS_ADD_COMMENT') . '" data-target-type="elements" data-target-id="' . $element->id . '">comment</span>';
										}
										$forms .= '</div></th>';
									}

									unset($t_elt);

									$forms .= '</tr></thead>';

									// -- Ligne du tableau --
									if (count($repeated_elements) > 0) {
										$forms .= '<tbody class="tw-border-0">';

										$visible_names = array_map(function($el) { return $el->name; }, $visible_elements);

										foreach ($repeated_elements as $r_element) {
											$forms .= '<tr class="!tw-bg-neutral-0 !tw-border-0">';
											$j     = 0;
											foreach ($r_element as $key => $r_elt) {
												if (!in_array($key, $visible_names)) {
													$j++;
													continue;
												}

												$element = null;
												foreach ($visible_elements as $el) {
													if ($el->name === $key) {
														$element = $el;
														break;
													}
												}

												if ($element) {
													$params = json_decode($element->params);
												} else {
													$params = null;
												}

												// Do not display elements with no value inside them.
												if (($show_empty_fields == 0 && trim($r_elt) == '') || empty($params->store_in_db)) {
													$forms .= '<td></td>';
													$j++;
													continue;
												}

												if ($key != 'id' && $key != 'parent_id' && isset($elements[$j])) {

													if (in_array($elements[$j]->plugin,['date','jdate'])) {
														if (!empty($r_elt) && ($r_elt != '0000-00-00 00:00:00' && $r_elt != '0000-00-00')) {
															$elt = date(EmundusHelperFabrik::getFabrikDateParam($elements[$j], 'date_form_format'), strtotime($r_elt));
														}
														else {
															$elt = '';
														}
													}
													elseif (($elements[$j]->plugin == 'birthday' || $elements[$j]->plugin == 'birthday_remove_slashes') && $r_elt > 0) {
														preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $r_elt, $matches);
														if (empty($matches)) {
															$elt = $r_elt;
														}
														else {
															$format = $params->list_date_format;

															$d = DateTime::createFromFormat($format, $r_elt);
															if ($d && $d->format($format) == $r_elt) {
																$elt = JHtml::_('date', $r_elt, Text::_('DATE_FORMAT_LC'));
															}
															else {
																$elt = JHtml::_('date', $r_elt, $format);
															}
														}
													}
													elseif ($elements[$j]->plugin == 'databasejoin') {
														$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

														if ($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist') {
															$select = $this->getSelectFromDBJoinElementParams($params,'t');
															$query->select($select)
																->from($this->_db->quoteName($params->join_db_name, 't'))
																->leftJoin($this->_db->quoteName($itemt->db_table_name . '_' . $itemg->id . '_repeat_repeat_' . $elements[$j]->name, 'checkbox_repeat') . ' ON ' . $this->_db->quoteName('checkbox_repeat.' . $elements[$j]->name) . ' = ' . $this->_db->quoteName('t.id'))
																->leftJoin($this->_db->quoteName($itemt->db_table_name . '_' . $itemg->id . '_repeat', 'repeat_grp') . ' ON ' . $this->_db->quoteName('repeat_grp.id') . ' = ' . $this->_db->quoteName('checkbox_repeat.parent_id'))
																->where($this->_db->quoteName('checkbox_repeat.parent_id') . ' = ' . $r_element->id);

															try {
																$this->_db->setQuery($query);
																$value = $this->_db->loadColumn();
																$elt   = '<ul>';
																foreach ($value as $val) {
																	$elt .= '<li>' . Text::_($val) . '</li>';
																}
																$elt .= "</ul>";
															}
															catch (Exception $e) {
																Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
																throw $e;
															}
														}
														else {
															$from  = $params->join_db_name;
															$where = $params->join_key_column . '=' . $this->_db->Quote($r_elt);
															$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;

															$query = preg_replace('#{thistable}#', $from, $query);
															$query = preg_replace('#{my->id}#', $aid, $query);
															$query = preg_replace('#{shortlang}#', $this->locales, $query);

															$this->_db->setQuery($query);
															$ret = $this->_db->loadResult();
															if (empty($ret)) {
																$ret = $r_elt;
															}
															$elt = Text::_($ret);
														}
													}
													elseif ($elements[$j]->plugin == 'cascadingdropdown') {
														$cascadingdropdown_id    = $params->cascadingdropdown_id;
														$r1                      = explode('___', $cascadingdropdown_id);
														$cascadingdropdown_label = $params->cascadingdropdown_label;
														$r2                      = explode('___', $cascadingdropdown_label);
														$select                  = !empty($params->cascadingdropdown_label_concat) ? "CONCAT(" . $params->cascadingdropdown_label_concat . ")" : $r2[1];
														$from                    = $r2[0];

														// Checkboxes behave like repeat groups and therefore need to be handled a second level of depth.
														if ($params->cdd_display_type == 'checkbox' || $params->cdd_display_type == 'multilist') {
															$select = !empty($params->cascadingdropdown_label_concat) ? " CONCAT(" . $params->cascadingdropdown_label_concat . ")" : 'GROUP_CONCAT(' . $r2[1] . ')';

                                                            $load_type = 'column';

															// Load the Fabrik join for the element to it's respective repeat_repeat table.
															$query = $this->_db->getQuery(true);
															$query
																->select([$this->_db->quoteName('join_from_table'), $this->_db->quoteName('table_key'), $this->_db->quoteName('table_join'), $this->_db->quoteName('table_join_key')])
																->from($this->_db->quoteName('#__fabrik_joins'))
																->where($this->_db->quoteName('element_id') . ' = ' . $elements[$j]->id);
															$this->_db->setQuery($query);
															$f_join = $this->_db->loadObject();

															$where = $r1[1] . ' IN (
	                                                    SELECT ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_key) . '
	                                                    FROM ' . $this->_db->quoteName($f_join->table_join) . '
	                                                    WHERE ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_join_key) . ' = ' . $r_element->id . ')';
														}
														else {
                                                            $load_type = 'result';
															$where = $r1[1] . '=' . $this->_db->quote($r_elt);
														}
														$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
														$query = preg_replace('#{thistable}#', $from, $query);
														$query = preg_replace('#{my->id}#', $aid, $query);
														$query = preg_replace('#{shortlang}#', $this->locales, $query);

														$this->_db->setQuery($query);
                                                        if ($load_type == 'column') {
                                                            $ret = $this->_db->loadColumn();
                                                        } else {
                                                            $ret = $this->_db->loadResult();
                                                        }

                                                        if (empty($ret)) {
                                                            $ret = $r_elt;
                                                        }

                                                        if ($load_type == 'column') {
                                                            $elt   = '<ul>';
                                                            foreach ($ret as $val) {
                                                                $elt .= '<li>' . Text::_($val) . '</li>';
                                                            }
                                                            $elt .= "</ul>";
                                                        } else {
                                                            $elt = Text::_($ret);
                                                        }
													}
													elseif ($elements[$j]->plugin == 'checkbox') {
														$elm = array();
														if(is_array(json_decode($r_elt)))
														{
															if (!empty(array_filter($params->sub_options->sub_values)))
															{
																if (!empty($r_elt))
																{
																	$index = array_intersect(json_decode($r_elt), $params->sub_options->sub_values);
																}
															}
															else
															{
																$index = json_decode($r_elt);
															}

															foreach ($index as $value)
															{
																if (!empty(array_filter($params->sub_options->sub_values)))
																{
																	$key   = array_search($value, $params->sub_options->sub_values);
																	$elm[] = Text::_($params->sub_options->sub_labels[$key]);
																}
																else
																{
																	$elm[] = $value;
																}
															}
														}
														$elt = '<ul>';
														foreach ($elm as $val) {
															$elt .= '<li>' . Text::_($val) . '</li>';
														}
														$elt .= "</ul>";
													}
													elseif ($elements[$j]->plugin == 'dropdown' || $elements[$j]->plugin == 'radiobutton') {
														$index = array_search($r_elt, $params->sub_options->sub_values);
														if (strlen($index) > 0) {
															$elt = Text::_($params->sub_options->sub_labels[$index]);
														}
														elseif (!empty($params->dropdown_populate)) {
															$elt = $r_elt;
														}
														else {
															$elt = "";
														}
													}
													elseif ($elements[$j]->plugin == 'internalid') {
														$elt = '';
													}
													elseif ($elements[$j]->plugin == 'field') {
														if ($params->password == 1) {
															$elt = '******';
														}
														elseif ($params->password == 3) {
															$elt = '<a href="mailto:' . $r_elt . '">' . $r_elt . '</a>';
														}
														elseif ($params->password == 5) {
															$elt = '<a href="' . $r_elt . '" target="_blank">' . $r_elt . '</a>';
														}
														else {
															$elt = Text::_($r_elt);
														}
													}
													elseif ($elements[$j]->plugin == 'yesno') {
														$elt = ($r_elt == 1) ? Text::_("JYES") : Text::_("JNO");
													}
													elseif ($elements[$j]->plugin == 'display') {
														$elements[$j]->content = empty($elements[$j]->eval) ? $elements[$j]->default : $r_elt;
														$elt                   = Text::_($elements[$j]->content);
													}
													elseif ($elements[$j]->plugin == 'calc') {
														$elt = $r_elt;

														$stripped = strip_tags($elt);
														if ($stripped != $elt) {
															$elt = strip_tags($elt, ['p', 'a', 'div', 'ul', 'li', 'br']);
														}
													}
													elseif ($elements[$j]->plugin == 'emundus_phonenumber') {
														$elt = substr($r_elt, 2, strlen($r_elt));
													}
													elseif ($elements[$j]->plugin == 'iban') {
														$elt = $r_elt;

														if($params->encrypt_datas == 1) {
															$elt = EmundusHelperFabrik::decryptDatas($r_elt);
														}

														$elt = chunk_split($elt, 4, ' ');
													}
													else {
														$elt = $r_elt;
													}

													if (!empty($elt) && is_string($elt)) {
														$elt = $html_sanitizer->sanitize($elt);
													}

													$forms .= '<td class="!tw-bg-neutral-0 tw-min-h-[45px] !tw-h-auto" style="border-bottom: 1px solid var(--neutral-400);"><div id="em_training_' . $r_element->id . '" class="course ' . $r_element->id . '"> ' . (($elements[$j]->plugin != 'field') ? Text::_($elt) : $elt) . '</div></td>';
												}
												$j++;
											}
											$forms .= '</tr>';
										}
										$forms .= '</tbody>';
									}
									$forms .= '</table>';
									$forms .= '</fieldset>';
								}
								// AFFICHAGE EN LIGNE
							}
							else {

								$check_not_empty_group = $this->checkEmptyGroups($elements, $itemt->db_table_name, $fnum);

								if($check_not_empty_group && !in_array($g_params->repeat_group_show_first, $hidden_group_param_values)) {
									$forms .= '<table class="em-mt-8 em-mb-16 em-personalDetail-table-inline tw-p-6 tw-border-separate tw-rounded-coordinator-cards tw-shadow-card tw-bg-neutral-0">';

									$forms .= '<div class="tw-flex tw-flex-row tw-justify-between form-group-title">';
									$forms .= '<h3 style="font-size: var(--em-coordinator-h3); font-weight: inherit; padding-left: 0;">' . JText::_($itemg->label) . '</h3>';
									if ($can_comment) {
										$comment_classes = 'comment-icon material-symbols-outlined tw-cursor-pointer tw-p-1 tw-h-fit ';
										foreach ($file_comments as $comment) {
											if ($comment['target_id'] == $itemg->group_id && $comment['target_type'] == 'groups') {
												$comment_classes .= ' has-comments tw-bg-main-500 tw-text-neutral-300 tw-rounded-full';
											}
										}

										$forms .= '<span class="' . $comment_classes . '" title="' . Text::_('COM_EMUNDUS_COMMENTS_ADD_COMMENT') . '" data-target-type="groups" data-target-id="' . $itemg->group_id . '">comment</span>';
									}
									$forms .= '</div>';

									$modulo = 0;
									foreach ($elements as &$element) {

										if($element->plugin === 'panel') {
											continue;
										}

										if (!empty(trim($element->label))) {
											// TODO : If databasejoin checkbox or multilist get value from children table. Add a query to get join table from jos_fabrik_joins where element_id = $element->id
											if ($element->plugin == 'databasejoin') {
												$params = json_decode($element->params);
												$query  = 'SELECT `id`, `' . $element->name . '` FROM `' . $itemt->db_table_name . '` WHERE fnum like ' . $this->_db->Quote($fnum);

												if ($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist') {
													$query = 'SELECT t.id, jd.' . $element->name . ' FROM `' . $itemt->db_table_name . '` as t LEFT JOIN `' . $itemt->db_table_name . '_repeat_' . $element->name . '` as jd ON jd.parent_id = t.id WHERE fnum like ' . $this->_db->Quote($fnum);
												}

											}
											else {
												$query = 'SELECT `id`, `' . $element->name . '` FROM `' . $itemt->db_table_name . '` WHERE fnum like ' . $this->_db->Quote($fnum);
											}
											$this->_db->setQuery($query);
											$res = $this->_db->loadRow();

											if (is_array($res) && count($res) > 1) {
												$element->content    = $res[1];
												$element->content_id = $res[0];
											}
											else {
												$element->content    = '';
												$element->content_id = -1;
											}

											if (is_array($res) && count($res) > 1) {
												if ($element->plugin == 'display') {
													$element->content = empty($element->eval) ? $element->default : $res[1];
												}
												else {
													$element->content = $res[1];
												}
												$element->content_id = $res[0];
											}
											else {
												$element->content    = '';
												$element->content_id = -1;
											}

											// Do not display elements with no value inside them.
											if ($show_empty_fields == 0 && (trim($element->content) == '' || trim($element->content_id) == -1 || ($element->plugin == 'checkbox' && $element->content == '[""]')) && $element->plugin != 'emundus_fileupload') {
												continue;
											}

											// Decrypt datas encoded
											if ($form_params->note == 'encrypted') {
												$element->content = EmundusHelperFabrik::decryptDatas($element->content,null,'aes-128-cbc',$element->plugin);
											}
											//

											if (in_array($element->plugin,['date','jdate']) && !empty($element->content)) {
												if ($element->content != '0000-00-00 00:00:00' && $element->content != '0000-00-00') {
													$elt = date(EmundusHelperFabrik::getFabrikDateParam($element, 'date_form_format'), strtotime($element->content));
												}
												else {
													$elt = '';
												}
											}
											elseif (($element->plugin == 'birthday' || $element->plugin == 'birthday_remove_slashes') && $element->content > 0) {
												preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $element->content, $matches);
												if (count($matches) == 0) {
													$elt = $element->content;
												}
												else {
													$format = json_decode($element->params)->list_date_format;

													$d = DateTime::createFromFormat($format, $element->content);
													if ($d && $d->format($format) == $element->content) {
														$elt = JHtml::_('date', $element->content, Text::_('DATE_FORMAT_LC'));
													}
													else {
														$elt = JHtml::_('date', $element->content, $format);
													}
												}
											}
											elseif ($element->plugin == 'databasejoin') {
												$params = json_decode($element->params);
												$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

												if ($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist') {
													$query = $this->_db->getQuery(true);

													$parent_id = strlen($element->content_id) > 0 ? $element->content_id : 0;
													$select    = $this->getSelectFromDBJoinElementParams($params);
													$query->select($select)
														->from($this->_db->quoteName($itemt->db_table_name . '_repeat_' . $element->name, 't'))
														->leftJoin($this->_db->quoteName($params->join_db_name, 'jd') . ' ON ' . $this->_db->quoteName('jd.' . $params->join_key_column) . ' = ' . $this->_db->quoteName('t.' . $element->name))
														->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($parent_id));

													try {
														$this->_db->setQuery($query);
														$value = $this->_db->loadColumn();
														$elt   = '<ul>';
														foreach ($value as $val) {
															$elt .= '<li>' . Text::_($val) . '</li>';
														}
														$elt .= "</ul>";
													}
													catch (Exception $e) {
														Log::add('Line 997 - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
														throw $e;
													}
												}
												else {
													$from  = $params->join_db_name;
													$where = $params->join_key_column . '=' . $this->_db->Quote($element->content);
													$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;

													$query = preg_replace('#{thistable}#', $from, $query);
													$query = preg_replace('#{my->id}#', $aid, $query);
													$query = preg_replace('#{shortlang}#', $this->locales, $query);

													$this->_db->setQuery($query);
													$ret = $this->_db->loadResult();
													if (empty($ret)) {
														$ret = $element->content;
													}
													$elt = Text::_($ret);
												}
											}
											elseif ($element->plugin == 'cascadingdropdown') {
												$params                  = json_decode($element->params);
												$cascadingdropdown_id    = $params->cascadingdropdown_id;
												$r1                      = explode('___', $cascadingdropdown_id);
												$cascadingdropdown_label = Text::_($params->cascadingdropdown_label);
												$r2                      = explode('___', $cascadingdropdown_label);
												$select                  = !empty($params->cascadingdropdown_label_concat) ? "CONCAT(" . $params->cascadingdropdown_label_concat . ")" : $r2[1];
												$from                    = $r2[0];
												$where                   = $r1[1] . '=' . $this->_db->Quote($element->content);
												$query                   = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
												$query                   = preg_replace('#{thistable}#', $from, $query);
												$query                   = preg_replace('#{my->id}#', $aid, $query);
												$query                   = preg_replace('#{shortlang}#', $this->locales, $query);

												$this->_db->setQuery($query);
												$ret = $this->_db->loadResult();
												if (empty($ret)) {
													$ret = $element->content;
												}
												$elt = Text::_($ret);
											}
											elseif ($element->plugin == 'checkbox' && !empty($element->content)) {
												$params = json_decode($element->params);
												$elm    = [];
												if(is_array(json_decode($element->content)))
												{
													$index = array_intersect(json_decode($element->content), $params->sub_options->sub_values);
													foreach ($index as $value)
													{
														$key   = array_search($value, $params->sub_options->sub_values);
														$elm[] = Text::_($params->sub_options->sub_labels[$key]);
													}
												}

												$elt = '<ul class="!tw-m-0 tw-pl-4">';
												foreach ($elm as $val)
												{
													$elt .= '<li>' . Text::_($val) . '</li>';
												}
												$elt .= "</ul>";

											}
											elseif (($element->plugin == 'dropdown' || $element->plugin == 'radiobutton') && isset($element->content)) {
												$params = json_decode($element->params);
												$index  = array_search($element->content, $params->sub_options->sub_values);

												if (strlen($index) > 0) {
													$elt = Text::_($params->sub_options->sub_labels[$index]);
												}
												elseif (!empty($params->dropdown_populate)) {
													$elt = $element->content;
												}
												elseif ($params->multiple == 1) {
													$elt = $elt = "<ul><li>" . implode("</li><li>", json_decode(@$element->content)) . "</li></ul>";
												}
												else {
													$elt = "";
												}
											}
											elseif ($element->plugin == 'internalid') {
												$elt = '';
											}
											elseif ($element->plugin == 'yesno') {
												$elt = '';
												if ($element->content === '1') {
													$elt = Text::_('JYES');
												}
												elseif ($element->content === '0') {
													$elt = Text::_('JNO');
												}
											}
											elseif ($element->plugin == 'field') {
												$params = json_decode($element->params);

												if ($params->password == 1) {
													$elt = '******';
												}
												elseif ($params->password == 3) {
													$elt = '<a href="mailto:' . $element->content . '" title="' . Text::_($element->label) . '">' . $element->content . '</a>';
												}
												elseif ($params->password == 5) {
													$elt = '<a href="' . $element->content . '" target="_blank" title="' . Text::_($element->label) . '">' . $element->content . '</a>';
												}
												else {
													$elt = $element->content;
												}
											}
											elseif ($element->plugin == 'emundus_fileupload') {
												$params = json_decode($element->params);
												$query  = $this->_db->getQuery(true);

												try {
													$query->select('esa.id,esa.value as attachment_name,eu.filename')
														->from($this->_db->quoteName('#__emundus_uploads', 'eu'))
														->leftJoin($this->_db->quoteName('#__emundus_setup_attachments', 'esa') . ' ON ' . $this->_db->quoteName('esa.id') . ' = ' . $this->_db->quoteName('eu.attachment_id'))
														->where($this->_db->quoteName('eu.fnum') . ' LIKE ' . $this->_db->quote($fnum))
														->andWhere($this->_db->quoteName('eu.attachment_id') . ' = ' . $this->_db->quote($params->attachmentId));
													$this->_db->setQuery($query);
													$attachment_upload = $this->_db->loadObject();

													if (!empty($attachment_upload->filename) && (($allowed_attachments !== true && in_array($params->attachmentId, $allowed_attachments)) || $allowed_attachments === true)) {
														$path = DS . 'images' . DS . 'emundus' . DS . 'files' . DS . $aid . DS . $attachment_upload->filename;
														$elt  = '<a href="' . $path . '" target="_blank" style="text-decoration: underline;">' . $attachment_upload->attachment_name . '</a>';
													}
													else {
														$elt = '';
													}
												}
												catch (Exception $e) {
													Log::add('component/com_emundus/models/application | Error at getting emundus_fileupload for applicant ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
													$elt = '';
												}
											}
											elseif ($element->plugin == 'emundus_phonenumber') {
												$elt = substr($element->content, 2, strlen($element->content));
											}
											elseif ($element->plugin == 'textarea') {
												if (json_decode($element->params)->use_wysiwyg == 1) {
													$elt = $element->content;
												}
												else {
													$elt = nl2br($element->content);
												}
											}
											elseif ($element->plugin == 'iban') {
												$elt    = $element->content;
												$params = json_decode($element->params);

												if ($params->encrypt_datas == 1) {
													$elt = EmundusHelperFabrik::decryptDatas($element->content);
												}

												$elt = chunk_split($elt, 4, ' ');
											}
											elseif ($element->plugin == 'booking') {
												$availability    = $element->content;

												if(!empty($availability))
												{
													$query = $this->_db->getQuery(true);
													$query->select('start_date,end_date')
														->from($this->_db->quoteName('#__emundus_setup_availabilities'))
														->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($availability));
													$this->_db->setQuery($query);
													$availability = $this->_db->loadObject();

													if(!empty($availability))
													{
														$elt = EmundusHelperDate::displayDate($availability->start_date, 'd.m.Y H:i', 0) . ' - ' . EmundusHelperDate::displayDate($availability->end_date, 'd.m.Y H:i', 0);
													}
												}
												else {
													$elt = '';
												}
											}
											else {
												$elt = $element->content;
											}

											if (!empty($elt) && is_string($elt)) {
												$elt = $html_sanitizer->sanitize($elt);
											}

											if ($modulo % 2) {
												$class = "table-strip-1";
											}
											else {
												$class = "table-strip-2 !tw-bg-neutral-0";
											}

											$tds = !empty(Text::_($element->label)) ? '<td style="padding-right:50px; padding-left: 0; border-bottom: 1px solid var(--neutral-400);"><b>' . Text::_($element->label) . '</b></td>' : '';
											$tds .= '<td class="tw-flex tw-flex-row tw-justify-between tw-w-full tw-items-center" style="width:100%; border-bottom: 1px solid var(--neutral-400);"><span>' . ((!in_array($element->plugin,['field','textarea','calc'])) ? Text::_($elt) : $elt) . '</span>';

											if ($can_comment) {
												$comment_classes = 'comment-icon material-symbols-outlined tw-cursor-pointer tw-p-1 tw-h-fit';
												foreach ($file_comments as $comment)
												{
													if ($comment['target_id'] == $element->id && $comment['target_type'] == 'elements')
													{
														$comment_classes .= ' has-comments tw-bg-main-500 tw-text-neutral-300 tw-rounded-full';
													}
												}
												$tds .= '<span class="' . $comment_classes . '" title="' . Text::_('COM_EMUNDUS_COMMENTS_ADD_COMMENT') . '" data-target-type="elements" data-target-id="' . $element->id . '">comment</span>';
											}

											$tds .= '</td>';
											$forms .= '<tr class="' . $class . '">' . $tds . '</tr>';

											$modulo++;
											unset($params);
										}
									}
								}
							}
							$forms .= '</table>';
						}
					}
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return $e->getMessage();
		}

		return $forms;
	}


	public function getFormsPDF($aid, $fnum = 0, $fids = null, $gids = 0, $profile_id = null, $eids = null, $attachments = true, $step_types = [1])
	{
		/* COULEURS*/
		$eMConfig          = JComponentHelper::getParams('com_emundus');
		$show_empty_fields = $eMConfig->get('show_empty_fields', 1);
		$em_breaker        = $eMConfig->get('export_application_pdf_breaker', '0');

		require_once(JPATH_SITE . '/components/com_emundus/helpers/list.php');
		$h_list    = new EmundusHelperList();
		$tableuser = $h_list->getFormsList($aid, $fnum, $fids, $profile_id, $step_types);
		$forms     = '';

		if (isset($tableuser)) {
			$allowed_groups = EmundusHelperAccess::getUserFabrikGroups($this->_user->id);

			foreach ($tableuser as $key => $itemt) {
				$query = $this->_db->getQuery(true);

				$form_params = json_decode($itemt->params);
				$breaker     = ($em_breaker) ? ($key === 0) ? '' : 'class="page-break"' : '';

				$groupes = [];

				$query->clear()
					->select('ff.id, ff.group_id, fg.id, fg.label, fg.params')
					->from($this->_db->quoteName('#__fabrik_formgroup', 'ff'))
					->join('INNER', $this->_db->quoteName('#__fabrik_groups', 'fg') . ' ON (' . $this->_db->quoteName('ff.group_id') . ' = ' . $this->_db->quoteName('fg.id') . ')')
					->where($this->_db->quoteName('fg.published') . ' = 1');

				if (!empty($gids) && $gids != 0) {
					$query->where($this->_db->quoteName('fg.id') . ' IN (' . implode(',', $gids) . ')');
				}

				$query->where($this->_db->quoteName('ff.form_id') . ' = ' . $itemt->form_id)
					->order($this->_db->quoteName('ff.ordering') . ' ASC');
				try {
					$this->_db->setQuery($query);
					$groupes = $this->_db->loadObjectList();
				}
				catch (Exception $e) {
					Log::add('Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
					throw $e;
				}

				$forms .= '<h2 ' . $breaker . '>';
				if (count($groupes) > 0) {
					$forms .= '<b><h2 class="pdf-page-title">' . preg_replace('/\s+/', ' ', Text::_(trim($itemt->label))) . '</h2></b>';
				}
				$forms .= '</h2>';

				// HANDLE CASE OF EVALUATION STEP, DISPLAY THE EVALUATOR NAME
				if (!empty($itemt->step_id)) {
					$evaluation_step_query = $this->_db->getQuery(true);
					$evaluation_step_query->clear()
						->select($this->_db->quoteName('u.name'))
						->from($this->_db->quoteName($itemt->db_table_name, 't'))
						->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.id') . ' = ' . $this->_db->quoteName('t.evaluator'))
						->where($this->_db->quoteName('t.fnum') . ' = ' . $this->_db->quote($fnum))
						->andWhere($this->_db->quoteName('t.step_id') . ' = ' . $this->_db->quote($itemt->step_id));

					if (!empty($itemt->evaluation_row_id)) {
						$evaluation_step_query->andWhere($this->_db->quoteName('t.id') . ' = ' . $this->_db->quote($itemt->evaluation_row_id));
					}

					$this->_db->setQuery($evaluation_step_query);
					$evaluator_name = $this->_db->loadResult();

					$forms .= '<h3>' . Text::_('EVALUATOR') . ': ' . $evaluator_name . '</h3>';
				}

				/*-- Liste des groupes -- */
				$hidden_group_param_values = [0, '-1', '-2'];
				foreach ($groupes as $itemg) {
					$query    = $this->_db->getQuery(true);
					$g_params = json_decode($itemg->params);

					if (!EmundusHelperAccess::isAllowedAccessLevel($this->_user->id, (int) $g_params->access)) {
						continue;
					}

					if ($allowed_groups !== true && !in_array($itemg->group_id, $allowed_groups)) {
						if(!in_array($g_params->repeat_group_show_first, $hidden_group_param_values) && !empty(Text::_($itemg->label)))
						{
							$forms .= '<h3 class="group">' . Text::_($itemg->label) . '</h3>';
							$forms .= '<table style="margin: 0 18px;text-align: center;width: 95%;border: solid 1px black;">
										<thead><tr><th style="font-size: 12px; text-align: center;font-weight: 400;">' . Text::_('COM_EMUNDUS_CANNOT_SEE_GROUP') . '</th></tr></thead>
									</table>';
						}
						continue;
					}

					$elements = [];

					$query->clear()
						->select('fe.id, fe.name, fe.label, fe.plugin, fe.params, fe.default, fe.eval')
						->from($this->_db->quoteName('#__fabrik_elements', 'fe'))
						->where($this->_db->quoteName('fe.published') . ' = 1')
						->where($this->_db->quoteName('fe.hidden') . ' = 0')
						->where($this->_db->quoteName('fe.group_id') . ' = ' . $itemg->group_id);

					if (!empty($eids)) {
						$query->where($this->_db->quoteName('fe.id') . ' IN (' . implode(',', $eids) . ')');
					}

					$query->order($this->_db->quoteName('fe.ordering') . ' ASC');

					try {
						$this->_db->setQuery($query);
						$elements = $this->_db->loadObjectList();
					}
					catch (Exception $e) {
						Log::add('Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
						throw $e;
					}

					if (count($elements) > 0) {

						$asTextArea = false;
						foreach ($elements as $key => $element) {
							if ($element->plugin == 'textarea') {
								$asTextArea = true;
							}
						}

						$group_label = Text::_($itemg->label);

						if ($itemg->group_id == 14) {
							$forms .= '<table>';
							foreach ($elements as $element) {
								if (!empty($element->label) && $element->label != ' ' && !empty($element->content)) {
									$forms .= '<tbody><tr><td>' . Text::_($element->label) . '</td></tr><tbody>';
								}
							}
							$forms .= '</table>';
							// TABLEAU DE PLUSIEURS LIGNES avec moins de 7 colonnes
						}
						elseif (((int) $g_params->repeated === 1 || (int) $g_params->repeat_group_button === 1) && count($elements) < 4 && !$asTextArea) {
							//-- Entrée du tableau -- */
							$t_elt = array();
							foreach ($elements as &$element) {
								$t_elt[] = $element->name;
							}
							unset($element);

							$query->clear()
								->select('table_join')
								->from($this->_db->quoteName('#__fabrik_joins'))
								->where($this->_db->quoteName('list_id') . ' = ' . $itemt->table_id)
								->where($this->_db->quoteName('group_id') . ' = ' . $itemg->group_id)
								->where($this->_db->quoteName('table_join_key') . ' LIKE ' . $this->_db->quote('parent_id'));
							try {
								$this->_db->setQuery($query);
								$table = $this->_db->loadResult();
							}
							catch (Exception $e) {
								Log::add('Line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
								throw $e;
							}

							$check_repeat_groups = $this->checkEmptyRepeatGroups($elements, $table, $itemt->db_table_name, $fnum);

							if ($check_repeat_groups) {
								$forms .= '<h3 class="group">' . $group_label . '</h3>';
								$forms .= '<table class="pdf-forms"><thead><tr class="background"> ';
								foreach ($elements as &$element) {
									$forms .= '<th scope="col" class="background">' . Text::_($element->label) . '</th>';
								}
								unset($element);

								if ($itemg->group_id == 174) {
									$query = 'SELECT `' . implode("`,`", $t_elt) . '`, id FROM ' . $table . '
                                        WHERE parent_id=(SELECT id FROM ' . $itemt->db_table_name . ' WHERE fnum like ' . $this->_db->Quote($fnum) . ') OR applicant_id=' . $aid;
								}
								else {
									$query = 'SELECT `' . implode("`,`", $t_elt) . '`, id FROM ' . $table . '
                                    WHERE parent_id=(SELECT id FROM ' . $itemt->db_table_name . ' WHERE fnum like ' . $this->_db->Quote($fnum) . ')';
								}

								try {
									$this->_db->setQuery($query);
									$repeated_elements = $this->_db->loadObjectList();
								}
								catch (Exception $e) {
									Log::add('Line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
									throw $e;
								}

								unset($t_elt);

								$forms .= '</tr></thead><tbody>';

								// -- Ligne du tableau --
								if (count($repeated_elements) > 0) {
									foreach ($repeated_elements as $r_element) {
										$forms .= '<tr>';
										$j     = 0;

										foreach ($r_element as $key => $r_elt) {

											if ($key != 'id' && $key != 'parent_id' && isset($elements[$j])) {

												$params = json_decode($elements[$j]->params);

												if (in_array($elements[$j]->plugin,['date','jdate']) && (!empty($r_elt) && ($r_elt != '0000-00-00 00:00:00' && $r_elt != '0000-00-00'))) {
													$elt = EmundusHelperDate::displayDate($r_elt, EmundusHelperFabrik::getFabrikDateParam($elements[$j],'date_table_format'), (int) EmundusHelperFabrik::getFabrikDateParam($elements[$j],'date_store_as_local'));
												}
												elseif (($elements[$j]->plugin == 'birthday' || $elements[$j]->plugin == 'birthday_remove_slashes') && $r_elt > 0) {
													$elt = EmundusHelperDate::displayDate($r_elt, $params->list_date_format);
												}
												elseif ($elements[$j]->plugin == 'databasejoin') {
													$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

													if ($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist') {

														$query = $this->_db->getQuery(true);

														$parent_id = strlen($elements[$j]->content_id) > 0 ? $elements[$j]->content_id : 0;
														$select    = $this->getSelectFromDBJoinElementParams($params,'t');

														$query->select($select)
															->from($this->_db->quoteName($params->join_db_name, 't'))
															->leftJoin($this->_db->quoteName($itemt->db_table_name . '_' . $itemg->id . '_repeat_repeat_' . $elements[$j]->name, 'checkbox_repeat') . ' ON ' . $this->_db->quoteName('checkbox_repeat.' . $elements[$j]->name) . ' = ' . $this->_db->quoteName('t.id'))
															->leftJoin($this->_db->quoteName($itemt->db_table_name . '_' . $itemg->id . '_repeat', 'repeat_grp') . ' ON ' . $this->_db->quoteName('repeat_grp.id') . ' = ' . $this->_db->quoteName('checkbox_repeat.parent_id'))
															->where($this->_db->quoteName('checkbox_repeat.parent_id') . ' = ' . $r_element->id);

														try {
															$this->_db->setQuery($query);
															$value = $this->_db->loadColumn();
															$elt   = '<ul>';
															foreach ($value as $val) {
																$elt .= '<li>' . Text::_($val) . '</li>';
															}
															$elt .= "</ul>";
														}
														catch (Exception $e) {
															Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
															throw $e;
														}
													}
													else {
														$from  = $params->join_db_name;
														$where = $params->join_key_column . '=' . $this->_db->Quote($r_elt);
														$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;

														$query = preg_replace('#{thistable}#', $from, $query);
														$query = preg_replace('#{my->id}#', $aid, $query);
														$query = preg_replace('#{shortlang}#', $this->locales, $query);

														$this->_db->setQuery($query);
														$elt = $this->_db->loadResult();
													}
												}
												elseif ($elements[$j]->plugin == 'cascadingdropdown') {
													$cascadingdropdown_id    = $params->cascadingdropdown_id;
													$r1                      = explode('___', $cascadingdropdown_id);
													$cascadingdropdown_label = $params->cascadingdropdown_label;
													$r2                      = explode('___', $cascadingdropdown_label);
													$select                  = !empty($params->cascadingdropdown_label_concat) ? "CONCAT(" . $params->cascadingdropdown_label_concat . ")" : $r2[1];

													// Checkboxes behave like repeat groups and therefore need to be handled a second level of depth.
													if ($params->cdd_display_type == 'checkbox' || $params->cdd_display_type == 'multilist') {
														$select = !empty($params->cascadingdropdown_label_concat) ? " CONCAT(" . $params->cascadingdropdown_label_concat . ")" : 'GROUP_CONCAT(' . $r2[1] . ')';

                                                        $load_type = 'column';

														// Load the Fabrik join for the element to it's respective repeat_repeat table.
														$query = $this->_db->getQuery(true);
														$query
															->select([$this->_db->quoteName('join_from_table'), $this->_db->quoteName('table_key'), $this->_db->quoteName('table_join'), $this->_db->quoteName('table_join_key')])
															->from($this->_db->quoteName('#__fabrik_joins'))
															->where($this->_db->quoteName('element_id') . ' = ' . $elements[$j]->id);
														$this->_db->setQuery($query);
														$f_join = $this->_db->loadObject();

														$where = $r1[1] . ' IN (
                                                    SELECT ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_key) . '
                                                    FROM ' . $this->_db->quoteName($f_join->table_join) . '
                                                    WHERE ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_join_key) . ' = ' . $r_element->id . ')';
													}
													else {
                                                        $load_type = 'result';
														$where = $r1[1] . '=' . $this->_db->Quote($r_elt);
													}

													$from  = $r2[0];
													$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
													$query = preg_replace('#{thistable}#', $from, $query);
													$query = preg_replace('#{my->id}#', $aid, $query);
													$query = preg_replace('#{shortlang}#', $this->locales, $query);

													$this->_db->setQuery($query);
                                                    if ($load_type == 'column') {
                                                        $ret = $this->_db->loadColumn();
                                                    } else {
                                                        $ret = $this->_db->loadResult();
                                                    }

                                                    if (empty($ret)) {
                                                        $ret = $r_elt;
                                                    }

                                                    if ($load_type == 'column') {
                                                        $elt = '<ul>';
                                                        foreach ($ret as $val) {
                                                            $elt .= '<li>' . Text::_($val) . '</li>';
                                                        }
                                                        $elt .= '</ul>';
                                                    } else {
                                                        $elt = Text::_($ret);
                                                    }
												}
												elseif ($elements[$j]->plugin == 'checkbox') {
													$elt = "<ul><li>" . implode("</li><li>", json_decode(@$r_elt)) . "</li></ul>";
												}
												elseif ($elements[$j]->plugin == 'dropdown' || $elements[$j]->plugin == 'radiobutton') {
													$index = array_search($r_elt, $params->sub_options->sub_values);
													if (strlen($index) > 0) {
														$elt = Text::_($params->sub_options->sub_labels[$index]);
													}
													elseif (!empty($params->dropdown_populate)) {
														$elt = $r_elt;
													}
													else {
														$elt = "";
													}
												}
												elseif ($elements[$j]->plugin == 'internalid') {
													$elt = '';
												}
												elseif ($elements[$j]->plugin == 'field') {
													if ($params->password == 1) {
														$elt = '******';
													}
													elseif ($params->password == 3) {
														$elt = '<a href="mailto:' . $r_elt . '">' . $r_elt . '</a>';
													}
													elseif ($params->password == 5) {
														$elt = '<a href="' . $r_elt . '" target="_blank">' . $r_elt . '</a>';
													}
													else {
														$elt = Text::_($r_elt);
													}
												}
												elseif ($elements[$j]->plugin == 'yesno') {
													$elt = ($r_elt == 1) ? Text::_("JYES") : Text::_("JNO");
												}
												else if ($elements[$j]->plugin == 'calc') {
													$elt = $r_elt;

													$stripped = strip_tags($elt);
													if ($stripped != $elt) {
														$elt = strip_tags($elt, ['p', 'a', 'div', 'ul', 'li', 'br']);
													}
												}
												elseif ($elements[$j]->plugin == 'emundus_phonenumber') {
													$elt = substr($r_elt, 2, strlen($r_elt));
												}
												elseif ($elements[$j]->plugin == 'iban') {
													$elt = $r_elt;

													if($params->encrypt_datas == 1) {
														$elt = EmundusHelperFabrik::decryptDatas($r_elt);
													}

													$elt = chunk_split($elt, 4, ' ');
												}
												else {
													$elt = Text::_($r_elt);
												}

												// trick to prevent from blank value in PDF when string is to long without spaces (usually emails)
												$elt   = str_replace('@', '<br>@', $elt);
												$forms .= '<td class="background-light"><div id="em_training_' . $r_element->id . '" class="course ' . $r_element->id . '">' . (($elements[$j]->plugin != 'field') ? Text::_($elt) : $elt) . '</div></td>';
											}
											$j++;
										}
										$forms .= '</tr>';
									}
								}
								$forms .= '</tbody></table></p>';
							}


							// TABLEAU DE PLUSIEURS LIGNES sans tenir compte du nombre de lignes
						}
						elseif ((int) $g_params->repeated === 1 || (int) $g_params->repeat_group_button === 1) {
							//-- Entrée du tableau -- */
							$t_elt = array();
							foreach ($elements as &$element) {
								$t_elt[] = $element->name;
							}
							unset($element);

							$query = 'SELECT table_join FROM #__fabrik_joins WHERE group_id=' . $itemg->group_id . ' AND table_join_key like "parent_id"';
							$this->_db->setQuery($query);
							$table = $this->_db->loadResult();

							$check_repeat_groups = $this->checkEmptyRepeatGroups($elements, $table, $itemt->db_table_name, $fnum);

							if ($check_repeat_groups) {
								$forms .= '<h3 class="group">' . $group_label . '</h3>';

								if ($itemg->group_id == 174) {
									$query = 'SELECT `' . implode("`,`", $t_elt) . '`, id FROM ' . $table . '
                                        WHERE parent_id=(SELECT id FROM ' . $itemt->db_table_name . ' WHERE fnum like ' . $this->_db->Quote($fnum) . ') OR applicant_id=' . $aid;
								}
								else {
									$query = 'SELECT `' . implode("`,`", $t_elt) . '`, id FROM ' . $table . '
                                    WHERE parent_id=(SELECT id FROM ' . $itemt->db_table_name . ' WHERE fnum like ' . $this->_db->Quote($fnum) . ')';
								}

								$this->_db->setQuery($query);
								$repeated_elements = $this->_db->loadObjectList();
								unset($t_elt);

								// -- Ligne du tableau --
								if (count($repeated_elements) > 0) {
									$i = 1;

									foreach ($repeated_elements as $r_element) {
										$j     = 0;
										$forms .= '<p class="pdf-repeat-count">---- ' . $i . ' ----</p>';
										$forms .= '<table class="pdf-forms">';
										foreach ($r_element as $key => $r_elt) {
											$params = json_decode($elements[$j]->params);

											// Do not display elements with no value inside them.
											if (($show_empty_fields == 0 && trim($r_elt) == '') || empty($params->store_in_db)) {
												$j++;
												continue;
											}

											if ((!empty($r_elt) || $r_elt == 0) && $key != 'id' && $key != 'parent_id' && isset($elements[$j])) {

												if (in_array($elements[$j]->plugin,['date','jdate']) && (!empty($r_elt) && ($r_elt != '0000-00-00 00:00:00' && $r_elt != '0000-00-00'))) {
													$elt = EmundusHelperDate::displayDate($r_elt, EmundusHelperFabrik::getFabrikDateParam($elements[$j],'date_table_format'), (int) EmundusHelperFabrik::getFabrikDateParam($elements[$j],'date_store_as_local'));
												}
												elseif (($elements[$j]->plugin == 'birthday' || $elements[$j]->plugin == 'birthday_remove_slashes') && $r_elt > 0) {
													$elt = EmundusHelperDate::displayDate($r_elt, $params->list_date_format);
												}
												elseif ($elements[$j]->plugin == 'databasejoin') {
													$params = json_decode($elements[$j]->params);
													$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

													if ($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist') {

														$query = $this->_db->getQuery(true);

														$parent_id = strlen($elements[$j]->content_id) > 0 ? $elements[$j]->content_id : 0;
														$select    = $this->getSelectFromDBJoinElementParams($params,'t');

														$query->select($select)
															->from($this->_db->quoteName($params->join_db_name, 't'))
															->leftJoin($this->_db->quoteName($itemt->db_table_name . '_' . $itemg->id . '_repeat_repeat_' . $elements[$j]->name, 'checkbox_repeat') . ' ON ' . $this->_db->quoteName('checkbox_repeat.' . $elements[$j]->name) . ' = ' . $this->_db->quoteName('t.id'))
															->leftJoin($this->_db->quoteName($itemt->db_table_name . '_' . $itemg->id . '_repeat', 'repeat_grp') . ' ON ' . $this->_db->quoteName('repeat_grp.id') . ' = ' . $this->_db->quoteName('checkbox_repeat.parent_id'))
															->where($this->_db->quoteName('checkbox_repeat.parent_id') . ' = ' . $r_element->id);

														try {
															$this->_db->setQuery($query);
															$value = $this->_db->loadColumn();
															$elt   = '<ul>';
															foreach ($value as $val) {
																$elt .= '<li>' . Text::_($val) . '</li>';
															}
															$elt .= "</ul>";
														}
														catch (Exception $e) {
															Log::add('Line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
															throw $e;
														}
													}
													else {
														$from  = $params->join_db_name;
														$where = $params->join_key_column . '=' . $this->_db->Quote($r_elt);
														$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;

														$query = preg_replace('#{thistable}#', $from, $query);
														$query = preg_replace('#{my->id}#', $aid, $query);
														$query = preg_replace('#{shortlang}#', $this->locales, $query);

														$this->_db->setQuery($query);
														$elt = Text::_($this->_db->loadResult());
													}
												}
												elseif (@$elements[$j]->plugin == 'cascadingdropdown') {
                                                    $cascadingdropdown_id    = $params->cascadingdropdown_id;
                                                    $r1                      = explode('___', $cascadingdropdown_id);
                                                    $cascadingdropdown_label = $params->cascadingdropdown_label;
                                                    $r2                      = explode('___', $cascadingdropdown_label);
                                                    $select                  = !empty($params->cascadingdropdown_label_concat) ? "CONCAT(" . $params->cascadingdropdown_label_concat . ")" : $r2[1];

                                                    // Checkboxes behave like repeat groups and therefore need to be handled a second level of depth.
                                                    if ($params->cdd_display_type == 'checkbox' || $params->cdd_display_type == 'multilist') {
                                                        $select = !empty($params->cascadingdropdown_label_concat) ? " CONCAT(" . $params->cascadingdropdown_label_concat . ")" : 'GROUP_CONCAT(' . $r2[1] . ')';

                                                        $load_type = 'column';

                                                        // Load the Fabrik join for the element to it's respective repeat_repeat table.
                                                        $query = $this->_db->getQuery(true);
                                                        $query
                                                            ->select([$this->_db->quoteName('join_from_table'), $this->_db->quoteName('table_key'), $this->_db->quoteName('table_join'), $this->_db->quoteName('table_join_key')])
                                                            ->from($this->_db->quoteName('#__fabrik_joins'))
                                                            ->where($this->_db->quoteName('element_id') . ' = ' . $elements[$j]->id);
                                                        $this->_db->setQuery($query);
                                                        $f_join = $this->_db->loadObject();

                                                        $where = $r1[1] . ' IN (
                                                    SELECT ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_key) . '
                                                    FROM ' . $this->_db->quoteName($f_join->table_join) . '
                                                    WHERE ' . $this->_db->quoteName($f_join->table_join . '.' . $f_join->table_join_key) . ' = ' . $r_element->id . ')';
                                                    }
                                                    else {
                                                        $load_type = 'result';
                                                        $where = $r1[1] . '=' . $this->_db->Quote($r_elt);
                                                    }

                                                    $from  = $r2[0];
                                                    $query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
                                                    $query = preg_replace('#{thistable}#', $from, $query);
                                                    $query = preg_replace('#{my->id}#', $aid, $query);
                                                    $query = preg_replace('#{shortlang}#', $this->locales, $query);

                                                    $this->_db->setQuery($query);
                                                    if ($load_type == 'column') {
                                                        $ret = $this->_db->loadColumn();
                                                    } else {
                                                        $ret = $this->_db->loadResult();
                                                    }

                                                    if (empty($ret)) {
                                                        $ret = $r_elt;
                                                    }

                                                    if ($load_type == 'column') {
                                                        $elt = '';
                                                        foreach ($ret as $val) {
                                                            $elt .= Text::_($val) . '<br/>';
                                                        }
                                                    } else {
                                                        $elt = Text::_($ret);
                                                    }
												}
												elseif ($elements[$j]->plugin == 'textarea') {
													$elt = Text::_($r_elt);
												}
												elseif ($elements[$j]->plugin == 'checkbox') {
													$elm = array();
													if(is_array(json_decode($r_elt)))
													{
														if (!empty(array_filter($params->sub_options->sub_values)) && !empty($r_elt))
														{
															$index = array_intersect(json_decode($r_elt), $params->sub_options->sub_values);
														}
														else
														{
															$index = json_decode($r_elt);
														}

														foreach ($index as $value)
														{
															if (!empty(array_filter($params->sub_options->sub_values)))
															{
																$key   = array_search($value, $params->sub_options->sub_values);
																$elm[] = Text::_($params->sub_options->sub_labels[$key]);
															}
															else
															{
																$elm[] = $value;
															}
														}
													}
													$elt = '<ul>';
													foreach ($elm as $val) {
														$elt .= '<li>' . Text::_($val) . '</li>';
													}
													$elt .= "</ul>";
												}
												elseif ($elements[$j]->plugin == 'dropdown' || @$elements[$j] == 'radiobutton') {
													$params = json_decode($elements[$j]->params);
													$index  = array_search($r_elt, $params->sub_options->sub_values);
													if (strlen($index) > 0) {
														$elt = Text::_($params->sub_options->sub_labels[$index]);
													}
													elseif (!empty($params->dropdown_populate)) {
														$elt = $r_elt;
													}
													else {
														$elt = "";
													}
												}
												elseif ($elements[$j]->plugin == 'internalid') {
													$elt = '';
												}
												elseif ($elements[$j]->plugin == 'field') {
													if ($params->password == 1) {
														$elt = '******';
													}
													elseif ($params->password == 3) {
														$elt = '<a href="mailto:' . $r_elt . '">' . $r_elt . '</a>';
													}
													elseif ($params->password == 5) {
														$elt = '<a href="' . $r_elt . '" target="_blank">' . $r_elt . '</a>';
													}
													else {
														$elt = Text::_($r_elt);
													}
												}
												elseif ($elements[$j]->plugin == 'yesno') {
													$elt = ($r_elt == 1) ? Text::_("JYES") : Text::_("JNO");
												}
												elseif ($elements[$j]->plugin == 'display') {
													$elt = empty($elements[$j]->eval) ? $elements[$j]->default : $r_elt;
												}
												elseif ($elements[$j]->plugin == 'emundus_phonenumber') {
													$elt = substr($r_elt, 2, strlen($r_elt));
												}
												else {
													$elt = Text::_($r_elt);
												}

												if ($show_empty_fields == 1 || !empty($elt)) {
													if ($elements[$j]->plugin == 'display') {
														$forms .= '<tr><td colspan="2" style="background-color: var(--neutral-200);"><span style="color: #000000;">' . (!empty($params->display_showlabel) && !empty(Text::_($elements[$j]->label)) ? Text::_($elements[$j]->label) . ' : ' : '') . '</span></td></tr><tr><td colspan="2"><span style="color: #000000;">' . $elt . '</span></td></tr><br/>';
													}
													elseif ($elements[$j]->plugin == 'textarea') {
														$forms .= '</table>';
														$forms .= '<div style="width: 93.5%;padding: 8px 16px;">';
														$forms .= '<div style="width: 100%; padding: 4px 8px;background-color: #F3F3F3;color: #000000;border: solid 1px #A4A4A4;border-bottom: unset;font-size: 12px">' .  (!empty(Text::_($elements[$j]->label)) ? Text::_($elements[$j]->label) . ' : ' : '')  . '</div>';
														if (json_decode($elements[$j]->params)->use_wysiwyg == 1) {
															$forms .= '<div style="width: 100%; padding: 4px 8px;color: #000000;border: solid 1px #A4A4A4;font-size: 12px">' . preg_replace('/<br\s*\/?>/','',Text::_($elt)) . '</div>';
														} else {
															$forms .= '<div style="width: 100%; padding: 4px 8px;color: #000000;border: solid 1px #A4A4A4;font-size: 12px;word-break:break-word; hyphens:auto;">' . Text::_($elt) . '</div>';
														}
														$forms .= '</div>';
														$forms .= '<table class="pdf-forms">';
													}
													else {
														$forms .= '<tr><td colspan="1" style="background-color: var(--neutral-200);"><span style="color: #000000;">' . (!empty(Text::_($elements[$j]->label)) ? Text::_($elements[$j]->label) : '') . '</span></td> <td> ' . (($elements[$j]->plugin != 'field') ? Text::_($elt) : $elt) . '</td></tr>';
													}
												}
											}
											$j++;
										}
										$forms .= '</table>';
										$i++;
									}
								}
							}


							// AFFICHAGE EN LIGNE
						}
						else {
							$check_not_empty_group = $this->checkEmptyGroups($elements, $itemt->db_table_name, $fnum);

							if ($check_not_empty_group) {
								$forms .= '<h3 class="group">' . $group_label . '</h3>';

								$forms .= '<table class="pdf-forms">';
								foreach ($elements as $element) {
									$params = json_decode($element->params);
									if (empty($params->store_in_db)) {
										continue;
									}

									$res = [];

									if (!empty($itemt->step_id)) {
										$query = 'SELECT `id`, `' . $element->name . '` FROM `' . $itemt->db_table_name . '` WHERE fnum like ' . $this->_db->Quote($fnum);
										$query .= ' AND step_id = ' . $itemt->step_id;

										if (!empty($itemt->evaluation_row_id)) {
											$query .= ' AND id = ' . $itemt->evaluation_row_id;
										}
									} else {
										$query = 'SELECT `id`, `' . $element->name . '` FROM `' . $itemt->db_table_name . '` WHERE fnum like ' . $this->_db->Quote($fnum);
									}

									try {
										$this->_db->setQuery($query);
										$res = $this->_db->loadRow();
									}
									catch (Exception $e) {
										Log::add('Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
										throw $e;
									}

									if (is_array($res) && count($res) > 1) {
										if ($element->plugin == 'display') {
											$element->content = empty($element->eval) ? $element->default : $res[1];
										}
										else {
											$element->content = $res[1];
										}
										$element->content_id = $res[0];
									}
									else {
										$element->content    = '';
										$element->content_id = -1;
									}

									// Decrypt datas encoded
									if ($form_params->note == 'encrypted') {
										$element->content = EmundusHelperFabrik::decryptDatas($element->content,null,'aes-128-cbc',$element->plugin);
									}
									//

									if (!empty($element->content) || (isset($params->database_join_display_type) && ($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist')) || $element->plugin == 'yesno') {

										if (!empty($element->label) && $element->label != ' ' || $element->plugin === 'display') {

											if (in_array($element->plugin,['date','jdate'])) {

												// Empty date elements are set to 0000-00-00 00:00:00 in DB.
												if ($show_empty_fields == 0 && ($element->content == '0000-00-00 00:00:00' || empty($element->content))) {
													continue;
												}
												elseif (!empty($element->content) && ($element->content != '0000-00-00 00:00:00' && $element->content != '0000-00-00')) {
													$elt = EmundusHelperDate::displayDate($element->content, EmundusHelperFabrik::getFabrikDateParam($element,'date_table_format'), (int) EmundusHelperFabrik::getFabrikDateParam($element,'date_store_as_local'));
												}
												else {
													$elt = '';
												}
											}
											elseif ($element->plugin == 'textarea') {
												$elt = nl2br($element->content);
											}
											elseif (($element->plugin == 'birthday' || $element->plugin == 'birthday_remove_slashes') && $element->content > 0) {
												$elt = EmundusHelperDate::displayDate($element->content, $params->list_date_format);
											}
											elseif ($element->plugin == 'databasejoin') {
												$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

												if ($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist') {

													$query = $this->_db->getQuery(true);

													$parent_id = strlen($element->content_id) > 0 ? $element->content_id : 0;
													$select    = $this->getSelectFromDBJoinElementParams($params);

													$query->select($select)
														->from($this->_db->quoteName($itemt->db_table_name . '_repeat_' . $element->name, 't'))
														->leftJoin($this->_db->quoteName($params->join_db_name, 'jd') . ' ON ' . $this->_db->quoteName('jd.' . $params->join_key_column) . ' = ' . $this->_db->quoteName('t.' . $element->name))
														->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($parent_id));

													try {
														$this->_db->setQuery($query);
														$value = $this->_db->loadColumn();
														$elt   = '<ul>';
														foreach ($value as $val) {
															$elt .= '<li>' . Text::_($val) . '</li>';
														}
														$elt .= "</ul>";
													}
													catch (Exception $e) {
														Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
														throw $e;
													}
												}
												else {
													$from  = $params->join_db_name;
													$where = $params->join_key_column . '=' . $this->_db->Quote($element->content);
													$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;

													$query = preg_replace('#{thistable}#', $from, $query);
													$query = preg_replace('#{my->id}#', $aid, $query);
													$query = preg_replace('#{shortlang}#', $this->locales, $query);

													$this->_db->setQuery($query);
													$elt = Text::_($this->_db->loadResult());
												}
											}
											elseif ($element->plugin == 'cascadingdropdown') {
												$cascadingdropdown_id    = $params->cascadingdropdown_id;
												$r1                      = explode('___', $cascadingdropdown_id);
												$cascadingdropdown_label = $params->cascadingdropdown_label;
												$r2                      = explode('___', $cascadingdropdown_label);
												$select                  = !empty($params->cascadingdropdown_label_concat) ? "CONCAT(" . $params->cascadingdropdown_label_concat . ")" : $r2[1];
												$from                    = $r2[0];
												$where                   = $r1[1] . '=' . $this->_db->Quote($element->content);
												$query                   = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
												$query                   = preg_replace('#{thistable}#', $from, $query);
												$query                   = preg_replace('#{my->id}#', $aid, $query);
												$query                   = preg_replace('#{shortlang}#', $this->locales, $query);

												$this->_db->setQuery($query);
												$elt = Text::_($this->_db->loadResult());

											}
											elseif ($element->plugin == 'textarea') {
												$elt = Text::_($element->content);
											}
											elseif ($element->plugin == 'checkbox') {
												$params = json_decode($element->params);
												$elm    = array();
												if (!empty($element->content) && is_array(json_decode($element->content))) {
													$index = array_intersect(json_decode($element->content), $params->sub_options->sub_values);
													foreach ($index as $value) {
														$key   = array_search($value, $params->sub_options->sub_values);
														$elm[] = Text::_($params->sub_options->sub_labels[$key]);
													}
												}
												$elt = '<ul>';
												foreach ($elm as $val) {
													$elt .= '<li>' . Text::_($val) . '</li>';
												}
												$elt .= "</ul>";
											}
											elseif ($element->plugin == 'dropdown' || $element->plugin == 'radiobutton') {
												$index = array_search($element->content, $params->sub_options->sub_values);
												if (strlen($index) > 0) {
													$elt = Text::_($params->sub_options->sub_labels[$index]);
												}
												elseif ($params->multiple == 1) {
													$elt = implode(", ", json_decode(@$element->content));
												}
												elseif (!empty($params->dropdown_populate)) {
													$elt = $r_elt;
												}
												else {
													$elt = "";
												}
											}
											elseif ($element->plugin == 'internalid') {
												$elt = '';
											}
											elseif ($element->plugin == 'yesno') {
												$elt = ($element->content == 1) ? Text::_('JYES') : Text::_('JNO');
											}
											elseif ($element->plugin == 'field') {
												$params = json_decode($element->params);

												if ($params->password == 1) {
													$elt = '******';
												}
												elseif ($params->password == 3) {
													$elt = '<a href="mailto:' . $element->content . '" title="' . Text::_($element->label) . '">' . $element->content . '</a>';
												}
												elseif ($params->password == 5) {
													$elt = '<a href="' . $element->content . '" target="_blank" title="' . Text::_($element->label) . '">' . $element->content . '</a>';
												}
												else {
													$elt = $element->content;
												}
											}
											elseif ($element->plugin == 'emundus_phonenumber') {
												$elt = substr($element->content, 2, strlen($element->content));
											}
											else if ($element->plugin == 'calc') {
												$elt = $element->content;

												$stripped = strip_tags($elt);
												if ($stripped != $elt) {
													$elt = strip_tags($elt, ['p', 'a', 'div', 'ul', 'li', 'br']);
												}
											}
											elseif ($element->plugin == 'iban') {
												$params = json_decode($element->params);
												$elt = $element->content;

												if($params->encrypt_datas == 1) {
													$elt = EmundusHelperFabrik::decryptDatas($element->content);
												}

												$elt = chunk_split($elt, 4, ' ');
											}
											elseif ($element->plugin == 'booking') {
												$availability    = $element->content;
												$elt = '';

												if(!empty($availability))
												{
													$query = $this->_db->getQuery(true);
													$query->select('start_date,end_date')
														->from($this->_db->quoteName('#__emundus_setup_availabilities'))
														->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($availability));
													$this->_db->setQuery($query);
													$availability = $this->_db->loadObject();

													if(!empty($availability))
													{
														$elt = EmundusHelperDate::displayDate($availability->start_date, 'd.m.Y H:i', 0) . ' - ' . EmundusHelperDate::displayDate($availability->end_date, 'd.m.Y H:i', 0);
													}
												}
											}
											elseif ($element->plugin == 'emundus_fileupload') {
												$params = json_decode($element->params);
												$query  = $this->_db->getQuery(true);

												try {
													$query->select('esa.id,esa.value as attachment_name,eu.filename')
														->from($this->_db->quoteName('#__emundus_uploads', 'eu'))
														->leftJoin($this->_db->quoteName('#__emundus_setup_attachments', 'esa') . ' ON ' . $this->_db->quoteName('esa.id') . ' = ' . $this->_db->quoteName('eu.attachment_id'))
														->where($this->_db->quoteName('eu.fnum') . ' LIKE ' . $this->_db->quote($fnum))
														->andWhere($this->_db->quoteName('eu.attachment_id') . ' = ' . $this->_db->quote($params->attachmentId));
													$this->_db->setQuery($query);
													$attachment_upload = $this->_db->loadObject();

													if (!empty($attachment_upload->filename)) {
														$path = DS . 'images' . DS . 'emundus' . DS . 'files' . DS . $aid . DS . $attachment_upload->filename;
														$elt  = '<a href="' . $path . '" target="_blank" style="text-decoration: underline;">' . $attachment_upload->attachment_name . '</a>';
													}
													else {
														$elt = '';
													}
												}
												catch (Exception $e) {
													Log::add('component/com_emundus/models/application | Error at getting emundus_fileupload for applicant ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
													$elt = '';
												}
											}
											else {
												$elt = Text::_($element->content);
											}

											if ($element->plugin == 'display') {
												$forms .= '<tr><td colspan="2"><span style="color: #000000;">' . (!empty($params->display_showlabel) && !empty(Text::_($element->label)) ? Text::_($element->label) . ' : ' : '') . '</span></td></tr><tr><td colspan="2"><span style="color: #000000;">' . $elt . '</span></td></tr><br/>';
											}
											elseif ($element->plugin == 'textarea') {
												$forms .= '</table>';
												$forms .= '<div style="width: 93.5%;padding: 8px 16px;">';
												$forms .= '<div style="width: 100%; padding: 4px 8px;background-color: #F3F3F3;color: #000000;border: solid 1px #A4A4A4;border-bottom: unset;font-size: 12px">' .  (!empty(Text::_($element->label)) ? Text::_($element->label) . ' : ' : '')  . '</div>';
												if (json_decode($element->params)->use_wysiwyg == 1) {
													$forms .= '<div style="width: 100%; padding: 4px 8px;color: #000000;border: solid 1px #A4A4A4;font-size: 12px">' . preg_replace('/<br\s*\/?>/','',Text::_($elt)) . '</div>';
												} else {
													$forms .= '<div style="width: 100%; padding: 4px 8px;color: #000000;border: solid 1px #A4A4A4;font-size: 12px;word-break:break-word; hyphens:auto;">' . Text::_($elt) . '</div>';
												}
												$forms .= '</div>';
												$forms .= '<table class="pdf-forms">';
											}
											else {
												$forms .= '<tr><td colspan="1" style="background-color: var(--neutral-200);"><span style="color: #000000;">' . (!empty(Text::_($element->label)) ? Text::_($element->label) : '') . '</span></td> <td> ' . (!in_array($element->plugin,['field','textarea','calc']) ? Text::_($elt) : $elt) . '</td></tr>';
											}
										}
									}
									elseif (empty($element->content) && $show_empty_fields == 1) {
										if (!empty($element->label) && $element->label != ' ') {
											$forms .= '<tr><td><span style="color: #000000;">' . Text::_($element->label) . ' ' . '</span></td> <td>' . $element->content . '</td></tr>';
										}
									}
								}
								$forms .= '</table><div></div>';
							}
						}
					}
				}
				$forms .= '<p></p>';
			}
		}
		$forms .= '<p></p>';

		if ($attachments) {
			$forms        .= '<div class="page-break pdf-attachments">';
			$upload_files = $this->getCountUploadedFile($fnum, $aid, $profile_id);
			$forms        .= $upload_files;

			$list_upload_files = $this->getListUploadedFile($fnum, $aid, $profile_id);
			$forms             .= $list_upload_files;
			$forms             .= '</div>';
		}

		return $forms;
	}

	public function getFormsPDFElts($aid, $elts, $options, $checklevel = true)
	{

		$tableuser = @EmundusHelperList::getFormsListByProfileID($options['profile_id'], $checklevel);

		$forms = "<style>
					table {
					    border-spacing: 1px;
					    background-color: #f2f2f2;
					    width: 100%;
					}
					th {
					    border-spacing: 1px; color: #666666;
					}
					td {
					    border-spacing: 1px;
					    background-color: #FFFFFF;
					}
					</style>";
		if (isset($tableuser)) {
			foreach ($tableuser as $key => $itemt) {
				$forms .= ($options['show_list_label'] == 1) ? '<h2>' . Text::_($itemt->label) . '</h2>' : '';
				// liste des groupes pour le formulaire d'une table
				$query = 'SELECT ff.id, ff.group_id, fg.id, fg.label, fg.params
                            FROM #__fabrik_formgroup ff, #__fabrik_groups fg
                            WHERE ff.group_id = fg.id AND fg.published = 1 AND
                                  ff.form_id = "' . $itemt->form_id . '"
                            ORDER BY ff.ordering';

				$this->_db->setQuery($query);

				$groupes = $this->_db->loadObjectList();

				/*-- Liste des groupes -- */
				foreach ($groupes as $keyg => $itemg) {

					$g_params = json_decode($itemg->params);

					if (!EmundusHelperAccess::isAllowedAccessLevel($this->_user->id, (int) $g_params->access)) {
						continue;
					}

					// liste des items par groupe
					$query = 'SELECT fe.id, fe.name, fe.label, fe.plugin, fe.params
                                FROM #__fabrik_elements fe
                                WHERE fe.published=1 AND
                                      fe.hidden=0 AND
                                      fe.group_id = "' . $itemg->group_id . '" AND
                                      fe.id IN (' . implode(',', $elts) . ')
                                ORDER BY fe.ordering';

					$this->_db->setQuery($query);

					$elements = $this->_db->loadObjectList();

					if (count($elements) > 0) {
						$forms .= ($options['show_group_label'] == 1) ? '<h3>' . Text::_($itemg->label) . '</h3>' : '';

						foreach ($elements as &$iteme) {
							$where = $options['rowid'] > 0 ? ' id=' . $options['rowid'] : ' 1=1 ';

							if ($checklevel) {
								$where .= ' AND user=' . $aid;
							}

							$query = 'SELECT `' . $iteme->name . '` FROM `' . $itemt->db_table_name . '` WHERE ' . $where;
							$this->_db->setQuery($query);

							$iteme->content = $this->_db->loadResult();
						}
						unset($iteme);

						if ($itemg->group_id == 14) {

							foreach ($elements as $element) {
								if (!empty($element->label) && $element->label != ' ') {
									if (in_array($element->plugin,['date','jdate']) && $element->content > 0) {
										$elt = date(EmundusHelperFabrik::getFabrikDateParam($element,'date_form_format'), strtotime($element->content));
									}
									else {
										$elt = $element->content;
									}
									$forms .= '<p class="form-element"><b>' . Text::_($element->label) . ': </b>' . Text::_($elt) . '</p>';
								}
							}

							// TABLEAU DE PLUSIEURS LIGNES
						}
						elseif ((int) $g_params->repeated === 1 || (int) $g_params->repeat_group_button === 1) {
							$forms .= '<p><table class="adminlist">
                              <thead>
                              <tr> ';

							//-- Entrée du tableau -- */
							//$nb_lignes = 0;
							$t_elt = array();
							foreach ($elements as $element) {
								$t_elt[] = $element->name;
								$forms   .= '<th scope="col">' . Text::_($element->label) . '</th>';
							}
							unset($element);
							//$table = $itemt->db_table_name.'_'.$itemg->group_id.'_repeat';
							$query = 'SELECT table_join FROM #__fabrik_joins WHERE group_id=' . $itemg->group_id;
							$this->_db->setQuery($query);
							$table = $this->_db->loadResult();

							if ($itemg->group_id == 174)
								$query = 'SELECT ' . implode(",", $t_elt) . ', id FROM ' . $table . '
                                        WHERE parent_id=(SELECT id FROM ' . $itemt->db_table_name . ' WHERE user=' . $aid . ') OR applicant_id=' . $aid;
							else
								$query = 'SELECT ' . implode(",", $t_elt) . ', id FROM ' . $table . '
                                    WHERE parent_id=(SELECT id FROM ' . $itemt->db_table_name . ' WHERE user=' . $aid . ')';
							$this->_db->setQuery($query);
							$repeated_elements = $this->_db->loadObjectList();
							unset($t_elt);
							$forms .= '</tr></thead><tbody>';

							// -- Ligne du tableau --
							foreach ($repeated_elements as $r_element) {
								$forms .= '<tr>';
								$j     = 0;
								foreach ($r_element as $key => $r_elt) {
									if ($key != 'id' && $key != 'parent_id' && isset($elements[$j]) && $elements[$j]->plugin != 'display') {

										if (in_array($elements[$j]->plugin,['date','jdate'])) {
											if (!empty($elements[$j]->content) && ($r_elt != '0000-00-00 00:00:00' && $r_elt != '0000-00-00')) {
												$elt = date(EmundusHelperFabrik::getFabrikDateParam($elements[$j],'date_form_format'), strtotime($r_elt));
											}
											else {
												$elt = '';
											}
										}
										elseif (($elements[$j]->plugin == 'birthday' || $elements[$j]->plugin == 'birthday_remove_slashes') && $r_elt > 0) {
											preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $r_elt, $matches);
											if (count($matches) == 0) {
												$elt = $r_elt;
											}
											else {
												$format = json_decode($elements[$j]->params)->list_date_format;

												$d = DateTime::createFromFormat($format, $r_elt);
												if ($d && $d->format($format) == $r_elt) {
													$elt = JHtml::_('date', $r_elt, Text::_('DATE_FORMAT_LC'));
												}
												else {
													$elt = JHtml::_('date', $r_elt, $format);
												}
											}
										}
										elseif ($elements[$j]->plugin == 'databasejoin') {
											$params = json_decode($elements[$j]->params);
											$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

											if ($params->database_join_display_type == 'checkbox') {

												$query = $this->_db->getQuery(true);

												$parent_id = strlen($elements[$j]->content_id) > 0 ? $elements[$j]->content_id : 0;
												$select    = $this->getSelectFromDBJoinElementParams($params);

												$query->select($select)
													->from($this->_db->quoteName($itemt->db_table_name . '_repeat_' . $elements[$j]->name, 't'))
													->leftJoin($this->_db->quoteName($params->join_db_name, 'jd') . ' ON ' . $this->_db->quoteName('jd.' . $params->join_key_column) . ' = ' . $this->_db->quoteName('t.' . $elements[$j]->name))
													->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($parent_id));

												try {
													$this->_db->setQuery($query);
													$res = $this->_db->loadColumn();
													$elt = implode(', ', $res);
												}
												catch (Exception $e) {
													Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
													throw $e;
												}
											}
											else {
												$from  = $params->join_db_name;
												$where = $params->join_key_column . '=' . $this->_db->Quote($r_elt);
												$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
												$query = preg_replace('#{thistable}#', $from, $query);
												$query = preg_replace('#{my->id}#', $aid, $query);
												$query = preg_replace('#{shortlang}#', $this->locales, $query);

												$this->_db->setQuery($query);
												$elt = $this->_db->loadResult();
											}
										}
										elseif ($elements[$j]->plugin == 'checkbox') {
											$elt = implode(", ", json_decode(@$r_elt));
										}
										elseif ($elements[$j]->plugin == 'dropdown' || $elements[$j]->plugin == 'radiobutton') {
											$params = json_decode($elements[$j]->params);
											$index  = array_search($r_elt, $params->sub_options->sub_values);
											if (strlen($index) > 0) {
												$elt = Text::_($params->sub_options->sub_labels[$index]);
											}
											else {
												$elt = "";
											}
										}
										elseif ($elements[$j]->plugin == 'fileupload') {
											$file_path = explode('/', $r_elt);
											$filename  = end($file_path);
											$elt       = '<a href="' . JUri::base() . $elt . '" target="_blank">' . $filename . '</a>';
										}
										elseif ($elements[$j]->plugin == 'yesno') {
											$elt = ($r_elt == 1) ? Text::_("JYES") : Text::_("JNO");
										}
										else
											$elt = $r_elt;

										$forms .= '<td><div id="em_training_' . $r_element->id . '" class="course ' . $r_element->id . '">' . Text::_($elt) . '</div></td>';
									}
									$j++;
								}
								$forms .= '</tr>';
							}
							$forms .= '</tbody></table></p>';

							// AFFICHAGE EN LIGNE
						}
						else {
							foreach ($elements as $element) {
								if (!empty($element->label) && $element->label != ' ' && $element->plugin != 'display') {

									if (in_array($element->plugin,['date','jdate']) && $element->content > 0) {
										if (!empty($element->content) && ($element->content != '0000-00-00 00:00:00' && $element->content != '0000-00-00')) {
											$elt = date(EmundusHelperFabrik::getFabrikDateParam($element,'date_form_format'), strtotime($element->content));
										}
										else {
											$elt = '';
										}

									}
									elseif (($element->plugin == 'birthday' || $element->plugin == 'birthday_remove_slashes') && $element->content > 0) {
										preg_match('/([0-9]{4})-([0-9]{1,})-([0-9]{1,})/', $element->content, $matches);
										if (count($matches) == 0) {
											$elt = $element->content;
										}
										else {
											$format = json_decode($element->params)->list_date_format;

											$d = DateTime::createFromFormat($format, $element->content);
											if ($d && $d->format($format) == $element->content) {
												$elt = JHtml::_('date', $element->content, Text::_('DATE_FORMAT_LC'));
											}
											else {
												$elt = JHtml::_('date', $element->content, $format);
											}
										}
									}
									elseif ($element->plugin == 'databasejoin') {
										$params = json_decode($element->params);
										$select = !empty($params->join_val_column_concat) ? "CONCAT(" . $params->join_val_column_concat . ")" : $params->join_val_column;

										if ($params->database_join_display_type == 'checkbox') {

											$query = $this->_db->getQuery(true);

											$parent_id = strlen($element->content_id) > 0 ? $element->content_id : 0;
											$select    = $this->getSelectFromDBJoinElementParams($params);

											$query->select($select)
												->from($this->_db->quoteName($itemt->db_table_name . '_repeat_' . $element->name, 't'))
												->leftJoin($this->_db->quoteName($params->join_db_name, 'jd') . ' ON ' . $this->_db->quoteName('jd.' . $params->join_key_column) . ' = ' . $this->_db->quoteName('t.' . $element->name))
												->where($this->_db->quoteName('parent_id') . ' = ' . $this->_db->quote($parent_id));

											try {
												$this->_db->setQuery($query);
												$res = $this->_db->loadColumn();
												$elt = implode(', ', $res);
											}
											catch (Exception $e) {
												Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query, Log::ERROR, 'com_emundus');
												throw $e;
											}
										}
										else {
											$from  = $params->join_db_name;
											$where = $params->join_key_column . '=' . $this->_db->Quote($element->content);
											$query = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
											$query = preg_replace('#{thistable}#', $from, $query);
											$query = preg_replace('#{my->id}#', $aid, $query);
											$query = preg_replace('#{shortlang}#', $this->locales, $query);

											$this->_db->setQuery($query);
											$elt = $this->_db->loadResult();
										}
									}
									elseif ($element->plugin == 'cascadingdropdown') {
										$params                  = json_decode($element->params);
										$cascadingdropdown_id    = $params->cascadingdropdown_id;
										$r1                      = explode('___', $cascadingdropdown_id);
										$cascadingdropdown_label = $params->cascadingdropdown_label;
										$r2                      = explode('___', $cascadingdropdown_label);
										$select                  = !empty($params->cascadingdropdown_label_concat) ? "CONCAT(" . $params->cascadingdropdown_label_concat . ")" : $r2[1];
										$from                    = $r2[0];
										$where                   = $r1[1] . '=' . $this->_db->Quote($element->content);
										$query                   = "SELECT " . $select . " FROM " . $from . " WHERE " . $where;
										$query                   = preg_replace('#{thistable}#', $from, $query);
										$query                   = preg_replace('#{my->id}#', $aid, $query);
										$query                   = preg_replace('#{shortlang}#', $this->locales, $query);

										$this->_db->setQuery($query);
										$elt = $this->_db->loadResult();
									}
									elseif ($element->plugin == 'checkbox') {
										$elt = implode(", ", json_decode(@$element->content));
									}
									elseif ($element->plugin == 'dropdown' || $element->plugin == 'radiobutton') {
										$params = json_decode($element->params);
										$index  = array_search($element->content, $params->sub_options->sub_values);
										if (strlen($index) > 0) {
											$elt = Text::_($params->sub_options->sub_labels[$index]);
										}
										else {
											$elt = "";
										}
									}
									elseif ($element->plugin == 'fileupload') {
										$file_path = explode('/', @$element->content);
										$filename  = end($file_path);
										$elt       = '<a href="' . JUri::base() . $element->content . '" target="_blank">' . $filename . '</a>';
									}
									elseif ($element->plugin == 'yesno') {
										$elt = ($element->content == 1) ? Text::_("JYES") : Text::_("JNO");
									}
									else {
										$elt = $element->content;
									}

									$forms .= '<p class="form-element"><b>' . Text::_($element->label) . ': </b>' . Text::_($elt) . '</p>';
								}
							}
						}
						//$forms .= '</fieldset>';
					}
				}
			}
		}

		return $forms;
	}

	public function getEmail($user_id)
	{
		$query = $this->_db->getQuery(true);

		$query->select('*')
			->from($this->_db->quoteName('#__messages', 'email'))
			->leftJoin($this->_db->quoteName('#__users', 'user') . ' ON user.id=email.user_id_from')
			->leftJoin($this->_db->quoteName('#__emundus_users', 'eu') . ' ON eu.user_id=user.id')
			->where($this->_db->quoteName('email.user_id_to') . ' = ' . $this->_db->quote($user_id))
			->order($this->_db->quoteName('date_time') . ' DESC');
		$this->_db->setQuery($query);
		$results['to'] = $this->_db->loadObjectList('message_id');

		$query->clear()
			->select('*')
			->from($this->_db->quoteName('#__messages', 'email'))
			->leftJoin($this->_db->quoteName('#__users', 'user') . ' ON user.id=email.user_id_to')
			->leftJoin($this->_db->quoteName('#__emundus_users', 'eu') . ' ON eu.user_id=user.id')
			->where($this->_db->quoteName('email.user_id_from') . ' = ' . $this->_db->quote($user_id))
			->order($this->_db->quoteName('date_time') . ' DESC');
		$this->_db->setQuery($query);
		$results['from'] = $this->_db->loadObjectList('message_id');

		return $results;
	}

	public function getApplicationMenu($user_id = 0, $fnum = '')
	{
		$user_id = $user_id ?: Factory::getApplication()->getIdentity()->id;
		$juser   = JFactory::getUser($user_id);

		$menus = [];
		try {
			$query = $this->_db->createQuery();

			$grUser = $juser->getAuthorisedViewLevels();

			$query->select('id, title, link, lft, rgt, note')
				->from($this->_db->quoteName('#__menu'))
				->where($this->_db->quoteName('published') . ' = 1')
				->where($this->_db->quoteName('menutype') . ' = ' . $this->_db->quote('application'))
				->where($this->_db->quoteName('access') . ' IN (' . implode(',', $grUser) . ')')
				->order($this->_db->quoteName('lft'));
			$this->_db->setQuery($query);
			$menus = $this->_db->loadAssocList();

			if (!empty($fnum)) {
				// get menu related to workflow steps of type evaluator
				$query->clear()
					->select('esp.id')
					->from($this->_db->quoteName('#__emundus_setup_programmes', 'esp'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.training = esp.code')
					->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.campaign_id = esc.id')
					->where('ecc.fnum LIKE ' . $this->_db->quote($fnum));

				$this->_db->setQuery($query);
				$program_id = $this->_db->loadResult();

				if (!empty($program_id)) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
					$m_workflow = new EmundusModelWorkflow();
					$workflows = $m_workflow->getWorkflows([], 0, 0, [$program_id]);

					$workflow_menus = [];
					foreach($workflows as $workflow) {
						$workflow_data = $m_workflow->getWorkflow($workflow->id);

						$ccid = EmundusHelperFiles::getIdFromFnum($fnum);
						foreach($workflow_data['steps'] as $step) {
							if ($m_workflow->isEvaluationStep($step->type)) {
								$step_data = $m_workflow->getStepData($step->id);

								try {
									$step_access = EmundusHelperAccess::getUserEvaluationStepAccess($ccid, $step_data, $user_id);

									if ($step_access['can_see']) {
										$workflow_menus[] = [
											'id' => $workflow->id . $step->id,
											'title' => $step->label,
											'link' => 'evaluator-step?format=raw&step_id=' . $step->id,
											'lft' => 9998,
											'rgt' => 9999,
											'note' => '1|r',
											'hasSons' => false
										];
									}
								} catch (Exception $e) {
									Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query->__toString(), Log::ERROR, 'com_emundus');
									continue;
								}
							}
						}
					}
					foreach ($menus as $key => $menu) {
						if (str_contains($menu['link'], 'layout=attachment')) {
							$pos = $key + 1;
							break;
						}
					}

					$menus = array_merge(array_slice($menus, 0, $pos), $workflow_menus, array_slice($menus, $pos));
				}
			}
		} catch (Exception $e) {
			Log::add('line ' . __LINE__ . ' - Error in model/application at query: ' . $query->__toString(), Log::ERROR, 'com_emundus');
		}

		return $menus;
	}

	public function getProgramSynthesis($cid)
	{
		$synthesis = null;

		try {
			$query = $this->_db->getQuery(true);

			$query->select('p.synthesis, p.id, p.label')
				->from($this->_db->quoteName('#__emundus_setup_programmes', 'p'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON c.training = p.code')
				->where($this->_db->quoteName('c.id') . ' = ' . $this->_db->quote($cid));
			$this->_db->setQuery($query);
			$synthesis = $this->_db->loadObject();

			if (!empty($synthesis->synthesis)) {
				if(!class_exists('HtmlSanitizerSingleton')) {
					require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'html.php');
				}

				$sanitizer = HtmlSanitizerSingleton::getInstance();
				$synthesis->synthesis = $sanitizer->sanitize($synthesis->synthesis);
			}
		}
		catch (Exception $e) {
			$synthesis = null;
		}

		return $synthesis;
	}

	public function getAttachments($ids)
	{
		try {
			$query = "SELECT id, fnum, user_id, filename FROM #__emundus_uploads WHERE id in (" . implode(',', $ids) . ")";
			$this->_db->setQuery($query);

			return $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}
	}

	public function getAttachmentsByFnum($fnum, $ids = null, $attachment_id = null, $profile = null)
	{
		try {
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

			$m_profiles = new EmundusModelProfile;
			$m_files    = new EmundusModelFiles;
			$fnumInfos  = $m_files->getFnumInfos($fnum);

			$profiles_by_campaign = $m_profiles->getProfilesIDByCampaign([$fnumInfos['id']]);

			// TODO : Group attachments by profile and adding profile column in jos_emundus_uploads
			$query = "SELECT DISTINCT eu.*, sa.value 
                        FROM #__emundus_uploads as eu
                        LEFT JOIN #__emundus_setup_attachments as sa ON sa.id = eu.attachment_id
                        LEFT JOIN #__emundus_setup_attachment_profiles as sap ON sap.attachment_id = sa.id AND sap.profile_id IN (".implode(',',$profiles_by_campaign).")
                        WHERE fnum like " . $this->_db->quote($fnum);

			if (isset($attachment_id) && !empty($attachment_id)) {
				if (is_array($attachment_id) && $attachment_id[0] != "") {
					$query .= " AND eu.attachment_id IN (" . implode(',', $attachment_id) . ")";
				}
				else {
					$query .= " AND eu.attachment_id = " . $attachment_id;
				}
			}

			if (!empty($ids) && $ids != "null") {
				$query .= " AND eu.id in ($ids)";
			}

            $query .= " ORDER BY sap.mandatory DESC,sap.ordering,sa.value ASC";

			$this->_db->setQuery($query);
			$docs = $this->_db->loadObjectList();
		}
		catch (Exception $e) {
			error_log($e->getMessage(), 0);

			return false;
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		// Sort the docs out that are not allowed to be exported by the user.
		$allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs(JFactory::getUser()->id);
		if ($allowed_attachments !== true) {
			foreach ($docs as $key => $doc) {
				if (!in_array($doc->attachment_id, $allowed_attachments)) {
					unset($docs[$key]);
				}
			}
		}

		return $docs;
	}

	public function getAccessFnum($fnum)
	{
		$access = [];

		if (!empty($fnum)) {
			$query = "SELECT jecc.fnum, jesg.label as gname, jea.*, jesa.label as aname FROM #__emundus_campaign_candidature as jecc
                    LEFT JOIN #__emundus_setup_campaigns as jesc on jesc.id = jecc.campaign_id
                    LEFT JOIN #__emundus_setup_programmes as jesp on jesp.code = jesc.training
                    LEFT JOIN #__emundus_setup_groups_repeat_course as jesgrc on jesgrc.course = jesp.code
                    LEFT JOIN #__emundus_setup_groups as jesg on jesg.id = jesgrc.parent_id
                    LEFT JOIN #__emundus_acl as jea on jea.group_id = jesg.id
                    LEFT JOIN #__emundus_setup_actions as jesa on jesa.id = jea.action_id
                    WHERE jecc.fnum like '".$fnum."' and jesa.status = 1 order by jecc.fnum, jea.group_id, jea.action_id";

			try
			{
				$db = Factory::getContainer()->get('DatabaseDriver');
				$db->setQuery($query);
				$res = $db->loadAssocList();


				$query = $db->createQuery();
				$query->select('id, label')
					->from('#__emundus_setup_actions')
					->where('status = 1');

				$db->setQuery($query);
				$actions = $db->loadAssocList('id');

				$groups_actions = [];
				foreach($res as $r)
				{
					$access['groups'][$r['group_id']]['gname'] = $r['gname'];
					$access['groups'][$r['group_id']]['isAssoc'] = false;
					$access['groups'][$r['group_id']]['isACL'] = true;
					$access['groups'][$r['group_id']]['actions'][$r['action_id']]['aname'] = $r['aname'];
					$access['groups'][$r['group_id']]['actions'][$r['action_id']]['c'] = $r['c'];
					$access['groups'][$r['group_id']]['actions'][$r['action_id']]['r'] = $r['r'];
					$access['groups'][$r['group_id']]['actions'][$r['action_id']]['u'] = $r['u'];
					$access['groups'][$r['group_id']]['actions'][$r['action_id']]['d'] = $r['d'];

					if (isset($groups_actions[$r['group_id']]))
					{
						$groups_actions[$r['group_id']][] = $r['action_id'];
					}
					else
					{
						$groups_actions[$r['group_id']] = [$r['action_id']];
					}
				}
				$query = "SELECT jeacl.group_id, jeacl.action_id as acl_action_id, jeacl.c as acl_c, jeacl.r as acl_r, jeacl.u as acl_u, jeacl.d as acl_d,
                        jega.fnum, jega.action_id, jega.c, jega.r, jega.u, jega.d, jesa.label as aname,
                        jesg.label as gname
                        FROM jos_emundus_acl as jeacl
                        LEFT JOIN jos_emundus_setup_actions as jesa ON jesa.id = jeacl.action_id
                        LEFT JOIN jos_emundus_setup_groups as jesg on jesg.id = jeacl.group_id
                        LEFT JOIN jos_emundus_group_assoc as jega on jega.group_id=jesg.id
                        WHERE  jega.fnum like ".$db->quote($fnum)." and jesa.status = 1
                        ORDER BY jega.fnum, jega.group_id, jega.action_id";
				$db->setQuery($query);
				$res = $db->loadAssocList();
				foreach($res as $r)
				{
					$overrideAction = ($r['acl_action_id'] == $r['action_id']) ? true : false;
					if (isset($access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]))
					{
						$access['groups'][$r['group_id']]['isAssoc']                           = true;
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['c'] += ($overrideAction) ? (($r['acl_c'] == -2 || $r['c'] == -2) ? -2 : max($r['acl_c'], $r['c'])) : $r['acl_c'];
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['r'] += ($overrideAction) ? (($r['acl_r'] == -2 || $r['r'] == -2) ? -2 : max($r['acl_r'], $r['r'])) : $r['acl_r'];
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['u'] += ($overrideAction) ? (($r['acl_u'] == -2 || $r['u'] == -2) ? -2 : max($r['acl_u'], $r['u'])) : $r['acl_u'];
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['d'] += ($overrideAction) ? (($r['acl_d'] == -2 || $r['d'] == -2) ? -2 : max($r['acl_d'], $r['d'])) : $r['acl_d'];
					}
					else
					{
						$access['groups'][$r['group_id']]['gname']                                 = $r['gname'];
						$access['groups'][$r['group_id']]['isAssoc']                               = true;
						$access['groups'][$r['group_id']]['isACL']                                 = false;
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['aname'] = $r['aname'];
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['c']     = ($overrideAction) ? (($r['acl_c'] == -2 || $r['c'] == -2) ? -2 : max($r['acl_c'], $r['c'])) : $r['acl_c'];
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['r']     = ($overrideAction) ? (($r['acl_r'] == -2 || $r['r'] == -2) ? -2 : max($r['acl_r'], $r['r'])) : $r['acl_r'];
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['u']     = ($overrideAction) ? (($r['acl_u'] == -2 || $r['u'] == -2) ? -2 : max($r['acl_u'], $r['u'])) : $r['acl_u'];
						$access['groups'][$r['group_id']]['actions'][$r['acl_action_id']]['d']     = ($overrideAction) ? (($r['acl_d'] == -2 || $r['d'] == -2) ? -2 : max($r['acl_d'], $r['d'])) : $r['acl_d'];
					}
				}

				$query = $db->createQuery();

				$query->select('eua.*, ju.name as uname, jesa.label as aname')
					->from($db->quoteName('#__emundus_users_assoc', 'eua'))
					->leftJoin($db->quoteName('#__users', 'ju') . ' ON ju.id = eua.user_id')
					->leftJoin($db->quoteName('#__emundus_setup_actions', 'jesa') . ' ON jesa.id = eua.action_id')
					->where('eua.fnum LIKE ' . $db->quote($fnum))
					->andWhere('jesa.status = 1')
					->order('eua.fnum, eua.user_id, eua.action_id');

				$db->setQuery($query);
				$res = $db->loadAssocList();

				$users_actions = [];
				foreach($res as $r)
				{
					if(isset($access['groups'][$r['user_id']]['actions'][$r['action_id']])) {
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['c'] += $r['c'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['r'] += $r['r'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['u'] += $r['u'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['d'] += $r['d'];
					} else {
						$access['users'][$r['user_id']]['uname'] = $r['uname'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['aname'] = $r['aname'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['c'] = $r['c'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['r'] = $r['r'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['u'] = $r['u'];
						$access['users'][$r['user_id']]['actions'][$r['action_id']]['d'] = $r['d'];
					}

					if (isset($users_actions[$r['user_id']])) {
						$users_actions[$r['user_id']][] = $r['action_id'];
					} else {
						$users_actions[$r['user_id']] = [$r['action_id']];
					}
				}

				foreach($actions as $action_id => $action) {
					foreach($groups_actions as $group_id => $group_actions)
					{
						if (!in_array($action_id, $group_actions)) {
							$access['groups'][$group_id]['actions'][$action_id] = [
								'aname' => $action['label'],
								'c' => 0,
								'r' => 0,
								'u' => 0,
								'd' => 0
							];
						}
					}

					foreach($users_actions as $user_id => $user_actions) {
						if (!in_array($action_id, $user_actions)) {
							$access['users'][$user_id]['actions'][$action_id]['aname'] = $action['label'];
							$access['users'][$user_id]['actions'][$action_id]['c'] = 0;
							$access['users'][$user_id]['actions'][$action_id]['r'] = 0;
							$access['users'][$user_id]['actions'][$action_id]['u'] = 0;
							$access['users'][$user_id]['actions'][$action_id]['d'] = 0;
						}
					}
				}
			}
			catch(Exception $e)
			{
				error_log($e->getMessage(), 0);
			}
		}

		return $access;
	}

	public function getActions()
	{
		$actions = array();
		$query   = $this->_db->getQuery(true);

		try {
			$query->select('*')
				->from($this->_db->quoteName('#__emundus_setup_actions'));
			$this->_db->setQuery($query);
			$actions = $this->_db->loadAssocList('id');
		}
		catch (Exception $e) {
			throw $e;
		}

		return $actions;
	}

	public function checkGroupAssoc($fnum, $gid, $aid = null)
	{

		try {
			if (!is_null($aid)) {
				$query = "select * from #__emundus_group_assoc where `action_id` = $aid and  `group_id` = $gid and `fnum` like " . $this->_db->quote($fnum);
			}
			else {
				$query = "select * from #__emundus_group_assoc where `group_id` = $gid and `fnum` like " . $this->_db->quote($fnum);
			}
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function updateGroupAccess($fnum, $gid, $actionId, $crud, $value)
	{

		try {
			if ($this->checkGroupAssoc($fnum, $gid) !== null) {
				if ($this->checkGroupAssoc($fnum, $gid, $actionId) !== null) {
					$query = "update #__emundus_group_assoc set " . $this->_db->quoteName($crud) . " = " . $value .
						" where `group_id` = $gid and `action_id` = $actionId and `fnum` like " . $this->_db->quote($fnum);
					$this->_db->setQuery($query);

					return $this->_db->execute();
				}
				else {
					return $this->_addGroupAssoc($fnum, $crud, $actionId, $gid, $value);
				}
			}
			else {
				return $this->_addGroupAssoc($fnum, $crud, $actionId, $gid, $value);
			}
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	private function _addGroupAssoc($fnum, $crud, $aid, $gid, $value)
	{

		$actionQuery = "select c, r, u, d from #__emundus_acl where action_id = $aid  and  group_id = $gid";
		$this->_db->setQuery($actionQuery);
		$actions        = $this->_db->loadAssoc();
		$actions[$crud] = $value;
		$query          = "INSERT INTO `#__emundus_group_assoc`(`group_id`, `action_id`, `fnum`, `c`, `r`, `u`, `d`) VALUES ($gid, $aid, " . $this->_db->quote($fnum) . ",{$actions['c']}, {$actions['r']}, {$actions['u']}, {$actions['d']})";
		$this->_db->setQuery($query);

		return $this->_db->execute();
	}

	public function checkUserAssoc($fnum, $uid, $aid = null)
	{

		try {
			if (!is_null($aid)) {
				$query = "select * from #__emundus_users_assoc where `action_id` = $aid and  `user_id` = $uid and `fnum` like " . $this->_db->quote($fnum);
			}
			else {
				$query = "select * from #__emundus_users_assoc where `user_id` = $uid and `fnum` like " . $this->_db->quote($fnum);
			}
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	private function _addUserAssoc($fnum, $crud, $aid, $uid, $value)
	{
		$inserted = false;

		try {
			$actionQuery = "select jea.c, jea.r, jea.u, jea.d from #__emundus_acl as jea left join #__emundus_groups as jeg on jeg.group_id = jea.group_id
        where jea.action_id = {$aid}  and jeg.user_id  = {$uid}";
			$this->_db->setQuery($actionQuery);
			$actions     = $this->_db->loadAssoc();
			$actionQuery = "select jega.c, jega.r, jega.u, jega.d from #__emundus_group_assoc as jega left join #__emundus_groups as jeg on jeg.group_id = jega.group_id
        where jega.action_id = {$aid} and jeg.user_id  = {$uid} and jega.fnum like {$this->_db->quote($fnum)}";
			$this->_db->setQuery($actionQuery);
			$actionAssoc = $this->_db->loadAssoc();
			if (!empty($actionAssoc)) {
				$actions['c'] += $actionAssoc['c'];
				$actions['r'] += $actionAssoc['r'];
				$actions['u'] += $actionAssoc['u'];
				$actions['d'] += $actionAssoc['d'];
			}
			$actions[$crud] = $value;
			$query          = "INSERT INTO `#__emundus_users_assoc`(`user_id`, `action_id`, `fnum`, `c`, `r`, `u`, `d`) VALUES ($uid, $aid, " . $this->_db->quote($fnum) . ",{$actions['c']}, {$actions['r']}, {$actions['u']}, {$actions['d']})";
			$this->_db->setQuery($query);

			$inserted = $this->_db->execute();
		} catch (Exception $e) {
			Log::add('Failed to add user assoc : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $inserted;
	}

	public function updateUserAccess($fnum, $uid, $actionId, $crud, $value)
	{

		try {
			if ($this->checkUserAssoc($fnum, $uid) !== null) {
				if ($this->checkUserAssoc($fnum, $uid, $actionId) !== null) {
					$query = "update #__emundus_users_assoc set " . $this->_db->quoteName($crud) . " = " . $value .
						" where `user_id` = $uid and `action_id` = $actionId and `fnum` like " . $this->_db->quote($fnum);
					$this->_db->setQuery($query);

					return $this->_db->execute();
				}
				else {
					return $this->_addUserAssoc($fnum, $crud, $actionId, $uid, $value);
				}
			}
			else {
				return $this->_addUserAssoc($fnum, $crud, $actionId, $uid, $value);
			}
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * @param $fnum string
	 * @param $gid int
	 * @param $current_user int If null, the current user will be used
	 * @return false|mixed
	 */
	public function deleteGroupAccess($fnum, $gid, $current_user = null)
	{
		$deleted = false;

		if (!empty($fnum) && !empty($gid)) {
			if (empty($current_user)) {
				$current_user = Factory::getApplication()->getIdentity()->id;
			}

			$query = $this->_db->getQuery(true);

			$query->delete('#__emundus_group_assoc')
				->where($this->_db->quoteName('group_id') . ' = ' . $gid)
				->andWhere($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));

			try {
				$this->_db->setQuery($query);
				$deleted = $this->_db->execute();
			} catch (Exception $e) {
				Log::add('Error in model/application at query: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}

			if ($deleted) {
				$query->clear()
					->select('label')
					->from('#__emundus_setup_groups')
					->where('id = ' . $gid);

				$this->_db->setQuery($query);
				$label = $this->_db->loadResult();

                if (!class_exists('EmundusModelFiles')) {
                    require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
                }
                $m_files = new EmundusModelFiles;
                $fnumInfos = $m_files->getFnumInfos($fnum);

				$logsParams = ['deleted' => ['details' => $label]];
				EmundusModelLogs::log($current_user, $fnumInfos['applicant_id'], $fnum, 11, 'd', 'COM_EMUNDUS_ACCESS_ACCESS_FILE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
			}
		}

		return $deleted;
	}

	//TODO: Add the deleteGroupsAccess function here (multiple groups, if no id provided all groups of fnum

	/**
	 * @param $fnum string
	 * @param $uid int
	 * @param $current_user int if null, the current user will be used
	 * @return false|mixed
	 */
	public function deleteUserAccess($fnum, $uid, $current_user = null)
	{
		$deleted = false;

		if (!empty($fnum) && !empty($uid)) {
			if (empty($current_user)) {
				$current_user = Factory::getApplication()->getIdentity()->id;
			}

			$query = $this->_db->getQuery(true);

			$query->delete('#__emundus_users_assoc')
				->where($this->_db->quoteName('user_id') . ' = ' . $uid)
				->andWhere($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));

			try {
				$this->_db->setQuery($query);
				$deleted = $this->_db->execute();
			} catch (Exception $e) {
				Log::add('Error in model/application at query: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}

			if ($deleted) {
				$query->clear()
					->select('name')
					->from('#__users')
					->where('id = ' . $uid);

				$this->_db->setQuery($query);
				$user_name = $this->_db->loadResult();

                if (!class_exists('EmundusModelFiles')) {
                    require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
                }
                $m_files = new EmundusModelFiles;
                $fnumInfos = $m_files->getFnumInfos($fnum);

				$logsParams = ['deleted' => ['details' => $user_name]];
				EmundusModelLogs::log($current_user, $fnumInfos['applicant_id'], $fnum, 11, 'd', 'COM_EMUNDUS_ACCESS_ACCESS_FILE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
			}
		}

		return $deleted;
	}

	public function getApplications($uid)
	{

		try {
			$query = 'SELECT ecc.*, esc.*, ess.step, ess.value, ess.class
                        FROM #__emundus_campaign_candidature AS ecc
                        LEFT JOIN #__emundus_setup_campaigns AS esc ON esc.id=ecc.campaign_id
                        LEFT JOIN #__emundus_setup_status AS ess ON ess.step=ecc.status
                        WHERE ecc.applicant_id =' . $uid . '
                        ORDER BY esc.end_date DESC';
			$this->_db->setQuery($query);
			$result = $this->_db->loadObjectList('fnum');

			return (array) $result;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function getApplication($fnum)
	{
		$result = null;
		$query  = $this->_db->getQuery(true);

		try {
			$query->select('ecc.*, esc.*, ess.step, ess.value, ess.class, esp.id as prog_id, esp.color as tag_color, esp.label as prog_label')
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.id=ecc.campaign_id')
				->leftJoin($this->_db->quoteName('#__emundus_setup_status', 'ess') . ' ON ess.step=ecc.status')
				->leftJoin($this->_db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON esc.training = esp.code')
				->where($this->_db->quoteName('ecc.fnum') . ' like ' . $this->_db->quote($fnum))
				->order($this->_db->quoteName('esc.end_date') . ' DESC');
			$this->_db->setQuery($query);
			$result = $this->_db->loadObject();
		}
		catch (Exception $e) {
			throw $e;
		}

		return $result;
	}

	/**
	 * Return the order for current fnum. If an order with confirmed status is found for fnum campaign period, then return the order
	 * If $sent is sent to true, the function will search for orders with a status of 'created' and offline paiement methode
	 *
	 * @param         $fnumInfos  $sent
	 * @param   bool  $cancelled
	 *
	 * @return bool|object
	 */
	public function getHikashopOrder($fnumInfos, $cancelled = false, $confirmed = true)
	{
		$eMConfig = ComponentHelper::getParams('com_emundus');

		require_once(JPATH_SITE.'/components/com_emundus/models/campaign.php');
		$m_campaign = new EmundusModelCampaign;

		$prog_id = $m_campaign->getProgrammeByTraining($fnumInfos['training'])->id;

		$query = $this->_db->getQuery(true);

		/* First determine the program the user is applying to is in the emundus_hikashop_programs */
		$query
			->select('hp.id')
			->from($this->_db->quoteName('#__emundus_hikashop_programs', 'hp'))
			->leftJoin($this->_db->quoteName('jos_emundus_hikashop_programs_repeat_code_prog', 'hpr') . ' ON ' . $this->_db->quoteName('hpr.parent_id') . ' = ' . $this->_db->quoteName('hp.id'))
			->where($this->_db->quoteName('hpr.code_prog') . ' = ' . $this->_db->quote($prog_id));
		$this->_db->setQuery($query);
		$rule = $this->_db->loadResult();

		/* If we find a row, we use the emundus_hikashop_programs, otherwise we use the eMundus config */
		$em_application_payment = isset($rule) ? 'programmes' : $eMConfig->get('application_payment', 'user');

		$order_status = array();
		if ($cancelled) {
			$order_status[] = 'cancelled';
		}
		else {
			if ($confirmed) {
				$order_status[] = 'confirmed';
			}
			switch ($eMConfig->get('accept_other_payments', 0)) {
				case 1:
					$order_status[] = 'created';
					break;
				case 3:
					$order_status[] = 'pending';
					break;
				case 4:
					array_push($order_status, 'created', 'pending');
					break;
				default:
					// No need to push to the array
					break;

			}
		}

		$query
			->clear()
			->select(['ho.*', $this->_db->quoteName('eh.user', 'user_cms_id')])
			->from($this->_db->quoteName('#__emundus_hikashop', 'eh'))
			->leftJoin($this->_db->quoteName('#__hikashop_order', 'ho') . ' ON ' . $this->_db->quoteName('ho.order_id') . ' = ' . $this->_db->quoteName('eh.order_id'))
			->where($this->_db->quoteName('ho.order_status') . ' IN (' . implode(", ", $this->_db->quote($order_status)) . ')')
			->order($this->_db->quoteName('order_created') . ' DESC');

		switch ($em_application_payment) {

			default :
			case 'fnum' :
				$query
					->where($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				break;

			case 'user' :
				$query
					->where($this->_db->quoteName('eh.user') . ' = ' . $this->_db->quote($fnumInfos['applicant_id']));
				break;

			case 'campaign' :
				$query
					->where($this->_db->quoteName('eh.campaign_id') . ' = ' . $this->_db->quote($fnumInfos['id']))
					->where($this->_db->quoteName('eh.user') . ' = ' . $this->_db->quote($fnumInfos['applicant_id']));
				break;

			case 'status' :
				$em_application_payment_status = $eMConfig->get('application_payment_status', '0');
				$payment_status                = explode(',', $em_application_payment_status);

				if (in_array($fnumInfos['status'], $payment_status)) {
					$query
						->where($this->_db->quoteName('eh.status') . ' = ' . $this->_db->quote($fnumInfos['status']))
						->where($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				}
				else {
					$query
						->where($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				}
				break;

			case 'programmes' :
				/* By using the parent_id from the emundus_hikashop_programs table, we can get the list of the other programs that use the same settings */
				/* We check only those with a payment_type of 2, for the others it's one payment by file */
				$hika_query = $this->_db->getQuery(true);
				$hika_query->select('hpr.id_prog')
					->from($this->_db->quoteName('#__emundus_hikashop_programs_repeat_id_prog', 'hpr'))
					->leftJoin($this->_db->quoteName('#__emundus_hikashop_programs', 'hp') . ' ON ' . $this->_db->quoteName('hpr.parent_id') . ' = ' . $this->_db->quoteName('hp.id'))
					->where($this->_db->quoteName('hpr.parent_id') . ' = ' . $this->_db->quote($rule))
					->andWhere($this->_db->quoteName('hp.payment_type') . ' = ' . $this->_db->quote(2));
				$this->_db->setQuery($hika_query);
				$progs_to_check = $this->_db->loadColumn();

				/* If there are programs, we must check if there was a payment on one of the campaigns this year */
				if (!empty($progs_to_check)) {
					$fnum_query = $this->_db->getQuery(true);
					/* Get the list of the candiate's files that are in the list of programs in the year*/
					$fnum_query->select('cc.fnum')
						->from($this->_db->quoteName('#__emundus_campaign_candidature', 'cc'))
						->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->_db->quoteName('sc.id') . ' = ' . $this->_db->quoteName('cc.campaign_id'))
						->leftJoin($this->_db->quoteName('#__emundus_setup_programmes','sp').' ON '.$this->_db->quoteName('sc.training').' = '.$this->_db->quoteName('sp.code'))
						->where($this->_db->quoteName('sp.id').' IN ('.implode(',',$this->_db->quote($progs_to_check)) . ')')
						->andWhere($this->_db->quoteName('sc.year') . ' = ' . $this->_db->quote($fnumInfos['year']))
						->andWhere($this->_db->quoteName('cc.applicant_id') . ' = ' . $this->_db->quote($fnumInfos['applicant_id']));
					$this->_db->setQuery($fnum_query);
					$program_year_fnum = $this->_db->loadColumn();
				}

				/* If we find another file in the list of programs during the same year, we can determine that he's already paid*/
				if (!empty($program_year_fnum)) {
					$query
						->where($this->_db->quoteName('eh.fnum') . ' IN (' . implode(',', $program_year_fnum) . ')');
				}
				else {
					$query
						->where($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				}
				break;
		}
		try {
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getHikashopCartOrder($fnumInfos, $cancelled = false, $confirmed = true)
	{
		$eMConfig = JComponentHelper::getParams('com_emundus');


		$query = $this->_db->getQuery(true);

		$em_application_payment = $eMConfig->get('application_payment', 'user');

		$query
			->select('eh.user,eh.cart_id')
			->from($this->_db->quoteName('#__emundus_hikashop', 'eh'))
			->where($this->_db->quoteName('eh.order_id') . ' = 0' . ' OR ' . $this->_db->quoteName('eh.order_id') . ' IS NULL');

		switch ($em_application_payment) {
			default :
			case 'fnum' :
				$query
					->andWhere($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				break;

			case 'user' :
				$query
					->andWhere($this->_db->quoteName('eh.user') . ' = ' . $this->_db->quote($fnumInfos['applicant_id']));
				break;

			case 'campaign' :
				$query
					->andWhere($this->_db->quoteName('eh.campaign_id') . ' = ' . $this->_db->quote($fnumInfos['id']))
					->andWhere($this->_db->quoteName('eh.user') . ' = ' . $this->_db->quote($fnumInfos['applicant_id']));
				break;

			case 'status' :
				$em_application_payment_status = $eMConfig->get('application_payment_status', '0');
				$payment_status                = explode(',', $em_application_payment_status);

				if (in_array($fnumInfos['status'], $payment_status)) {
					$query
						->andWhere($this->_db->quoteName('eh.status') . ' = ' . $this->_db->quote($fnumInfos['status']))
						->andWhere($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				}
				else {
					$query
						->andWhere($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				}
				break;
		}

		try {
			$this->_db->setQuery($query);
			$cart_pending = $this->_db->loadObject();

			if (!empty($cart_pending)) {
				return null;
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		$order_status = array();
		if ($cancelled) {
			array_push($order_status, 'cancelled');
		}
		else {
			if ($confirmed) {
				array_push($order_status, 'confirmed');
			}
			switch ($eMConfig->get('accept_other_payments', 0)) {
				case 1:
					array_push($order_status, 'created');
					break;
				case 3:
					array_push($order_status, 'pending');
					break;
				case 4:
					array_push($order_status, 'created', 'pending');
					break;
				default:
					// No need to push to the array
					break;

			}
		}

		$query->clear()
			->select(['ho.*', $this->_db->quoteName('eh.user', 'user_cms_id')])
			->from($this->_db->quoteName('#__emundus_hikashop', 'eh'))
			->leftJoin($this->_db->quoteName('#__hikashop_order', 'ho') . ' ON ' . $this->_db->quoteName('ho.order_id') . ' = ' . $this->_db->quoteName('eh.order_id'))
			->where($this->_db->quoteName('ho.order_status') . ' IN (' . implode(", ", $this->_db->quote($order_status)) . ')')
			->order($this->_db->quoteName('order_created') . ' DESC');

		switch ($em_application_payment) {

			default :
			case 'fnum' :
				$query
					->where($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				break;

			case 'user' :
				$query
					->where($this->_db->quoteName('eh.user') . ' = ' . $fnumInfos['applicant_id']);
				break;

			case 'campaign' :
				$query
					->where($this->_db->quoteName('eh.campaign_id') . ' = ' . $fnumInfos['id'])
					->where($this->_db->quoteName('eh.user') . ' = ' . $fnumInfos['applicant_id']);
				break;

			case 'status' :
				$em_application_payment_status = $eMConfig->get('application_payment_status', '0');
				$payment_status                = explode(',', $em_application_payment_status);

				if (in_array($fnumInfos['status'], $payment_status)) {
					$query
						->where($this->_db->quoteName('eh.status') . ' = ' . $fnumInfos['status'])
						->where($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				}
				else {
					$query
						->where($this->_db->quoteName('eh.fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));
				}
				break;
		}
		try {
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getHikashopCart($fnumInfos)
	{

		$query = $this->_db->getQuery(true);

		$query
			->select('cart_id')
			->from($this->_db->quoteName('#__emundus_hikashop'))
			->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnumInfos['fnum']));

		try {
			$this->_db->setQuery($query);

			return $this->_db->loadResult();
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Return the checkout URL order for current fnum.
	 *
	 * @param $pid   string|int   the applicant's profile_id
	 *
	 * @return bool|string
	 */
	public function getHikashopCheckoutUrl($pid)
	{

		try {
			$query = 'SELECT CONCAT(link, "&Itemid=", id) as url
                        FROM #__menu
                        WHERE alias like "checkout' . $pid . '" and published = 1';
			$this->_db->setQuery($query);
			$url = $this->_db->loadResult();

			if (empty($url)) {
				$query = 'SELECT CONCAT(m.link, "&Itemid=", m.id) as url
                    FROM #__menu m
                    LEFT JOIN #__emundus_setup_profiles esp on esp.menutype = m.menutype
                    WHERE m.alias like "checkout%" and m.published = 1 and esp.id = '.$pid;
				$this->_db->setQuery($query);
				$url = $this->_db->loadResult();
			}


			return $url;
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Return the checkout URL order for current fnum.
	 *
	 * @param $pid   string|int   the applicant's profile_id
	 *
	 * @return bool|string
	 */
	public function getHikashopCartUrl($pid)
	{

		try {
			$query = 'SELECT CONCAT(link, "&Itemid=", id) as url
                        FROM #__menu
                        WHERE alias like "cart' . $pid . '"';
			$this->_db->setQuery($query);
			$url = $this->_db->loadResult();

			return $url;
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/**
	 * Move an application file from one programme to another
	 *
	 * @param         $fnum_from String the fnum of the source
	 * @param         $fnum_to   String the fnum of the moved application
	 * @param         $campaign  String the programme id to move the file to
	 * @param   null  $status
	 *
	 * @return bool
	 */
	public function moveApplication(string $fnum_from, string $fnum_to, $campaign, $status = null, $params = array()): bool
	{

		$query = $this->_db->getQuery(true);

		try {

			$query->clear()
				->select('*')
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum_from));
			$this->_db->setQuery($query);
			$cc_line = $this->_db->loadAssoc();

			if (!empty($cc_line)) {

				$fields = [
					$this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum_to),
					$this->_db->quoteName('campaign_id') . ' = ' . $this->_db->quote($campaign),
					$this->_db->quoteName('copied') . ' = 2'
				];

				if (!empty($status)) {
					$fields[] = $this->_db->quoteName('status') . ' = ' . $this->_db->quote($status);
				}

				$query->clear()
					->update($this->_db->quoteName('#__emundus_campaign_candidature'))
					->set($fields)
					->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($cc_line['id']));

				$this->_db->setQuery($query);
				$this->_db->execute();

				JPluginHelper::importPlugin('emundus');
				JFactory::getApplication()->triggerEvent('onCallEventHandler', array(
						'onAfterMoveApplication',
						array(
							'fnum_from'   => $fnum_from,
							'fnum_to'     => $fnum_to,
							'campaign_id' => $campaign,
							'params'      => $params)
					)
				);

				return true;

			}
			else {
				return false;
			}

		}
		catch (Exception $e) {

			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Duplicate an application file (form data)
	 *
	 * @param $fnum_from String the fnum of the source
	 * @param $fnum_to   String the fnum of the duplicated application
	 * @param $pid       Int the profile_id to get list of forms
	 *
	 * @return bool
	 */
	public function copyApplication($fnum_from, $fnum_to, $pid = null, $copy_attachment = 0, $campaign_id = null, $copy_tag = 0, $move_hikashop_command = 0, $delete_from_file = 0, $params = array(), $copyUsersAssoc = 0, $copyGroupsAssoc = 0)
	{

		$pids = [];

		try {
			$divergent_users = false;
			$m_profiles      = new EmundusModelProfile();
			$fnumInfos       = $m_profiles->getFnumDetails($fnum_from);
			$fnumToInfos     = $m_profiles->getFnumDetails($fnum_to);

			if ($fnumInfos['applicant_id'] !== $fnumToInfos['applicant_id']) {
				$divergent_users = true;
			}

			if (!empty($campaign_id)) {
				$pids = $m_profiles->getProfilesIDByCampaign((array) $campaign_id);
			}

			if (empty($pid) && empty($campaign_id)) {
				$pids[] = (!empty($fnumInfos['profile_id_form'])) ? $fnumInfos['profile_id_form'] : $fnumInfos['profile_id'];
			}
			elseif (!empty($pid)) {
				$pids[] = $pid;
			}

			$forms = array();
			foreach ($pids as $profile) {
				$menus = EmundusHelperMenu::buildMenuQuery($profile);
				foreach ($menus as $menu) {
					$forms[] = $menu;
				}
			}

			$tempArray = array_unique(array_column($forms, 'db_table_name'));
			$forms     = array_values(array_intersect_key($forms, $tempArray));

			foreach ($forms as $form) {
				$query = $this->_db->getQuery(true);

				$query->clear()
					->select('*')
					->from($this->_db->quoteName($form->db_table_name))
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum_from));
				$this->_db->setQuery($query);
				$stored = $this->_db->loadAssoc();

				if (!empty($stored)) {
					$parent_id = $stored['id'];
					unset($stored['id']);
					$stored['fnum'] = $fnum_to;
					$q              = 1;


					foreach ($stored as $key => $value) {
						if(is_null($value)) {
							unset($stored[$key]);
						}
						if ($divergent_users && $key === 'user' && $value == $fnumInfos['applicant_id']) {
							$stored[$key] = $fnumToInfos['applicant_id'];
						}
					}

					$query->clear()
						->insert($this->_db->quoteName($form->db_table_name))
						->columns(array_keys($stored))
						->values(implode(',', $this->_db->quote($stored)));
					$this->_db->setQuery($query);
					$this->_db->execute();
					$id = $this->_db->insertid();

					// liste des groupes pour le formulaire d'une table
					$query->clear()
						->select('ff.id, ff.group_id, fe.name, fg.id, fg.label, (IF( ISNULL(fj.table_join), fl.db_table_name, fj.table_join)) as `table`, fg.params as `gparams`')
						->from($this->_db->quoteName('#__fabrik_formgroup', 'ff'))
						->leftJoin($this->_db->quoteName('#__fabrik_lists', 'fl') . ' ON fl.form_id=ff.form_id')
						->leftJoin($this->_db->quoteName('#__fabrik_groups', 'fg') . ' ON fg.id=ff.group_id')
						->leftJoin($this->_db->quoteName('#__fabrik_elements', 'fe') . ' ON fe.group_id=fg.id')
						->leftJoin($this->_db->quoteName('#__fabrik_joins', 'fj') . ' ON (fj.group_id = fe.group_id AND fj.list_id != 0 AND fj.element_id = 0)')
						->where($this->_db->quoteName('ff.form_id') . ' = ' . $this->_db->quote($form->form_id))
						->where($this->_db->quoteName('fe.published') . ' = 1')
						->order($this->_db->quoteName('ff.ordering'));
					$q     = 2;
					$this->_db->setQuery($query);
					$groups = $this->_db->loadObjectList();

					// get data and update current form
					$data = array();
					if (count($groups) > 0) {
						foreach ($groups as $group) {
							$group_params = json_decode($group->gparams);
							if ($group_params->repeat_group_button == 1) {
								$data[$group->group_id]['repeat_group']   = $group_params->repeat_group_button;
								$data[$group->group_id]['group_id']       = $group->group_id;
								$data[$group->group_id]['element_name'][] = $group->name;
								$data[$group->group_id]['table']          = $group->table;
							}
						}

						if (count($data) > 0) {
							foreach ($data as $d) {
								$q     = 3;
								$query = 'SELECT ' . implode(',', $this->_db->quoteName($d['element_name'])) . ' FROM ' . $d['table'] . ' WHERE parent_id=' . $parent_id;
								$this->_db->setQuery($query);
								$stored = $this->_db->loadAssocList();

								if (count($stored) > 0) {
									$arrayValue = [];

									foreach ($stored as $rowvalues) {
										unset($rowvalues['id']);
										$rowvalues['parent_id'] = $id;
										$arrayValue[]           = '(' . implode(',', $this->_db->quote($rowvalues)) . ')';
									}
									unset($stored[0]['id']);
									$q = 4;

									// update form data
									$query = 'INSERT INTO ' . $d['table'] . ' (`' . implode('`,`', array_keys($stored[0])) . '`)' . ' VALUES ' . implode(',', $arrayValue);
									$this->_db->setQuery($query);
									$this->_db->execute();
								}
							}
						}
					}
				}
			}

			// sync documents uploaded
			// 1. get list of uploaded documents for previous file defined as duplicated
			if ($copy_attachment) {
				$query = $this->_db->getQuery(true);

				$query->select('jeu.*, jsa.lbl')
					->from('#__emundus_uploads AS jeu')
					->leftJoin('#__emundus_setup_attachments AS jsa ON jsa.id=jeu.attachment_id')
					->where('jeu.fnum LIKE ' . $this->_db->quote($fnum_from));

				$this->_db->setQuery($query);

				$documents = [];
				try {
					$documents = $this->_db->loadAssocList();
				}
				catch (Exception $e) {
					Log::add('Error getting documents for fnum ' . $fnum_from . ' in emundus model application at query ' . $query, Log::ERROR, 'com_emundus');
				}

				if (!empty($documents)) {
					foreach ($documents as $document) {
						$file_ext = pathinfo($document['filename'], PATHINFO_EXTENSION);
						$new_file = $fnumToInfos['applicant_id'] . '-' . $campaign_id . '-' . trim($document['lbl'], ' _') . '-' . rand() . '.' . $file_ext;

						// try to copy file with new name
						$copied = copy(JPATH_SITE . DS . "images/emundus/files" . DS . $fnumInfos['applicant_id'] . DS . $document['filename'], JPATH_SITE . DS . "images/emundus/files" . DS . $fnumToInfos['applicant_id'] . DS . $new_file);
						if (!$copied) {
							Log::add("La copie " . $document['file'] . " du fichier a échoué...\n", Log::ERROR, 'com_emundus');
						}

						$document['user_id']      = $fnumToInfos['applicant_id'];
						$document['filename']     = $new_file;
						$document['fnum']         = $fnum_to;
						$document['is_validated'] = empty($document['is_validated']) ? '-2' : $document['is_validated'];
						$document['modified_by']  = empty($document['modified_by']) ? $document['user_id'] : $document['modified_by'];
						unset($document['id']);
						unset($document['lbl']);

						try {
							$query->clear();
							$query->insert($this->_db->quoteName('#__emundus_uploads'))
								->columns(array_keys($document))
								->values(implode(", ", $this->_db->quote($document)));

							$this->_db->setQuery($query);
							$this->_db->execute();

						}
						catch (Exception $e) {
							Log::add('Error inserting document in jos_emundus_uploads table for fnum ' . $fnum_to . ' : ' . $e, Log::ERROR, 'com_emundus');
						}
					}
				}
			}

			if ($copy_tag) {
				$query = $this->_db->getQuery(true);
				$query->select('*')
					->from($this->_db->quoteName('#__emundus_tag_assoc'))
					->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum_from));
				$this->_db->setQuery($query);
				$tags_assoc_rows = $this->_db->loadAssocList();
				if (count($tags_assoc_rows) > 0) {
					foreach ($tags_assoc_rows as $key => $row) {
						$query->clear()
							->insert($this->_db->quoteName('#__emundus_tag_assoc'))
							->set($this->_db->quoteName('id_tag') . ' = ' . $row['id_tag'])
							->set($this->_db->quoteName('user_id') . ' = ' . $row['user_id'])
							->set($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum_to))
							->set($this->_db->quoteName('date_time') . ' = ' . $this->_db->quote($row['date_time']));
						$this->_db->setQuery($query);
						$this->_db->execute();

					}
				}
			}

			if ($move_hikashop_command) {
				$query = $this->_db->getQuery(true);
				$query->update($this->_db->quoteName('#__emundus_hikashop'))
					->set($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum_to))
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum_from));
				$this->_db->setQuery($query);
				$this->_db->execute();
			}

			if ($delete_from_file) {
				$query = $this->_db->getQuery(true);
				$query->update($this->_db->quoteName('#__emundus_campaign_candidature'))
					->set($this->_db->quoteName('published') . ' = -1')
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum_from));
				$this->_db->setQuery($query);
				$this->_db->execute();
			}
			if ($copyUsersAssoc) {
				$this->copyUsersAssoc($fnum_from, $fnum_to);
			}
			if ($copyGroupsAssoc) {
				$this->copyGroupsAssoc($fnum_from, $fnum_to);
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
			echo $query->__toString();
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $this->_mainframe->getIdentity()->id . ' -> ' . $q . ' :: ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		PluginHelper::importPlugin('emundus');
		$this->_mainframe->triggerEvent('onCallEventHandler', array(
				'onAfterCopyApplication',
				array(
					'fnum_from'             => $fnum_from,
					'fnum_to'               => $fnum_to,
					'pid'                   => $pid,
					'copy_attachment'       => $copy_attachment,
					'campaign_id'           => $campaign_id,
					'copy_tag'              => $copy_tag,
					'move_hikashop_command' => $move_hikashop_command,
					'delete_from_file'      => $delete_from_file,
					'params'                => $params)
			)
		);

		return true;
	}

	/**
	 * Duplicate all documents (files)
	 *
	 * @param         $fnum_from String the fnum of the source
	 * @param         $fnum_to   String the fnum of the duplicated application
	 * @param         $pid       Int the profile_id to get list of forms
	 * @param   null  $duplicated
	 *
	 * @return bool
	 */
	public function copyDocuments($fnum_from, $fnum_to, $pid = null, $can_delete = null)
	{


		try {
			if (empty($pid)) {
				$m_profiles = new EmundusModelProfile();

				$fnumInfos = $m_profiles->getFnumDetails($fnum_from);
				$pid       = (isset($fnumInfos['profile_id_form']) && !empty($fnumInfos['profile_id_form'])) ? $fnumInfos['profile_id_form'] : $fnumInfos['profile_id'];
			}

			// 1. get list of uploaded documents for previous file defined as duplicated
			$query = 'SELECT eu.*
                        FROM #__emundus_uploads as eu
                        LEFT JOIN #__emundus_setup_attachment_profiles as esap on esap.attachment_id=eu.attachment_id AND esap.profile_id=' . $pid . '
                        WHERE eu.fnum like ' . $this->_db->Quote($fnum_from);

			if (empty($pid)) {
				$query .= ' AND esap.duplicate=1';
			}

			$this->_db->setQuery($query);
			$stored = $this->_db->loadAssocList();

			if (count($stored) > 0) {
				// 2. copy DB définition and duplicate files in applicant directory
				foreach ($stored as $row) {
					$src                   = $row['filename'];
					$ext                   = explode('.', $src);
					$ext                   = $ext[count($ext) - 1];
					$cpt                   = 0 - (int) (strlen($ext) + 1);
					$dest                  = substr($row['filename'], 0, $cpt) . '-' . $row['id'] . '.' . $ext;
					$row['filename']       = $dest;
					$row['fnum']           = $fnum_to;
					$row['can_be_deleted'] = empty($can_delete) ? 0 : 1;
					unset($row['id']);

					try {
						$query = 'INSERT INTO #__emundus_uploads (`' . implode('`,`', array_keys($row)) . '`) VALUES(' . implode(',', $this->_db->Quote($row)) . ')';
						$this->_db->setQuery($query);
						$this->_db->execute();
						$id   = $this->_db->insertid();
						$path = EMUNDUS_PATH_ABS . $row['user_id'] . DS;
						if (!copy($path . $src, $path . $dest)) {
							$query = 'UPDATE #__emundus_uploads SET filename=' . $src . ' WHERE id=' . $id;
							$this->_db->setQuery($query);
							$this->_db->execute();
						}
					}
					catch (Exception $e) {
						$error = JUri::getInstance() . ' :: USER ID : ' . $row['user_id'] . ' -> ' . $e->getMessage();
						Log::add($error, Log::ERROR, 'com_emundus');
					}
				}
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}

	public function copyUsersAssoc($fnum_from, $fnum_to)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->select('eua.*')
				->from($this->_db->quoteName('#__emundus_users_assoc', 'eua'))
				->where($this->_db->quoteName('eua.fnum') . ' LIKE ' . $this->_db->quote($fnum_from));

			$this->_db->setQuery($query);
			$users_assoc = $this->_db->loadAssocList();

			if (count($users_assoc) > 0) {
				// 2. copy DB définition and duplicate files in applicant directory
				foreach ($users_assoc as $user) {
					$user['fnum'] = $fnum_to;
					unset($user['id']);

					try {
						$query->clear()
							->insert($this->_db->quoteName('#__emundus_users_assoc'))
							->columns(array_keys($user))
							->values(implode(", ", $this->_db->quote($user)));
						$this->_db->setQuery($query);
						$this->_db->execute();
					}
					catch (Exception $e) {
						$error = JUri::getInstance() . ' :: USER ID : ' . $user['user_id'] . ' -> ' . $e->getMessage();
						Log::add($error, Log::ERROR, 'com_emundus');
					}
				}
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}

	public function copyGroupsAssoc($fnum_from, $fnum_to)
	{
		$query = $this->_db->getQuery(true);

		try {
			$query->select('ega.*')
				->from($this->_db->quoteName('#__emundus_group_assoc', 'ega'))
				->where($this->_db->quoteName('ega.fnum') . ' LIKE ' . $this->_db->quote($fnum_from));
			$this->_db->setQuery($query);
			$groups_assoc = $this->_db->loadAssocList();

			if (count($groups_assoc) > 0) {
				// 2. copy DB définition and duplicate files in applicant directory
				foreach ($groups_assoc as $group) {
					$group['fnum'] = $fnum_to;
					unset($group['id']);

					try {
						$query->clear()
							->insert($this->_db->quoteName('#__emundus_group_assoc'))
							->columns(array_keys($group))
							->values(implode(", ", $this->_db->quote($group)));
						$this->_db->setQuery($query);
						$this->_db->execute();
					}
					catch (Exception $e) {
						$error = JUri::getInstance() . ' :: fnum : ' . $group['user_id'] . ' :: group : ' . $group['group_id'] . ' -> ' . $e->getMessage();
						Log::add($error, Log::ERROR, 'com_emundus');
					}
				}
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}

		return true;
	}

	/**
	 * Duplicate all documents (files)
	 *
	 * @param          $fnum             String     the fnum of application file
	 * @param          $applicant        Object     the applicant user ID
	 * @param   array  $param
	 * @param   int    $status
	 *
	 * @return bool
	 */
	public function sendApplication($fnum, $applicant, $param = array(), $status = 1)
	{
		include_once(JPATH_SITE . '/components/com_emundus/models/emails.php');

		if ($status == '-1') {
			$status = $applicant->status;
		}


		try {
			// Vérification que le dossier à été entièrement complété par le candidat
			$query = 'SELECT id
                        FROM #__emundus_declaration
                        WHERE fnum  like ' . $this->_db->Quote($fnum);
			$this->_db->setQuery($query);
			$this->_db->execute();
			$id       = $this->_db->loadResult();
			$offset   = JFactory::getConfig()->get('offset', 'UTC');
			$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
			$dateTime = $dateTime->setTimezone(new DateTimeZone($offset));
			$now      = $dateTime->format('Y-m-d H:i:s');

			if ($id > 0) {
				$query = 'UPDATE #__emundus_declaration SET time_date=' . $this->_db->quote($now) . ', user=' . $applicant->id . ' WHERE id=' . $id;
			}
			else {
				$query = 'INSERT INTO #__emundus_declaration (time_date, user, fnum, type_mail)
                                VALUE (' . $this->_db->quote($now) . ', ' . $applicant->id . ', ' . $this->_db->Quote($fnum) . ', "paid_validation")';
			}

			$this->_db->setQuery($query);
			$this->_db->execute();

			// Insert data in #__emundus_campaign_candidature
			$query = 'UPDATE #__emundus_campaign_candidature SET submitted=1, date_submitted=' . $this->_db->quote($now) . ', status=' . $status . ' WHERE applicant_id=' . $applicant->id . ' AND campaign_id=' . $applicant->campaign_id . ' AND fnum like ' . $this->_db->Quote($applicant->fnum);
			$this->_db->setQuery($query);
			$this->_db->execute();

			// Send emails defined in trigger
			$m_emails     = new EmundusModelEmails;
			$code         = array($applicant->code);
			$to_applicant = '0,1';
			$m_emails->sendEmailTrigger($status, $code, $to_applicant, $applicant);

		}
		catch (Exception $e) {
			// catch any database errors.
			Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return true;
	}

	/**
	 * Check if iframe can be used
	 *
	 * @param $url String url to check
	 *
	 * @return bool
	 */
	function allowEmbed($url)
	{

		$eMConfig = JComponentHelper::getParams('com_emundus');
		$header   = $eMConfig->get('headerCheck', '0') == 1 ? @get_headers($url, 1) : true;

		// URL okay?
		if (!$header || stripos($header[0], '200 ok') === false) {
			return false;
		}

		// Check X-Frame-Option
		elseif (isset($header['X-Frame-Options']) && (stripos($header['X-Frame-Options'], 'SAMEORIGIN') !== false || stripos($header['X-Frame-Options'], 'deny') !== false)) {
			return false;
		}

		// Everything passed? Return true!
		return true;
	}

	/**
	 * Gets the first page of the application form. Used for opening a file.
	 *
	 * @param   string  $redirect
	 * @param   null    $fnums
	 *
	 * @return String The URL to the form.
	 * @since 3.8.8
	 */
	function getFirstPage($redirect = '/index.php', $fnums = null)
	{
		$user = $this->_mainframe->getSession()->get('emundusUser');
		$query = $this->_db->getQuery(true);

		if (!empty($fnums)) {

			$fnums = is_array($fnums) ? implode(',', $fnums) : $fnums;

			$query->select(['CONCAT(m.link,"&Itemid=", m.id) as link', $this->_db->quoteName('cc.fnum')])
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('cc.campaign_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->_db->quoteName('esp.id') . ' = ' . $this->_db->quoteName('esc.profile_id'))
				->leftJoin($this->_db->quoteName('#__menu', 'm') . ' ON ' . $this->_db->quoteName('m.menutype') . ' = ' . $this->_db->quoteName('esp.menutype') . ' AND ' . $this->_db->quoteName('m.published') . '=1 AND ' . $this->_db->quoteName('link') . ' <> "" AND ' . $this->_db->quoteName('link') . ' <> "#"')
				->where($this->_db->quoteName('cc.fnum') . ' IN(' . $fnums . ')')
				->order($this->_db->quoteName('m.lft') . ' DESC');
			$this->_db->setQuery($query);

			try {
				$redirect = $this->_db->loadAssocList('fnum');
			}
			catch (Exception $e) {
				Log::add('Error getting first page of application at model/application in query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');
			}

		}
		else {
			if (!empty($user->menutype)) {
				$query->select('CONCAT(link,"&Itemid=", id) as link')
					->from($this->_db->quoteName('#__menu'))
					->where($this->_db->quoteName('published').'=1 AND '.$this->_db->quoteName('menutype').' LIKE '.$this->_db->quote($user->menutype).' AND '.$this->_db->quoteName('link').' <> "" AND '.$this->_db->quoteName('link').' <> "#"')
					->order($this->_db->quoteName('lft').' ASC');

				try {
					$this->_db->setQuery($query);
					$redirect = $this->_db->loadResult();

					if (!empty($redirect)) {
						$redirect = EmundusHelperAccess::buildFormUrl($redirect, $user->fnum);
					}
				} catch (Exception $e) {
					Log::add('Error getting first page of application at model/application in query : '.preg_replace("/[\r\n]/"," ",$query->__toString()), Log::ERROR, 'com_emundus');
				}
			}
		}

		return $redirect;
	}

	public function attachment_validation($attachment_id, $state)
	{

		try {
			$query = 'UPDATE #__emundus_uploads  SET `is_validated` = ' . (int) $state . ' WHERE `id` = ' . (int) $attachment_id;
			$this->_db->setQuery($query);

			return $this->_db->execute();
		}
		catch (Exception $e) {
			throw $e;
		}
	}


	/** Gets the URL of the final form in the application in order to submit.
	 *
	 * @param $fnums
	 *
	 * @return Mixed
	 */
	function getConfirmUrl($fnums = null)
	{
		$return = false;

		$user = JFactory::getSession()->get('emundusUser');

		$query = $this->_db->getQuery(true);

		if (!empty($fnums)) {
			$query->select(['CONCAT(m.link,"&Itemid=", m.id) as link', $this->_db->quoteName('cc.fnum')])
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('cc.campaign_id'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->_db->quoteName('esp.id') . ' = ' . $this->_db->quoteName('esc.profile_id'))
				->leftJoin($this->_db->quoteName('#__menu', 'm') . ' ON ' . $this->_db->quoteName('m.menutype') . ' = ' . $this->_db->quoteName('esp.menutype') . ' AND ' . $this->_db->quoteName('m.published') . '>=0 AND ' . $this->_db->quoteName('m.level') . '=1 AND ' . $this->_db->quoteName('m.link') . ' <> "" AND ' . $this->_db->quoteName('m.link') . ' <> "#"')
				->where($this->_db->quoteName('cc.fnum') . ' IN(' . implode(',', $fnums) . ')')
				->order($this->_db->quoteName('m.lft') . ' ASC');

			$this->_db->setQuery($query);
			try {
				$return = $this->_db->loadAssocList('fnum');
			}
			catch (Exception $e) {
				Log::add('Error getting confirm URLs in model/application at query -> ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');
			}
		}
		else {

			if (!empty($user->menutype)) {
				$query->select(['id', 'link'])
					->from($this->_db->quoteName('#__menu'))
					->where($this->_db->quoteName('published') . '=1 AND ' . $this->_db->quoteName('menutype') . ' LIKE ' . $this->_db->quote($user->menutype) . ' AND ' . $this->_db->quoteName('link') . ' <> "" AND ' . $this->_db->quoteName('link') . ' <> "#"')
					->order($this->_db->quoteName('lft') . ' DESC');
				try {
					$this->_db->setQuery($query);
					$res = $this->_db->loadObject();

					$return = $res->link . '&Itemid=' . $res->id;
				}
				catch (Exception $e) {
					Log::add('Error getting first page of application at model/application in query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');
				}
			}
		}

		return $return;
	}


	public function searchFilesByKeywords($fnum)
	{

		$jinput = JFactory::getApplication()->input;
		$search = $jinput->get('search');

		$query = 'SELECT eu.id AS aid, esa.*, eu.attachment_id, eu.filename, eu.description, eu.timedate, eu.can_be_deleted, eu.can_be_viewed, eu.is_validated, esc.label as campaign_label, esc.year, esc.training
            FROM #__emundus_uploads AS eu
            LEFT JOIN #__emundus_setup_attachments AS esa ON  eu.attachment_id=esa.id
            WHERE eu.fnum like ' . $this->_db->Quote($fnum) . '
            AND $where LIKE ' . $search;

		$this->_db->setQuery($query);

		return $this->_db->execute();
	}

	/**
	 * @param $elements
	 * @param $table
	 * @param $parent_table
	 * @param $fnum
	 *
	 * @return bool
	 *
	 */
	public function checkEmptyRepeatGroups($elements, $table, $parent_table, $fnum)
	{
		$query    = $this->_db->getQuery(true);
		$subQuery = $this->_db->getQuery(true);

		$eMConfig          = JComponentHelper::getParams('com_emundus');
		$show_empty_fields = $eMConfig->get('show_empty_fields', 1);

		$elements = array_map(function ($obj) {
			return 't.' . $obj->name;
		}, $elements);

		$subQuery
			->select($this->_db->quoteName('id'))
			->from($this->_db->quoteName($parent_table))
			->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));

		$query
			->select(implode(',', $elements))
			->from($this->_db->quoteName($table, 't'))
			->leftJoin($this->_db->quoteName($parent_table, 'j') . ' ON ' . $this->_db->quoteName('j.id') . ' = ' . $this->_db->quoteName('t.parent_id'))
			->where($this->_db->quoteName('t.parent_id') . " = (" . $subQuery . ")");

		try {
			$this->_db->setQuery($query);
			$this->_db->execute();

			if ($this->_db->getNumRows() >= 1) {
				$res = $this->_db->loadAssoc();

				$elements = array_map(function ($arr) {
					if (is_numeric($arr)) {
						return (empty(floatval($arr)));
					}
					else {
						if ($arr == "0000-00-00 00:00:00") {
							return true;
						}

						return empty($arr);
					}
				}, $res);

				$elements = array_filter($elements, function ($el) {
					return $el === false;
				});

				return !empty($elements);
			}
			else {
				if ($show_empty_fields == 0) {
					return false;
				}
			}

			return true;

		}
		catch (Exception $e) {
			Log::add('Error checking if repeat group is empty at model/application in query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * @param $elements
	 * @param $parent_table
	 * @param $fnum
	 *
	 * @return bool
	 *
	 */
	public function checkEmptyGroups($elements, $parent_table, $fnum) {
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$eMConfig = JComponentHelper::getParams('com_emundus');
		$show_empty_fields = $eMConfig->get('show_empty_fields', 1);

		$databases_join_params = [];
		$elements_name = array_map(function($obj) use ($db,$parent_table,&$databases_join_params) {
			if($obj->plugin == 'databasejoin'){
				$params = json_decode($obj->params);
				if($params->database_join_display_type == 'checkbox' || $params->database_join_display_type == 'multilist'){
					$databases_join_params[] = $db->quoteName($parent_table.'_repeat_' . $obj->name).' ON '.$db->quoteName($parent_table.'_repeat_' . $obj->name).'.parent_id = t.id';

					return $parent_table.'_repeat_' . $obj->name.'.'.$obj->name;
				}
			}
			return 't.'.$obj->name;
		}, $elements);

		$query->select(implode(',', $db->quoteName($elements_name)))
			->from($db->quoteName($parent_table,'t'));
		if(!empty($databases_join_params)){
			foreach ($databases_join_params as $db_join)
			{
				$query->leftJoin($db_join);
			}
		}
		$query->where($db->quoteName('t.fnum') . ' LIKE ' . $db->quote($fnum));

		try {
			$db->setQuery($query);
			$db->execute();
			if ($db->getNumRows() == 1)
			{
				$res = $db->loadAssoc();

				$at_least_one_visible = false;
				foreach($res as $element_name => $element_value) {
					$current_fb_element = array_filter($elements, function($el) use ($element_name) {return $el->name === $element_name;});
					$current_fb_element = current($current_fb_element);

					if (!empty($current_fb_element)) {
						if ($current_fb_element->plugin === 'yesno' && !is_null($element_value) && $element_value !== '') {
							$at_least_one_visible = true;
						}

						if (is_numeric($element_value)) {
							if (!empty(floatval($element_value))) {
								$at_least_one_visible = true;
							}
						} else if ($element_value !== "0000-00-00 00:00:00" && !empty($element_value)) {
							$at_least_one_visible = true;
							if($current_fb_element->plugin === 'checkbox' && $element_value === '[""]')
							{
								$at_least_one_visible = false;
							}
						}
					}

					if($at_least_one_visible)
					{
						break;
					}
				}

				return $at_least_one_visible;
			}
			elseif ($db->getNumRows() > 1)
			{
				return true;
			}
			else
			{
				if($show_empty_fields == 0){
					return false;
				}
			}

			return true;

		} catch (Exception $e ) {
			Log::add('Error checking if group is empty at model/application in query : '.preg_replace("/[\r\n]/"," ",$query->__toString()), Log::ERROR, 'com_emundus');
			return false;
		}
	}

	/// get count uploaded files
	public function getCountUploadedFile($fnum, $user_id, $profile = null)
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'application.php');
		$m_application = new EmundusModelApplication;

		$html    = '';
		$uploads = $m_application->getUserAttachmentsByFnum($fnum, '', $profile);

		$nbuploads = 0;
		foreach ($uploads as $upload) {
			if (strrpos($upload->filename, "application_form") === false) {
				$nbuploads++;
			}
		}
		$titleupload = $nbuploads > 0 ? Text::_('COM_EMUNDUS_ATTACHMENTS_FILES_UPLOADED') : Text::_('COM_EMUNDUS_ATTACHMENTS_ERROR_FILE_UPLOADED');
		$html        .= '<h2>' . $titleupload . ' : ' . $nbuploads . '</h2>';

		return $html;
	}

	/// get list uploaded files
	public function getListUploadedFile($fnum, $user_id, $profile = null)
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'application.php');
		$m_application = new EmundusModelApplication;

		$html    = '';
		$uploads = $m_application->getUserAttachmentsByFnum($fnum, '', $profile);

		$nbuploads = 0;
		foreach ($uploads as $upload) {
			if (strrpos($upload->filename, "application_form") === false) {
				$nbuploads++;
			}
		}

		$html .= '<ol>';
		foreach ($uploads as $upload) {
			if (strrpos($upload->filename, "application_form") === false) {
				$path_href = JURI::base() . EMUNDUS_PATH_REL . $user_id . '/' . $upload->filename;
				$html      .= '<li><b>' . $upload->value . '</b>';
				$html      .= '<ul>';
				$html      .= '<li><a href="' . $path_href . '" dir="ltr" target="_blank">' . $upload->filename . '</a> (' . strftime("%d/%m/%Y %H:%M", strtotime($upload->timedate)) . ')<br/><b>' . Text::_('COM_EMUNDUS_ATTACHMENTS_DESCRIPTION') . '</b> : ' . $upload->description . '</li>';
				$html      .= '</ul>';
				$html      .= '</li>';
			}
		}
		$html .= '</ol>';

		return $html;
	}

	/**
	 * Update emundus upload data in database, and even the file content
	 *
	 * @param   object data, must at least contain the id of the upload, the fnum and the modifier user id
	 *
	 * @return (array) containing status of update and file content update
	 */
	public function updateAttachment($data)
	{
		$return = [
			"update" => false
		];

		if (!empty($data['id']) && !empty($data['user']) && !empty($data['fnum'])) {
			$is_validated   = array(1 => 'VALID', 0 => 'INVALID', 2 => 'COM_EMUNDUS_ATTACHMENTS_WARNING', -2 => 'COM_EMUNDUS_ATTACHMENTS_WAITING');
			$can_be_viewed  = array(1 => 'JYES', 0 => 'JNO');
			$can_be_deleted = array(1 => 'JYES', 0 => 'JNO');

			if (isset($data['file'])) {
				$content    = file_get_contents($data['file']['tmp_name']);
				$attachment = $this->getUploadByID($data['id']);
				$updated    = file_put_contents(EMUNDUS_PATH_REL . $attachment['user_id'] . "/" . $attachment['filename'], $content);
				$return['file_update'] = (bool)$updated;
			}

			$oldData = $this->getUploadByID($data['id']);

			$query = $this->_db->getQuery(true);
			$query->update($this->_db->quoteName('#__emundus_uploads'));

			if (isset($data['description'])) {
				$query->set($this->_db->quoteName('description') . ' = ' . $this->_db->quote($data['description']));
			}

			if (isset($data['is_validated'])) {
				if (in_array($data['is_validated'], array_keys($is_validated))) {
					$query->set($this->_db->quoteName('is_validated') . ' = ' . $this->_db->quote($data['is_validated']));
				}
			}

			if (isset($data['can_be_viewed'])) {
				if (in_array($data['can_be_viewed'], array_keys($can_be_viewed))) {
					$query->set($this->_db->quoteName('can_be_viewed') . ' = ' . $this->_db->quote($data['can_be_viewed']));
				}
			}

			if (isset($data['can_be_deleted'])) {
				if (in_array($data['can_be_deleted'], array_keys($can_be_deleted))) {
					$query->set($this->_db->quoteName('can_be_deleted') . ' = ' . $this->_db->quote($data['can_be_deleted']));
				}
			}

			$query->set($this->_db->quoteName('modified') . ' = ' . $this->_db->quote(date("Y-m-d H:i:s")))
				->set($this->_db->quoteName('modified_by') . ' = ' . $this->_db->quote($data['user']))
				->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($data['id']));

			try {
				$this->_db->setQuery($query);
				$return['update'] = $this->_db->execute();

				if ($return['update']) {
					$newData = $this->getUploadByID($data['id']);
					$logger = array();

					$includedKeys = ['description', 'can_be_deleted', 'can_be_viewed', 'is_validated'];

					require_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
					require_once(JPATH_SITE . '/components/com_emundus/models/files.php');

					$m_files = new EmundusModelFiles();
					$applicant_id = ($m_files->getFnumInfos($data['fnum']))['applicant_id'];
					$attachmentParams = $this->getAttachmentByID($newData['attachment_id']);

					if (empty($_FILES)) {
						// find the difference(s)
						foreach ($oldData as $key => $value) {
							// by default : null = "invalid" or -2
							if ($key === 'is_validated' and is_null($value)) {
								$value = -2;            # recheck !!!
							}

							$logsStd = new stdClass();
							if ($oldData[$key] !== $newData[$key] and in_array($key, $includedKeys)) {
								$logsStd->description = '<b>' . '[' . $attachmentParams['value'] . ']' . '</b>';
								$logsStd->element = '<u>' . Text::_($key) . '</u>';

								// check if a var with same name as the key exists
								$column_values = ${$key};
								if (!empty($column_values) && in_array($oldData[$key], array_keys($column_values))) {
									$logsStd->old = Text::_($column_values[$oldData[$key]]);
									$logsStd->new = Text::_($column_values[$newData[$key]]);
								}
								else {
									$logsStd->old = $oldData[$key];
									$logsStd->new = $newData[$key];
								}

								$logger[] = $logsStd;
							}
							else {
								continue;
							}

							$logsParams = array('updated' => $logger);
						}
						EmundusModelLogs::log($this->_user->id, $applicant_id, $data['fnum'], 4, 'u', 'COM_EMUNDUS_ACCESS_ATTACHMENT_UPDATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
					}
					else {
						$logsStd = new stdClass();

						$logsStd->element = '[' . $attachmentParams['value'] . ']';
						$logsStd->details = $_FILES['file']['name'];
						$logsParams       = array('created' => [$logsStd]);
						EmundusModelLogs::log($this->_user->id, $applicant_id, $data['fnum'], 4, 'c', 'COM_EMUNDUS_ACCESS_ATTACHMENT_CREATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));
					}

                    PluginHelper::importPlugin('emundus', 'custom_event_handler');
                    Factory::getApplication()->triggerEvent('onAfterUpdateAttachment', array($data, $data['fnum'], $oldData, $applicant_id));
                    Factory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterUpdateAttachment', ['attachment' => $data, 'fnum' => $data['fnum'], 'oldData' => $oldData, 'applicant_id' => $applicant_id]]);
				}
			}
			catch (Exception $e) {
				Log::add('Failed to update attachment ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $return;
	}

	/**
	 * Generate preview based on file types
	 *
	 * @param   int user id of the applicant
	 * @param   string filename
	 *
	 * @return string preview html tags
	 */
	public function getAttachmentPreview($user, $fileName)
	{
		$preview   = [
			'status'    => true,
			'overflowX' => false,
			'overflowY' => false,
			'style'     => '',
			'msg'       => '',
			'error'     => ''
		];
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

		$filePath   = EMUNDUS_PATH_REL . $user . "/" . $fileName;
		$fileExists = File::exists($filePath);

		if ($fileExists) {

			// create preview based on filetype
			if ($extension == 'pdf') {
				$preview['content'] = '<iframe src="/index.php?option=com_emundus&task=getfile&u=images/emundus/files/'. $user . '/' . $fileName . '" style="width:100%;height:100%;" border="0"></iframe>';
			}
			else if ($extension == 'txt') {
				$content              = file_get_contents($filePath);
				$preview['overflowY'] = true;
				$preview['content']   = '<div class="wrapper" style="max-width: 100%;margin: 5px;padding: 20px;background-color: white;"><pre style="white-space: break-spaces;">' . $content . '</pre></div>';
			}
			else if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
				$mimeType           = mime_content_type($extension);
				if (empty($mimeType)) {
					switch ($extension) {
						case 'jpeg':
						case 'jpg':
							$mimeType = 'image/jpeg';
							break;
						case 'png':
							$mimeType = 'image/png';
							break;
						case 'gif':
							$mimeType = 'image/gif';
							break;
						default:
							$mimeType = 'text/plain';
							break;
					}
				}

				$base64_images_preview = ComponentHelper::getParams('com_emundus')->get('base64_images_preview', 1);

				if ($base64_images_preview)
				{
					$content            = base64_encode(file_get_contents(JPATH_SITE . DS . $filePath));
					$preview['content'] = '<div class="wrapper" style="height: 100%;display: flex;justify-content: center;align-items: center;"><img src="data:' . $mimeType . ';base64,' . $content . '" style="display: block;max-width:100%;max-height:100%;width: auto;height: auto;" /></div>';
				} else {
					$preview['content'] = '<div class="wrapper" style="height: 100%;display: flex;justify-content: center;align-items: center;"><img src="' . Uri::base() . $filePath . '" style="display: block;max-width:100%;max-height:100%;width: auto;height: auto;" /></div>';
				}
			}
			else if (in_array($extension, ['doc', 'docx', 'odt', 'rtf'])) {
				require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

				switch ($extension) {
					case 'odt':
						$class = 'ODText';
						break;
					case 'rtf':
						$class = 'RTF';
						break;
					case 'doc':
					case 'docx':
					default:
						$class = 'Word2007';
				}

				$phpWord    = \PhpOffice\PhpWord\IOFactory::load(JPATH_SITE . DS . $filePath, $class);
				$htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
				$content    = $htmlWriter->getContent();

				$contentWithoutSpaces = preg_replace('/\s+/', '', $content);
				if (strpos($contentWithoutSpaces, '<body></') !== false) {
					$preview['status']  = false;
					$preview['error']   = 'unavailable';
					$preview['content'] = '<div style="width:100%;height: 100%;display: flex;justify-content: center;align-items: center;"><p style="margin:0;text-align:center;">' . Text::_('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_UNAVAILABLE') . '</p></div>';
				}
				else {
					$preview['content']   = '<div class="wrapper">' . $content . '</div>';
					$preview['overflowY'] = true;
					$preview['style']     = 'word';
					$preview['msg']       = Text::_('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_INCOMPLETE_MSG');
				}
			}
			else if (in_array($extension, ['xls', 'xlsx', 'ods', 'csv'])) {
				require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

				$phpSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(JPATH_SITE . DS . $filePath);
				$htmlWriter     = new \PhpOffice\PhpSpreadsheet\Writer\Html($phpSpreadsheet);
				$htmlWriter->setGenerateSheetNavigationBlock(true);
				$htmlWriter->setSheetIndex(0);
				$preview['content']   = $htmlWriter->generateHtmlAll();
				$preview['overflowY'] = true;
				$preview['overflowX'] = true;
				$preview['style']     = 'sheet';

				$preview['msg'] = Text::_('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_INCOMPLETE_MSG');
			}
			else if (in_array($extension, ['ppt', 'pptx', 'odp'])) {
				// ? PHPPresentation is not giving html support... need to create it manually ?
				$preview['content']   = $this->convertPowerPointToHTML($filePath);
				$preview['overflowY'] = true;
				$preview['style']     = 'presentation';

				$preview['msg'] = Text::_('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_INCOMPLETE_MSG');
			}
			else if (in_array($extension, ['mp3', 'wav', 'ogg'])) {
				$preview['content'] = '<div class="wrapper" style="height: 100%;display: flex;justify-content: center;align-items: center;"><audio controls><source src="' . JURI::base() . $filePath . '" type="audio/' . $extension . '"></audio></div>';
			}
			else if (in_array($extension, ['mp4', 'webm', 'ogg'])) {
				$preview['content'] = '<div class="wrapper" style="height: 100%;display: flex;justify-content: center;align-items: center;"><video controls  style="max-width: 100%;"><source src="' . JURI::base() . $filePath . '" type="video/' . $extension . '"></video></div>';
			}
			else {
				$preview['status']  = false;
				$preview['error']   = 'unsupported';
				$preview['content'] = '<div style="width:100%;height: 100%;display: flex;flex-direction: column;justify-content: center;align-items: center;"><p style="margin:0;text-align:center;">' . Text::_('COM_EMUNDUS_ATTACHMENTS_FILE_TYPE_NOT_SUPPORTED') . '</p><p><a href="' . JURI::base() . $filePath . '" target="_blank" download>' . Text::_('COM_EMUNDUS_ATTACHMENTS_DOWNLOAD') . '</a></p></div>';
			}
		}
		else {
			$preview['status']  = false;
			$preview['error']   = 'file_not_found';
			$preview['content'] = '<div style="width:100%;height: 100%;display: flex;justify-content: center;align-items: center;"><p style="margin:0;text-align:center;">' . Text::_('COM_EMUNDUS_ATTACHMENTS_FILE_NOT_FOUND') . '</p></div>';
		}

		return $preview;
	}

	/**
	 * @param $filePath
	 *
	 * @return string (html content)
	 */
	private function convertPowerPointToHTML($filePath)
	{
		$content = '';

		// create a ziparchive
		$zip = new ZipArchive;

		if ($zip->open($filePath)) {
			// get xml content of all slides
			$slides = [];
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				if (strpos($filename, 'ppt/slides/slide') !== false) {
					$slides[] = $zip->getFromIndex($i);
				}
			}

			// get style properties of all slides
			$slideStyles = [];
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				if (strpos($filename, 'ppt/slideMasters/slideMaster') !== false) {
					$slideStyles[] = $zip->getFromIndex($i);
				}
			}

			$zip->close();

			// create html content from slides and style
			$content = '<div class="wrapper" style="display: flex;flex-direction:column;justify-content: flex-start;align-items: center;">';
			foreach ($slides as $key => $slide) {
				$content .= '<div class="slide" style="width: 100%;height: 100%;">';

				$dom = new DOMDocument();
				$dom->loadXML($slide);

				$xpath = new DOMXPath($dom);

				$query   = '//a:p';
				$entries = $xpath->query($query);

				foreach ($entries as $e_key => $entry) {
					$content .= "<p>";

					// use . for relative query
					$query        = './/a:t';
					$text_entries = $xpath->query($query, $entry);

					foreach ($text_entries as $text) {
						$content .= $text->nodeValue;
					}

					$content .= "</p>";
				}

				// $content .= $dom->saveXML();

				$content .= '</div>';
			}
		}

		return $content;
	}

	public function getValuesByElementAndFnum($fnum, $eid, $fid, $raw = 0, $wheres = [], $uid = null,$format = true,$repeate_sperator = ",")
	{

		$query = $this->_db->getQuery(true);

		$result = '';

		try {
			$query->select('db_table_name')
				->from($this->_db->quoteName('#__fabrik_lists'))
				->where($this->_db->quoteName('form_id') . ' = ' . $fid);
			$this->_db->setQuery($query);
			$table = $this->_db->loadResult();

			$query->clear()
				->select('applicant_id')
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('fnum') . ' LIKE ' . $fnum);
			$this->_db->setQuery($query);
			$aid = $this->_db->loadResult();

			$query->clear()
				->select('fe.id,fe.name,fe.group_id,fe.plugin,fe.default,fe.params,fg.params as group_params')
				->from($this->_db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->_db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $this->_db->quoteName('fg.id') . ' = ' . $this->_db->quoteName('fe.group_id'))
				->where($this->_db->quoteName('fe.id') . ' = ' . $this->_db->quote($eid));
			$this->_db->setQuery($query);
			$element      = $this->_db->loadObject();
			$group_params = json_decode($element->group_params);

			if ($table == 'jos_emundus_evaluations') {
				$params     = JComponentHelper::getParams('com_emundus');
				$multi_eval = $params->get('multi_eval', 0);

				if ($multi_eval == 1) {
					$wheres[] = $this->_db->quoteName('user') . ' = ' . $this->_db->quote(JFactory::getUser()->id);
				}
			}

			if ($group_params->repeat_group_button == 1) {
				$query->clear()
					->select('join_from_table,table_join,table_key,table_join_key')
					->from($this->_db->quoteName('#__fabrik_joins'))
					->where($this->_db->quoteName('group_id') . ' = ' . $this->_db->quote($element->group_id))
					->andWhere($this->_db->quoteName('table_join_key') . ' = ' . $this->_db->quote('parent_id'));
				$this->_db->setQuery($query);
				$join_params = $this->_db->loadObject();

				$query->clear()
					->select($this->_db->quoteName('r.' . $element->name))
					->from($this->_db->quoteName($join_params->join_from_table, 'p'))
					->leftJoin($this->_db->quoteName($join_params->table_join, 'r') . ' ON ' . $this->_db->quoteName('r.' . $join_params->table_join_key) . ' = ' . $this->_db->quoteName('p.' . $join_params->table_key))
					->where($this->_db->quoteName('p.fnum') . ' LIKE ' . $this->_db->quote($fnum));
				foreach ($wheres as $where) {
					$query->where($where);
				}
				$this->_db->setQuery($query);
				$values = $this->_db->loadColumn();
			}
			else {
				$query->clear()
					->select($this->_db->quoteName($element->name))
					->from($this->_db->quoteName($table))
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
				if (!empty($uid)) {
					$query->andWhere($this->_db->quoteName('user') . ' = ' . $this->_db->quote($uid));
				}
				foreach ($wheres as $where) {
					$query->where($where);
				}
				$this->_db->setQuery($query);
				$values = $this->_db->loadResult();
			}

			$elt = [];
			if ($format) {
				if (!is_array($values)) {
					$values = [$values];
				}

				if (!empty($values) || $element->plugin == 'yesno') {
					foreach ($values as $value) {
						if($raw == 0)
						{
							$elt[] = EmundusHelperFabrik::formatElementValue($element->name, $value, $element->group_id, $aid, true);
						} else {
							$elt[] = $value;
						}
					}
				}

				$result = implode($repeate_sperator, $elt);
			} else {
				$result = $values;
			}
		}
		catch (Exception $e) {
			Log::add('Problem when get values of element ' . $eid . ' with fnum ' . $fnum . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $result;
	}

	/**
	 * @param $element farbik element object
	 * @param $value   value of the element
	 * @param $table   table name
	 * @param $applicant_id
	 *
	 * @return $elt
	 * @throws Exception
	 * @deprecated Use EmundusHelperFabrik::formatElementValue instead
	 */
	public function formatElementValue($element, $value, $table, $applicant_id)
	{
		$query = $this->_db->getQuery(true);
		$query->select('group_id')
			->from($this->_db->quoteName('#__fabrik_elements'))
			->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($element->id));
		$this->_db->setQuery($query);
		$group_id = $this->_db->loadResult();

		return EmundusHelperFabrik::formatElementValue($element->name,$value,$group_id,$applicant_id,true);
	}


	public function invertFnumsOrderByColumn($fnum_from, $target_fnum, $order_column = 'ordering')
	{
		$reordered = false;

		$excluded_columns = ['fnum', 'id', 'user', 'user_id', 'applicant_id'];
		if (!in_array($order_column, $excluded_columns) && !empty($order_column) && !empty($fnum_from) && !empty($target_fnum)) {

			$query = $this->_db->getQuery(true);

			$query->select($order_column)
				->from('#__emundus_campaign_candidature as ecc')
				->where('fnum LIKE ' . $this->_db->quote($fnum_from));

			$this->_db->setQuery($query);

			try {
				$old_position = $this->_db->loadResult();

				$query->clear()
					->select($order_column)
					->from('#__emundus_campaign_candidature as ecc')
					->where('fnum LIKE ' . $this->_db->quote($target_fnum));

				$this->_db->setQuery($query);
				$new_position = $this->_db->loadResult();

				$query->clear()
					->update('#__emundus_campaign_candidature')
					->set($this->_db->quoteName($order_column) . ' = ' . $new_position)
					->where('fnum LIKE ' . $this->_db->quote($fnum_from));

				$this->_db->setQuery($query);
				$reordered = $this->_db->execute();

				if ($reordered) {
					$query->clear()
						->update('#__emundus_campaign_candidature')
						->set($this->_db->quoteName($order_column) . ' = ' . $old_position)
						->where('fnum LIKE ' . $this->_db->quote($target_fnum));

					$this->_db->setQuery($query);
					$reordered = $this->_db->execute();
				}
			}
			catch (Exception $e) {
				Log::add('Failed to get ' . $order_column . ' in __emundus_campaign_candidature for ' . $fnum_from . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $reordered;
	}

	private function getSelectFromDBJoinElementParams($params, $alias = 'jd')
	{
		$select = '';

		if (!empty($params))
		{
			$select = $this->_db->quoteName($params->join_val_column);
			if (!empty($params->join_val_column_concat))
			{
				$select = 'CONCAT(' . $params->join_val_column_concat . ')';
				$select = preg_replace('#{thistable}#', $alias, $select);
				$select = preg_replace('#{shortlang}#', $this->locales, $select);
			}
		}

		return $select;
	}

	public function createTab($name, $user_id)
	{
		$tab_id = 0;

		if (!empty($user_id) && !empty($name) && strlen($name) <= 255 && strlen($name) >= 3) {
			/**
			 * \d: digits
			 * \s: Space character.
			 * \p{L}: Unicode letters.
			 * u :  unicode characters accepted
			 */
			$regex = '/^[\d\s\p{L}\'"\-]{3,255}$/u';

			if (preg_match($regex, $name)) {

				$query = $this->_db->getQuery(true);

				$query->insert($this->_db->quoteName('#__emundus_campaign_candidature_tabs'))
					->set($this->_db->quoteName('name') . ' = ' . $this->_db->quote($name))
					->set($this->_db->quoteName('ordering') . ' = 1')
					->set($this->_db->quoteName('applicant_id') . ' = ' . $user_id);
				try {
					$this->_db->setQuery($query);
					$this->_db->execute();

					$tab_id = $this->_db->insertid();
				}
				catch (Exception $e) {
					Log::add('Failed to create for user ' . $user_id . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			}
		}

		return $tab_id;
	}

	public function getTabs($user_id)
	{
		$tabs = [];

		if (!empty($user_id)) {

			$query = $this->_db->getQuery(true);

			$query->select('ecct.*,count(ecc.id) as no_files')
				->from($this->_db->quoteName('#__emundus_campaign_candidature_tabs', 'ecct'))
				->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.tab') . ' = ' . $this->_db->quoteName('ecct.id'))
				->where($this->_db->quoteName('ecct.applicant_id') . ' = ' . $user_id)
				->group($this->_db->quoteName('ecct.id'))
				->order($this->_db->quoteName('ecct.ordering'));

			try {
				$this->_db->setQuery($query);
				$tabs = $this->_db->loadAssocList();
			}
			catch (Exception $e) {
				Log::add(JUri::getInstance() . ' :: USER ID : ' . JFactory::getUser()->id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $tabs;
	}

	public function updateTabs($tabs, $user_id)
	{
		$updated = false;

		if (!empty($tabs) && !empty($user_id)) {
			try {

				$query = $this->_db->getQuery(true);

				$updates = [];
				foreach ($tabs as $tab) {
					$tab->id = (int) $tab->id;
					$owned   = $this->isTabOwnedByUser($tab->id, $user_id);

					if ($owned) {
						$query->clear()
							->update($this->_db->quoteName('#__emundus_campaign_candidature_tabs'))
							->set($this->_db->quoteName('name') . ' = ' . $this->_db->quote($tab->name))
							->set($this->_db->quoteName('ordering') . ' = ' . $this->_db->quote($tab->ordering))
							->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($tab->id));

						$this->_db->setQuery($query);
						$updates[] = $this->_db->execute();
					}
				}

				$updated = !in_array(false, $updates) && !empty($updates);
			}
			catch (Exception $e) {
				Log::add('Failed to update tabs for user ' . $user_id . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}

	public function deleteTab(int $tab_id, int $user_id)
	{
		$deleted = false;

		if (!empty($tab_id) && !empty($user_id)) {
			$owned = $this->isTabOwnedByUser($tab_id, $user_id);

			if ($owned) {

				$query = $this->_db->getQuery(true);

				try {
					$query->clear()
						->update($this->_db->quoteName('#__emundus_campaign_candidature'))
						->set($this->_db->quoteName('tab') . ' = NULL')
						->where($this->_db->quoteName('tab') . ' = ' . $tab_id)
						->where($this->_db->quoteName('applicant_id') . ' = ' . $user_id);
					$this->_db->setQuery($query);
					$this->_db->execute();

					$query->clear()
						->delete($this->_db->quoteName('#__emundus_campaign_candidature_tabs'))
						->where($this->_db->quoteName('id') . ' = ' . $tab_id);
					$this->_db->setQuery($query);
					$deleted = $this->_db->execute();
				}
				catch (Exception $e) {
					Log::add('Failed to create for user ' . $user_id . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					$deleted = false;
				}
			}
		}

		return $deleted;
	}

	public function moveToTab($fnum, $tab)
	{
		$moved = false;

		if (!empty($fnum) && !empty($tab)) {
			$tab   = (int) $tab;
			$owned = $this->isTabOwnedByUser($tab, 0, $fnum);

			if ($owned) {

				$query = $this->_db->getQuery(true);

				try {
					$query->clear()
						->update($this->_db->quoteName('#__emundus_campaign_candidature'))
						->set($this->_db->quoteName('tab') . ' = ' . $this->_db->quote($tab))
						->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
					$this->_db->setQuery($query);
					$moved = $this->_db->execute();
				}
				catch (Exception $e) {
					Log::add('Failed to move fnum ' . $fnum . ' in tab ' . $tab . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			}
		}

		return $moved;
	}

	public function copyFile($fnum, $fnum_to)
	{
		$result = false;

		if (!empty($fnum) && !empty($fnum_to)) {
			try {

				$query = $this->_db->getQuery(true);

				$query->insert($this->_db->quoteName('#__emundus_campaign_candidature_links'))
					->set($this->_db->quoteName('date_time') . ' = ' . $this->_db->quote(date('Y-m-d H:i:s')))
					->set($this->_db->quoteName('fnum_from') . ' = ' . $this->_db->quote($fnum))
					->set($this->_db->quoteName('fnum_to') . ' = ' . $this->_db->quote($fnum_to));
				$this->_db->setQuery($query);
				$result = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Failed to copy fnum from ' . $fnum . ' to ' . $fnum_to . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $result;
	}

	public function renameFile($fnum, $new_name)
	{
		$result = false;

		$new_name = trim($new_name);
		if (!empty($fnum) && !empty($new_name)) {
			$regex = '/^[\d\s\p{L}\'"\-]{3,255}$/u';

			if (preg_match($regex, $new_name)) {

				$query = $this->_db->getQuery(true);

				$query->update($this->_db->quoteName('#__emundus_campaign_candidature'))
					->set($this->_db->quoteName('name') . ' = ' . $this->_db->quote($new_name))
					->where($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));

				try {
					$this->_db->setQuery($query);
					$result = $this->_db->execute();
				}
				catch (Exception $e) {
					Log::add('Failed to rename file ' . $fnum . ' with name ' . $new_name . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			}
			else {
				throw new Exception(Text::_('COM_EMUNDUS_INVALID_NAME'));
			}
		}

		return $result;
	}

	public function getCampaignsAvailableForCopy($fnum)
	{
		$campaigns = [];

		try {

			$query = $this->_db->getQuery(true);

			$query->select('esc.training')
				->from($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('ecc.campaign_id'))
				->where($this->_db->quoteName('ecc.fnum') . ' LIKE ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);
			$prog_code = $this->_db->loadResult();

			if (!empty($prog_code)) {
				$query->clear()
					->select('esc.id,esc.label')
					->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
					->where($this->_db->quoteName('esc.training') . ' LIKE ' . $this->_db->quote($prog_code))
					->where($this->_db->quoteName('esc.start_date') . ' < NOW()')
					->where($this->_db->quoteName('esc.end_date') . ' > NOW()');
				$this->_db->setQuery($query);
				$campaigns_object = $this->_db->loadObjectList('id');

				foreach ($campaigns_object as $key => $campaign) {
					$campaigns[$campaign->id] = $campaign->label;
				}
			}
		}
		catch (Exception $e) {
			Log::add('Failed to get available campaigns via fnum ' . $fnum . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $campaigns;
	}

	/**
	 * @param $tab_id
	 * @param $user_id  - if empty, check for fnum
	 * @param $fnum     - if empty, check for user_id
	 *
	 * @return bool
	 */
	public function isTabOwnedByUser($tab_id, $user_id = 0, $fnum = '')
	{
		$owned = false;

		$tab_id  = (int) $tab_id;
		$user_id = (int) $user_id;
		$fnum    = preg_replace('/^[^0-9]+$/', '', $fnum);

		if (!empty($tab_id)) {

			$query = $this->_db->getQuery(true);

			$tab_id_found = 0;
			if (!empty($user_id)) {
				$query->select('ecct.id')
					->from($this->_db->quoteName('#__emundus_campaign_candidature_tabs', 'ecct'))
					->where('ecct.id = ' . $this->_db->quote($tab_id))
					->andWhere('ecct.applicant_id = ' . $this->_db->quote($user_id));

				$this->_db->setQuery($query);
				$tab_id_found = $this->_db->loadResult();
			}
			elseif (!empty($fnum)) {
				$query->select('ecct.id')
					->from($this->_db->quoteName('#__emundus_campaign_candidature_tabs', 'ecct'))
					->leftJoin('#__emundus_campaign_candidature as ecc ON ecc.applicant_id = ecct.applicant_id')
					->where('ecc.fnum = ' . $this->_db->quote($fnum))
					->andWhere('ecct.id = ' . $this->_db->quote($tab_id));

				$this->_db->setQuery($query);
				$tab_id_found = $this->_db->loadResult();
			}
			$owned = !empty($tab_id_found);
		}

		return $owned;
	}

	/**
	 * @param $action
	 * @param $fnum
	 * @param $module_id if not specified, will use the first published module
	 * @param $redirect
	 *
	 * @return bool true if the action was done successfully
	 */
	public function applicantCustomAction($action, $fnum, $module_id = 0, $redirect = false, $user_id = null)
	{
		$done = false;

		if(empty($user_id)) {
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		if (!empty($action) && !empty($fnum)) {
			$query = $this->_db->getQuery(true);

			$query->select('id, params')
				->from('#__modules')
				->where('module LIKE ' . $this->_db->quote('mod_emundus_applications'))
				->andWhere('published = 1');

			if (!empty($module_id)) {
				$query->andWhere('id = ' . $this->_db->quote($module_id));
			}

			$this->_db->setQuery($query);
			$module = $this->_db->loadAssoc();

			if (!empty($module['params'])) {
				$params = json_decode($module['params'], true);

				if (!empty($params['mod_em_application_custom_actions'][$action])) {
					$current_action = $params['mod_em_application_custom_actions'][$action];

					if (isset($current_action['mod_em_application_custom_action_new_status'])) {
						$query->clear()
							->select('status')
							->from('#__emundus_campaign_candidature')
							->where('fnum LIKE ' . $this->_db->quote($fnum));

						$this->_db->setQuery($query);
						$status = $this->_db->loadResult();

						if (in_array($status, $current_action['mod_em_application_custom_action_status']) && $status != $current_action['mod_em_application_custom_action_new_status']) {
							require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
							$m_files = new EmundusModelFiles();
							$updated = $m_files->updateState($fnum, $current_action['mod_em_application_custom_action_new_status'],$user_id);

							if ($updated) {
								$done = true;

								if ($redirect) {
									$app = JFactory::getApplication();
									$app->redirect('/');
								}
							}
						}
					}
				}
			}
		}

		return $done;
	}

	/**
	 * Get shared users of an application file
	 *
	 * @param $ccid
	 * @param $fnum
	 *
	 * @return null
	 *
	 * @since version 1.40.0
	 */
	public function getSharedFileUsers($ccid = null, $fnum = null)
	{
		if (!empty($ccid)) {
			$cache_key = 'shared_file_users_' . $ccid;
		} else {
			$cache_key = 'shared_file_users_' . $fnum;
		}
		$shared_file_users = $this->h_cache->get($cache_key);

		if (empty($shared_file_users) && (!empty($ccid) || !empty($fnum))) {
			$query = $this->_db->getQuery(true);

			$query->select('efr.*,eu.firstname as user_firstname,eu.lastname as user_lastname, eu.profile_picture')
				->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
				->leftJoin($this->_db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->_db->quoteName('eu.user_id') . ' = ' . $this->_db->quoteName('efr.user_id'));
			if (!empty($ccid)) {
				$query->where($this->_db->quoteName('ccid') . ' = ' . $ccid);
			} else {
				$query->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));
			}
			$this->_db->setQuery($query);
			$shared_file_users = $this->_db->loadObjectList();

			if (!empty($shared_file_users)) {
				$this->h_cache->set($cache_key, $shared_file_users);
			}
		}

		return $shared_file_users;
	}

	/**
	 * Share a file with users
	 *
	 * @param $emails
	 * @param $ccid
	 * @param $user_id
	 * @param $auto_accept
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function shareFileWith($emails, $ccid, $user_id = null, $auto_accept = 0)
	{
		$default_rights = [
			'r',
			'u',
			'show_history',
			'show_shared_users',
		];
		$application_module = ModuleHelper::getModule('mod_emundus_applications');
		if (!empty($application_module->id)) {
			$params = json_decode($application_module->params);

			if (!empty($params->mod_emundus_applications_collaborate_default_rights)) {
				$default_rights = $params->mod_emundus_applications_collaborate_default_rights;
			}
		}

		$results = ['status' => true, 'emails' => [], 'failed_emails' => []];
		if (empty($user_id)) {
			$user_id = $this->_user->id;
		}

		$shared_users = $this->getSharedFileUsers($ccid);
		foreach ($shared_users as $shared_user) {
			$index_to_remove = array_search($shared_user->email, $emails);
			if ($index_to_remove !== false) {
				unset($emails[$index_to_remove]);
			}
		}

		if (!empty($emails)) {
			PluginHelper::importPlugin('emundus');
			$query = $this->_db->getQuery(true);

			$query->select('fnum,applicant_id,campaign_id')
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('id') . ' = ' . $ccid);
			$this->_db->setQuery($query);
			$file_info = $this->_db->loadObject();

			foreach ($emails as $email) {
				$query->clear()
					->select('id')
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('email') . ' = ' . $this->_db->quote($email));
				$this->_db->setQuery($query);
				$shared_user_id = $this->_db->loadResult();

				if (!empty($shared_user_id)) {
					$query->clear()
						->select('firstname,lastname')
						->from($this->_db->quoteName('#__emundus_users'))
						->where($this->_db->quoteName('user_id') . ' = ' . $shared_user_id);
					$this->_db->setQuery($query);
					$shared_user_infos = $this->_db->loadObject();
				}

				$columns = [
					'time_date',
					'student_id',
					'fnum',
					'keyid',
					'campaign_id',
					'email',
					'ccid',
					'user_id',
					'r',
					'u',
					'show_history',
					'show_shared_users',
					'uploaded'
				];

				$key = md5(date('Y-m-d h:m:i') . '::' . $file_info->fnum . '::' . $file_info->applicant_id . '::' . $email . '::' . rand());
				$values = [
					$this->_db->quote(EmundusHelperDate::getNow()),
					$file_info->applicant_id,
					$this->_db->quote($file_info->fnum),
					$this->_db->quote($key),
					$file_info->campaign_id,
					$this->_db->quote($email),
					$ccid,
					(int)$shared_user_id,
					in_array('r', $default_rights) ? 1 : 0,
					in_array('u', $default_rights) ? 1 : 0,
					in_array('show_history', $default_rights) ? 1 : 0,
					in_array('show_shared_users', $default_rights) ? 1 : 0,
					$auto_accept
				];

				$query->clear()
					->insert($this->_db->quoteName('#__emundus_files_request'))
					->columns($columns)
					->values(implode(',', $values));

				try {
					$this->_db->setQuery($query);
					$shared = $this->_db->execute();

					if ($shared) {
						$results['emails'][$email] = $key;

						Factory::getApplication()->triggerEvent('onCallEventHandler', array(
								'onAfterShareFileWith',
								[
									'email' => $email,
									'fnum' => $file_info->fnum,
									'applicant_id' => $file_info->applicant_id,
									'ccid' => $ccid,
									'shared_user_infos' => $shared_user_infos
								]
							)
						);
					} else {
						$results['failed_emails'][] = $email;
					}
				} catch (Exception $e) {
					$results['status'] = false;
					Log::add('Failed to share file with ccid ' . $ccid . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			}

			$cache_key = 'shared_file_users_' . $ccid;
			$this->h_cache->set($cache_key, []);
		}

		return $results;
	}

	/**
	 *
	 * Get link to the collaboration page
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since version 1.40.0
	 */
	public function getCollaborationAcceptionLink()
	{
		$collaboration_url = '';

		$emundus_config = ComponentHelper::getParams('com_emundus');
		$collaboration_id = $emundus_config->get('collaborate_link', 0);

		if (!empty($collaboration_id)) {
			$menu_item = Factory::getApplication()->getMenu()->getItems('id', $collaboration_id, true);

			if ($menu_item->type === 'url' && strpos($menu_item->link, 'http') !== false) {
				$collaboration_url = $menu_item->link;
			} else {
				$collaboration_url = Uri::base() . $menu_item->alias . '/';
			}

			if (strpos($collaboration_url, '?') !== false) {
				$collaboration_url .= '&key=';
			} else {
				$collaboration_url .= '?key=';
			}
		}

		return $collaboration_url;
	}

	/**
	 * Remove a shared user from a file
	 *
	 * @param $request_id
	 * @param $ccid
	 * @param $user_id
	 *
	 * @return false
	 *
	 * @since version 1.40.0
	 */
	public function removeSharedUser(int $request_id, int $ccid, int $user_id): bool
	{
		$removed = false;
		if (empty($user_id)) {
			$user_id = $this->_user->id;
		}

		try {
			$query = $this->_db->getQuery(true);

			$query->select('efr.email, u.id as user_id')
				->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
				->leftJoin($this->_db->quoteName('#__users', 'u') . ' ON ' . $this->_db->quoteName('u.email') . ' = ' . $this->_db->quoteName('efr.email'))
				->where($this->_db->quoteName('efr.id') . ' = ' . $request_id)
				->where($this->_db->quoteName('efr.ccid') . ' = ' . $ccid);
			$this->_db->setQuery($query);
			$sharedUser = $this->_db->loadAssoc();

			$query->clear()
				->delete($this->_db->quoteName('#__emundus_files_request'))
				->where($this->_db->quoteName('id') . ' = ' . $request_id)
				->where($this->_db->quoteName('ccid') . ' = ' . $ccid);
			$this->_db->setQuery($query);
			$removed = $this->_db->execute();

			if ($removed) {
				if (!class_exists('EmundusHelperFiles')) {
					require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
				}
				$fnum = EmundusHelperFiles::getFnumFromId($ccid);
				$query->clear()
					->delete($this->_db->quoteName('#__emundus_users_assoc'))
					->where($this->_db->quoteName('user_id') . ' = ' . $sharedUser['id'])
					->andWhere($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));

				$this->_db->setQuery($query);
				$this->_db->execute();

				PluginHelper::importPlugin('emundus');
				$dispatcher = Factory::getApplication()->getDispatcher();
				$onAfterRemoveSharedUser = new GenericEvent('onCallEventHandler', ['onAfterRemoveSharedUser', ['request_id' => $request_id, 'ccid' => $ccid, 'email' => $sharedUser['email']]]);
				$dispatcher->dispatch('onCallEventHandler', $onAfterRemoveSharedUser);
			}
		}
		catch (Exception $e) {
			Log::add('Failed to remove shared user via request_id ' . $request_id . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $removed;
	}

	/**
	 * Regenerate a collaboration key
	 *
	 * @param $request_id
	 * @param $ccid
	 * @param $user_id
	 *
	 * @return array
	 *
	 * @since version 1.40.0
	 */
	public function regenerateKey($request_id,$ccid,$user_id)
	{
		$results = ['status' => true, 'email' => '', 'key' => ''];
		if(empty($user_id)) {
			$user_id = $this->_user->id;
		}

		try {
			$query = $this->_db->getQuery(true);

			$query->select('fnum,applicant_id,campaign_id')
				->from($this->_db->quoteName('#__emundus_campaign_candidature'))
				->where($this->_db->quoteName('id') . ' = ' . $ccid);
			$this->_db->setQuery($query);
			$file_info = $this->_db->loadObject();

			if(!empty($file_info)) {
				$results['key'] = md5(date('Y-m-d h:m:i') . '::' . $file_info->fnum . '::' . $file_info->applicant_id . '::' . rand());

				$query->clear()
					->update($this->_db->quoteName('#__emundus_files_request'))
					->set($this->_db->quoteName('keyid') . ' = ' . $this->_db->quote($results['key']))
					->where($this->_db->quoteName('id') . ' = ' . $request_id)
					->where($this->_db->quoteName('ccid') . ' = ' . $ccid);
				$this->_db->setQuery($query);
				$results['status'] = $this->_db->execute();

				if($results['status']) {
					$query->clear()
						->select('email')
						->from($this->_db->quoteName('#__emundus_files_request'))
						->where($this->_db->quoteName('id') . ' = ' . $request_id);
					$this->_db->setQuery($query);
					$results['email'] = $this->_db->loadResult();
				}
			}

			//TODO: Log this action
		}
		catch (Exception $e) {
			Log::add('Failed to remove shared user via request_id ' . $request_id . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $results;
	}

	/**
	 * Update right of user on a shared file
	 *
	 * @param $request_id
	 * @param $ccid
	 * @param $right
	 * @param $value
	 *
	 * @return false
	 *
	 * @since version 1.40.0
	 */
	public function updateRight($request_id, $ccid, $right, $value)
	{
		$updated = false;

		if (!empty($request_id) && !empty($ccid) && !empty($right)) {
			try {
				$query = $this->_db->getQuery(true);

				$query->update($this->_db->quoteName('#__emundus_files_request'))
					->set($this->_db->quoteName($right) . ' = ' . (int)$value)
					->where($this->_db->quoteName('id') . ' = ' . $request_id)
					->where($this->_db->quoteName('ccid') . ' = ' . $ccid);
				$this->_db->setQuery($query);
				$updated = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Failed to update right via request_id ' . $request_id . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}

			if ($updated) {
				$this->h_cache->set('shared_file_users_' . $ccid, null);
			}
		}

		return $updated;
	}

	/**
	 * Get files shared with me
	 *
	 * @param $user_id
	 *
	 * @return null
	 *
	 * @since version 1.40.0
	 */
	public function getMyFilesRequests($user_id = null)
	{
		if(empty($user_id)) {
			$user_id = $this->_user->id;
		}

		$cache_key      = 'my_shared_files_' . $user_id;
		$files = $this->h_cache->get($cache_key);

		if (empty($files)) {
			try {
				$query = $this->_db->getQuery(true);

				$query->select('efr.r,efr.u,efr.show_history,efr.show_shared_users,ecc.id,ecc.fnum,ecc.applicant_id,ecc.campaign_id,ecc.status,ecc.published,ecc.form_progress,ecc.attachment_progress, esc.label, esc.start_date, esc.end_date, esc.admission_start_date, esc.admission_end_date, esc.training, esc.year, esc.profile_id')
					->from($this->_db->quoteName('#__emundus_files_request', 'efr'))
					->leftJoin($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->_db->quoteName('ecc.id') . ' = ' . $this->_db->quoteName('efr.ccid'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->_db->quoteName('esc.id') . ' = ' . $this->_db->quoteName('ecc.campaign_id'))
					->where($this->_db->quoteName('efr.user_id') . ' = ' . $user_id)
					->where($this->_db->quoteName('ecc.published') . ' = 1')
					->where($this->_db->quoteName('efr.uploaded') . ' = 1');
				$this->_db->setQuery($query);
				$files = $this->_db->loadObjectList('fnum');

				if(!empty($files)) {
					$this->h_cache->set($cache_key,$files);
				}
			}
			catch (Exception $e) {
				Log::add('Failed to get my files requests with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $files;
	}

	/**
	 * Get elements locked by the owner of the file
	 *
	 * @param $fid
	 * @param $fnum
	 * @param $user_id
	 *
	 * @return array|mixed
	 *
	 * @since version 1.40.0
	 */
	public function getLockedElements($fid,$fnum,$user_id = null)
	{
		$locked_elements = [];

		if(empty($user_id)) {
			$user_id = $this->_user->id;
		}

		if(!empty($fid) && !empty($fnum)) {
			try {
				$query = $this->_db->getQuery(true);

				$query->select('locked_elements')
					->from($this->_db->quoteName('#__emundus_campaign_candidature'))
					->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));
				$this->_db->setQuery($query);
				$locked_elements = $this->_db->loadResult();

				if(!empty($locked_elements)) {
					$locked_elements = json_decode($locked_elements, true);
					if(!empty($locked_elements[$fid])) {
						$locked_elements = $locked_elements[$fid];
					} else {
						$locked_elements = [];
					}
				} else {
					$locked_elements = [];
				}
			}
			catch (Exception $e) {
				Log::add('Failed to get locked elements of form ' . $fid . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $locked_elements;
	}

	/**
	 * Lock a Fabrik element
	 *
	 * @param $element
	 * @param $fid
	 * @param $ccid
	 * @param $state
	 * @param $user_id
	 *
	 * @return false
	 *
	 * @since version 1.40.0
	 */
	public function lockElement($element,$fid,$ccid,$state = 1,$user_id = null) {
		$locked = false;

		if(empty($user_id)) {
			$user_id = $this->_user->id;
		}

		if(!empty($element) && !empty($fid) && !empty($ccid)) {
			try {
				$query = $this->_db->getQuery(true);

				$query->select('locked_elements')
					->from($this->_db->quoteName('#__emundus_campaign_candidature'))
					->where($this->_db->quoteName('id') . ' = ' . $ccid);
				$this->_db->setQuery($query);
				$locked_elements = $this->_db->loadResult();

				if (!empty($locked_elements)) {
					$locked_elements = json_decode($locked_elements, true);
				}
				else {
					$locked_elements = [];
				}

				if($state == 1) {
					$locked_elements[$fid][] = $element;
				} else {
					$index = array_search($element,$locked_elements[$fid]);
					if($index !== false) {
						unset($locked_elements[$fid][$index]);
					}
				}

				$query->clear()
					->update($this->_db->quoteName('#__emundus_campaign_candidature'))
					->set($this->_db->quoteName('locked_elements') . ' = ' . $this->_db->quote(json_encode($locked_elements)))
					->where($this->_db->quoteName('id') . ' = ' . $ccid);
				$this->_db->setQuery($query);
				$locked = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Failed to lock element ' . $element . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $locked;
	}

	/**
	 * Save a form session to retrieve datas later
	 *
	 * @param $element
	 * @param $fid
	 * @param $value
	 * @param $fnum
	 * @param $user_id
	 *
	 * @return false
	 *
	 * @since version 1.40.0
	 */
	public function saveFormSession($element,$fid,$value,$fnum,$user_id = null)
	{
		$saved = false;

		if(empty($user_id)) {
			$user_id = $this->_user->id;
		}

		if(!empty($element) && !empty($fid) && !empty($fnum)) {
			try {
				$query = $this->_db->getQuery(true);

				$query->select('data')
					->from($this->_db->quoteName('#__fabrik_form_sessions'))
					->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum))
					->where($this->_db->quoteName('form_id') . ' = ' . $this->_db->quote($fid))
					->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($user_id));
				$this->_db->setQuery($query);
				$datas = $this->_db->loadResult();

				if (!empty($datas)) {
					$datas = json_decode($datas, true);
				}
				else {
					$datas = [];
				}

				$datas[$element] = $value;

				$query->clear()
					->update($this->_db->quoteName('#__fabrik_form_sessions'))
					->set($this->_db->quoteName('data') . ' = ' . $this->_db->quote(json_encode($datas)))
					->set($this->_db->quoteName('last_update') . ' = ' . $this->_db->quote(time()))
					->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum))
					->where($this->_db->quoteName('form_id') . ' = ' . $this->_db->quote($fid))
					->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($user_id));
				$this->_db->setQuery($query);
				$saved = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Failed to save form session for element ' . $element . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $saved;
	}

	/**
	 * Clear a form session
	 *
	 * @param $fid
	 * @param $fnum
	 * @param $user_id
	 *
	 * @return false
	 *
	 * @since version 1.40.0
	 */
	public function clearFormSession($fid,$fnum,$user_id = null)
	{
		$cleared = false;

		if(empty($user_id)) {
			$user_id = $this->_user->id;
		}

		if(!empty($fid) && !empty($fnum)) {
			try {
				$query = $this->_db->getQuery(true);

				$query->clear()
					->delete($this->_db->quoteName('#__fabrik_form_sessions'))
					->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum))
					->where($this->_db->quoteName('form_id') . ' = ' . $this->_db->quote($fid))
					->where($this->_db->quoteName('user_id') . ' = ' . $this->_db->quote($user_id));
				$this->_db->setQuery($query);
				$cleared = $this->_db->execute();
			}
			catch (Exception $e) {
				Log::add('Failed to clear form session for form ' . $fid . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $cleared;
	}

    /**
     * Retrieve Fabrik Groups associated to a fnum (campaign form and phases)
     *
     * @param $fnum
     *
     * @return array
     *
     */
    public function getFabrikDataByFnum(string $fnum, string $type = 'form', bool $use_evaluation_forms = true): array
    {
        $result = [];

        if (!empty($fnum) && !empty($type)) {
            require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
	        require_once(JPATH_SITE . '/components/com_emundus/models/files.php');

            $m_profile  = new EmundusModelProfile;
            $m_files    = new EmundusModelFiles;

            $query = $this->_db->createQuery();

            $fnumInfos  = $m_files->getFnumInfos($fnum);
            $profiles = $m_profile->getProfilesIDByCampaign([$fnumInfos['campaign_id']]);

			if (!empty($profiles)) {
				require_once(JPATH_SITE . '/components/com_emundus/models/form.php');
				$m_form = new EmundusModelForm();

				$forms = [102]; // Default form
                foreach ($profiles as $profile) {
                    $forms_data = $m_form->getFormsByProfileId($profile);
                    if (!empty($forms_data)) {
                        $forms_data_ids = array_map(function($form) {
                            return $form->id;
                        }, $forms_data);

                        $forms = array_merge($forms, $forms_data_ids);
                    }
                }

				require_once(JPATH_SITE . '/components/com_emundus/models/workflow.php');
				$m_workflow = new EmundusModelWorkflow();
				$workflow_data = $m_workflow->getWorkflowByFnum($fnum);

				if (!empty($workflow_data['steps'])) {
					foreach($workflow_data['steps'] as $step) {
						if ($m_workflow->isEvaluationStep($step->type) && $use_evaluation_forms) {
							if (!in_array($step->form_id, $forms)) {
								$forms[] = $step->form_id;
							}
						}
					}
				}

				if (!empty($forms)) {
					$forms = array_unique($forms);

					switch ($type) {
						case 'list':
							// Retrieve the list ids
							$query->clear()
								->select('jfl.id')
								->from($this->_db->quoteName('#__fabrik_lists', 'jfl'))
								->where('jfl.form_id IN (' . implode(',', $this->_db->quote($forms)) . ')')
								->andWhere('jfl.published = 1');

							try {
								$this->_db->setQuery($query);
								$result = $this->_db->loadColumn();
							} catch (Exception $e) {
								Log::add('Failed to get Fabrik lists on query: ' . $query->__toString() . ' with error -> ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
							}
							break;
						case 'group':
						case 'element':
							// Retrieve the group ids
							$groups = [];
							$query->clear()
								->select('jffg.group_id')
								->from($this->_db->quoteName('#__fabrik_formgroup', 'jffg'))
								->leftJoin($this->_db->quoteName('#__fabrik_groups', 'jfg') . ' ON ' . $this->_db->quoteName('jfg.id') . ' = ' . $this->_db->quoteName('jffg.group_id'))
								->where('jffg.form_id IN (' . implode(',', $this->_db->quote($forms)) . ')')
								->andWhere('jfg.published = 1');

							try {
								$this->_db->setQuery($query);
								$groups = $this->_db->loadColumn();
							} catch (Exception $e) {
								Log::add('Failed to get Fabrik groups on query: ' . $query->__toString() . ' with error -> ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
							}

							if (!empty($groups)) {
								if ($type == 'group') {
									$result = $groups;
								} else {
									// Retrieve the element ids
									$query->clear()
										->select('jfe.id')
										->from($this->_db->quoteName('#__fabrik_elements', 'jfe'))
										->where('jfe.group_id IN (' . implode(',', $this->_db->quote($groups)) . ')')
										->andWhere('jfe.published = 1');

									try {
										$this->_db->setQuery($query);
										$result = $this->_db->loadColumn();
									} catch (Exception $e) {
										Log::add('Failed to get Fabrik elements on query: ' . $query->__toString() . ' with error -> ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
									}
								}
							}
							break;
						default:
							$result = $forms;
							break;
					}
				}
			}
        }

        return $result;
    }
}
