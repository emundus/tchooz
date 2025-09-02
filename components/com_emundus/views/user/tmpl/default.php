<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

$email        = $this->user;
$current_user = Factory::getApplication()->getIdentity();

$title = 'COM_EMUNDUS_MAIL_SEND';
$body = 'COM_EMUNDUS_ACCESS_PLATFORM';
if($current_user->activation == -2) {
	$title = 'COM_EMUNDUS_MAIL_RESEND';
    $body = 'COM_EMUNDUS_ACCESS_PLATFORM_REENABLE';
}
?>
<div class="em-activation-header">
    <p><a class="em-back-button em-pointer em-w-auto em-float-left" style="text-decoration: unset"
          href="<?php echo Uri::base() ?>index.php?option=com_users&task=user.logout&<?php echo Session::getFormToken() ?>=1"><span
                    class="material-symbols-outlined em-mr-4 tw-text-neutral-600">navigate_before</span><?= Text::_('COM_EMUNDUS_MAIL_GB_BUTTON'); ?>
        </a></p>
</div>

<section class="em-activation">
    <section class="info">
        <div class="infoContainer">
            <div class="em-flex-column">
                <div class="tw-bg-profile-light em-flex-column tw-rounded-full tw-p-3">
                    <div class="tw-bg-profile-medium em-flex-column tw-rounded-full tw-p-3">
                        <span class="material-symbols-outlined em-font-size-32 tw-text-profile-full">mail</span>
                    </div>
                </div>
            </div>
            <h3 class="em-mb-32 em-mt-24"><?= Text::_($title) ?></h3>
            <p class="instructions"><?= Text::sprintf($body, $this->user_email); ?></p>
            <div class="resend em-mt-48">
                <p><?= Text::_('COM_EMUNDUS_MAIL_NOT_RECEIVE_DESC'); ?>
                    <!--<span onclick="activation(<?= $this->user->id; ?>)" class="em-pointer"><?= Text::_('COM_EMUNDUS_MAIL_NOT_RECEIVE_DESC_2'); ?></span>-->
                </p>
                <div class="containerButtons">
                    <input id="email" type="text" name="email" value="<?= $this->user_email ?>" class="mail">
                    <input type="button" onclick="activation()" class="btn btn-primary btn-resend"
                           value="<?= Text::_('COM_EMUNDUS_MAIL_SEND_NEW'); ?>">
                </div>
            </div>
        </div>
    </section>
</section>

<div class="em-page-loader"></div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        const loaders = document.getElementsByClassName('em-page-loader');
        for (loader of loaders) {
            loader.style.display = 'none';
        }
        document.getElementById('g-page-surround').style.background = 'white';
        document.getElementById('g-footer').style.display = 'none';
        document.getElementById('header-c').style.display = 'none';
    });

    function activation() {
        document.getElementsByClassName('em-page-loader')[0].style.display = 'block';
        return new Promise(function (resolve, reject) {
            let formData = new FormData();
            formData.append('email', document.getElementById('email').value);
            fetch(window.location.origin + '/index.php?option=com_emundus&controller=users&task=activation', {
                body: formData,
                method: 'post'
            }).then((response) => {
                if (response.ok) {
                    return response.json();
                } else {
                    Swal.fire({
                        title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ERROR_MESSAGE'),
                        type: 'error',
                        showConfirmButton: false,
                        showCancelButton: false,
                        timer: 1500,
                        customClass: {
                            title: 'em-swal-title',
                        },
                    });

                    reject(response);
                }
            }).then((res) => {
                document.getElementsByClassName('em-page-loader')[0].style.display = 'none';

                if (res.status) {
                    Swal.fire({
                        title: Joomla.JText._('COM_EMUNDUS_MAIL_SENDED'),
                        text: res.msg,
                        type: "success",
                        showConfirmButton: false,
                        showCancelButton: false,
                        timer: 1500,
                        customClass: {
                            title: 'em-swal-title',
                        },
                    });
                } else {
                    Swal.fire({
                        title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ERROR_MESSAGE'),
                        text: res.msg,
                        type: "error",
                        showConfirmButton: false,
                        showCancelButton: false,
                        timer: 1500,
                        customClass: {
                            title: 'em-swal-title',
                        },
                    });
                    reject(res.msg);
                }
            });
        });
    }
</script>

