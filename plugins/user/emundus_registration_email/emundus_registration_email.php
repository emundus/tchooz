<?php

/**
 * @package   Registration Email
 * @author    Hugo Moracchini
 * @copyright Copyright (c)2018 eMundus SA
 * @license   GNU General Public License version 2, or later
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die('Restricted access');
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// phpcs:enable PSR1.Files.SideEffects

class plgUserEmundus_registration_email extends CMSPlugin
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
	 * Constructor
	 *
	 * @access public
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @throws Exception
	 * @since  3.9.1
	 */
	public function __construct(&$subject, $config)
	{

		parent::__construct($subject, $config);
		$this->loadLanguage();

		$input = $this->app->input;

		if ($input->getInt('emailactivation') && Factory::getUser()->guest) {
			$userId = $input->getInt('u');
			$user   = Factory::getUser($userId);

			if (!$user->guest) {

				$table = Table::getInstance('user', 'JTable');
				$table->load($userId);

				if (empty($table->id)) {
					throw new Exception('User cannot be found');
				}

				$params = new JRegistry($table->params);

				$token = $params->get('emailactivation_token');
				$token = md5($token);

				require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');
				$redirect = EmundusHelperMenu::getHomepageLink($this->params->get('activation_redirect', 'index.php'));

				if (!empty($token) && strlen($token) === 32 && $input->getInt($token, 0, 'get') === 1) {

					// Remove token and from user params.
					$params->set('emailactivation_token', null);
					$table->params = $params->toString();

					// Unblock the user :)
					$table->block      = 0;
					$table->activation = 1;

					// save user data
					if ($table->store()) {
						$this->app->enqueueMessage(Text::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ACTIVATED'), 'success');
					}
					else {
						throw new RuntimeException($table->getError());
					}

				}
				elseif ($table->block == 0) {
					$this->app->enqueueMessage(Text::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ALREADY_ACTIVATED'), 'warning');
				}
				else {
					$this->app->enqueueMessage(Text::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ERROR_ACTIVATED'), 'error');
				}

				if (!empty($redirect)) {
					$this->app->redirect($redirect);
				}
			}
		}
	}

	/**
	 * Call our custom plugin event after the user is saved.
	 *
	 * @param $user
	 * @param $isnew
	 * @param $result
	 * @param $error
	 *
	 * @throws Exception
	 * @since 3.9.1
	 *
	 */
	public function onUserAfterSave($user, $isnew, $result, $error)
	{
		$app = Factory::getApplication();
		// The method check here ensures that if running as a CLI Application we don't get any errors
		if (method_exists($app, 'isClient') && ($app->isClient('cli'))) {
			return;
		}

		$this->onAfterStoreUser($user, $isnew, $result, $error);
	}


	/**
	 * Once a new user is created, add the activation email token in his params.
	 *
	 * @param $new
	 * @param $isnew
	 * @param $result
	 * @param $error
	 *
	 * @throws Exception
	 * @since 3.9.1
	 *
	 */
	public function onAfterStoreUser($new, $isnew, $result, $error)
	{
		$userId   = (int) $new['id'];
		$user     = Factory::getUser($userId);
		$eMConfig = ComponentHelper::getParams('com_emundus');

		if (!$isnew || !Factory::getApplication()->getIdentity()->guest) {
			return;
		}

		// If user is found in the LDAP system.
		if (PluginHelper::getPlugin('authentication', 'ldap')) {
			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
			$m_users = new EmundusModelusers();
			$return  = $m_users->searchLDAP($user->username);

			if (!empty($return->users[0])) {
				return;
			}
		}

		if (PluginHelper::getPlugin('authentication', 'miniorangesaml')) {
			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
			$m_users    = new EmundusModelusers();
			$isSamlUser = $m_users->isSamlUser($userId);

			if ($isSamlUser) {
				return;
			}
		}

		if (PluginHelper::getPlugin('system', 'emundusproxyredirect')) {
			$params       = json_decode(PluginHelper::getPlugin('system', 'emundusproxyredirect')->params, true);
			$http_headers = $_SERVER;

			if ($params['test_mode'] == 1) {
				$http_headers = [
					'username' => 'developer',
					'email'    => 'dev@emundus.io'
				];

				$login_route   = Uri::root() . 'connexion';
				$current_route = Uri::getInstance()->toString();

				if ($current_route != $login_route) {
					return false;
				}
			}

			if (!empty($http_headers[$params['username']]) && !empty($http_headers[$params['email']])) {
				return false;
			}
		}

		if ($result && !$error) {
			// for anonym sessions
			$allow_anonym_files = $eMConfig->get('allow_anonym_files', 0);
			if ($allow_anonym_files && preg_match('/^fake.*@emundus\.io$/', $user->email)) {
				$user->setParam('skip_activation', true);
				$user->setParam('send_mail', false);
			}

			// Generate the activation token.
			$activation = md5(mt_rand());

			// Store token in User's Parameters
			$user->setParam('emailactivation_token', $activation);
			$user->save();

			// Set the user table instance to include the new token.
			$table = Table::getInstance('user');
			$table->load($userId);

			// Block the user (until he activates).
			$table->block = $eMConfig->get('block_user', 1);

			// Save user data
			if (!$table->store()) {
				throw new RuntimeException($table->getError());
			}

			// Send activation email
			if ($this->sendActivationEmail($user->getProperties(), $activation)) {
				//Force user logout
				if ($this->params->get('logout', null) && $userId === (int) Factory::getApplication()->getIdentity()->id) {
					$this->app->logout();
					$this->app->redirect(Route::_(''), false);
				}
			}

			$this->onUserAfterLogin($new);
		}
	}

	/**
	 * Hooks on the Joomla! login event. Detects silent logins and disables the Multi-Factor
	 * Authentication page in this case.
	 *
	 * Moreover, it will save the redirection URL and the Captive URL which is necessary in Joomla 4. You see, in Joomla
	 * 4 having unified sessions turned on makes the backend login redirect you to the frontend of the site AFTER
	 * logging in, something which would cause the Captive page to appear in the frontend and redirect you to the public
	 * frontend homepage after successfully passing the Two Step verification process.
	 *
	 * @param   array  $options  Passed by Joomla. user: a User object; responseType: string, authentication response type.
	 *
	 * @return void
	 * @since  4.2.0
	 */
	public function onUserAfterLogin(array $options): void
	{
		$this->app = Factory::getApplication();
		$query     = $this->db->getQuery(true);

		$query->select($this->db->quoteName(array('id', 'block', 'params')))
			->from($this->db->quoteName('#__users'))
			->where($this->db->quoteName('username') . ' LIKE ' . $this->db->quote($options['username']));
		$this->db->setQuery($query);
		$result = $this->db->loadObject();

		$token = json_decode($result->params);
		$token = $token->emailactivation_token;

		if ($token != null) {
			$fields     = array(
				$this->db->quoteName('block') . ' = ' . $this->db->quote(0),
				$this->db->quoteName('activation') . ' = ' . $this->db->quote(-1),
			);
			$conditions = array(
				$this->db->quoteName('id') . ' = ' . $this->db->quote($options['id']),
			);
			$query->clear()
				->update($this->db->quoteName('#__users'))
				->set($fields)
				->where($conditions);
			$this->db->setQuery($query);
			$this->db->execute();

			$credentials             = array();
			$credentials['username'] = $options['username'];
			$credentials['password'] = $options['password_clear'];

			$options             = array();
			$options['redirect'] = '/index.php?option=com_emundus&view=user';
			$this->app->login($credentials, $options);
		}
		else {
			$fields     = array(
				$this->db->quoteName('block') . ' = ' . $this->db->quote(0),
				$this->db->quoteName('activation') . ' = ' . $this->db->quote(1),
			);
			$conditions = array(
				$this->db->quoteName('id') . ' = ' . $this->db->quote($options['id']),
			);
			$query->clear()
				->update($this->db->quoteName('#__users'))
				->set($fields)
				->where($conditions);
			$this->db->setQuery($query);
			$this->db->execute();
		}
	}

	/**
	 * Send activation email to user in order to proof it
	 *
	 * @param   array   $data   JUser Properties ($user->getProperties)
	 * @param   string  $token  Activation token
	 *
	 * @return bool
	 * @throws Exception
	 * @since  3.9.1
	 *
	 * @access private
	 *
	 */
	private function sendActivationEmail($data, $token)
	{

		$params = json_decode($data['params']);
		if (isset($params->skip_activation)) {
			return false;
		}

		$input    = $this->app->input;
		$civility = is_array($input->post->get('jos_emundus_users___civility')) ? $input->post->get('jos_emundus_users___civility')[0] : $input->post->get('jos_emundus_users___civility');
		$password = !empty($data['password_clear']) ? $data['password_clear'] : $input->post->get('jos_emundus_users___password');

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'controllers' . DS . 'messages.php');
		$c_messages = new EmundusControllerMessages();

		$userID   = (int) $data['id'];
		$baseURL  = rtrim(Uri::root(), '/');
		$md5Token = md5($token);

		// Get a SEF friendly URL or else sites with SEF return 404.
		// WARNING: This requires making a root level menu item in the backoffice going to com_users&task=edit on the slug /activation.
		// TODO: Possibly use JRoute to make this work without needing a menu item?
		if (Factory::getApplication()->get('sef') == 0) {
			$activation_url_rel = '/index.php?option=com_users&task=edit&emailactivation=1&u=' . $userID . '&' . $md5Token . '=1';
		}
		else {
			$activation_url_rel = '/activation?emailactivation=1&u=' . $userID . '&' . $md5Token . '=1';
		}
		$activation_url = $baseURL . $activation_url_rel;

		$post = [
			'CIVILITY'           => $civility,
			'USER_NAME'          => $data['name'],
			'USER_EMAIL'         => $data['email'],
			'SITE_NAME'          => Factory::getApplication()->get('sitename'),
			'ACTIVATION_URL'     => $activation_url,
			'ACTIVATION_URL_REL' => $activation_url_rel,
			'BASE_URL'           => $baseURL,
			'USER_LOGIN'         => $data['username'],
			'USER_PASSWORD'      => $password
		];

		// Send the email.
		return $c_messages->sendEmailNoFnum($data['email'], $this->params->get('email', 'registration_email'), $post, $userID);
	}
}
