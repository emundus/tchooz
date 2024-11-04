<?php
?>
<div class="mod_emundus_attachments__container">
	<?php use Joomla\CMS\Language\Text;

	if(!empty($title)) : ?>
		<h2><?php echo $title; ?></h2>
	<?php endif; ?>
    <?php if (!empty($attachments)) : ?>
	<div class="flex flex-wrap" style="gap: 16px">
		<?php foreach ($attachments as $attachment) : ?>
			<div class="mod_emundus_attachments__attachment_block">
				<h3><?php echo $attachment->label; ?></h3>
				<div class="mod_emundus_attachments__attachment_fileinfo">
					<div class="mod_emundus_attachments__filesize">
						<span><?php echo Text::_('MOD_EMUNDUS_ATTACHMENTS_FILESIZE'); ?></span>
						<?php echo $attachment->size; ?>
					</div>
					<div class="mod_emundus_attachments__created">
						<span><?php echo Text::_('MOD_EMUNDUS_ATTACHMENTS_CREATED'); ?></span>
						<?php echo date(Text::_('DATE_FORMAT_LC4'),strtotime($attachment->created)); ?>
					</div>
				</div>
				<div>
					<a class="mod_emundus_attachments__download_link" target="_blank" href="<?php echo $attachment->link; ?>">
						<?php echo Text::_('MOD_EMUNDUS_ATTACHMENTS_DOWNLOAD'); ?>
						<i class="zmdi zmdi-cloud-download dropfiles-download"></i></a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
    <?php else : ?>
        <p class="ml-3"><?php echo Text::_('MOD_EMUNDUS_ATTACHMENTS_NO_ATTACHMENTS'); ?></p>
    <?php endif; ?>
</div>
