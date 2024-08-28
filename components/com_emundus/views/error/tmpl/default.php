<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

?>

<div class="left-side-404 container-404" >
	<h2 class="gradient-404"><?php echo Text::_('COM_EMUNDUS_ERROR_TITLE'); ?></h2>
	<p><?php echo Text::_($this->error_message); ?></p>

	<?php if ($this->error_code == 404) : ?>
		<img src="<?php echo Uri::root(); ?>media/com_emundus/images/tchoozy/complex-illustrations/page-not-found.svg" alt="Erreur 404" />
	<?php elseif ($this->error_code == 403) : ?>
		<img src="<?php echo Uri::root(); ?>media/com_emundus/images/tchoozy/complex-illustrations/403-error.svg" alt="Erreur 403" />
	<?php else : ?>
		<img src="<?php echo Uri::root(); ?>media/com_emundus/images/tchoozy/complex-illustrations/building.svg" alt="Erreur 403" />
	<?php endif; ?>
	<p><a href="/"><?php echo Text::_('COM_EMUNDUS_ERROR_404_BUTTON'); ?></a></p>
</div>
