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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;

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
class PlgFabrik_FormEmundusCampaign extends plgFabrik_Form
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

		if ($params->get($pname) == '') {
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

		if ($params->get($pname) == '') {
			return $default;
		}

		return $params->get($pname);
	}

	public function onBeforeLoad()
	{
		$formModel = $this->getModel();
		$current_url = Uri::getInstance()->toString();
		$parse       = parse_url($current_url);

		if (strpos($current_url, 'redirect') !== false) {
			$new_url = str_replace($parse['scheme'] . '://' . $parse['host'], '', strstr($current_url, '&redirect=', true));
			$this->app->redirect($new_url);
		}

		$applicant_can_renew = ComponentHelper::getParams('com_emundus')->get('applicant_can_renew', '0');
		$cid = $this->app->getInput()->getInt('cid');
		if(!empty($cid)) {
			require_once JPATH_SITE . '/components/com_emundus/models/campaign.php';
			$m_campaign = new EmundusModelCampaign;
			$allowed_campaigns = $m_campaign->getAllowedCampaign($this->app->getIdentity()->id);

			if (!in_array($cid, $allowed_campaigns)) {
				switch ($applicant_can_renew) {
					case 0:
						$message = Text::_('CANNOT_HAVE_MULTI_FILE');
						break;
					case 2:
						$message = Text::_('USER_HAS_FILE_FOR_CAMPAIGN');
						break;
					case 3:
						$message = Text::_('USER_HAS_FILE_FOR_YEAR');
						break;
					default:
						$message = Text::_('USER_HAS_NO_ACCESS_TO_CAMPAIGN');
				}

				$this->app->enqueueMessage(Text::_($message), 'error');
				$this->app->redirect('index.php');
			}

			$formModel->data['jos_emundus_campaign_candidature___campaign_id_raw'] = $cid;
			$formModel->data['jos_emundus_campaign_candidature___campaign_id'] = $cid;
		}
	}

	/**
	 * Main script.
	 *
	 * @return Bool
	 * @throws Exception
	 */
	public function onBeforeCalculations()
	{
		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_emundus.campaign.php'), Log::ALL, array('com_emundus'));

		include_once(JPATH_BASE . '/components/com_emundus/models/profile.php');
		$m_profile = new EmundusModelProfile;

		$session   = $this->app->getSession();
		$form_type = $this->getParam('form_type', 'cc');

		$timezone = new DateTimeZone($this->app->get('offset'));
		$now      = Factory::getDate()->setTimezone($timezone);

		$query = $this->_db->getQuery(true);

		$formModel = $this->getModel();

		// This allows the plugin to be run from a different context while retaining the same functionality.
		switch ($form_type) {

			case 'user':
				$query->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('email') . ' LIKE ' . $this->_db->quote($formModel->formData['email_raw']));
				$this->_db->setQuery($query);
				try {
					$user = $this->_db->loadResult();
					if (empty($user)) {
						return false;
					}
				}
				catch (Exception $e) {
					return false;
				}

				$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user);

				$campaign_id = $formModel->formData['campaign_id_raw'];

				$campaign_id = is_array($campaign_id) ? $campaign_id[0] : $campaign_id;
				if (empty($campaign_id)) {
					return false;
				}

				$query->clear()
					->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__emundus_setup_campaigns'))
					->where($this->_db->quoteName('id') . ' = ' . $campaign_id);
				$this->_db->setQuery($query);
				try {
					if (empty($this->_db->loadResult())) {
						return false;
					}
				}
				catch (Exception $e) {
					return false;
				}

				// create new fnum
				require_once JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php';
				$fnum = EmundusHelperFiles::createFnum($campaign_id, $user->id);

				$query->clear()
					->insert($this->_db->quoteName('#__emundus_campaign_candidature'))
					->columns($this->_db->quoteName(['date_time', 'applicant_id', 'user_id', 'campaign_id', 'fnum']))
					->values($this->_db->quote($now) . ', ' . $user->id . ', ' . $user->id . ', ' . $campaign_id . ', ' . $this->_db->quote($fnum));
				break;

			case 'cc':
			default:
				$user = $session->get('emundusUser');
				if (empty($user)) {
					$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user);
				}
				$fnum_tmp    = $formModel->formData['fnum'];
				$id          = $formModel->formData['id'];
				$campaign_id = $formModel->formData['campaign_id_raw'];

				$campaign_id = is_array($campaign_id) ? $campaign_id[0] : $campaign_id;
				if (empty($campaign_id)) {
					return false;
				}

				// create new fnum
				require_once JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php';
				$fnum = EmundusHelperFiles::createFnum($campaign_id, $user->id);

				$query->update($this->_db->quoteName('#__emundus_campaign_candidature'))
					->set($this->_db->quoteName('fnum') . ' = ' . $this->_db->Quote($fnum))
					->set($this->_db->quoteName('date_time') . ' = ' . $this->_db->quote($now))
					->where($this->_db->quoteName('id') . ' = ' . $id . ' AND ' . $this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->Quote($fnum_tmp) . ' AND ' . $this->_db->quoteName('campaign_id') . '=' . $campaign_id);
				break;
		}

		try {
			$this->_db->setQuery($query);
			$this->_db->execute();

			PluginHelper::importPlugin('emundus');

			$this->app->triggerEvent('onCreateNewFile', [$user->id, $fnum, $campaign_id]);
			$this->app->triggerEvent('onCallEventHandler', ['onCreateNewFile', ['user_id' => $user->id, 'fnum' => $fnum, 'cid' => $campaign_id]]);

		}
		catch (Exception $e) {
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');
		}

		$query->clear()
			->select('esc.*,  esp.label as plabel, esp.menutype')
			->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
			->join('LEFT', $this->_db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->_db->quoteName('esp.id') . ' = ' . $this->_db->quoteName('esc.profile_id'))
			->where($this->_db->quoteName('esc.id') . '=' . $campaign_id);

		try {
			$this->_db->setQuery($query);
			$campaign = $this->_db->loadAssoc();
		}
		catch (Exception $e) {
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
		}

		if (!empty($campaign)) {
			jimport('joomla.user.helper');
			$user_profile = UserHelper::getProfile($user->id)->emundus_profile;

			$schoolyear = $campaign['year'];
			$profile    = $campaign['profile_id'];
			$firstname  = ucfirst($user_profile['firstname']);
			$lastname   = ucfirst($user_profile['lastname']);

			// Insert data in #__emundus_users
			$p = $m_profile->isProfileUserSet($user->id);
			if ($p['cpt'] == 0) {
				$query->clear()
					->insert($this->_db->quoteName('#__emundus_users'))
					->columns($this->_db->quoteName(['user_id', 'firstname', 'lastname', 'profile', 'schoolyear', 'registerDate']))
					->values($this->_db->quote($user->id) . ', ' . $this->_db->quote(ucfirst($firstname)) . ', ' . $this->_db->quote(strtoupper($lastname)) . ', ' . $profile . ', ' . $this->_db->quote($schoolyear) . ', ' . $this->_db->quote($user->registerDate));

				try {
					$this->_db->setQuery($query);
					$this->_db->execute();
				}
				catch (Exception $e) {
					Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
				}
			}

			$query->clear()
				->select($this->_db->quoteName('id'))
				->from($this->_db->quoteName('#__emundus_users_profiles'))
				->where($this->_db->quoteName('user_id') . ' = ' . $user->id . ' AND ' . $this->_db->quoteName('profile_id') . ' = ' . $profile);

			try {
				$this->_db->setQuery($query);
				if (empty($this->_db->loadResult())) {
					$query->clear()
						->insert($this->_db->quoteName('#__emundus_users_profiles'))
						->columns($this->_db->quoteName(['user_id', 'profile_id']))
						->values($this->_db->quote($user->id) . ', ' . $profile);

					try {
						$this->_db->setQuery($query);
						$this->_db->execute();
					}
					catch (Exception $e) {
						Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
					}
				}
			}
			catch (Exception $e) {
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
			}
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
		$user = $this->app->getSession()->get('emundusUser');
		if ($user->id) {
			EmundusModelLogs::log($user->id, $user->id, $fnum, 1, 'c', 'COM_EMUNDUS_ACCESS_FILE_CREATE');
		}

		if ($form_type == 'cc') {
			$this->app->enqueueMessage(Text::_('FILE_OK'));
			$this->app->redirect($this->getParam('emunduscampaign_redirect_url', null) ?: 'index.php?option=com_emundus&task=openfile&fnum=' . $fnum);
		}

		return true;
	}

	/**
	 * Check Campaign Limit
	 *
	 * @return Bool|null
	 * @throws Exception
	 */
	public function onBeforeProcess()
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		// Get default profile for applicant
		$pid = 1000;
		$query->select('id')
			->from($db->quoteName('#__emundus_setup_profiles'))
			->where($db->quoteName('id') . " = " . $db->quote($pid));
		$db->setQuery($query);
		$exist = $db->loadResult();

		if (!$exist)
		{
			$query->clear()
				->select('id')
				->from($db->quoteName('#__emundus_setup_profiles'))
				->where($db->quoteName('published') . " = 1");
			$db->setQuery($query);
			$pid = $db->loadResult();
		}
		//

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		$m_campaign = new EmundusModelCampaign;

		$form_type = $this->getParam('form_type', 'cc');

		switch ($form_type) {
			case 'user':
				$campaign_id = is_array($this->app->getInput()->get('jos_emundus_users___campaign_id_raw')) ? $this->app->getInput()->get('jos_emundus_users___campaign_id_raw')[0] : $this->app->getInput()->getInt('jos_emundus_users___campaign_id_raw');

				break;

			case 'cc':
				$campaign_id = is_array($this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')) ? $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')[0] : $this->app->getInput()->getInt('jos_emundus_campaign_candidature___campaign_id_raw');

				break;
		}

		if (!empty($campaign_id)) {
			// Check if the campaign limit has been obtained
			if ($m_campaign->isLimitObtained($campaign_id) === true) {
				$this->getModel()->formErrorMsg     = '';
				$this->getModel()->getForm()->error = Text::_('LIMIT_OBTAINED');

				return false;
			}

			$query->clear()
				->select('profile_id')
				->from($db->quoteName('#__emundus_setup_campaigns'))
				->where($db->quoteName('id') . " = " . $db->quote($campaign_id));
			$db->setQuery($query);
			$campaign_pid = $db->loadResult();

			$pid = ($campaign_pid > 0) ? $campaign_pid : $pid;
		}

		$this->getModel()->updateFormData('jos_emundus_users___profile', $pid, true);

		return true;
	}
}
