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
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);

include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php');

$view = hikaInput::get()->getCmd('view');
$ctrl = hikaInput::get()->getCmd('ctrl');
if(!empty($view) && !$ctrl) {
	hikaInput::get()->set('ctrl', $view);
	$layout = hikaInput::get()->getCmd('layout');
	if(!empty($layout)){
		hikaInput::get()->set('task', $layout);
	}
}
elseif(!empty($ctrl) && !$view) {
	hikaInput::get()->set('view', $ctrl);
	$layout = hikaInput::get()->getString('task');
	if(!empty($layout)){
		hikaInput::get()->set('layout', $layout);
	}
}

$taskGroup = hikaInput::get()->getCmd('ctrl','dashboard');
$config =& hikashop_config();
if(HIKASHOP_J40)
	JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'left'));
else
	JHTML::_('behavior.tooltip');
$bar = JToolBar::getInstance('toolbar');
$bar->addButtonPath(HIKASHOP_BUTTON);

if($taskGroup != 'update' && !$config->get('installcomplete')){
	$url = hikashop_completeLink('update&task=install',false,true);
	echo "<script>document.location.href='".$url."';</script>\n";
	echo 'Install not finished... You will be redirected to the second part of the install screen<br/>';
	echo '<a href="'.$url.'">Please click here if you are not automatically redirected within 3 seconds</a>';
	return;
}

$currentuser = JFactory::getUser();
if($taskGroup != 'update' && !$currentuser->authorise('core.manage', 'com_hikashop')) {
	$app = JFactory::getApplication();
	$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
	return;
}
if(in_array($taskGroup, array('config', 'view', 'email', 'field', 'massaction')) && !$currentuser->authorise('core.admin', 'com_hikashop')) {
	$app = JFactory::getApplication();
	$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');
	return;
}

ob_start();

$classGroup = hikashop_get('controller.'.$taskGroup);

if(empty($classGroup)) {
	$app = JFactory::getApplication();
	$app->enqueueMessage('Page not found : '.$taskGroup, 'warning');
	return;
}

hikaInput::get()->set('view', $classGroup->getName() );
$classGroup->execute( hikaInput::get()->get('task','listing'));
$classGroup->redirect();
if(hikaInput::get()->getString('tmpl') !== 'component'){
	echo hikashop_footer();
}

$joomla_version_class = 'hika_j'.(int)HIKASHOP_JVERSION;
if((int)HIKASHOP_JVERSION == 5) {
	$joomla_version_class .= ' hika_j4';
}
$html = ob_get_clean();
if(HIKASHOP_J50 && hikaInput::get()->getString('tmpl') === 'component') {
	$html = '<div id="header"><div class="header-title"></div><div class="header-items"></div><div id="header-more-items"></div></div>'. $html;
}
echo '<div id="hikashop_main_content" class="hikashop_main_content '.$joomla_version_class.'">'.$html.'</div>';

hikashop_cleanCart();
