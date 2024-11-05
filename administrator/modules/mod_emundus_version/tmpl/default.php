<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_version
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<div class="header-item-content joomlaversion">
    <div class="header-item-text no-link" style="display:flex;align-items: start;gap: 4px">
        <img src="modules/mod_emundus_version/assets/tchooz_favicon.png" style="width: 14px" alt="Tchooz!" />
        <span class="visually-hidden"><?php echo Text::sprintf('MOD_EMUNDUS_VERSION_CURRENT_VERSION_TEXT', $version); ?></span>
        <div>
            <span aria-hidden="true"><?php echo $version; ?></span><br/>
            <span aria-hidden="true"><?php echo Text::sprintf('MOD_EMUNDUS_VERSION_ON_GIT_BRANCH', $gitbranch); ?></span><br/>
            <span aria-hidden="true"><?php echo Text::sprintf('MOD_EMUNDUS_VERSION_LAST_UPDATED_AT', $last_update); ?></span>
        </div>
    </div>
</div>
