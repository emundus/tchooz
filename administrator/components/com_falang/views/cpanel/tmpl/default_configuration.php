<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

?>

<table class="adminlist table table-striped">
    <tr class="row0">
        <th width="220" align="left"><?php echo Text::_('COM_FALANG_CPANEL_CONFIGURATION_PLG_FALANG'); ?></th>
        <td ><?php
            $falang_driver = PluginHelper::getPlugin('system', 'falangdriver');
            if (!empty($falang_driver)) {?>
                <i class="fa fa-check fa-success"></i>
            <?php } else { ?>
                <i class="fa fa-times fa-danger"></i>
            <?php } ?>
        </td>
    </tr>
    <tr class="row1">
        <th width="220" align="left"><?php echo Text::_('COM_FALANG_CPANEL_CONFIGURATION_PLG_LANG_FILTER'); ?></th>
        <td><?php
            $language_filter = PluginHelper::getPlugin('system', 'languagefilter');
            if (!empty($language_filter)) {?>
                <i class="fa fa-check fa-success"></i>
            <?php } else { ?>
                <i class="fa fa-times fa-danger"></i>
            <?php } ?>
        </td>
    </tr>
    <tr class="row0">
        <th width="220" align="left"><?php echo Text::_('COM_FALANG_CPANEL_CONFIGURATION_PLG_QJUMP'); ?></th>
        <td><?php
            $quick_jump = PluginHelper::getPlugin('system', 'falangquickjump');
            if (!empty($quick_jump)) {?>
                <i class="fa fa-check fa-success"></i>
            <?php } else { ?>
                <i class="fa fa-times fa-danger"></i>
            <?php } ?>
        </td>
    </tr>

</table>