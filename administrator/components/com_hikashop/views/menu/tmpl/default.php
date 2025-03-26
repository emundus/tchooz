<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
$doc = JFactory::getDocument();
$doc->addCustomTag('<div class="theme-overlay">
	<img class="overlay-spinner " src="http://localhost/J510/Joomla_5.1.0-Stable-Full_Package_fr/media/com_hikashop/images/spinner_03.svg">
</div>');

$doc->addStyleDeclaration("
.theme-overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: #000;
	opacity: 0.975;
	z-index: 9999;
	display: none;
}
img.overlay-spinner {
    display: block;
    margin-left: auto;
    margin-right: auto;
    position: relative;
    top: 250px;
}	
");

$doc->addScriptDeclaration("
document.addEventListener('DOMContentLoaded', function() {
	const themeButton = document.querySelector('button.dropdown-item[data-color-scheme-switch]');
	const overlay = document.querySelector('.theme-overlay');

	if (themeButton && overlay) {
		const observer = new MutationObserver(function(mutationsList, observer) {
			for (const mutation of mutationsList) {
				if (mutation.type === 'attributes' && mutation.attributeName === 'data-bs-theme') {
					overlay.style.display = 'block';
					setTimeout(function() {
						location.reload();
					}, 100);
				}
			}
		});

		observer.observe(themeButton, { attributes: true });

		themeButton.addEventListener('click', function() {
			const currentTheme = themeButton.getAttribute('data-bs-theme');
			if (currentTheme === 'light') {
				themeButton.setAttribute('data-bs-theme', 'dark');
				themeButton.setAttribute('data-color-scheme', 'dark');
			} else {
				themeButton.setAttribute('data-bs-theme', 'light');
				themeButton.setAttribute('data-color-scheme', 'light');
			}
		});
	}
});
");
?>

<nav class="hk-navbar hk-navbar-default">
	<div class="hk-container-fluid">
		<ul class="hk-nav hk-navbar-nav">
<?php
$config = hikashop_config();
foreach($this->menus as $menu) {
	$task = !empty($menu['task']) ? $menu['task'] : 'view';
	$icon = !empty($menu['icon']) ? '<i class="'.$menu['icon'].'"></i> ' : '';

	$dropdown = false;
	if(!empty($menu['children'])) {
		foreach($menu['children'] as &$child) {
			$childTask = !empty($child['task']) ? $child['task'] : 'view';
			if(!empty($child['acl']) && !hikashop_isAllowed($config->get('acl_'.$child['acl'].'_'.$childTask, 'all'))) {
				$child = false;
				continue;
			}
			if(!empty($child['url']))
				$dropdown = true;
			if(isset($child['active']) && $child['active']) {
				$menu['active'] = true;
			}
		}
		unset($child);
	}

	if(!empty($menu['acl']) && !hikashop_isAllowed($config->get('acl_'.$menu['acl'].'_'.$task, 'all')) && !$dropdown)
		continue;

	$classes = !empty($menu['active']) ? ' active' : '';
	if(!isset($menu['options'])) $menu['options'] = '';

	if(!$dropdown) {
?>
			<li class="<?php echo trim($classes); ?>"><a href="<?php echo $menu['url']; ?>" <?php echo $menu['options']; ?>><?php echo $icon . $menu['name']; ?></a></li>
<?php
		continue;
	}

?>
			<li class="hkdropdown<?php echo $classes; ?>">
				<a href="#" class="hkdropdown-toggle" data-toggle="hkdropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo $icon . $menu['name']; ?> <span class="caret"></span></a>
				<ul class="hkdropdown-menu">
<?php
	foreach($menu['children'] as $k => $child) {
		if(empty($child))
			continue;
		$childTask = !empty($child['task']) ? $child['task'] : 'view';
		$childIcon = !empty($child['icon']) ? '<i class="'.$child['icon'].'"></i> ' : '';
		if(!isset($child['options'])) $child['options'] = '';
		$classes = !empty($child['active']) ? ' active' : '';

		if(!empty($child['url'])) {
			echo '<li><a class="'.trim($classes).'" href="'.$child['url'].'" '.$child['options'].'>' . $childIcon . $child['name'] . '</a></li>';
		} elseif(!empty($menu['children'][$k-1]) && !empty($menu['children'][$k-1]['url'])) {
			echo '<li role="separator" class="divider" '.$child['options'].'></li>';
		}
	}
?>
				</ul>
			</li>
<?php
}
?>
		</ul>
	</div>
</nav>
