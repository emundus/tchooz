<?php
/**
 * @version 2: emundusupdate 2024-04-04 Laura Grandin
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Redirection du formulaire profil lors de la soumission.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

use GuzzleHttp\Client as GuzzleClient;

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */
class PlgFabrik_FormEmundusupdateprofile extends plgFabrik_Form {

	/**
	 * Status field
	 *
	 * @var  string
	 */
	protected $URLfield = '';
	protected $signer_type = '';

	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
	}

	/**
	 * Get an element name
	 *
	 * @param string $pname Params property name to look up
	 * @param bool   $short Short (true) or full (false) element name, default false/full
	 *
	 * @return    string    element full name
	 */
	public function getFieldName($pname, $short = false) {
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
	 * @param string $pname   Params property name to get the value for
	 * @param mixed  $default Default value
	 *
	 * @return  mixed  value
	 */
	public function getParam(string $pname, $default = '') {
		$params = $this->getParams();

		if ($params->get($pname) == '') {
			return $default;
		}

		return $params->get($pname);
	}


	/**
	 * @param array $signer_value
	 *
	 * @return array
	 * @throws Exception
	 */
	private function proccessSignerValues(array $signer_value) : array {

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$jinput = JFactory::getApplication()->input;

		// Run ___ table/column analysis.
		$s_queries = [];
		foreach ($signer_value as $key => $value) {
			$value = trim($value);
			if (strpos($value, '___') !== false) {
				unset($signer_value[$key]);
				$tmp_split = explode('___', $value);
				// Build an array of [table => column] assocs for the different signer names.
				if ((isset($s_queries[$tmp_split[0]]) && !in_array($tmp_split[1], $s_queries[$tmp_split[0]])) || !isset($s_queries[$tmp_split[0]])) {
					$s_queries[$tmp_split[0]][] = $tmp_split[1];
				}
			}
		}

		if (!empty($s_queries)) {
			foreach ($s_queries as $table => $columns) {
				$query->clear()
					->select($db->quoteName($columns))
					->from($db->quoteName($table));

				if ($this->signer_type === 'student') {
					$query->where($db->quoteName('fnum').' = '.$db->quote(JFactory::getSession()->get('emundusUser')->fnum));
				} else {
					$query->where($db->quoteName('user_id').' = '.JFactory::getUser()->id);
				}

				$db->setQuery($query);

				try {
					$signer_value = array_merge($signer_value, $db->loadRow());
				} catch (Exception $e) {

					// This backup solution gets the value in the INPUT, in case all else fails.
					if (count($columns) === 1 && !empty($jinput->getRaw($table.'___'.$columns[0]))) {
						$signer_value[] = $jinput->getRaw($table.'___'.$columns[0]);
					} else {
						return [];
					}
				}
			}
		}

		return $signer_value;
	}

	/**
	 * Main script.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function onAfterProcess() : void {

		// Attach logging system.
		jimport('joomla.log.log');
		JLog::addLogger(['text_file' => 'com_emundus.yousign.php'], JLog::ALL, array('com_emundus.yousign'));

		$app = JFactory::getApplication();

		$menu = $app->getMenu();
		$formModel = $this->getModel();
		$app->enqueueMessage(JText::_('PROFILE_SAVED'), 'info');
		// to do : ajouter l'alias du menu choisi dans les paramètres dans l'url
		$item = $menu->getItems('link', 'index.php?option=com_fabrik&view=form&formid='.$formModel->id, true);
		if (!empty($item)) {
			$app->redirect(JURI::root() . '/' . $item->route);
		}
		// to do : veiller à ce qui n'y ait pas de changement de langue à la sauvegarde
	}

	/**
	 * Raise an error - depends on whether you are in admin or not as to what to do
	 *
	 * @param array   &$err   Form models error array
	 * @param string   $field Name
	 * @param string   $msg   Message
	 *
	 * @return  void
	 * @throws Exception
	 */
	protected function raiseError(array &$err, string $field, string $msg) : void {
		$app = JFactory::getApplication();

		if ($app->isClient('administrator')) {
			$app->enqueueMessage($msg, 'notice');
		} else {
			$err[$field][0][] = $msg;
		}
	}


	/**
	 * @param string $user_email
	 * @param        $param
	 * @param string $value
	 *
	 * @return bool
	 * @since version
	 */
	private function setUserParam(string $user_email, $param, string $value) : bool {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('id'))
			->from($db->quoteName('jos_users'))
			->where($db->quoteName('email').' LIKE '.$db->quote($user_email));
		$db->setQuery($query);

		try {
			$user_id = $db->loadResult();
		} catch (Exception $e) {
			JLog::add('Error getting user by email when saving param : '.$e->getMessage(), JLog::ERROR, 'com_emundus.yousign');
			return false;
		}

		if (empty($user_id)) {
			JLog::add('User not found', JLog::ERROR, 'com_emundus.yousign');
			return false;
		}

		$user = JFactory::getUser($user_id);

		$table = JTable::getInstance('user', 'JTable');
		$table->load($user->id);

		// Store token in User's Parameters
		$user->setParam($param, $value);

		// Get the raw User Parameters
		$params = $user->getParameters();

		// Set the user table instance to include the new token.
		$table->params = $params->toString();

		// Save user data
		if (!$table->store()) {
			JLog::add('Error saving params : '.$table->getError(), JLog::ERROR, 'com_emundus.yousign');
			return false;
		}
		return true;
	}
}
