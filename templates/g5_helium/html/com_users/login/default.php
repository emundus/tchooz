<?php

/**
 * @package         Joomla.Site
 * @subpackage      com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Symfony\Component\Yaml\Yaml;

/** @var \Joomla\Component\Users\Site\View\Login\HtmlView $this */

$cookieLogin = $this->user->get('cookieLogin');

$app      = Factory::getApplication();
$session  = $app->getSession();
$jinput   = Factory::getApplication()->input;
$redirect = base64_decode($jinput->get->getBase64('redirect'));

$eMConfig = ComponentHelper::getParams('com_emundus');

if (!empty($cookieLogin) || $this->user->get('guest')) {
	
	$this->campaign            = $jinput->get('cid');
	$this->course              = $jinput->get('course');
	$this->displayRegistration = $eMConfig->get('display_registration_link', 1);
	$this->registrationLink    = $eMConfig->get('registration_link', '');
	$this->displayForgotten    = $eMConfig->get('display_forgotten_password_link', 1);
	$this->forgottenLink       = $eMConfig->get('forgotten_password_link', 'index.php?option=com_users&view=reset') ?: 'index.php?option=com_users&view=reset';

	if (empty($this->registrationLink)) {
		require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'menu.php');
		$alias = EmundusHelperMenu::getSefAliasByLink('index.php?option=com_fabrik&view=form&formid=307');

		if(empty($alias)) {
			$alias = 'inscription';
		}

		if (!empty($this->campaign) && !empty($this->course)) {
			$this->registrationLink = $alias.'?course=' . $this->course . '&cid=' . $this->campaign;
		}
		else {

			$this->registrationLink = $alias;
		}
	}
	$session->set('cid', $this->campaign);
	$session->set('course', $this->course);

	require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'settings.php');
	$m_settings = new EmundusModelsettings();

	$this->favicon = $m_settings->getFavicon();

	$this->oauth2Config = null;
	$this->state = null;
	$this->nonce = EmundusHelperMenu::getNonce();
	$emundusOauth2 = PluginHelper::getPlugin('authentication','emundus_oauth2');
	if(!empty($emundusOauth2)) {
		$this->oauth2Config = json_decode($emundusOauth2->params);
		$this->state = bin2hex(random_bytes(128/8));
	}

	echo $this->loadTemplate('login');
}
else {
	echo $this->loadTemplate('logout');
}
