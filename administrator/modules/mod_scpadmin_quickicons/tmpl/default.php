<?php
/**
 * @Scpadmin_quickicions module
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

/** @var array<string,mixed> $buttons */

$html = HTMLHelper::_('icons.buttons', $buttons);
?>    
    <link href="<?php echo Uri::root(); ?>media/com_securitycheckpro/css/cpanelui.css" rel="stylesheet" type="text/css">
    
    <?php if (!empty($html)) : ?>
    <div class="card-body">
        <nav class="quick-icons px-3 pb-3" aria-label="Securitycheck Pro Info Module" tabindex="-1">
            <ul class="nav flex-wrap">
        <?php echo $html; ?>
            </ul>    
        </nav>
    </div>
    <?php endif; ?>