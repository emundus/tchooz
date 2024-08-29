<?php

/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2018 eMundus. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      eMundus SAS - Hugo Moracchini
 */

namespace Joomla\Plugin\Authentication\EmundusOauth2\Extension;

require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\Utilities\ArrayHelper;
use Emundus\OAuth2;
use Joomla\CMS\User\User;

use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Emundus Oauth2 Authentication plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Authentication.emundus_oauth2
 * @since       2.0.0
 */
class EmundusOauth2 extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;
	
	/**
	 * @var  string  The authorisation url.
	 */
	protected $authUrl;

	/**
	 * @var  string  The access token url.
	 */
	protected $tokenUrl;

	/**
	 * @var  string  The REST request domain.
	 */
	protected $domain;

	/**
	 * @var  string[]  Scopes available based on mode settings.
	 */
	protected $scopes;

	/**
	 * @var  string  The authorisation url.
	 */
	protected $logoutUrl;

	/**
	 * @var  object  OpenID attributes.
	 */
	protected $attributes;

	/**
	 * @var object  Mapping attributes.
	 *
	 * @since version 2.0.0
	 */
	protected $mapping;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onUserAuthenticate' => 'onUserAuthenticate',
			'onUserLogout' => 'onUserLogout',
			'onOauth2Authenticate' => 'onOauth2Authenticate',
			'onOauth2Authorise' => 'onOauth2Authorise',
			'onOAuthAfterRegister' => 'onOAuthAfterRegister'
		];
	}


	public function __construct(DispatcherInterface $dispatcher, array $config = [])
	{
		parent::__construct($dispatcher, $config);
		
		$this->loadLanguage();

		$configurations = (array)$this->params->get('configurations', null);
		if(!empty($configurations)) {
			$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
			if (!empty($type)) {
				foreach ($configurations as $configuration) {
					if ($configuration->type === $type) {
						$parameters = ['client_id', 'client_secret', 'scopes', 'auth_url', 'token_url', 'redirect_url', 'sso_account_url', 'emundus_profile', 'email_id', 'logout_url', 'platform_redirect_url', 'attributes', 'debug_mode'];

						foreach ($parameters as $parameter) {
							$this->params->set($parameter, $configuration->{$parameter});
						}
					}
				}
			} else {
				$parameters = ['client_id', 'client_secret', 'scopes', 'auth_url', 'token_url', 'redirect_url', 'sso_account_url', 'emundus_profile', 'email_id', 'logout_url', 'platform_redirect_url', 'attributes', 'debug_mode', 'attribute_mapping','mapping'];

				foreach ($parameters as $parameter) {
					$this->params->set($parameter, $configurations['configurations0']->{$parameter});
				}
			}
		}

		$this->scopes = explode(',', $this->params->get('scopes', 'openid'));
		$this->authUrl = $this->params->get('auth_url');
		$this->domain = $this->params->get('domain');
		$this->tokenUrl = $this->params->get('token_url');
		$this->logoutUrl = $this->params->get('logout_url');

		jimport('joomla.log.log');
		Log::addLogger(array('text_file' => 'com_emundus.oauth2.php'), Log::ALL, array('com_emundus'));
	}

	/**
	 * Handles authentication via the OAuth2 client.
	 *
	 * @param array $credentials Array holding the user credentials
	 * @param array $options Array of extra options
	 * @param object &$response Authentication response object
	 *
	 * @return  boolean
	 * @throws \Exception
	 */
	public function onUserAuthenticate(AuthenticationEvent $event)
	{
		$db = $this->getDatabase();
		$app = $this->getApplication();
		$credentials = $event->getCredentials();
		$options = $event->getOptions();
		$response    = $event->getAuthenticationResponse();
		
		$authenticate = false;
		if(is_string($this->params->get('attributes'))) {
			$this->attributes = json_decode($this->params->get('attributes'));
		}
		elseif (is_object($this->params->get('attributes'))) {
			$this->attributes = (array) $this->params->get('attributes');
		}
		else {
			$this->attributes = $this->params->get('attributes');
		}

		$response->type = 'OAuth2';

		if (ArrayHelper::getValue($options, 'action') == 'core.login.site') {

			$username = ArrayHelper::getValue($credentials, 'username');
			if (!$username) {
				$response->status = Authentication::STATUS_FAILURE;
				$response->error_message = Text::_('JGLOBAL_AUTH_NO_USER');
			} else {
				try {
					$token = ArrayHelper::getValue($options, 'token');
					if(empty($token)) {
						return true;
					}
					$url = $this->params->get('sso_account_url');
					$oauth2 = new OAuth2\Client();
					$oauth2->setToken($token);
					$oauth2->setOption('scope', $this->scopes);
					$result = $oauth2->query($url);

					$body = json_decode($result->body);

					$debug_mode = $this->params->get('debug_mode', 0);
					if($debug_mode) {
						$jsonString = json_encode($body, JSON_PRETTY_PRINT);
						// Write in the file
						$path   = JPATH_ROOT . '/logs/oauth2_attributes.json';
						if(file_exists($path)){
							$debug_file = file_get_contents($path);
							$debug_file = substr(ltrim($debug_file, '['), 0, -1);
							$debug_file .= ",\n".$jsonString;
							file_put_contents($path, '['.$debug_file.']');
						} else {
							$fp     = fopen($path, 'w');
							if($fp) {
								fwrite($fp, '['.$jsonString.']');
								fclose($fp);
							}
						}
					}

					foreach ($this->attributes as $attribute) {
						if ($attribute->table_name == 'jos_users' || (in_array($attribute->column_name, ['firstname', 'lastname']))) {
							$response->{$attribute->column_name} = !empty($body->attributes) && isset($body->attributes->{$attribute->attribute_name}) ? $body->attributes->{$attribute->attribute_name} : $body->{$attribute->attribute_name};
						}
					}

					if (!empty($response->username)) {
						$query = $db->getQuery(true);

						if (empty(UserHelper::getUserId($response->username)) && !empty($response->email)) {
							$query->select('username')
								->from('#__users')
								->where('email = ' . $db->quote($response->email));

							$db->setQuery($query);

							try {
								$existing_username = $db->loadResult();
							} catch (\Exception $e) {
								Log::add('Failed to check if user exists from mail but with another username ' .$e->getMessage(), Log::ERROR, 'com_emundus.error');
							}

							if (!empty($existing_username)) {
								$response->username = $existing_username;
							}
						}

						if (!empty($body->name) && !empty($body->family_name)) {
							$response->firstname = trim(str_replace($body->family_name, '', $body->name));
							$body->firstname = $response->firstname;
							$response->lastname = $body->family_name;
						}

						$response->profile = $this->params->get('emundus_profile', 9);
						$response->status = Authentication::STATUS_SUCCESS;
						$response->isnew = empty(UserHelper::getUserId($response->username));
						$response->error_message = '';
						$user = new User(UserHelper::getUserId($response->username));

						// Mapping
						$response->emundus_profiles = [];
						$response->openid_profiles = [];
						$attribute_mapping = $this->params->get('attribute_mapping');
						if(!empty($attribute_mapping)) {
							if(is_string($this->params->get('mapping'))) {
								$this->mapping = json_decode($this->params->get('mapping'));
							}
							elseif (is_object($this->params->get('mapping'))) {
								$this->mapping = (array) $this->params->get('mapping');
							}
							else {
								$this->mapping = $this->params->get('mapping');
							}

							if(!empty($this->mapping)) {
								$openid_groups = !empty($body->attributes) && isset($body->attributes->{$attribute_mapping}) ? $body->attributes->{$attribute_mapping} : $body->{$attribute_mapping};

								foreach ($this->mapping as $map) {
									$response->openid_profiles[] = $map->emundus_profile;
									if (in_array($map->attribute_value, $openid_groups)) {
										$response->emundus_profiles[] = $map->emundus_profile;
									}
								}
							}
						}

						if ($user->get('block')) {
							$response->status = Authentication::STATUS_FAILURE;
							$response->error_message = Text::_('JGLOBAL_AUTH_ACCESS_DENIED');
						} else {
							$authenticate = true;

							$response->annex_data = [];
							foreach ($this->attributes as $attribute) {
								if ($attribute->table_name !== 'jos_users' && !empty($body->{$attribute->attribute_name}) && !empty($attribute->column_join_user_id)) {

									$response->annex_data[] = [
										'table'               => $attribute->table_name,
										'column'              => $attribute->column_name,
										'value'               => $body->{$attribute->attribute_name},
										'column_join_user_id' => $attribute->column_join_user_id
									];
								}
							}

							if (!$response->isnew) {
								if (!empty($response->annex_data)) {
									$query = $db->getQuery(true);

									$user_id = UserHelper::getUserId($response->username);

									foreach ($response->annex_data as $data) {
										if (is_array($data['value'])) {
											$data['value'] = implode(',', $data['value']);
										}
										$query->clear()
											->update($data['table'])
											->set($db->quoteName($data['column']) . ' = ' . $db->quote($data['value']))
											->where($db->quoteName($data['column_join_user_id']) . ' = ' . $user_id);
										$db->setQuery($query);

										try {
											$db->execute();
										}
										catch (\Exception $e) {
											Log::add('Failed to execute update query ' . $e->getMessage(), Log::ERROR, 'com_emundus.oauth2');
										}
									}
								}
							} else {
								$app->getSession()->set('skip_activation', true);

								$response->params = ['skip_activation' => true];
								$response->activation = 1;
							}
						}

					} else {
						$response->status = Authentication::STATUS_FAILURE;
						$response->error_message = Text::_('JGLOBAL_AUTH_NO_USER');
					}
				} catch (\Exception $e) {
					$response->status = Authentication::STATUS_FAILURE;
				}
			}
		}

		return $authenticate;
	}

	/**
	 * Authenticate the user via the oAuth2 login and authorise access to the
	 * appropriate REST API end-points.
	 */
	public function onOauth2Authenticate()
	{
		$app = $this->getApplication();

		$oauth2 = new OAuth2\Client();
		$oauth2->setOption('authurl', $this->authUrl);
		$oauth2->setOption('clientid', $this->params->get('client_id'));
		$oauth2->setOption('scope', $this->scopes);
		$oauth2->setOption('redirecturi', $this->params->get('redirect_url'));
		$oauth2->setOption('requestparams', array('access_type' => 'offline', 'approval_prompt' => 'auto'));
		$oauth2->setOption('sendheaders', true);
		try {
			$oauth2->authenticate();
		} catch (\Exception $e) {
			$app->enqueueMessage(Text::_('PLG_AUTHENTICATION_EMUNDUS_OAUTH2_CCI_CONNECT_DOWN'));

			//TODO: Get login url from menu helper
			$app->redirect('connexion');
		}
	}

	/**
	 * Swap the authorisation code for a persistent token and authorise access
	 * to Joomla!.
	 *
	 * @return  bool  True if the authorisation is successful, false otherwise.
	 * @throws \Exception
	 */
	public function onOauth2Authorise()
	{
		$app = $this->getApplication();

		$oauth2 = new OAuth2\Client();
		$oauth2->setOption('tokenurl', $this->tokenUrl);
		$oauth2->setOption('clientid', $this->params->get('client_id'));
		$oauth2->setOption('clientsecret', $this->params->get('client_secret'));
		$oauth2->setOption('redirecturi', $this->params->get('redirect_url'));

		try {
			$result = $oauth2->authenticate();
		} catch (\Exception $e) {
			Log::add('Error when try to connect with oauth2 : ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			$app->enqueueMessage(Text::_('PLG_AUTHENTICATION_EMUNDUS_OAUTH2_CONNECT_DOWN'), 'error');

			//TODO: Get login url from menu helper
			$app->redirect(Route::_('connexion'));
		}

		// We insert a temporary username, it will be replaced by the username retrieved from the OAuth system.
		$credentials = ['username' => 'temporary_username'];

		// Adding the token to the login options allows Joomla to use it for logging in.
		$options = [
			'token' => $result,
			'provider' => 'openid',
			'redirect' => $this->params->get('platform_redirect_url'),
			'remember' => true
		];

		// Perform the log in.
		return ($app->login($credentials, $options) === true);
	}

	// After the login has been executed, we need to send the user an email.
	public function onOAuthAfterRegister($event)
	{
		$db = $this->getDatabase();
		$app = $this->getApplication();

		$user = $event->getArgument('user');

		if ($user['type'] == 'OAuth2') {
			$user_id = UserHelper::getUserId($user['username']);

			// check if there is a email template to send
			if ($this->params->get('email_id')) {
				if (!class_exists('EmundusModelEmails')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
				}
				$m_emails = new \EmundusModelEmails();

				$post = [
					'USER_NAME' => $user['fullname'],
					'SITE_URL' => Uri::base(),
					'USER_EMAIL' => $user['email'],
					'USER_PASS' => $user['password'],
					'USERNAME' => $user['username']
				];
				$sent = $m_emails->sendEmailNoFnum($user['email'], $this->params->get('email_id'), $post, $user_id);

				if($sent) {
					return true;
				}
			}

			if (!empty($user['annex_data'])) {
				$query = $db->getQuery(true);

				foreach($user['annex_data'] as $data) {
					if(is_array($data['value'])) {
						$data['value'] = implode(',', $data['value']);
					}

					$query->clear()
						->update($data['table'])
						->set($db->quoteName($data['column']) . ' = ' . $db->quote($data['value']))
						->where($db->quoteName($data['column_join_user_id']) . ' = ' . $user_id);

					$db->setQuery($query);

					try {
						$db->execute();
					} catch (\Exception $e) {
						Log::add('Failed to execute update query ' . $e->getMessage(), Log::ERROR, 'com_emundus.oauth2');
					}
				}
			}
		}
	}

	public function onUserLogout($options)
	{
		$app = $this->getApplication();

		// No remember me for admin
		if ($app->isClient('administrator')) {
			return false;
		}
		
		if(!empty($this->logoutUrl)) {
			$app->redirect($this->logoutUrl);
		}
		
		return true;
	}
}
