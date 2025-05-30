<?php
/**
 * @package        Joomla.Site
 * @subpackage     mod_menu
 * @copyright      Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Note. It is important to remove spaces between elements.
$title    = $item->anchor_title ? 'title="' . $item->anchor_title . '" ' : '';
$icon_css = $item->getParams()->get('menu_image_css', '');
$class    = 'class="';

if ($item->anchor_css) {
	$class .= $item->anchor_css;
}
if (!empty($icon_css)) {
	$class .= ' em-flex-row';
}
$class .= '"';
if ($item->menu_image) {
	$item->getParams()->get('menu_text', 1) ?
		$linktype = '<img src="' . $item->menu_image . '" alt="' . $item->title . '" /><span class="image-title">' . $item->title . '</span> ' :
		$linktype = '<img src="' . $item->menu_image . '" alt="' . $item->title . '" />';
}
else {
	if (!empty($icon_css)) {
		$linktype = '<span class="material-symbols-outlined" style="font-size: 16px; color: black;margin-right: 4px">' . $icon_css . '</span><span>' . $item->title . '</span>';
	}
	else {
		$linktype = $item->title;
	}
}

switch ($item->browserNav) :
	default:
	case 0:
		?>
        <a <?php echo $class; ?>href="<?php echo $item->flink; ?>" <?php echo $title; ?>><?php echo $linktype; ?></a><?php
		break;
	case 1:
		// _blank
		?><a <?php echo $class; ?>href="<?php echo $item->flink; ?>"
        target="_blank" <?php echo $title; ?>><?php echo $linktype; ?></a><?php
		break;
	case 2:
		// window.open
		?><a <?php echo $class; ?>href="<?php echo $item->flink; ?>"
        onclick="window.open(this.href,'targetWindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');return false;" <?php echo $title; ?>><?php echo $linktype; ?></a>
		<?php
		break;
endswitch;
