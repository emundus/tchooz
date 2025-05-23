<?php
/**
 * Fabrik Visualization module - display a fabrik visualization within a Joomla page
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

jimport('joomla.filesystem.file');

// Load front end language file as well
$lang = Factory::getLanguage();
$lang->load('com_fabrik', JPATH_SITE . '/components/com_fabrik');

if (!defined('COM_FABRIK_FRONTEND'))
{
	throw new RuntimeException(Text::_('COM_FABRIK_SYSTEM_PLUGIN_NOT_ACTIVE'), 400);
}

jimport('joomla.application.component.model');
jimport('joomla.application.component.helper');
BaseDatabaseModel::addIncludePath(COM_FABRIK_FRONTEND . '/models', 'FabrikFEModel');

$app = Factory::getApplication();
$input = $app->input;

require_once COM_FABRIK_FRONTEND . '/controller.php';
require_once COM_FABRIK_FRONTEND . '/controllers/visualization.php';

// $$$rob looks like including the view does something to the layout variable
$origLayout = $input->get('layout', '', 'string');
require_once COM_FABRIK_FRONTEND . '/views/list/view.html.php';
$input->set('layout', $origLayout);

BaseDatabaseModel::addIncludePath(COM_FABRIK_FRONTEND . '/models');
Table::addIncludePath(COM_FABRIK_BASE . '/administrator/components/com_fabrik/tables');
$document = Factory::getDocument();

require_once COM_FABRIK_FRONTEND . '/views/form/view.html.php';

$id = intval($params->get('id', 1));

/*
 * This all works fine for a list
 * going to try to load a package so u can access the form and list
 */
$moduleclass_sfx = $params->get('moduleclass_sfx', '');

$viewName = 'visualization';
$db = FabrikWorker::getDbo();
$query = $db->getQuery(true);
$query->select('plugin')->from('#__fabrik_visualizations')->where('id = ' . (int) $id);
$db->setQuery($query);
$name = $db->loadResult();
$path = JPATH_SITE . '/plugins/fabrik_visualization/' . $name . '/controllers/' . $name . '.php';

if (file_exists($path))
{
	require_once $path;
}
else
{
	$app->enqueueMessage('Could not load visualization: ' . $name, 'notice');

	return;
}

$controllerName = 'FabrikControllerVisualization' . $name;
$controller = new $controllerName;
$controller->addViewPath(JPATH_SITE . '/plugins/fabrik_visualization/' . $name . '/views');
$controller->addViewPath(COM_FABRIK_FRONTEND . '/views');

// Add the model path
$modelpaths = BaseDatabaseModel::addIncludePath(JPATH_SITE . '/plugins/fabrik_visualization/' . $name . '/models');
$modelpaths = BaseDatabaseModel::addIncludePath(COM_FABRIK_FRONTEND . '/models');

$origId = $input->getInt('visualizationid');
$origView = $input->get('view');

$input->set('view', $viewName);
$input->set('visualizationid', $id);
$controller->display();
$input->set('visualizationid', $origId);
$input->set('view', $origView);
