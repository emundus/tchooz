<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Tchooz\Factories\LayoutFactory;

$data = LayoutFactory::prepareVueData();

$link_exports = EmundusHelperMenu::getSefAliasByLink('index.php?option=com_emundus&view=export_select_columns&layout=exports');
?>


<div id="em-exports"
     component="Exports/Exports"
     fnums_count="<?= $this->fnumsCount; ?>"
     export_link="<?= $link_exports; ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>