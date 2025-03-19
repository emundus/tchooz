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

<button type="button" class="tw-text-link-regular tw-cursor-pointer tw-font-semibold tw-flex tw-items-center tw-group"
	<?php if ($params->get('back_type') == 'previous') : ?>
        onclick="<?php echo $back_link; ?>"
	<?php else : ?>
        onclick="window.location.href='<?php echo $back_link; ?>'"
	<?php endif; ?>
>
    <span class="material-symbols-outlined tw-mr-1 tw-text-link-regular" aria-hidden="true">navigate_before</span>
	<span class="group-hover:tw-underline"><?php echo Text::_($params->get('button_text', 'MOD_EMUNDUS_BACK_BUTTON_LABEL')); ?></span>
</button>