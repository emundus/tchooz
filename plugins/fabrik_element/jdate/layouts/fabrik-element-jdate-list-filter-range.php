<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

$d    = $displayData;
$from = $d->from;
$to   = $d->to;

if ($d->filterType === 'range-hidden') :
	?>
	<input type="hidden" name="<?php echo $from->name; ?>"
		class="<?php echo $d->class; ?>"
		value="<?php echo $from->value; ?>"
		id="<?php echo $d->htmlId; ?>-0" />

	<input type="hidden" name="<?php echo $to->name; ?>"
		class="<?php echo $d->class; ?>"
		value="<?php echo $to->value; ?>"
		id="<?php echo $d->htmlId; ?>-1" />
<?php
else :
    ?>
    <div class="fabrikDateListFilterRange tw-flex tw-flex-col tw-gap-2" >
        <div class="row">
            <div class="tw-w-1/4 tw-p-0 tw-flex tw-items-center">
                <label for="<?php echo $from->id; ?>"><?php echo Text::_('COM_FABRIK_DATE_RANGE_BETWEEN') . ' '; ?>
                </label></div>
            <div class="tw-w-3/4 tw-p-0"><?php echo $d->jCalFrom; ?></div>
        </div>
        <div class="row">
            <div class="tw-w-1/4 tw-p-0 tw-flex tw-items-center">
                <label for="<?php echo $to->id; ?>">	<?php echo Text::_('COM_FABRIK_DATE_RANGE_AND') . ' '; ?>
                </label></div>
            <div class="tw-w-3/4 tw-p-0"><?php echo $d->jCalTo; ?></div>
        </div>
    </div>
<?php
endif;