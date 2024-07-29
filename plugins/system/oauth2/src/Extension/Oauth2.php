<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.cache
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Oauth2\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Page Cache Plugin.
 *
 * @since  1.5
 */
final class Oauth2 extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Constructor
	 *
	 * @param   DispatcherInterface              $dispatcher                 The object to observe
	 * @param   array                            $config                     An optional associative
	 *                                                                       array of configuration
	 *                                                                       settings. Recognized key
	 *                                                                       values include 'name',
	 *                                                                       'group', 'params',
	 *                                                                       'language'
	 *                                                                       (this list is not meant
	 *                                                                       to be comprehensive).
	 *
	 * @since   4.2.0
	 */
	public function __construct(
		DispatcherInterface $dispatcher,
		array $config
	) {
		parent::__construct($dispatcher, $config);

		Log::addLogger(['text_file' => 'plugins.oauth2.php'], Log::ALL, 'plugins.oauth2');

		if (!isset($this->params)) {
			$plugin = PluginHelper::getPlugin('system', 'oauth2');
			$this->params = new Registry($plugin->params);
		}
	}

	/**
	 * Returns an array of CMS events this plugin will listen to and the respective handlers.
	 *
	 * @return  array
	 *
	 * @since   4.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		/**
		 * Note that onAfterRender and onAfterRespond must be the last handlers to run for this
		 * plugin to operate as expected. These handlers put pages into cache. We must make sure
		 * that a. the page SHOULD be cached and b. we are caching the complete page, as it's
		 * output to the browser.
		 */
		return [
			'onAfterRoute'   => 'onAfterRoute',
		];
	}

	/**
	 * Returns a cached page if the current URL exists in the cache.
	 *
	 * @param   Event  $event  The Joomla event being handled
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onAfterRoute(Event $event)
	{
		$app = $this->getApplication();

		PluginHelper::importPlugin('authentication');
		$dispatcher = $app->getDispatcher();

		$uri = clone Uri::getInstance();
		$queries = $uri->getQuery(true);

		$task = ArrayHelper::getValue($queries, 'task');

		if ($task == 'oauth2.authenticate') {
			$data = $app->getUserState('users.login.form.data', array());
			$data['return'] = $app->input->get('return', null);
			$app->setUserState('users.login.form.data', $data);
			$dispatcher->triggerEvent('onOauth2Authenticate', array());

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
				$array = $dispatcher->triggerEvent('onOauth2Authorise', array());

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
