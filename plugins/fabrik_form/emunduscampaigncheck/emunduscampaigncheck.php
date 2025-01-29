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
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.emunduscampaigncheck
 * @since       3.0
 */
class PlgFabrik_FormEmundusCampaignCheck extends plgFabrik_Form
{

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

	/**
	 * Main script.
	 *
	 * @return Bool
	 * @throws Exception
	 */
	public function onBeforeStore()
	{
		require_once (JPATH_SITE . '/components/com_emundus/helpers/menu.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');

		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_emundus.campaign-check.php'), Log::ALL, array('com_emundus.campaign-check'));

		$user = $this->app->getSession()->get('emundusUser');

		$homepage_link = EmundusHelperMenu::getHomepageLink();

		$eMConfig    = JComponentHelper::getParams('com_emundus');
		$id_profiles = $eMConfig->get('id_profiles', '0');
		$id_profiles = explode(',', $id_profiles);

		$campaign_id = is_array($this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')) ? $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')[0] : $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw');

		$applicant_can_renew = $this->getParam('applicant_can_renew', 'em_config');

		if ($applicant_can_renew === 'em_config') {
			$applicant_can_renew = $eMConfig->get('applicant_can_renew', '0');
		}

		// Check if the campaign limit has been obtained
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		$m_campaign      = new EmundusModelCampaign;
		$isLimitObtained = $m_campaign->isLimitObtained($campaign_id);

		if ($isLimitObtained === true) {
			Log::add('User: ' . $user->id . ' Campaign limit is obtained', Log::ERROR, 'com_emundus.campaign-check');
			$this->app->enqueueMessage(Text::_('LIMIT_OBTAINED'), 'error');
			$this->app->redirect($homepage_link);
			return false;
		}

		if (EmundusHelperAccess::asAccessAction(1, 'c')) {
			$applicant_can_renew = 1;
		}
		else {
			foreach ($user->emProfiles as $profile) {
				if (in_array($profile->id, $id_profiles)) {
					$applicant_can_renew = 1;
					break;
				}
			}
		}

		$query = $this->_db->getQuery(true);

		switch ($applicant_can_renew) {

			// Cannot create new campaigns at all.
			case 0:
				$query->select('COUNT(' . $this->_db->quoteName('id') . ')')
					->from($this->_db->quoteName('#__emundus_campaign_candidature'))
					->where($this->_db->quoteName('applicant_id') . ' = ' . $user->id)
					->andWhere($this->_db->quoteName('published') . ' <> ' . $this->_db->quote('-1'));

				$this->_db->setQuery($query);
				$files = $this->_db->loadResult();

				if ($files > 0) {
					Log::add('User: ' . $user->id . ' already has a file.', Log::ERROR, 'com_emundus.campaign-check');
					$this->getModel()->formErrorMsg     = '';
					$this->getModel()->getForm()->error = Text::_('CANNOT_HAVE_MULTI_FILE');
					$this->app->enqueueMessage(Text::_('CANNOT_HAVE_MULTI_FILE'), 'error');
					$this->app->redirect($homepage_link);
				}

				break;

			// If the applicant can only have one file per campaign.
			case 2:
				$query->select($this->_db->quoteName('campaign_id'))
					->from($this->_db->quoteName('#__emundus_campaign_candidature'))
					->where($this->_db->quoteName('applicant_id') . ' = ' . $user->id)
					->andWhere($this->_db->quoteName('published') . ' <> ' . $this->_db->quote('-1'))
					->andWhere($this->_db->quoteName('campaign_id') . ' = ' . $campaign_id);

				try {
					$this->_db->setQuery($query);
					$user_campaigns = $this->_db->loadColumn();

					if (!empty($user_campaigns)) {
						Log::add('User: ' . $user->id . ' already has a file for campaign id: ' . $campaign_id, Log::ERROR, 'com_emundus.campaign-check');
						$this->getModel()->formErrorMsg     = '';
						$this->getModel()->getForm()->error = Text::_('USER_HAS_FILE_FOR_CAMPAIGN');
						$this->app->enqueueMessage(Text::_('USER_HAS_FILE_FOR_CAMPAIGN'), 'error');
						$this->app->redirect($homepage_link);
					}

				}
				catch (Exception $e) {
					Log::add('plugin/emundus_campaign SQL error at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.campaign-check');
					$this->getModel()->formErrorMsg     = '';
					$this->getModel()->getForm()->error = Text::_('ERROR');
				}

				break;

			// If the applicant can only have one file per school year.
			case 3:
				$timezone = new DateTimeZone($this->app->get('offset'));
				$now      = Factory::getDate()->setTimezone($timezone);

				$query->select($this->_db->quoteName('sc.year'))
					->from($this->_db->quoteName('#__emundus_campaign_candidature', 'cc'))
					->leftJoin($this->_db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->_db->quoteName('sc.id') . ' = ' . $this->_db->quoteName('cc.campaign_id'))
					->where($this->_db->quoteName('applicant_id') . ' = ' . $user->id);

				try {
					$this->_db->setQuery($query);
					$user_years = $this->_db->loadColumn();

					$query->clear()
						->select($this->_db->quoteName('id'))
						->from($this->_db->quoteName('#__emundus_setup_campaigns'))
						->where($this->_db->quoteName('published') . ' = 1')
						->andWhere($this->_db->quoteName('end_date') . ' >= ' . $this->_db->quote($now))
						->andWhere($this->_db->quoteName('start_date') . ' <=  ' . $this->_db->quote($now))
						->andWhere($this->_db->quoteName('year') . ' NOT IN (' . implode(',', $this->_db->q($user_years)) . ')');

					$this->_db->setQuery($query);
					$campaigns = $this->_db->loadColumn();

					if (!in_array($campaign_id, $campaigns)) {
						Log::add('User: ' . $user->id . ' already has a file for year belong to campaign: ' . $campaign_id, Log::ERROR, 'com_emundus.campaign-check');
						$this->app->enqueueMessage(Text::_('USER_HAS_FILE_FOR_YEAR'), 'error');
						$this->app->redirect($homepage_link);
					}
				}
				catch (Exception $e) {
					Log::add('plugin/emundus_campaign SQL error at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus.campaign-check');
					$this->getModel()->formErrorMsg     = '';
					$this->getModel()->getForm()->error = Text::_('ERROR');
				}

				break;
		}
	}
}
