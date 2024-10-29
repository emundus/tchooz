<?php

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

class EmundusModelUser extends JModelList
{

	public function __construct()
	{
		parent::__construct();
	}

	public function sendActivationEmail($data, $token, $email)
	{
		$app = Factory::getApplication();
		if (json_decode($data['params'])->skip_activation) {
			return false;
		}

		$input    = $app->input;
		$civility = is_array($input->post->get('jos_emundus_users___civility')) ? $input->post->get('jos_emundus_users___civility')[0] : $input->post->get('jos_emundus_users___civility');
		$password = !empty($data['password_clear']) ? $data['password_clear'] : $input->post->get('jos_emundus_users___password');

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'controllers' . DS . 'messages.php');
		$c_messages = new EmundusControllerMessages();

		$userID   = (int) $data['id'];
		$baseURL  = rtrim(JURI::root(), '/');
		$md5Token = md5($token);

		// Compile the user activated notification mail values.
		$config = JFactory::getConfig();

		if ($config->get('sef') == 0) {
			$activation_url_rel = '/index.php?option=com_users&task=edit&emailactivation=1&u=' . $userID . '&' . $md5Token . '=1';
		} else
		{
			$activation_url_rel = '/activation?emailactivation=1&u=' . $userID . '&' . $md5Token . '=1';
		}

		$activation_url = $baseURL . $activation_url_rel;

		$logo = EmundusHelperEmails::getLogo(true);

		$post = [
			'CIVILITY'           => $civility,
			'USER_NAME'          => $data['name'],
			'USER_EMAIL'         => $email,
			'SITE_NAME'          => $config->get('sitename'),
			'ACTIVATION_URL'     => $activation_url,
			'ACTIVATION_URL_REL' => $activation_url_rel,
			'BASE_URL'           => $baseURL,
			'USER_LOGIN'         => $email,
			'USER_PASSWORD'      => $password,
			'LOGO'               => Uri::base().'images/custom/'.$logo
		];

		return $c_messages->sendEmailNoFnum($email, 'registration_email', $post, $userID);
	}

	public function updateEmailUser($user_id, $email)
	{
		$db           = JFactory::getDbo();
		$session      = JFactory::getSession();
		$current_user = $session->get('emundusUser');

		$query      = $db->getQuery(true);
		$fields     = array(
			$db->quoteName('email') . ' = ' . $db->quote($email),
			$db->quoteName('username') . ' = ' . $db->quote($email)
		);
		$conditions = array(
			$db->quoteName('id') . ' = ' . $db->quote($user_id)
		);
		$query->update($db->quoteName('#__users'))->set($fields)->where($conditions);
		$db->setQuery($query);

		try {
			$db->execute();
		}
		catch (Exception $e) {
			JLog::add('Error updating email user: ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
		}

		$query->clear();
		$fields2     = array(
			$db->quoteName('email') . ' = ' . $db->quote($email)
		);
		$conditions2 = array(
			$db->quoteName('user_id') . ' = ' . $db->quote($user_id)
		);
		$query->update($db->quoteName('#__emundus_users'))->set($fields2)->where($conditions2);
		$db->setQuery($query);

		try {
			$db->execute();
		}
		catch (Exception $e) {
			JLog::add('Error updating email user: ' . $e->getMessage(), JLog::ERROR, 'com_emundus');
		}

		$current_user->username = $email;
		$current_user->email    = $email;
		$session->set('emundusUser', $current_user);
	}

	public function getUsernameByEmail($email)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$username = '';

		try {
			// Check if $email is not a username
			$query->select('username')
				->from($db->quoteName('#__users'))
				->where($db->quoteName('username') . ' LIKE ' . $db->quote($email));
			$db->setQuery($query);
			$username = $db->loadResult();

			if (empty($username)) {
				$query->clear()
					->select('username')
					->from($db->quoteName('#__users'))
					->where($db->quoteName('email') . ' LIKE ' . $db->quote($email));
				$db->setQuery($query);
				$username = $db->loadResult();
			}
		}
		catch (Exception $e) {
			JLog::add(basename(__FILE__) . ' | Error getting username with email : ' . $email, JLog::ERROR, 'com_emundus');
		}

		return $username;
	}

}
