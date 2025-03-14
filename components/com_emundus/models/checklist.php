<?php
/**
 * CheckList model : displays applicant checklist (docs and forms).
 *
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     eMundus SAS - Jonas Lerebours
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;

class EmundusModelChecklist extends JModelList
{
	private $app;
	private $_user;
	protected $_db;
	private $_need = 0;
	protected $_forms = 0;
	private $_attachments = 0;

	function __construct($student_id = null)
	{
		parent::__construct();
		jimport('joomla.log.log');
		JLog::addLogger(['text_file' => 'com_emundus.checklist.php'], JLog::ALL, array('com_emundus.checklist'));

		require_once(JPATH_SITE . '/components/com_emundus/helpers/menu.php');

		$this->app  = Factory::getApplication();
		$student_id = !empty($student_id) ?? $this->app->input->getInt('sid', 0);

		if (version_compare(JVERSION, '4.0', '>')) {
			$this->_db    = Factory::getContainer()->get('DatabaseDriver');
			if(Factory::getApplication()->isClient('site'))
			{
				$current_user = $this->app->getIdentity()->id;
			}

			if (!empty($student_id) && is_numeric($student_id)) {
				$this->_user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($student_id);
			}
			else {
				$this->_user = $this->app->getSession()->get('emundusUser');
			}
		}
		else {
			$this->_db    = Factory::getDBO();
			$current_user = Factory::getUser()->id;

			if (!empty($student_id) && is_numeric($student_id)) {
				$this->_user = Factory::getUser($student_id);
			}
			else {
				$this->_user = Factory::getSession()->get('emundusUser');
			}
		}


		if (!empty($student_id) && is_numeric($student_id)) {
			if (EmundusHelperAccess::asPartnerAccessLevel($current_user)) {

				if (!empty($this->_user->id)) {
					$query = $this->_db->getQuery(true);

					$query->select('jeu.profile')
						->from($this->_db->quoteName('#__emundus_users', 'jeu'))
						->where('jeu.user_id = ' . $this->_user->id);

					try {
						$this->_db->setQuery($query);
						$profile = $this->_db->loadResult();

						if (!empty($profile)) {
							$this->_user->profile = $profile;
						}
					}
					catch (Exception $e) {
						JLog::add('Failed to get user profile ' . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
					}
				}
				else {
					JLog::add('User ' . $current_user . ' tried to read checklist of user ' . $student_id . ' but user does not exists.', JLog::INFO, 'com_emundus.checklist');
					$this->app->enqueueMessage(JText::_('COM_USERS_USER_NOT_FOUND'), 'warning');
				}
			}
			else {
				JLog::add('[' . $_SERVER['REMOTE_ADDR'] . '] User ' . $current_user . ' tried to read checklist of user ' . $student_id . ' but does not have the rights to do it.', JLog::WARNING, 'com_emundus.checklist');
				$this->app->enqueueMessage(JText::_('ACCESS_DENIED'), 'warning');
				$this->app->redirect('/checklist');
			}
		}
	}

	function getGreeting()
	{
		$query = 'SELECT id, title, text FROM #__emundus_setup_checklist WHERE page = "checklist" ';
		$note  = 0;
		if ($note && is_numeric($note) && $note > 1) {
			$this->_need = $note;
		}
		$query .= 'AND (whenneed = ' . $this->_need . ' OR whenneed=' . $this->_user->status . ')';
		$this->_db->setQuery($query);

		return $this->_db->loadObject();
	}

	function getInstructions()
	{
		$query = $this->_db->getQuery(true);

		$query->select('id, title, text')
			->from($this->_db->quoteName('#__emundus_setup_checklist'))
			->where($this->_db->quoteName('page') . ' = ' . $this->_db->quote('instructions'));
		$this->_db->setQuery($query);

		return $this->_db->loadObject();
	}

	function getFormsList()
	{
		$forms = EmundusHelperMenu::buildMenuQuery($this->_user->profile);

		foreach ($forms as $form) {
			$query = $this->_db->getQuery(true);

			$query->select('COUNT(*)')
				->from($this->_db->quoteName($form->db_table_name))
				->where($this->_db->quoteName('user') . ' = ' . $this->_db->quote($this->_user->id))
				->andWhere($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($this->_user->fnum));
			$this->_db->setQuery($query);
			$form->nb = $this->_db->loadResult();
			if ($form->nb == 0) {
				$this->_forms = 1;
				$this->_need  = $this->_attachments == 1 ?: 0;
			}
		}

		return $forms;
	}

	function getAttachmentsList()
	{
		$attachments = [];

		if (!empty($this->_user->profile)) {
			$attachments = $this->getAttachmentsForProfile($this->_user->profile, $this->_user->campaign_id);

			foreach ($attachments as $attachment) {
				$query = $this->_db->getQuery(true);

				$query->select('COUNT(*)')
					->from('#__emundus_uploads')
					->where('user_id = ' . $this->_user->id)
					->where('attachment_id = ' . $attachment->id)
					->where('fnum like ' . $this->_db->quote($this->_user->fnum));

				$this->_db->setQuery($query);
				$attachment->nb = $this->_db->loadResult();
			}

			foreach ($attachments as $attachment) {
				if ($attachment->nb > 0) {

					$query = 'SELECT * FROM #__emundus_uploads WHERE user_id = ' . $this->_user->id . ' AND attachment_id = ' . $attachment->id . ' AND fnum like ' . $this->_db->Quote($this->_user->fnum);
					$this->_db->setQuery($query);
					$attachment->liste = $this->_db->loadObjectList();

				} elseif ($attachment->mandatory == 1) {
					$this->_attachments = 1;
					$this->_need = $this->_forms = 1 ?? 0;
				}
			}
		}

		return $attachments;
	}

	public function getAttachmentsForCampaignId($campaign_id)
	{
		$attachments = [];

		if (!empty($campaign_id)) {
			$query = $this->_db->getQuery(true);

			$query->select('DISTINCT attachments.id, attachments.nbmax, attachments.value, attachments.lbl, attachments.description, attachments.allowed_types, profiles.mandatory, profiles.duplicate, profiles.has_sample, profiles.sample_filepath')
				->from($this->_db->quoteName('#__emundus_setup_attachments', 'attachments'))
				->leftJoin($this->_db->quoteName('#__emundus_setup_attachment_profiles', 'profiles') . ' ON attachments.id = profiles.attachment_id')
				->where('profiles.campaign_id = ' . $campaign_id)
				->andWhere('profiles.displayed = 1')
				->andWhere('attachments.published = 1')
				->order('profiles.mandatory DESC, profiles.ordering ASC');

			try {
				$this->_db->setQuery($query);
				$attachments = $this->_db->loadObjectList();
			} catch (Exception $e) {
				JLog::add('Failed to get attachments for campaign ' . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
			}
		}

		return $attachments;
	}

	/**
	 * Get attachments for a profile
	 * Be aware that this method will dispatch onAfterGetAttachmentsForProfile event
	 * This event can be used to add or remove attachments from the list based on some conditions
	 * @param $profile_id
	 * @param $campaign_id
	 * @return array|mixed
	 */
	public function getAttachmentsForProfile($profile_id, $campaign_id = null)
	{
		$attachments = [];

		if (!empty($profile_id)) {
			if (!empty($campaign_id)) {
				$attachments = $this->getAttachmentsForCampaignId($campaign_id);
			}

			if (empty($attachments)) {
				$query = $this->_db->getQuery(true);

				$query->select('DISTINCT attachments.id, attachments.nbmax, attachments.value, attachments.lbl, attachments.description, attachments.allowed_types, profiles.mandatory, profiles.duplicate, profiles.has_sample, profiles.sample_filepath')
					->from($this->_db->quoteName('#__emundus_setup_attachments', 'attachments'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_attachment_profiles', 'profiles') . ' ON attachments.id = profiles.attachment_id')
					->where('profiles.profile_id = ' . $profile_id)
					->andWhere('profiles.campaign_id IS NULL')
					->andWhere('profiles.displayed = 1')
					->andWhere('attachments.published = 1')
					->order('profiles.mandatory DESC, profiles.ordering ASC');

				try {
					$this->_db->setQuery($query);
					$attachments = $this->_db->loadObjectList();
				} catch (Exception $e) {
					JLog::add('Failed to get attachments for campaign ' . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
				}
			}

			// Sometimes mandatory attachments are linked to the profile but also to some form fields.
			// To allow that, we dispatch an event onAfterGetMandatoryAttachmentsForProfile
			// to allow other components to add/remove their own mandatory attachments.

			JPluginHelper::importPlugin('emundus', 'custom_event_handler');
			\Joomla\CMS\Factory::getApplication()->triggerEvent('onAfterGetAttachmentsForProfile', array($profile_id, &$attachments));
			\Joomla\CMS\Factory::getApplication()->triggerEvent('onCallEventHandler', ['onAfterGetAttachmentsForProfile', ['profile_id' => $profile_id, 'attachments' => &$attachments]]);
		}

		return $attachments;
	}

	function getNeed()
	{
		return $this->_need;
	}

	function getSent()
	{
		$query = $this->_db->getQuery(true);

		$query->select('submitted')
			->from($this->_db->quoteName('#__emundus_campaign_candidature'))
			->where($this->_db->quoteName('applicant_id') . ' = ' . $this->_db->quote($this->_user->id))
			->andWhere($this->_db->quoteName('fnum') . ' like ' . $this->_db->quote($this->_user->fnum));
		$this->_db->setQuery($query);
		$res = $this->_db->loadResult();

		return $res > 0;
	}

	function getResult()
	{
		$query = $this->_db->getQuery(true);

		$query->select('final_grade')
			->from($this->_db->quoteName('#__emundus_final_grade'))
			->where($this->_db->quoteName('student_id') . ' = ' . $this->_db->quote($this->_user->id))
			->andWhere($this->_db->quoteName('fnum') . ' like ' . $this->_db->quote($this->_user->fnum));
		$this->_db->setQuery($query);

		return $this->_db->loadResult();
	}

	function getApplicant()
	{
		$query = 'SELECT profile FROM #__emundus_users WHERE user_id = ' . $this->_user->id;
		$this->_db->setQuery($query);
		if ($this->_db->loadResult() == 8) {
			return false;
		}

		return true;
	}

	function getIsOtherActiveCampaign()
	{
		$query = $this->_db->getQuery(true);

		$query->select('COUNT(id) as cpt')
			->from($this->_db->quoteName('#__emundus_setup_campaigns'))
			->where($this->_db->quoteName('id') . ' NOT IN (SELECT campaign_id FROM #__emundus_campaign_candidature WHERE applicant_id = ' . $this->_user->id . ')');
		$this->_db->setQuery($query);
		$cpt = $this->_db->loadResult();

		return $cpt > 0;
	}

	function getConfirmUrl($profile = null)
	{
		$confirm_url = '';
		if (empty($profile)) {
			$profile = $this->_user->profile;
		}

		if (!empty($profile)) {
			$query = $this->_db->getQuery(true);

			$query->select('CONCAT(m.link,"&Itemid=", m.id) as link')
				->from($this->_db->quoteName('#__emundus_setup_profiles', 'esp'))
				->leftJoin($this->_db->quoteName('#__menu', 'm') . ' ON ' . $this->_db->quoteName('m.menutype') . ' = ' . $this->_db->quoteName('esp.menutype'))
				->where($this->_db->quoteName('esp.id') . ' = ' . $profile)
				->andWhere('m.published > 0')
				->andWhere('m.level = 1')
				->order('m.lft DESC');

			try {
				$this->_db->setQuery($query);
				$confirm_url = $this->_db->loadResult();
			}
			catch (Exception $e) {
				JLog::add('Failed to get confirm url from profile ' . $profile . ' ' . $e->getMessage(), JLog::ERROR, 'com_emundus.checklist');
			}
		}
		else {
			JLog::add('Failed to get confirm url from profile because profile is not set', JLog::WARNING, 'com_emundus.checklist');
		}

		return $confirm_url;
	}


	function setDelete($can_be_deleted = 0, $student = null)
	{
		$toggled = false;

		if (empty($student)) {
			$session = $this->app->getSession();
			$student = $session->get('emundusUser');
		}
		$can_be_deleted = $can_be_deleted >= 1 ? 1 : 0;

		$query = $this->_db->getQuery(true);

		$query->update($this->_db->quoteName('#__emundus_uploads'))
			->set($this->_db->quoteName('can_be_deleted') . ' = ' . $can_be_deleted)
			->where($this->_db->quoteName('user_id') . ' = ' . $student->id)
			->andWhere($this->_db->quoteName('fnum') . ' like ' . $this->_db->quote($student->fnum));

		if ($can_be_deleted === 1) {
			$emundus_config = ComponentHelper::getParams('com_emundus');
			$attachment_ids = $emundus_config->get('attachment_to_keep_non_deletable', []);

			if (!empty($attachment_ids)) {
				$query->andWhere($this->_db->quoteName('attachment_id') . ' NOT IN (' . implode(',', $attachment_ids) . ')');
			}
		}

		try {
			$this->_db->setQuery($query);
			$toggled = $this->_db->execute();
		} catch (Exception $e) {
			JLog::add('Error in model/checklist at query : '.$query, JLog::ERROR, 'com_emundus');
		}

		return $toggled;
	}


	public function formatFileName(string $file, string $fnum, array $post = []): string
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
		$m_emails = new EmundusModelEmails;

		$aid            = intval(substr($fnum, 21, 7));
		$tags           = $m_emails->setTags($aid, $post, $fnum, '', $file);
		$formatted_file = preg_replace($tags['patterns'], $tags['replacements'], $file);
		$formatted_file = $m_emails->setTagsFabrik($formatted_file, array($fnum));

		// Format filename
		$formatted_file = $m_emails->stripAccents($formatted_file);
		$formatted_file = preg_replace('/[^A-Za-z0-9 _.-]/', '', $formatted_file);
		$formatted_file = preg_replace('/\s/', '', $formatted_file);

		return strtolower($formatted_file);
	}
}

