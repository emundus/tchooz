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

<button type="button" class="em-back-button tw-cursor-pointer"
	<?php if ($params->get('back_type') == 'previous') : ?>
        onclick="<?php echo $back_link; ?>"
	<?php else : ?>
        onclick="window.location.href='<?php echo $back_link; ?>'"
	<?php endif; ?>
>
    <span class="material-symbols-outlined tw-text-neutral-600 tw-mr-1" aria-hidden="true">navigate_before</span>
	<?php echo Text::_($params->get('button_text', 'MOD_EMUNDUS_BACK_BUTTON_LABEL')); ?>
</button>