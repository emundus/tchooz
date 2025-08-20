<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use \Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/menu.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/users.php');

/**
 * Manage anonymous registration form
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.btobprofile
 * @since       3.0
 */
class PlgFabrik_FormAnonymRegistration extends plgFabrik_Form
{
	private EmundusModelUsers $m_users;

	private DatabaseDriver $db;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->m_users = new EmundusModelUsers();
		Log::addLogger(array('text_file' => 'plugin.anonymregistration.php'), JLog::ALL, array('plugin.anonymregistration'));
	}

	public function onBeforeLoad()
	{
		$user = Factory::getApplication()->getIdentity();

		if (!$user->guest) {
			$this->app->redirect(EmundusHelperMenu::getHomepageLink());
		}

		$query = $this->db->createQuery();
		$query->select('value')
			->from('#__emundus_setup_config')
			->where('namekey = ' . $this->db->quote('anonymous'));

		$this->db->setQuery($query);
		$params = $this->db->loadResult();

		if (empty($params)) {
			$this->app->redirect(EmundusHelperMenu::getHomepageLink());
		} else {
			$params = json_decode($params, true);
			if (empty($params['enabled'])) {
				$this->app->redirect(EmundusHelperMenu::getHomepageLink());
			}
		}

		$token_field_name = $this->getElementName((int)$this->getParams()->get('token_field', 0));
		if(empty($token_field_name))
		{
			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_ANONYM_REGISTRATION_TOKEN_FIELD_NOT_SET'), 'error');
			$this->app->redirect(EmundusHelperMenu::getHomepageLink());
			return;
		}

		if(!class_exists('EmundusModelUsers')) {
			require_once JPATH_ROOT . '/components/com_emundus/helpers/users.php';
		}
		$token   = EmundusHelperUsers::generateToken();

		if (!empty($token)) {
			$form_model = $this->getModel();
			$db_table_name = $form_model->getTableName();
			$form_model->data[$db_table_name . '___' . $token_field_name] = $token;
		} else {
			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_ANONYM_REGISTRATION_FAILED'), 'error');
			$this->app->redirect(EmundusHelperMenu::getHomepageLink());
		}
	}

	public function onAfterProcess()
	{
		$form_model = $this->getModel();
		$db_table_name = $form_model->getTableName();

		$email_field_name = $this->getElementName((int)$this->getParams()->get('email_field', 0));
		$token_field_name = $this->getElementName((int)$this->getParams()->get('token_field', 0));

		$user_data = [
			'profile_id' => $this->getParams()->get('profile_id') ?? 1000, // Default to 1000 if not set
			'email' => $form_model->getElementData($db_table_name . '___' . $email_field_name),
			'token' => $form_model->getElementData($db_table_name . '___' . $token_field_name),
			'is_anonym' => true
		];

		$campaign_id = 0;
		$campaign_field = $this->getParams()->get('campaign_field', '');
		if (!empty($campaign_field)) {
			$campaign_field_name =  $this->getElementName((int)$campaign_field);
			$campaign_id = $form_model->getElementData($db_table_name . '___' . $campaign_field_name);

			if (empty($campaign_id)) {
				$campaign_id = 0;
			} else if (is_array($campaign_id)) {
				$campaign_id = (int)current($campaign_id);
			} else {
				$campaign_id = (int)$campaign_id;
			}
		}

		$user_id = $this->createAnonymUser($user_data['email'], $user_data['token'], $user_data['profile_id'], $campaign_id);
		if (!empty($user_id)) {
			$user_data['user_id'] = $user_id;

			$program_code = '';
			$program_field = $this->getParams()->get('program_field', '');
			if (!empty($program_field)) {
				$program_code = $form_model->getElementData($program_field);
			}

			$response = $this->m_users->onAfterAnonymUserMapping($user_data, $campaign_id, $program_code);
			if ($response['status']) {
				$this->app->redirect($response['data']['redirect_url']);
			} else {
				$this->app->enqueueMessage($response['message'] , 'error');
				$this->app->redirect(EmundusHelperMenu::getHomepageLink());
			}
		} else {
			$this->app->enqueueMessage(Text::_('COM_EMUNDUS_ANONYM_REGISTRATION_FAILED'), 'error');
			$this->app->redirect(EmundusHelperMenu::getHomepageLink());
		}
	}

	/**
	 * Create an anonymous user
	 * @param string $email
	 * @param string $token
	 * @param int $profile_id
	 * @param int $campaign_id
	 * @return int
	 */
	private function createAnonymUser(string $email, string $token, int $profile_id = 1000, int $campaign_id = 0): int
	{
		$user_id = 0;

		if (!empty($email) && !empty($token))
		{
			if (!$this->isValidToken($token))
			{
				throw new Exception(Text::_('INVALID_TOKEN'));
			}

			$firstname = 'Anonym';
			$lastname  = 'User';
			$params = [
				'name' =>  $firstname . ' ' . $lastname,
				'username' => $email,
				'email' => $email,
				'profile' => $profile_id,
				'firstname' => $firstname,
				'lastname' => $lastname,
				'em_campaigns' => !empty($campaign_id) ? [$campaign_id] : [],
				'anonym' => true,
				'params' => [
					'skip_activation' => true,
					'send_email' => false,
					'anonym_activation' => false
				]
			];

			$created = $this->m_users->addUserFromParams($params, false);

			if ($created) {
				$query = $this->db->createQuery();

				$query->select('id')
					->from('#__users')
					->where('email like ' . $this->db->quote($email));

				$this->db->setQuery($query);
				$user_id = (int)$this->db->loadResult();

				$token_duration_validity = 1; // Default to 1 week
				$token_duration_validity_unit = 'week'; // Default to week
				$query->clear()
					->select('value')
					->from($this->db->quoteName('#__emundus_setup_config'))
					->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('anonymous'));
				$this->db->setQuery($query);
				$anonym_config = $this->db->loadResult();

				if (!empty($anonym_config))
				{
					$anonym_config = json_decode($anonym_config);
					$anonym_params = $anonym_config->params ?? null;
					if (!empty($anonym_params->token_duration_validity)) {
						$token_duration_validity = $anonym_params->token_duration_validity;
					}

					if (!empty($anonym_params->token_duration_validity_unit)) {
						$token_duration_validity_unit = $anonym_params->token_duration_validity_unit;
					}
				}

				$hashtoken = password_hash($token, PASSWORD_BCRYPT);
				$query->clear()
					->update('#__emundus_users')
					->set('token = ' . $this->db->quote($hashtoken))
					->set('token_expiration = ' . $this->db->quote(date('Y-m-d H:i:s', strtotime('+' . $token_duration_validity . ' ' . $token_duration_validity_unit))))
					->set('is_anonym = 1')
					->where('user_id = ' . $user_id);

				try {
					$this->db->setQuery($query);
					$this->db->execute();
				} catch (Exception $e) {
					Log::add('Error while updating token for user ' . $user_id . ': ' . $e->getMessage(), Log::ERROR, 'plugin.anonymregistration');
					$this->app->enqueueMessage(Text::_('COM_EMUNDUS_ANONYM_REGISTRATION_FAILED'), 'error');
					$this->app->redirect(EmundusHelperMenu::getHomepageLink());
				}
			} else
			{
				throw new Exception(Text::_('COM_EMUNDUS_ANONYM_REGISTRATION_FAILED'));
			}
		}

		return $user_id;
	}

	private function isValidToken(string $token, int $length = 16): bool
	{
		if (empty($token)) {
			return false;
		}

		$expectedLength = $length * 2;
		return (strlen($token) === $expectedLength) && ctype_xdigit($token);
	}

	private function getElementName(int $element_id): string
	{
		$name = '';

		if (!empty($element_id)) {
			$query = $this->db->createQuery();

			$query->select('name')
				->from('#__fabrik_elements')
				->where('id = ' . (int) $element_id);

			try {
				$this->db->setQuery($query);
				$name = $this->db->loadResult();
			} catch (Exception $e) {
				Log::add('Error while getting element name for element ' . $element_id . ': ' . $e->getMessage(), Log::ERROR, 'plugin.anonymregistration');
			}
		}

		return $name;
	}
}