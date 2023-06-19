<?php
/**
 * List tabs layout
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

$d = $displayData;
$i = 0;
?>

<ul class="nav nav-tabs fabrik-list-tabs" role="tablist">
	<?php foreach ($d->tabs as $tab) :
		$style = array();
		$style[] = isset($tab->class) && $tab->class !== '' ? 'class="' . $tab->class . '"' : '';
		$style[] = isset($tab->css) && $tab->css !== '' ? 'style="' . $tab->css . '"': '';
		$href = isset($tab->href) ? $tab->href : $tab->id;

		?>
		<li role="presentation" data-role="fabrik_tab nav-item" <?php echo implode(' ', $style); ?>>
			<a class="nav-link" href="<?php echo $href; ?>"
					id="<?php echo $tab->id; ?>">
					<?php echo Text::_($tab->label); ?>
			</a>
		</li>
		<?php
		$i++;
	endforeach;
	?>
</ul>

