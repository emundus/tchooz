<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

?>

<?php if($this->format == 'raw') : ?>
<style>
    .container-404 {
        font-family: var(--em-profile-font), Inter, sans-serif;
        display: flex;
        justify-content: space-around;
        flex-direction: column;
        align-items: center;
        width: 100%;
        position: relative;
        z-index: 1;
    }

    @supports (-webkit-background-clip: text) {
        .container-404 .gradient-404 {
            background: linear-gradient(95deg, #353544 7.64%, #5B5A72 52.08%, #B0B0BF 98.42%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    }

    .container-404 h2 {
        text-align: center;
        font-weight: 700;
        color: var(--em-primary-color);
        font-size: 40px;
        margin-bottom: 0;
        line-height: 48px;
    }

    .container-404 p {
        text-align: center;
        font-size: 20px;
        font-weight: 600;
    }

    .container-404 p a {
        background-color: var(--em-profile-color);
        color: var(--neutral-0);
        border: 1px solid var(--em-profile-color);
        padding: var(--em-spacing-vertical) var(--em-spacing-horizontal);
        border-radius: var(--em-applicant-br);
        font-size: var(--em-applicant-font-size);
        font-weight: 400;
    }

    .container-404 p a:hover,
    .container-404 p a:active,
    .container-404 p a:focus {
        background-color: transparent;
        color: var(--em-profile-color);
        text-decoration: none;
        border: 1px solid var(--em-profile-color);
    }

    .container-404 img {
        width: 40vw;
    }
</style>
<?php endif; ?>

<div class="left-side-404 container-404">
    <h2 class="gradient-404"><?php echo Text::_('COM_EMUNDUS_ERROR_TITLE'); ?></h2>
    <p><?php echo Text::_($this->error_message); ?></p>

	<?php if ($this->error_code == 404) : ?>
        <img src="<?php echo Uri::root(); ?>media/com_emundus/images/tchoozy/complex-illustrations/page-not-found.svg"
             alt="Erreur 404"/>
	<?php elseif ($this->error_code == 403) : ?>
        <img src="<?php echo Uri::root(); ?>media/com_emundus/images/tchoozy/complex-illustrations/403-error.svg"
             alt="Erreur 403"/>
	<?php else : ?>
        <img src="<?php echo Uri::root(); ?>media/com_emundus/images/tchoozy/complex-illustrations/building.svg"
             alt="Erreur 403"/>
	<?php endif; ?>
    <?php if($this->format == 'html') : ?>
    <p><a href="/"><?php echo Text::_('COM_EMUNDUS_ERROR_404_BUTTON'); ?></a></p>
    <?php endif; ?>
</div>
