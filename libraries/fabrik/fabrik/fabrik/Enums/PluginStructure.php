<?php

/**
 * Fabrik Enums for Plugin Structure
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Enums;

// No direct access
defined('_JEXEC') or die('Restricted access');

enum PluginStructure: int {
	case J3 = 0;
	case J4	= 1;
}
