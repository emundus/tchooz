<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Plugin\System\EmundusPublicAccess\Extension\EmundusPublicAccess;

$fnum = EmundusPublicAccess::getPublicAccessFnum();

?>


<h1><?= Text::_('MO_EMUNDUS_APPLICATIONS_PUBLIC_ACCESS_LAYOUT_TITLE') ?></h1>

<div class="tw-bg-white tw-shadow tw-rounded-coordinator tw-p-6 tw-mt-4">
    <p>
        <?= Text::_('MO_EMUNDUS_APPLICATIONS_PUBLIC_ACCESS_LAYOUT_INTRO') ?>
    </p>

    <div class="tw-w-full tw-flex tw-justify-end tw-mt-4 tw-gap-2">
        <a class="tw-btn-primary tw-w-fit" href="/index.php?option=com_emundus&task=openfile&fnum=<?= $fnum ?>">
            <?= Text::_('MO_EMUNDUS_APPLICATIONS_PUBLIC_ACCESS_OPEN_FILE') ?>
        </a>
        <a class="tw-btn-secondary tw-w-fit" href="/index.php?option=com_users&task=user.logout&<?= Session::getFormToken(); ?>=1">
            <?= Text::_('MO_EMUNDUS_APPLICATIONS_PUBLIC_ACCESS_CLOSE_SESSION') ?>
        </a>
    </div>
</div>
