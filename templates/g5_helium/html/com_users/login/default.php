<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

/** @var \Joomla\Component\Users\Site\View\Login\HtmlView $this */

$cookieLogin = $this->user->get('cookieLogin');

$app = Factory::getApplication();
$session = $app->getSession();
$jinput = Factory::getApplication()->input;
$redirect = base64_decode($jinput->get->getBase64('redirect'));

$eMConfig = ComponentHelper::getParams('com_emundus');

if (!empty($cookieLogin) || $this->user->get('guest')) {

    // Get campaign ID and course from url
    $this->campaign = $jinput->get('cid');
    $this->course   = $jinput->get('course');
    $this->displayRegistration   = $eMConfig->get('display_registration_link',1);
    $this->registrationLink   = $eMConfig->get('registration_link','');
    $this->displayForgotten   = $eMConfig->get('display_forgotten_password_link',1);
    $this->forgottenLink   = $eMConfig->get('forgotten_password_link','index.php?option=com_users&view=reset') ?: 'index.php?option=com_users&view=reset';

    if(empty($this->registrationLink)){
        if(!empty($this->campaign) && !empty($this->course)){
            $this->registrationLink = 'index.php?option=com_users&view=registration&course=' . $this->course . '&cid=' . $this->campaign;
        } else {
            $this->registrationLink = 'index.php?option=com_users&view=registration';
        }
    }
    $session->set('cid',$this->campaign);
    $session->set('course', $this->course);

    // The user is not logged in or needs to provide a password.
    echo $this->loadTemplate('login');
} else {
    // The user is already logged in.
    echo $this->loadTemplate('logout');
}
