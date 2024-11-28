<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die('Access Deny');

require_once(dirname(__FILE__).DS.'helper.php');

$document = Factory::getApplication()->getDocument();
$wa = $document->getWebAssetManager();
$wa->registerAndUseStyle('mod_emundus_events', 'modules/mod_emundus_events/css/mod_emundus_events.css');

$table = $params->get('table', 'data_events');
$period = $params->get('period', 1);
$events = modEmundusEventsHelper::getEvents($table, $period);

$bg_color = $params->get('background_color', '#FDFBFF');
$text_color = $params->get('text_color', '#622E68');
$border_color = $params->get('border_color', '#E8E2F5');
$number = $params->get('number', 3);
$year = $params->get('show_year', 1);
$end = $params->get('end_date', 2);

require(ModuleHelper::getLayoutPath('mod_emundus_events'));
?>
