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
class PlgFabrik_FormConnectFromToken extends plgFabrik_Form
{
	private EmundusModelUsers $m_users;

	private DatabaseDriver $db;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->m_users = new EmundusModelUsers();
		Log::addLogger(array('text_file' => 'plugin.connectfromtoken.php'), JLog::ALL, array('plugin.connectfromtoken'));
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
	}

	public function onAfterProcess()
	{
		$form_model = $this->getModel();
		$db_table_name = $form_model->getTableName();

		$token_field_name = $this->getElementName((int)$this->getParams()->get('token_field', 0));
		$token = $form_model->getElementData($db_table_name . '___' . $token_field_name);

		if (!empty($token)) {
			try {
				$ip = Factory::getApplication()->input->server->get('REMOTE_ADDR');
				$this->m_users->connectUserFromToken($token, $ip);
			} catch (Exception $e) {
				Log::add('Error while connecting user from token: ' . $e->getMessage(), Log::ERROR, 'plugin.connectfromtoken');
				$this->app->redirect(EmundusHelperMenu::getHomepageLink());
			}
		}
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
				Log::add('Error while getting element name for element ' . $element_id . ': ' . $e->getMessage(), Log::ERROR, 'plugin.connectfromtoken');
			}
		}

		return $name;
	}
}