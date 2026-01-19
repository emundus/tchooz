<?php
/**
 * @version     $Id: emundus_period.php 10709 2016-04-07 09:58:52Z emundus.fr $
 * @package     Joomla
 * @copyright   Copyright (C) 2016 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Automation\EventContextEntity;

class plgSystemEmundus_block_user extends CMSPlugin
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

	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();
	}

	function onAfterInitialise()
	{
		if(!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
		}
		if(!class_exists('EmundusHelperMenu'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/menu.php');
		}


		$user  = $this->app->getIdentity();
		$input = $this->app->input;
		$uri   = Uri::getInstance();

		if (
			!$this->app->isClient('administrator') &&
			!empty($user->id) &&
			EmundusHelperAccess::isApplicant($user->id) &&
			($input->get('option', '') != 'com_emundus' && $input->get('view', '') != 'user') &&
			!str_contains($uri->toString(), 'logout')
		)
		{
			$activationUri = $this->app->getUserState('users.login.activation.return');
			if (!empty($activationUri))
			{
				$this->app->setUserState('users.login.activation.return', null);
				$this->app->redirect($activationUri);
			}

			$token = $user->getParam('emailactivation_token', '');
			$token = md5($token);

			if (!empty($token) && strlen($token) === 32 && $this->app->input->getInt($token, 0) === 1 && $input->getInt('emailactivation', 0) == 1)
			{
				$user->activation = 1;
				$user->setParam('emailactivation_token', null);

				if (!$user->save())
				{
					$this->app->enqueueMessage(Text::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ACTIVATION_ERROR'), 'error');
					return;
				}

				// dispatch event First Login
				$dispatcher = $this->app->getDispatcher();
				PluginHelper::importPlugin('emundus');
				$onCallEventHandler = new GenericEvent(
					'onCallEventHandler',
					[
						'onAfterUserActivation',
						[
							'context' => new EventContextEntity(
								$user,
								[],
								[$user->id]
							)
						]
					]
				);
				$dispatcher->dispatch('onCallEventHandler', $onCallEventHandler);

				$this->app->enqueueMessage(Text::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ACTIVATED'), 'success');

				$redirect = EmundusHelperMenu::getHomepageLink($this->params->get('activation_redirect', 'index.php'));
				if (!empty($redirect))
				{
					$this->app->redirect($redirect);
				}
			}
			elseif (($user->activation == 1 || $user->activation == 0) && $input->getInt('emailactivation', 0) == 1)
			{
				$this->app->enqueueMessage(JText::_('PLG_EMUNDUS_REGISTRATION_EMAIL_ALREADY_ACTIVATED'), 'warning');

				$redirect = EmundusHelperMenu::getHomepageLink($this->params->get('activation_redirect', 'index.php'));
				if (!empty($redirect))
				{
					$this->app->redirect($redirect);
				}
			}
			else
			{
				if (((int) $user->activation == -1 || $user->activation == -2) && !str_contains($uri->toString(), 'activation'))
				{
					if (empty($user->getParam('emailactivation_token')))
					{
						$activation = md5(mt_rand());
						$user->setParam('emailactivation_token', $activation);
						if($user->save())
						{
							$this->sendReActivationEmail($user, $activation);
						}
					}

					$this->app->redirect('activation');
				}
			}
		}
	}

	private function sendReActivationEmail($user, $token)
	{
		define('JPATH_COMPONENT', 'com_emundus');

		if ($user->getParam('skip_activation', false)) {
			return false;
		}

		if(!class_exists('EmundusModelEmails'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
		}
		$m_emails = new EmundusModelEmails();

		$baseURL  = rtrim(Uri::root(), '/');
		$md5Token = md5($token);

		if ($this->app->get('sef') == 0) {
			$activation_url_rel = '/index.php?option=com_users&task=edit&emailactivation=1&u=' . $user->id . '&' . $md5Token . '=1';
		}
		else {
			$activation_url_rel = '/activation?emailactivation=1&u=' . $user->ud . '&' . $md5Token . '=1';
		}
		$activation_url = $baseURL . $activation_url_rel;

		if(!class_exists('EmundusHelperEmails'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/emails.php');
		}
		$logo = EmundusHelperEmails::getLogo(true);

		$post = [
			'USER_NAME'          => $user->name,
			'USER_EMAIL'         => $user->email,
			'SITE_NAME'          => $this->app->get('sitename'),
			'ACTIVATION_URL'     => $activation_url,
			'ACTIVATION_URL_REL' => $activation_url_rel,
			'BASE_URL'           => $baseURL,
			'USER_LOGIN'         => $user->username,
			'LOGO'               => Uri::base().'images/custom/'.$logo
		];

		return $m_emails->sendEmailNoFnum($user->email, 'enable_inactive_account', $post, $user->id);
	}
}
