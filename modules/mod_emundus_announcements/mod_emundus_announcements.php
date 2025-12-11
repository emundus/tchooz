<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_emundus_custom
 *
 * @copyright   Copyright (C) 2018 eMundus. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die;

$announcement_content = $params->get('announcement_content', '');
$announcement_type = $params->get('announcement_type', 'urgency');

require ModuleHelper::getLayoutPath('mod_emundus_announcements', 'default');
