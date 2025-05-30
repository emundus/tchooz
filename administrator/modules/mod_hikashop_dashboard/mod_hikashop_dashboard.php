<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
if(!include_once(JPATH_ROOT.'/administrator/components/com_hikashop/helpers/helper.php')) {
	echo 'This module can not work without the Hikashop Component';
	return;
};

$moduleclass_sfx = $params->get('moduleclass_sfx','');

$statisticsClass = hikashop_get('class.statistics');
$statistics = $statisticsClass->getDashboard('joomla_dashboard');

$statistics_slots = array();
foreach($statistics as $key => &$stat) {
	$slot = (int)@$stat['slot'];
	$stat['slot'] = $slot;
	$stat['key'] = $key;
	$statistics_slots[ $slot ] = $slot;
}
unset($stat);
asort($statistics_slots);

require(JModuleHelper::getLayoutPath('mod_hikashop_dashboard','default'));
