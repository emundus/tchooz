<?php
$d = $displayData;
?>

<div id="<?php echo $d->id; ?>" class="fabrikinput fabrikElementReadOnly" style="background-color: <?php echo $d->backgroundColor ?>;border: solid 1px <?php echo $d->borderColor; ?>;<?php if ($d->type == 4): ?>padding: 0;<?php endif; ?>" name="<?php echo $d->name; ?>">
    <span class="material-icons<?php echo $d->iconType ?>" style="color: <?php echo $d->iconColor ?>"><?php echo $d->icon ?></span>

    <div class="fabrikElementContent tw-w-full" <?php if ($d->type == 4): ?>style="margin-left: 0;"<?php endif; ?>>
	    <?php if ($d->accordion == 1) : ?>
            <div class="tw-flex tw-items-center tw-justify-between tw-cursor-pointer"
                 href="#<?php echo $d->id; ?>-content" onclick="toggleCollapse(this)" data-te-collapse-init data-toggle="collapse" aria-expanded="false" aria-controls="<?php echo $d->id; ?>-content" id="<?php echo $d->id; ?>-heading">
                <h3>
                    <?php echo $d->title ?>
                </h3>
                <span class="material-symbols-outlined tw-transition-transform tw-duration-300" id="<?php echo $d->id; ?>-icon">expand_more</span>
            </div>
	    <?php endif; ?>

        <div <?php if ($d->accordion == 1) : ?>class="show collapse"<?php endif ?>
             id="<?php echo $d->id; ?>-content"
             data-te-collapse-item>
		    <?php if (!empty($d->title) && $d->accordion == 0) : ?>
                <h3><?php echo $d->title ?></h3>
		    <?php endif; ?>
            <div class="<?php if (!empty($d->title)) : ?>tw-mt-2<?php else : ?>!tw-mt-0<?php endif; ?>">
                <div class="tw-whitespace-pre-wrap" id="<?php echo $d->id; ?>-value" style="color: <?php echo $d->textColor; ?>"><?php echo $d->value;?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($d->accordion == 1) : ?>
<script>
    jQuery('#<?php echo $d->id; ?>-heading').on('click', function () {
        let icon = jQuery('#<?php echo $d->id; ?>-icon');
        if(icon.css('transform') == 'none')
            icon.css('transform', 'rotate(180deg)');
        else
            icon.css('transform', '');
    });

    function toggleCollapse(event) {
        let target = event.getAttribute('href');
        if(target) {
            let content = document.querySelector(target);

            if(content) {
                content.classList.toggle('show');
            }
        }
    }
</script>
<?php endif; ?>
