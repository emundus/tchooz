<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$d = $displayData;

$dayAttribs   = $d->attribs . ' aria-label="' . htmlspecialchars(Text::_('PLG_ELEMENT_BIRTHDAY_DAY'), ENT_QUOTES) . '"';
$monthAttribs = $d->attribs . ' aria-label="' . htmlspecialchars(Text::_('PLG_ELEMENT_BIRTHDAY_MONTH'), ENT_QUOTES) . '"';
$yearAttribs  = $d->attribs . ' aria-label="' . htmlspecialchars(Text::_('PLG_ELEMENT_BIRTHDAY_YEAR'), ENT_QUOTES) . '"';
?>

<div class="fabrikSubElementContainer" id="<?php echo $d->id;?>" role="group" aria-label="<?php echo htmlspecialchars(Text::_('PLG_ELEMENT_BIRTHDAY_GROUP'), ENT_QUOTES); ?>">
	<table role="presentation">
		<tbody>
			<tr>
				<td>
					<?php echo HTMLHelper::_('select.genericlist', $d->day_options, $d->day_name, $dayAttribs, 'value', 'text', $d->day_value, $d->day_id);?>
				</td>
				<td> <?php echo "&nbsp;".$d->separator."&nbsp;";?></td>
				<td>
					<?php echo HTMLHelper::_('select.genericlist', $d->month_options, $d->month_name, $monthAttribs, 'value', 'text', $d->month_value, $d->month_id); ?>
				</td>
				<td> <?php echo "&nbsp;".$d->separator."&nbsp;";?></td>
				<td>
			<?php echo HTMLHelper::_('select.genericlist', $d->year_options, $d->year_name, $yearAttribs, 'value', 'text', $d->year_value, $d->year_id); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
