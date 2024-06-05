<?php

/**
 * @package         Joomla.Site
 * @subpackage      mod_articles_category
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

?>

<p>
    <a class="em-back-button tw-cursor-pointer" href="<?php echo Uri::base() ?>">
        <span class="material-icons tw-mr-1">navigate_before</span>
        <?php echo Text::_('MOD_EMUNDUS_BACK_BUTTON_LABEL') ?>
    </a>
</p>
