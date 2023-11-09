<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2018 eMundus. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      eMundus SAS - Benjamin Rivalland
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Joomla User plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  User.emundus
 * @since       5.0.0
 */
class plgUserEmundus extends CMSPlugin
{
	/**
	 * @var    \Joomla\CMS\Application\CMSApplication
	 *
	 * @since  3.2
	 */
	protected $app;

	/**
	 * @var    \Joomla\Database\DatabaseDriver
	 *
	 * @since  3.2
	 */
	protected $db;

	/**
	 * Remove all sessions for the user name
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was succesfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  boolean
	 * @throws Exception
	 * @since   1.6
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success) {
			return false;
		}

		$this->db = Factory::getDbo();
		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName('#__session'))
			->where($this->db->quoteName('userid') . ' = ' . (int) $user['id']);
		$this->db->setQuery($query);
		$this->db->execute();

		$this->db->setQuery('SHOW TABLES');
		$tables = $this->db->loadColumn();

		foreach ($tables as $table) {

			if (strpos($table, '_messages') > 0 && !strpos($table, '_eb_')) {
				$query->clear()
					->delete($this->db->quoteName($table))
					->where($this->db->quoteName('user_id_from') . ' = ' . (int) $user['id'] . ' OR ' . $this->db->quoteName('user_id_to') . ' = ' . (int) $user['id']);
			}

			if (strpos($table, 'emundus_') === false)  {
				continue;
			}
			if (strpos($table, 'emundus_group_assoc') > 0) {
				continue;
			}
			if (strpos($table, 'emundus_groups_eval') > 0) {
				continue;
			}
			if (strpos($table, 'emundus_tag_assoc') > 0) {
				continue;
			}
			if (strpos($table, 'emundus_stats') > 0) {
				continue;
			}
			if (strpos($table, '_repeat') > 0) {
				continue;
			}
			if (strpos($table, 'setup_') > 0 || strpos($table, '_country') > 0 || strpos($table, '_users') > 0 || strpos($table, '_acl') > 0) {
				continue;
			}

			if (strpos($table, '_files_request') > 0 || strpos($table, '_evaluations') > 0 || strpos($table, '_final_grade') > 0) {
				$query->clear()->delete($this->db->quoteName($table))->where($this->db->quoteName('student_id') . ' = ' . (int) $user['id']);
			}
			elseif (strpos($table, '_uploads') > 0 || strpos($table, '_groups') > 0 || strpos($table, '_emundus_users') > 0 || strpos($table, '_emundus_emailalert') > 0) {
				$query->clear()
					->delete($this->db->quoteName($table))
					->where($this->db->quoteName('user_id') . ' = ' . (int) $user['id']);
			}
			elseif (strpos($table, '_emundus_comments') > 0 || strpos($table, '_emundus_campaign_candidature') > 0) {
				$query->clear()
					->delete($this->db->quoteName($table))
					->where($this->db->quoteName('applicant_id') . ' = ' . (int) $user['id']);
			}
			else {
				continue;
			}

			try {
				$this->db->setQuery($query);
				$this->db->execute();
			}
			catch (Exception $exception) {
				continue;
			}
		}

		$dir = EMUNDUS_PATH_ABS . $user['id'] . DS;

		if (!$dh = opendir($dir)) {
			return false;
		}

		while (false !== ($obj = readdir($dh))) {
			if ($obj == '.' || $obj == '..') continue;
			if (!unlink($dir . $obj))
				Factory::getApplication()->enqueueMessage(JText::_("FILE_NOT_FOUND") . " : " . $obj . "\n", 'error');
		}

		closedir($dh);
		rmdir($dir);

		if ($this->params->get('send_email_delete', 0) == 1) {
			require_once(JPATH_SITE . '/components/com_emundus/controllers/messages.php');
			require_once(JPATH_SITE . '/components/com_emundus/helpers/emails.php');

			$c_messages = new EmundusControllerMessages();
			$post       = [
				'NAME'      => $user['name'],
				'LOGO'      => EmundusHelperEmails::getLogo(),
				'SITE_NAME' => Factory::getConfig()->get('sitename'),
			];
			$c_messages->sendEmailNoFnum($user['email'], 'delete_user', $post);
		}

		return true;
	}

	/**
	 * @param $user
	 * @param $isnew
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since version
	 */
	public function onUserBeforeSave($user, $isnew)
	{
		$result = true;
		$fabrik = $this->app->input->post->get('listid', null);

		// In case we are signing up a new user via Fabrik, check that the profile ID is either an applicant, or one of the allowed non-applicant profiles.
		if ($isnew && !empty($fabrik)) {
			$params                   = ComponentHelper::getParams('com_emundus');
			$allowed_special_profiles = explode(',', $params->get('allowed_non_applicant_profiles', ''));

			$profile = $this->app->input->post->get('jos_emundus_users___profile');
			if (is_array($profile)) {
				$profile = $profile[0];
			}

			$query = $this->db->getQuery(true);

			$query->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('published') . ' = 0');
			$this->db->setQuery($query);

			try {
				$non_applicant_profiles = $this->db->loadColumn();
			}
			catch (Exception $e) {
				// TODO: Handle error handling in this plugin...
				$result = false;
			}

			// If the user's profile is in the list of special profiles and NOT in the allowed profiles.
			if (in_array($profile, array_diff($non_applicant_profiles, $allowed_special_profiles))) {
				$this->app->enqueueMessage('Restricted profile', 'error');
				$this->app->redirect('/index.php');

				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Utility method to act on a user after it has been saved.
	 *
	 * This method sends a registration email to new users created in the backend.
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isnew    True if a new user is stored.
	 * @param   boolean  $success  True if user was succesfully stored in the database.
	 * @param   string   $msg      Message.
	 *
	 * @return  bool
	 * @throws Exception
	 * @since   1.6
	 */
	public function onUserAfterSave($user, $isnew, $success, $msg)
	{
		$app = Factory::getApplication();
		// The method check here ensures that if running as a CLI Application we don't get any errors
		if (method_exists($app, 'isClient') && ($app->isClient('site') || $app->isClient('cli'))) {
			return;
		}

		$input      = $this->app->input;
		$details    = $input->post->get('jform', null, 'none');
		$fabrik     = $input->post->get('listid', null);
		$option     = $input->get->get('option', null);
		$controller = $input->get->get('controller', null);
		$task       = $input->get->get('task', null);

		$profile = 0;

		// If the details are empty, we are probably signing in via LDAP for the first time.
		if ($isnew && empty($details) && empty($fabrik)) {
			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
			$m_users = new EmundusModelusers();

			if (PluginHelper::getPlugin('authentication', 'ldap') && ($option !== 'com_emundus' && $controller !== 'users' && $task !== 'adduser')) {
				$return = $m_users->searchLDAP($user['username']);

				if (!empty($return->users[0])) {
					$params       = ComponentHelper::getParams('com_emundus');
					$ldapElements = explode(',', $params->get('ldapElements'));

					$details['firstname'] = $return->users[0][trim($ldapElements[2])];
					$details['name']      = $return->users[0][trim($ldapElements[3])];
					if (is_array($details['firstname'])) {
						$details['firstname'] = $details['firstname'][0];
					}
					if (is_array($details['name'])) {
						$details['name'] = $details['name'][0];
					}

					// Give the user an LDAP param.
					$o_user = Factory::getUser($user['id']);

					// Store token in User's Parameters
					$o_user->setParam('ldap', '1');
					$o_user->save();
				}
			}

			if (PluginHelper::getPlugin('authentication', 'externallogin') && ($option !== 'com_emundus' && $controller !== 'users' && $task !== 'adduser')) {
				$username = explode(' ', $user["name"]);
				$name     = '';
				if (count($username) > 2) {
					for ($i = 1; $i > count($username); $i++) {
						$name .= ' ' . $username[$i];
					}
				}
				else {
					$name = $username[1];
				}

				$details['name']                        = $name;
				$details['emundus_profile']['lastname'] = $name;
				$details['firstname']                   = $username[0];
			}

			if (PluginHelper::getPlugin('authentication', 'miniorangesaml') && ($option !== 'com_emundus' && $controller !== 'users' && $task !== 'adduser')) {
				$o_user = JFactory::getUser($user['id']);

				$username        = explode(' ', $user["name"]);
				$details['name'] = count($username) > 2 ? implode(' ', array_slice($username, 1)) : $username[1];

				$details['emundus_profile']['lastname'] = $user['name'];
				$details['firstname']                   = $username[0];

				$o_user->setParam('saml', '1');
				$o_user->save();

				// Set the user table instance to include the new token.
				$table = JTable::getInstance('user', 'JTable');
				$table->load($o_user->id);
				$table->block = 0;

				// Save user data
				if (!$table->store()) {
					throw new RuntimeException($table->getError());
				}

				$eMConfig = ComponentHelper::getParams('com_emundus');
				$profile  = $eMConfig->get('saml_default_profile', 1000);
			}
		}

		$query = $this->db->getQuery(true);

		if (is_array($details) && count($details) > 0 && $task != 'reset.complete') {
			$campaign_id = @isset($details['emundus_profile']['campaign']) ? $details['emundus_profile']['campaign'] : @$details['campaign'];
			$lastname    = @isset($details['emundus_profile']['lastname']) ? $details['emundus_profile']['lastname'] : @$details['name'];
			$firstname   = @isset($details['emundus_profile']['firstname']) ? $details['emundus_profile']['firstname'] : @$details['firstname'];

			if ($isnew) {
				$query->update($this->db->quoteName('#__users'))
					->set($this->db->quoteName('name') . ' = ' . ($this->db->quote(ucfirst($firstname)) . ' ' . $this->db->quote(strtoupper($lastname))))
					->set($this->db->quoteName('usertype') . ' = (SELECT u.title FROM #__usergroups AS u
												LEFT JOIN #__user_usergroup_map AS uum ON u.id=uum.group_id
												WHERE uum.user_id=' . $user['id'] . ' ORDER BY uum.group_id DESC LIMIT 1)')
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($user['id']));
				$this->db->setQuery($query);

				try {
					$this->db->execute();
				}
				catch (Exception $e) {
					// catch any database errors.
				}

				if (!empty($campaign_id)) {
					// Get the profile ID from the campaign selected
					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__emundus_setup_campaigns'))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($campaign_id));
					$this->db->setQuery($query);
					$campaign = $this->db->loadAssocList();

					$profile = $campaign[0]['profile_id'];
				}
				elseif (empty($profile)) {
					$profile = 1000;
				}

				// Insert data in #__emundus_users
				$columns = array('user_id', 'firstname', 'lastname', 'profile', 'registerDate');
				$values  = array($user['id'], $this->db->quote(ucfirst($firstname)), $this->db->quote(strtoupper($lastname)), $profile, $this->db->quote($user['registerDate']));
				$query->clear()
					->insert($this->db->quoteName('#__emundus_users'))
					->columns($this->db->quoteName($columns))
					->values(implode(',', $values));

				$this->db->setQuery($query);
				try {
					$this->db->execute();
				}
				catch (Exception $e) {
					// catch any database errors.
				}

				// Insert data in #__emundus_users_profiles
				$columns = array('user_id', 'profile_id');
				$values  = array($user['id'], $profile);

				$query->clear()
					->insert($this->db->quoteName('#__emundus_users_profiles'))
					->columns($this->db->quoteName($columns))
					->values(implode(',', $values));

				$this->db->setQuery($query);
				try {
					$this->db->execute();
				}
				catch (Exception $e) {
					// catch any database errors.
				}

				if (!empty($campaign_id)) {
					$query->clear()
						->insert($this->db->quoteName('#__emundus_campaign_candidature'))
						->columns($this->db->quoteName(array('applicant_id', 'campaign_id', 'fnum')))
						->values($this->db->quote($user['id']) . ',' . $this->db->quote($campaign_id) . ', CONCAT(DATE_FORMAT(NOW(),\'%Y%m%d%H%i%s\'),LPAD(' . $this->db->quote($campaign_id) . ', 7, \'0\'),LPAD(' . $this->db->quote($user['id']) . ', 7, \'0\'))');
					$this->db->setQuery($query);
					try {
						$this->db->execute();
					}
					catch (Exception $e) {
						// catch any database errors.
					}
				}

			}
			else {
				try {
					if (!empty($firstname) && !empty($lastname)) {
						// Update name and firstname from #__users
						$this->db->setQuery('UPDATE #__users SET name=' . $this->db->quote(ucfirst($firstname) . ' ') . ' ' . $this->db->quote(strtoupper($lastname)) . ' WHERE id=' . $user['id']);
						$this->db->execute();

						$this->db->setQuery('UPDATE #__emundus_users SET lastname=' . $this->db->quote(strtoupper($lastname)) . ', firstname=' . $this->db->quote(ucfirst($firstname)) . ' WHERE user_id=' . $user['id']);
						$this->db->execute();

						$this->db->setQuery('UPDATE #__emundus_personal_detail SET last_name=' . $this->db->quote(strtoupper($lastname)) . ', first_name=' . $this->db->quote(ucfirst($firstname)) . ' WHERE user=' . $user['id']);
						$this->db->execute();
					}

					if (!empty($details['email1'])) {
						$this->db->setQuery('UPDATE #__emundus_users SET email=' . $this->db->quote($details['email1']) . ' WHERE user_id=' . $user['id']);
						$this->db->execute();

						$e_session        = Factory::getSession()->get('emundusUser');
						$e_session->email = $details['email1'];
						Factory::getSession()->set('emundusUser', $e_session);
					}
				}
				catch (Exception $e) {
					JLog::add('Error at line ' . __LINE__ . ' of file ' . __FILE__ . ' : ' . '. Error is : ' . preg_replace("/[\r\n]/", " ", $e->getMessage()), JLog::ERROR, 'com_emundus');
				}

				$this->onUserLogin($user);
			}
		}
	}


	/**
	 * This method should handle any login logic and report back to the subject
	 *
	 * @param   array  $user     Holds the user data
	 * @param   array  $options  Array holding options (remember, autoregister, group)
	 *
	 * @return  boolean True on success
	 * @throws Exception
	 * @since   1.5
	 */
	public function onUserLogin($user, $options = array())
	{
		// Here you would do whatever you need for a login routine with the credentials
		// Remember, this is not the authentication routine as that is done separately.
		// The most common use of this routine would be logging the user into a third party application
		// In this example the boolean variable $success would be set to true if the login routine succeeds
		// ThirdPartyApp::loginUser($user['username'], $user['password']);
		$input    = $this->app->input;
		$redirect = $input->get->getBase64('redirect');

		if (empty($redirect)) {
			parse_str($input->server->getVar('HTTP_REFERER'), $return_url);
			$previous_url = base64_decode($return_url['return']);
			if (empty($previous_url)) {
				$previous_url = base64_decode($input->POST->getVar('return'));
			}
			if (empty($previous_url)) {
				$previous_url = base64_decode($return_url['redirect']);
			}
		}
		else {
			$previous_url = base64_decode($redirect);
		}

		$isAdmin = $this->app->isClient('administrator');
		if (!$isAdmin) {

			// Users coming from an OAuth system are immediately signed in and thus need to have their data entered in the eMundus table.
			if ($user['type'] == 'OAuth2') {

				// Insert the eMundus user info into the DB.
				if ($user['isnew']) {
					$query = $this->db->getQuery(true);

					$query->select('*')
						->from('#__emundus_users')
						->where('user_id = ' . JFactory::getUser()->id);

					try {
						$this->db->setQuery($query);
						$result = $this->db->loadObject();
					}
					catch (Exception $e) {
						JLog::add('Error checking if user is not already in emundus users', JLog::ERROR, 'com_emundus.error');
					}

					if (empty($result) && empty($result->id)) {
						require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
						$m_users     = new EmundusModelUsers();
						$user_params = [
							'firstname' => $user['firstname'],
							'lastname'  => $user['lastname'],
							'profile'   => $user['profile']
						];
						$m_users->addEmundusUser(Factory::getUser()->id, $user_params);
					}

					$o_user   = new JUser(JUserHelper::getUserId($user['username']));
					$pass     = bin2hex(openssl_random_pseudo_bytes(4));
					$password = array('password' => $pass, 'password2' => $pass);
					$o_user->bind($password);
					$o_user->save();
					$user['password'] = $pass;
					unset($pass, $password);
					// Set the user table instance to not block the user.
					$table = JTable::getInstance('user', 'JTable');
					$table->load(Factory::getUser()->id);
					$table->block = 0;
					if (!$table->store()) {
						throw new RuntimeException($table->getError());
					}

					PluginHelper::importPlugin('authentication');
					$this->app->triggerEvent('onOAuthAfterRegister', ['user' => $user]);
				}

				// Add the Oauth provider type to the Joomla user params.
				if (!empty($options['provider'])) {
					$o_user = new JUser(JUserHelper::getUserId($user['username']));
					$o_user->setParam('OAuth2', $options['provider']);
					$o_user->setParam('token', json_encode($options['token']));
					$o_user->save();
				}

				$previous_url = "";
				if (!empty($options['redirect'])) {
					$previous_url = $options['redirect'];
				}

			}
			if ($user['type'] == 'externallogin') {
				try {
					$query = $this->db->getQuery(true);

					$user_id = Factory::getUser()->id;

					if (isset($user['firstname']) || isset($user['lastname'])) {
						$query->clear()
							->update('#__emundus_users');

						if (isset($user['firstname'])) {
							$query->set($this->db->quoteName('firstname') . ' = ' . $this->db->quote($user['firstname']));
						}
						if (isset($user['lastname'])) {
							$query->set($this->db->quoteName('lastname') . ' = ' . $this->db->quote($user['lastname']));
						}
						$query->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));

						$this->db->setQuery($query);
						$this->db->execute();
					}

					$query->clear()
						->update('#__users')
						->set($this->db->quoteName('activation') . ' = 1')
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($user_id));
					$this->db->setQuery($query);
					$this->db->execute();


					if (!empty($user['other_properties'])) {
						foreach ($user['other_properties'] as $key => $other_property) {
							if (!empty($other_property->values)) {
								$table  = explode('___', $key)[0];
								$column = explode('___', $key)[1];

								$query->clear()
									->select($this->db->quoteName($column))
									->from($this->db->quoteName($table))
									->where($this->db->quoteName('user_id') . ' = ' . $user_id);
								if ($other_property->method == 'insert') {
									$query->andWhere($this->db->quoteName($column) . ' = ' . $other_property->values);
								}
								$this->db->setQuery($query);
								$result = $this->db->loadResult();

								if (empty($result)) {
									$query->clear();
									if ($other_property->method == 'update') {
										$query->update($this->db->quoteName($table));
									}
									if ($other_property->method == 'insert') {
										$query->insert($this->db->quoteName($table));
									}
									$query->set($this->db->quoteName($column) . ' = ' . $this->db->quote($other_property->values));

									if ($other_property->method == 'update') {
										$query->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));
									}
									if ($other_property->method == 'insert') {
										$query->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));
									}
									$this->db->setQuery($query);
									$this->db->execute();
								}
							}
						}
					}
				}
				catch (Exception $e) {
					JLog::add('plugins/user/emundus/emundus.php | Error when update some informations on profile with external login : ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
				}

			}

			// Init first_login parameter
			$user  = Factory::getUser();
			$table = JTable::getInstance('user', 'JTable');

			$user = Factory::getSession()->get('emundusUser');
			if (empty($user) || empty($user->id)) {
				include_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
				$m_profile = new EmundusModelProfile();
				$m_profile->initEmundusSession();
				$user = Factory::getSession()->get('emundusUser');

				$user->just_logged = true;
			}


			// Log the action of signing in.
			// No id exists in jos_emundus_actions for signin so we use -2 instead.
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');

			// if user_id is null -> there is no session data because the account is not activated yet, so don't log
			if ($user->id) {
				EmundusModelLogs::log($user->id, $user->id, null, -2, '', 'COM_EMUNDUS_LOGS_USER_LOGIN');
			}

			if (empty($user->lastvisitDate)) {
				$user->first_logged = true;
			}
			Factory::getSession()->set('emundusUser', $user);

			if ($options['redirect'] === 0) {
				$previous_url = '';
			}
			else {
				if ($user->activation != -1) {
					$cid_session = JFactory::getSession()->get('login_campaign_id');
					if (!empty($cid_session)) {
						$previous_url = 'index.php?option=com_fabrik&view=form&formid=102&cid=' . $cid_session;
						Factory::getSession()->clear('login_campaign_id');
					}
				}
			}

			PluginHelper::importPlugin('emundus', 'custom_event_handler');
			Factory::getApplication()->triggerEvent('onCallEventHandler', ['onUserLogin', ['user_id' => $user->id]]);

			if (!empty($previous_url)) {
				$this->app->redirect($previous_url);
			}
		}

		return true;
	}

	/**
	 * This method should handle any logout logic and report back to the subject
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options (client, ...).
	 *
	 * @return  Bool  True on success
	 * @throws Exception
	 * @since   1.5
	 */
	public function onUserLogout($user, $options = array())
	{
		$my        = JFactory::getUser();
		$session   = JFactory::getSession();

		$userid = (int) $user['id'];

		// Get by position instead of id and type (2 mod_emundus_user_dropdown are present)
		$modules = JModuleHelper::getModules('header-c');
		foreach ($modules as $module) {
			$params = new JRegistry($module->params);
			$url    = $params->get('url_logout', 'index.php');
		}

		if ($url == '') {
			$url = 'index.php';
		}

		// Make sure we're a valid user first
		if ($user['id'] == 0 && !$my->get('tmp_user')) {
			return true;
		}

		$sharedSessions = $this->app->get('shared_session', '0');

		// Check if the user is using oAuth2
		if (Factory::getUser($user["id"])->getParam('OAuth2')) {

			PluginHelper::importPlugin('authentication');
			$this->app->triggerEvent('onUserAfterLogout', $user['id']);

			return true;
		}

		// Check to see if we're deleting the current session
		if ($my->id == $userid && ($sharedSessions || (!$sharedSessions && $options['clientid'] == $this->app->getClientId()))) {
			// Hit the user last visit field
			$my->setLastVisit();

			// Destroy the php session for this user
			$session->destroy();
		}

		$forceLogout = $this->params->get('forceLogout', 1) && $this->app->get('session_metadata', true);

		if ($forceLogout) {
			$clientId       = $sharedSessions ? null : (int) $options['clientid'];
			UserHelper::destroyUserSessions($user['id'], false, $clientId);
		}

		if ($this->app->isClient('site')) {
			$this->app->getInput()->cookie->set('joomla_user_state', '', 1, $this->app->get('cookie_path', '/'), $this->app->get('cookie_domain', ''));
		}

		$this->app->redirect($url);

		return true;
	}

	/**
	 * This method will return a user object
	 *
	 * If options['autoregister'] is true, if the user doesn't exist yet they will be created
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options (remember, autoregister, group).
	 *
	 * @return  User
	 *
	 * @since   1.5
	 */
	protected function _getUser($user, $options = [])
	{
		$instance = User::getInstance();
		$id       = (int) UserHelper::getUserId($user['username']);

		if ($id) {
			$instance->load($id);

			return $instance;
		}

		// @todo : move this out of the plugin
		$params = ComponentHelper::getParams('com_users');

		// Read the default user group option from com_users
		$defaultUserGroup = $params->get('new_usertype', $params->get('guest_usergroup', 1));

		$instance->id             = 0;
		$instance->name           = $user['fullname'];
		$instance->username       = $user['username'];
		$instance->password_clear = $user['password_clear'];

		// Result should contain an email (check).
		$instance->email  = $user['email'];
		$instance->groups = [$defaultUserGroup];

		// If autoregister is set let's register the user
		$autoregister = $options['autoregister'] ?? $this->params->get('autoregister', 1);

		if ($autoregister) {
			if (!$instance->save()) {
				Log::add('Failed to automatically create account for user ' . $user['username'] . '.', Log::WARNING, 'error');
			}
		}
		else {
			// No existing user and autoregister off, this is a temporary user.
			$instance->set('tmp_user', true);
		}

		return $instance;
	}
}
