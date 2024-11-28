<?php
defined('_JEXEC') or die;

switch ($number) {
    case 1:
        $class = 'tw-grid tw-grid-cols-1';
        break;
    case 2:
        $class = 'tw-grid tw-grid-cols-2';
        break;
    case 3:
    default:
        $class = 'tw-grid tw-grid-cols-3';
        break;
}

?>

<?php if (!empty($events)) : ?>
    <div class="mod_emundus_events__list tw-gap-3 tw-px-6 tw-py-12 <?= $class ?>">
        <?php foreach ($events as $event) : ?>
            <div style="background-color: <?php echo $bg_color; ?>;border-color: <?php echo $border_color; ?>" class="mod_emundus_events__block">
                <!-- ICON -->
                <div class="mod_emundus_events__block_icon">
                    <!-- MONTH -->
                    <div style="background-color: <?php echo $text_color; ?>" class="mod_emundus_events__month">
                        <?php echo date('M', strtotime($event->start_date)); ?>
                    </div>
                    <!-- DAY -->
                    <div class="mod_emundus_events__day">
                        <?php echo date('d', strtotime($event->start_date)); ?>
                    </div>
                </div>
                <div style="text-align: center">
                    <!-- DATE -->
                    <p class="mod_emundus_events__date tw-flex tw-items-center tw-justify-center tw-gap-1">
		                <?php
		                $dateFormat = ($year == 2) ? 'd.m' : 'd.m.Y';
		                if ($event->start_date != $event->end_date && $end == 1) :
			                echo date($dateFormat, strtotime($event->start_date));
		                else:
			                echo date($dateFormat, strtotime($event->start_date));
		                endif;
		                ?>
		                <?php if ($event->start_date != $event->end_date && $end == 1) : ?>
                            <span class="material-symbols-outlined">arrow_right_alt</span>
                            <?php echo date($dateFormat, strtotime($event->end_date)); ?>
		                <?php endif; ?>
                    </p>

                    <!-- TITLE -->
	                <?php if(!empty($event->link)) : ?>
                        <a style="text-decoration-color: <?php echo $text_color; ?>;cursor: pointer" class="mod_emundus_events__title mod_emundus_events__link" href="<?php echo $event->link; ?>">
                            <label style="color: <?php echo $text_color; ?>;font-weight: bold;cursor: pointer"><?php echo $event->title; ?></label>
                        </a>
                    <?php else : ?>
                        <label style="color: <?php echo $text_color; ?>;cursor: unset;font-weight: bold" class="mod_emundus_events__title"><?php echo $event->title; ?></label>
                    <?php endif; ?>

                    <!-- DESCRIPTION -->
                    <p class="mod_emundus_events__description"><?php echo $event->description; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

