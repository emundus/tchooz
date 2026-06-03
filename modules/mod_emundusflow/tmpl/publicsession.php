<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository;

defined('_JEXEC') or die('Restricted access');

assert($applicationFile instanceof ApplicationFileEntity);
$document = Factory::getApplication()->getDocument();
$wa       = $document->getWebAssetManager();
$wa->registerAndUseScript('mod_emundusflow.publicsession', 'modules/mod_emundusflow/script/publicsession.js');

$applicationFileAccessRepository = new ApplicationFileAccessRepository();
$access = $applicationFileAccessRepository->getByApplicationFileId($applicationFile->getId());
$formattedDate = $access->getExpirationDate()->format('d/m/Y');
$now = new DateTimeImmutable();
$interval = $now->diff($access->getExpirationDate());

$color = 'blue';
if ($interval->format('%R%a') < 8)
{
    $color = 'red';
} else if ($interval->format('%R%a') < 16)
{
    $color = 'orange';
}
?>

<div class="tw-mb-6 tw-p-4 tw-bg-<?= $color ?>-50 tw-border tw-border-<?= $color ?>-300 tw-rounded-coordinator">
    <div class="tw-flex tw-items-start tw-gap-3">
        <span class="material-symbols-outlined tw-text-<?= $color ?>-600 tw-text-xl tw-mt-0.5">info</span>
        <p>
            <?= sprintf(Text::_('COM_EMUNDUS_FILE_ACCESS_TOKEN_EXPIRES_AT'), $formattedDate); ?>
        </p>
    </div>

    <div class="tw-mt-4 tw-flex tw-justify-end">
        <button id="renew-public-access-token" class="tw-btn-secondary">
            <span class="material-symbols-outlined tw-mr-1">key_vertical</span>
            <span><?= Text::_('COM_EMUNDUS_FILE_ACCESS_TOKEN_RENEW') ?></span>
        </button>
    </div>
</div>

