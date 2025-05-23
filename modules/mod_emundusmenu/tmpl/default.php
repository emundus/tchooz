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
?>
<ul class="gf-menu l1 emundus" id="em_user_menu">
	<?php
	foreach ($list as $i => &$item) :
        if($item->getParams()->get('menu_show') != 0) :
		$item->anchor_css = "item";
		$class            = 'item-' . $item->id;
		if ($item->id == $active_id) {
			$class .= ' current';
		}

		if (in_array($item->id, $path)) {
			$class .= ' active';
		}
        elseif ($item->type == 'alias') {
			$aliasToId = $item->getParams()->get('aliasoptions');
			if (count($path) > 0 && $aliasToId == $path[count($path) - 1]) {
				$class .= ' active';
			}
            elseif (in_array($aliasToId, $path)) {
				$class .= ' alias-parent-active';
			}
		}

		if ($item->deeper) {
			$class .= ' deeper';
		}

		if ($item->parent) {
			$class .= ' parent';
		}

		if (!empty($class)) {
			$class = ' class="' . trim($class) . '"';
		}

		echo '<li' . $class . '>';

		// Render the menu item.
		switch ($item->type) :
			case 'separator':
			case 'url':
			case 'component':
				require JModuleHelper::getLayoutPath('mod_emundusmenu', 'default_' . $item->type);
				break;

			default:
				require JModuleHelper::getLayoutPath('mod_emundusmenu', 'default_url');
				break;
		endswitch;

		// The next item is deeper.
		if ($item->deeper) {
			echo '<div class="dropdown ';
			if (($item->level + 1) == 3) {
				echo 'flyout';
			}
			echo '"><div class="column"><ul class="level' . ($item->level + 1) . '">';
		}
		// The next item is shallower.
        elseif ($item->shallower) {
			echo '</li>';
			echo str_repeat('</ul></div></div></li>', $item->level_diff);
		}
		// The next item is on the same level.
		else {
			echo '</li>';
		}
        endif;
	endforeach;
	?></ul>
