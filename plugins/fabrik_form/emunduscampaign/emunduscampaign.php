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
use Tchooz\Traits\TraitDispatcher;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create an application file from a Fabrik form
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.emunduscampaign
 * @since       3.0
 */
class PlgFabrik_FormEmundusCampaign extends plgFabrik_Form
{
	use TraitDispatcher;

	public function getParam(string $pname, mixed $default = ''): mixed
	{
		$params = $this->getParams();

		if ($params->get($pname) == '')
		{
			return $default;
		}

		return $params->get($pname);
	}

	public function onBeforeLoad()
	{
		if(!class_exists('EmundusHelperMenu'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/menu.php';
		}

		/**
		 * @var FabrikFEModelForm $formModel
		 */
		$formModel   = $this->getModel();

		$current_user = $this->app->getIdentity();
		$current_url = Uri::getInstance()->toString();
		$parse       = parse_url($current_url);

		if (strpos($current_url, 'redirect') !== false)
		{
			$new_url = str_replace($parse['scheme'] . '://' . $parse['host'], '', strstr($current_url, '&redirect=', true));
			$this->app->redirect($new_url);
		}

		$emundus_config      = ComponentHelper::getParams('com_emundus');
		$applicant_can_renew = $emundus_config->get('applicant_can_renew', '0');
		$cid                 = $this->app->getInput()->getInt('cid');

		if (!empty($cid))
		{
			if(!class_exists('EmundusModelCampaign'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/campaign.php';
			}
			$m_campaign        = new EmundusModelCampaign();

			$allowed_campaigns = $m_campaign->getAllowedCampaign($current_user->id);

			if (!in_array($cid, $allowed_campaigns))
			{
				$message = match ((int)$applicant_can_renew)
				{
					0 => Text::_('CANNOT_HAVE_MULTI_FILE'),
					2 => Text::_('USER_HAS_FILE_FOR_CAMPAIGN'),
					3 => Text::_('USER_HAS_FILE_FOR_YEAR'),
					default => Text::_('USER_HAS_NO_ACCESS_TO_CAMPAIGN'),
				};

				$this->app->enqueueMessage(Text::_($message), 'error');
				$this->app->redirect(EmundusHelperMenu::getHomepageLink());
			}

			if (!class_exists('EmundusHelperFiles'))
			{
				require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
			}

			if (!empty($current_user->id) && !EmundusHelperFiles::checkLimitationFilesRules($current_user->id, $cid))
			{
				$this->app->enqueueMessage(Text::_('COM_EMUNDUS_LIMIT_FILES_BY_CAMPAIGN_BY_STATUS_REACHED'), 'error');
				$this->app->redirect(EmundusHelperMenu::getHomepageLink());
			}

			$formModel->data['jos_emundus_campaign_candidature___campaign_id_raw'] = $cid;
			$formModel->data['jos_emundus_campaign_candidature___campaign_id']     = $cid;
		}
	}

	public function onBeforeCalculations()
	{
		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_emundus.campaign.php'), Log::ALL, array('com_emundus'));

		if(!class_exists('EmundusModelProfile'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
		}
		$m_profile = new EmundusModelProfile();

		$session   = $this->app->getSession();
		$form_type = $this->getParam('form_type', 'cc');

		$timezone = new DateTimeZone($this->app->get('offset'));
		$now      = Factory::getDate()->setTimezone($timezone);

		$query = $this->_db->getQuery(true);

		/**
		 * @var FabrikFEModelForm $formModel
		 */
		$formModel = $this->getModel();

		$application_choice = $this->app->getInput()->getInt('application_choice', 0);

		switch ($form_type)
		{
			case 'user':
				$query->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__users'))
					->where($this->_db->quoteName('email') . ' LIKE ' . $this->_db->quote($formModel->formData['email_raw']));
				$this->_db->setQuery($query);
				try
				{
					$user = $this->_db->loadResult();
					if (empty($user))
					{
						return false;
					}
				}
				catch (Exception $e)
				{
					return false;
				}

				$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user);

				$campaign_id = $formModel->formData['campaign_id_raw'];

				$campaign_id = is_array($campaign_id) ? $campaign_id[0] : $campaign_id;
				if (empty($campaign_id))
				{
					return false;
				}

				$query->clear()
					->select($this->_db->quoteName('id'))
					->from($this->_db->quoteName('#__emundus_setup_campaigns'))
					->where($this->_db->quoteName('id') . ' = ' . $campaign_id);
				$this->_db->setQuery($query);
				try
				{
					if (empty($this->_db->loadResult()))
					{
						return false;
					}
				}
				catch (Exception $e)
				{
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
				if (empty($user))
				{
					$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user);
				}
				$fnum_tmp           = $formModel->formData['fnum'];
				$id                 = $formModel->formData['id'];
				$campaign_id        = $formModel->formData['campaign_id_raw'];

				$campaign_id = is_array($campaign_id) ? $campaign_id[0] : $campaign_id;
				if (empty($campaign_id))
				{
					return false;
				}

				if(!class_exists('EmundusHelperFiles'))
				{
					require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';
				}
				$fnum = EmundusHelperFiles::createFnum($campaign_id, $user->id);

				$query->clear()
					->update($this->_db->quoteName('#__emundus_campaign_candidature'))
					->set($this->_db->quoteName('fnum') . ' = ' . $this->_db->Quote($fnum))
					->set($this->_db->quoteName('date_time') . ' = ' . $this->_db->quote($now))
					->where($this->_db->quoteName('id') . ' = ' . $id . ' AND ' . $this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->Quote($fnum_tmp) . ' AND ' . $this->_db->quoteName('campaign_id') . '=' . $campaign_id);
				break;
		}

		try
		{
			$this->_db->setQuery($query);
			$this->_db->execute();

			$this->dispatchJoomlaEvent('onCreateNewFile', ['user_id' => $user->id, 'fnum' => $fnum, 'cid' => $campaign_id, 'application_choice' => $application_choice]);
		}
		catch (Exception $e)
		{
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');
		}

		$query->clear()
			->select('esc.*,  esp.label as plabel, esp.menutype')
			->from($this->_db->quoteName('#__emundus_setup_campaigns', 'esc'))
			->join('LEFT', $this->_db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->_db->quoteName('esp.id') . ' = ' . $this->_db->quoteName('esc.profile_id'))
			->where($this->_db->quoteName('esc.id') . '=' . $campaign_id);

		try
		{
			$this->_db->setQuery($query);
			$campaign = $this->_db->loadAssoc();
		}
		catch (Exception $e)
		{
			Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
		}

		if (!empty($campaign))
		{
			jimport('joomla.user.helper');
			$user_profile = UserHelper::getProfile($user->id)->emundus_profile;

			$schoolyear = $campaign['year'];
			$profile    = $campaign['profile_id'];
			$firstname  = ucfirst($user_profile['firstname']);
			$lastname   = ucfirst($user_profile['lastname']);

			$p = $m_profile->isProfileUserSet($user->id);
			if ($p['cpt'] == 0)
			{
				$insert = (object) [
					'user_id'      => $user->id,
					'firstname'    => ucfirst($firstname),
					'lastname'     => strtoupper($lastname),
					'profile'      => $profile,
					'schoolyear'   => $schoolyear,
					'registerDate' => $user->registerDate,
				];

				try
				{
					$this->_db->insertObject('#__emundus_users', $insert);
				}
				catch (Exception $e)
				{
					Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
				}
			}

			$query->clear()
				->select($this->_db->quoteName('id'))
				->from($this->_db->quoteName('#__emundus_users_profiles'))
				->where($this->_db->quoteName('user_id') . ' = ' . $user->id . ' AND ' . $this->_db->quoteName('profile_id') . ' = ' . $profile);

			try
			{
				$this->_db->setQuery($query);
				if (empty($this->_db->loadResult()))
				{
					$insert = (object) [
						'user_id'    => $user->id,
						'profile_id' => $profile,
					];

					try
					{
						$this->_db->insertObject('#__emundus_users_profiles', $insert);
					}
					catch (Exception $e)
					{
						Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
					}
				}
			}
			catch (Exception $e)
			{
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query, Log::ERROR, 'com_emundus');
			}
		}

		if(!class_exists('EmundusModelLogs'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
		}

		$user = $this->app->getSession()->get('emundusUser');
		if ($user->id)
		{
			EmundusModelLogs::log($user->id, $user->id, $fnum, 1, 'c', 'COM_EMUNDUS_ACCESS_FILE_CREATE');
		}

		if ($form_type == 'cc')
		{
			$this->app->enqueueMessage(Text::_('FILE_OK'));
			$this->app->redirect($this->getParam('emunduscampaign_redirect_url', null) ?: 'index.php?option=com_emundus&task=openfile&fnum=' . $fnum);
		}

		return true;
	}

	public function onBeforeProcess()
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		/**
		 * @var FabrikFEModelForm $formModel
		 */
		$formModel = $this->getModel();

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

		if(!class_exists('EmundusModelCampaign'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/campaign.php');
		}
		$m_campaign = new EmundusModelCampaign();

		$form_type = $this->getParam('form_type', 'cc');
		switch ($form_type)
		{
			case 'user':
				$campaign_id = is_array($this->app->getInput()->get('jos_emundus_users___campaign_id_raw')) ? $this->app->getInput()->get('jos_emundus_users___campaign_id_raw')[0] : $this->app->getInput()->getInt('jos_emundus_users___campaign_id_raw');

				break;

			case 'cc':
				$campaign_id = is_array($this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')) ? $this->app->getInput()->get('jos_emundus_campaign_candidature___campaign_id_raw')[0] : $this->app->getInput()->getInt('jos_emundus_campaign_candidature___campaign_id_raw');

				break;
		}

		if (!empty($campaign_id))
		{
			// Check if the campaign limit has been obtained
			if ($m_campaign->isLimitObtained($campaign_id) === true)
			{
				$formModel->formErrorMsg     = '';
				$formModel->getForm()->error = Text::_('LIMIT_OBTAINED');

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

		$formModel->updateFormData('jos_emundus_users___profile', $pid, true);

		return true;
	}
}
