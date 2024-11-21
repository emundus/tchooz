<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>
<div class="toggle-editor btn-toolbar float-end clearfix mt-3">
    <div class="btn-group">
        <button type="button" disabled class="tw-btn-secondary js-tiny-toggler-button em-flex-gap-8 em-flex-row tw-mb-4" style="z-index: 9999;">
            <span class="material-symbols-outlined">code</span>
            <?php echo Text::_('PLG_TINY_BUTTON_TOGGLE_EDITOR'); ?>
        </button>
    </div>
</div>
