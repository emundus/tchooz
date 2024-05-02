<?php
/**
 * @package     eMundus.OAuth2
 *
 * @copyright   Copyright (C) 2018 eMundus All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

/**
 * Listens for oAuth2 authorization tokens.
 *
 * @package  eMundus.OAuth2
 */
class PlgSystemOauth2 extends CMSPlugin {

	function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage();

		Log::addLogger(['text_file' => 'plugins.oauth2.php'], Log::ALL, 'plugins.oauth2');

		if (!isset($this->params)) {
			$plugin = PluginHelper::getPlugin('system', 'oauth2');
			$this->params = new Registry($plugin->params);
		}
	}

	/**
	 * This plugin runs OAuth2 logic if it is detected that we are trying to login/register via an OAuth2 source.
	 *
	 * @return  void
	 * @throws Exception
	 */
	public function onAfterRoute() {
		$app = Factory::getApplication();

		PluginHelper::importPlugin('authentication');
		$dispatcher = JEventDispatcher::getInstance();

		$uri = clone Uri::getInstance();
		$queries = $uri->getQuery(true);

		$task = ArrayHelper::getValue($queries, 'task');

		if ($task == 'oauth2.authenticate') {
			$data = $app->getUserState('users.login.form.data', array());
			$data['return'] = $app->input->get('return', null);
			$app->setUserState('users.login.form.data', $data);
			$dispatcher->trigger('onOauth2Authenticate', array());

		} else {
			$code = ArrayHelper::getValue($queries, 'code', null, 'WORD');
			$session_state = ArrayHelper::getValue($queries, 'session_state', null, 'WORD');
			if(empty($session_state)) {
				$session_state = ArrayHelper::getValue($queries, 'state', null, 'WORD');
			}

			$session_state_required = $this->params->get('session_state_required', 1);

			if (!$session_state_required) {
				$type = ArrayHelper::getValue($queries, 'type', null, 'WORD');
				if (count($queries) > 1 && empty($type)) {
					return;
				}
			} else if (empty($session_state)) {
				return;
			}

			if (!empty($code)) {
				$array = $dispatcher->trigger('onOauth2Authorise', array());

				// redirect user to appropriate area of site.
				if ($array[0] === true) {
					$data = $app->getUserState('users.login.form.data', array());
					$app->setUserState('users.login.form.data', array());

					if ($return = ArrayHelper::getValue($data, 'return'))
						$app->redirect(Route::_($return, false));
					else
						$app->redirect(Route::_(Uri::current(), false));

				} else {
					$app->redirect(Route::_('index.php?option=com_users&view=login', false));
				}
			}
		}
	}
}