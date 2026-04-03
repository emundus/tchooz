<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Tchooz\Repositories\Campaigns\CampaignRepository;

defined('_JEXEC') or die;

$lang = Factory::getApplication()->getLanguage();
$lang->setDefault('fr-FR');
$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus', 'fr-FR');

$app = Factory::getApplication();
$session = $app->getSession();
$campaignId = (int) $session->get('cid', 0);
$showCreateApplicationFile = false;

if (!empty($campaignId))
{
    $campaignRepository = new CampaignRepository(false);
	$campaign = $campaignRepository->getById($campaignId);

    if (!empty($campaign) && $campaign->isPublic())
    {
	    $showCreateApplicationFile = true;
    }
}


?>

<div class="tw-mt-4 tw-w-full tw-flex tw-flex-col tw-gap-4 tw-text-center">
	<div class="tw-flex tw-flex-row tw-items-center tw-w-full tw-gap-2">
		<hr class="tw-w-full" />
		<span class="tw-w-fit tw-text-neutral-500"><?= strtoupper(Text::_('COM_EMUNDUS_OR')); ?></span>
		<hr class="tw-w-full" />
	</div>

    <h2><?= Text::_('COM_EMUNDUS_NOT_AUTHENTICATED'); ?></h2>

    <?php if ($showCreateApplicationFile): ?>
        <p><?= Text::_('COM_EMUNDUS_APPLY_TO_CAMPAIGN_PUBLICLY'); ?></p>

        <a href="<?= Route::_('/index.php?option=com_emundus&task=applyPubliclyToCampaign&cid=' . $campaignId); ?>"
           class="tw-btn-primary">
		    <?= Text::_('COM_EMUNDUS_APPLY_PUBLICALLY_BUTTON'); ?>
        </a>

        <div>
            <p><?= Text::_('COM_EMUNDUS_ALREADY_HAVE_PUBLIC_ACCESS_FILE'); ?></p>
            <a class="tw-underline" href="<?= Route::_('index.php?option=com_users&view=login', false); ?>">
			    <?= Text::_('COM_EMUNDUS_RETRIEVE_PUBLIC_ACCESS_FILE'); ?>
            </a>
        </div>
    <?php else: ?>
        <p><?= Text::_('COM_EMUNDUS_RETRIEVE_PUBLIC_FILE_FROM_ACCESS_KEY'); ?></p>

        <form method="post" action="<?php echo Route::_('index.php?option=com_emundus&task=authenticatepublicaccess'); ?>" id="publicAccessForm">
            <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>

            <!-- Access token -->
            <div class="tw-mb-6">
                <input
                    type="password"
                    id="pa_access_token"
                    name="access_token"
                    required
                    autocomplete="off"
                    spellcheck="false"
                    placeholder="<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_TOKEN_PLACEHOLDER'); ?>"
                />
            </div>

            <button
                type="submit"
                class="tw-w-full tw-btn-primary">
                <?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_SUBMIT'); ?>
            </button>
        </form>
    <?php endif; ?>
</div>