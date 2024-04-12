<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die('Access Deny');
require_once(dirname(__FILE__) . DS . 'helper.php');

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('bootstrap-336', 'media/com_emundus/lib/bootstrap-336/css/bootstrap.min.css');
$wa->registerAndUseStyle('mod_emundus_calendar_add', 'media/com_emundus/css/mod_emundus_calendar_add.css');

$user   = Factory::getApplication()->getIdentity();
$helper = new modEmundusTimeslotsHelper;

$calendars = $helper->getCalendars();

if ($calendars !== false) {
	require(ModuleHelper::getLayoutPath('mod_emundus_calendar_create_timeslots'));
}

?>