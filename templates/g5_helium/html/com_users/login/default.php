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
use Symfony\Component\Yaml\Yaml;

/** @var \Joomla\Component\Users\Site\View\Login\HtmlView $this */

$cookieLogin = $this->user->get('cookieLogin');

$app      = Factory::getApplication();
$session  = $app->getSession();
$jinput   = Factory::getApplication()->input;
$redirect = base64_decode($jinput->get->getBase64('redirect'));

$eMConfig = ComponentHelper::getParams('com_emundus');

if (!empty($cookieLogin) || $this->user->get('guest')) {

	// TODO: get registration link from jos_menu

	$this->campaign            = $jinput->get('cid');
	$this->course              = $jinput->get('course');
	$this->displayRegistration = $eMConfig->get('display_registration_link', 1);
	$this->registrationLink    = $eMConfig->get('registration_link', '');
	$this->displayForgotten    = $eMConfig->get('display_forgotten_password_link', 1);
	$this->forgottenLink       = $eMConfig->get('forgotten_password_link', 'index.php?option=com_users&view=reset') ?: 'index.php?option=com_users&view=reset';

	if (empty($this->registrationLink)) {
		if (!empty($this->campaign) && !empty($this->course)) {
			$this->registrationLink = 'inscription?course=' . $this->course . '&cid=' . $this->campaign;
		}
		else {
			$this->registrationLink = 'inscription';
		}
	}
	$session->set('cid', $this->campaign);
	$session->set('course', $this->course);

	if(file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml')) {
		$yaml = Yaml::parse(file_get_contents(JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml'));
		$this->favicon = $yaml['favicon'];
	}

	if(!file_exists($this->favicon)) {
		$this->favicon = '/images/custom/favicon.png';
	}

	echo $this->loadTemplate('login');
}
else {
	echo $this->loadTemplate('logout');
}
