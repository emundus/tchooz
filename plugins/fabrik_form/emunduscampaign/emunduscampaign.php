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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
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
		$current_url = Uri::getInstance()->toString();
		$parse       = parse_url($current_url);

		if (strpos($current_url, 'redirect') !== false) {
			$new_url = str_replace($parse['scheme'] . '://' . $parse['host'], '', strstr($current_url, '&redirect=', true));
			$this->app->redirect($new_url);
		}
	}

	/**
	 * Main script.
	 *
	 * @return Bool
	 * @throws Exception
	 */
	public function onAfterProcess()
	{

		jimport('joomla.log.log');
		JLog::addLogger(array('text_file' => 'com_emundus.campaign.php'), JLog::ALL, array('com_emundus'));

		include_once(JPATH_BASE . '/components/com_emundus/models/profile.php');
		$m_profile = new EmundusModelProfile;

		$session   = $this->app->getSession();
		$form_type = $this->getParam('form_type', 'cc');

		$timezone = new DateTimeZone($this->app->get('offset'));
		$now      = Factory::getDate()->setTimezone($timezone);

		$query = $this->_db->getQuery(true);

		// This allows the plugin to be run from a different context while retaining the same functionality.
		switch ($form_type) {

			case 'user':
				$query->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('email') . ' LIKE ' . $this->_db->quote($this->app->getInput()->getString('jos_emundus_users___email_raw')));
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

				$campaign_id = $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw', 0);

				if (empty($campaign_id)) {
					$campaign_id = $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id', 0);
				}

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
				$fnum = date('YmdHis') . str_pad($campaign_id, 7, '0', STR_PAD_LEFT) . str_pad($user->id, 7, '0', STR_PAD_LEFT);

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
				$fnum_tmp    = $this->app->getInput()->getString('jos_emundus_campaign_candidature___fnum', '');
				$id          = $this->app->getInput()->getInt('jos_emundus_campaign_candidature___id', 0);
				$campaign_id = $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw', 0);

				if (empty($campaign_id)) {
					$campaign_id = $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id', 0);
				}

				$campaign_id = is_array($campaign_id) ? $campaign_id[0] : $campaign_id;
				if (empty($campaign_id)) {
					return false;
				}

				// create new fnum
				$fnum = date('YmdHis') . str_pad($campaign_id, 7, '0', STR_PAD_LEFT) . str_pad($user->id, 7, '0', STR_PAD_LEFT);

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
			JLog::add(JUri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . preg_replace("/[\r\n]/", " ", $query->__toString()), JLog::ERROR, 'com_emundus');
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
			JLog::add(JUri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, JLog::ERROR, 'com_emundus');
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
					JLog::add(JUri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, JLog::ERROR, 'com_emundus');
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
						JLog::add(JUri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, JLog::ERROR, 'com_emundus');
					}
				}
			}
			catch (Exception $e) {
				JLog::add(JUri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, JLog::ERROR, 'com_emundus');
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

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		$m_campaign = new EmundusModelCampaign;

		$form_type = $this->getParam('form_type', 'cc');

		switch ($form_type) {
			case 'user':
				$campaign_id = is_array($this->app->getInput()->get('jos_emundus_users___campaign_id_raw')) ? $this->app->getInput()->get('jos_emundus_users___campaign_id_raw')[0] : $this->app->getInput()->getInt('jos_emundus_users___campaign_id_raw');
				if (empty($campaign_id)) {
					return false;
				}

				if ($m_campaign->isLimitObtained($campaign_id) === true) {
					$this->getModel()->formErrorMsg     = '';
					$this->getModel()->getForm()->error = Text::_('LIMIT_OBTAINED');

					return false;
				}
				break;

			case 'cc':
				$campaign_id = is_array($this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')) ? $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')[0] : $this->app->getInput()->getInt('jos_emundus_campaign_candidature___campaign_id_raw');

				// Check if the campaign limit has been obtained
				if ($m_campaign->isLimitObtained($campaign_id) === true) {
					$this->getModel()->formErrorMsg     = '';
					$this->getModel()->getForm()->error = Text::_('LIMIT_OBTAINED');

					return false;
				}
				break;
		}
	}
}
