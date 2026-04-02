<?php
/**
 * @Scpadmin_quickicions module
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Module\Scpadmin_quickicons\Administrator\Helper\Scpadmin_quickiconsHelper;

/** @var \Joomla\Registry\Registry $params */
/** @var \Joomla\Module\ModuleInterface|\stdClass $module */
/** @var array<string,mixed> $attribs */

$user = Factory::getApplication()->getIdentity();

// Añadido ACL (Si se deniega el acceso a la administración de Securitycheck Pro el módulo no será mostrado)
if ($user->authorise('core.manage', 'com_securitycheckpro')) {
    $buttons = Scpadmin_quickiconsHelper::getButtons($params);
    include ModuleHelper::getLayoutPath('mod_scpadmin_quickicons', $params->get('layout', 'default'));
}