<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileActionRedirectToFile;
use Tchooz\Entities\ApplicationFile\Actions\CustomApplicationFileAction;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Services\ApplicationFile\ApplicationFileActionsRegistry;

defined('_JEXEC') or die;

$fnum = $this->getOptions()->get('fnum', '');
$context = $this->getOptions()->get('context', 'multiple');

if (empty($fnum))
{
	return;
}

$app = Factory::getApplication();
$lang = $app->getLanguage();
$lang->setDefault('fr-FR');
$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus', 'fr-FR');
$registry = new ApplicationFileActionsRegistry();
$applicationRepository = new ApplicationFileRepository();
$application = $applicationRepository->getByFnum($fnum);
$actions = $registry->getAvailableActions($application, $context);

if (!empty($actions))
{
	$document = $app->getDocument();
	$wa = $document->getWebAssetManager();
	$document->addScriptOptions('layout.emundus.actions', array_map(function ($action) {
		return $action->__serialize();
	}, $actions));
	$wa->registerAndUseScript('layout.emundus.fieldtohtmlfactory', 'layouts/emundus/script/FieldToHtmlFactory.js');
	$wa->registerAndUseScript('layout.emundus.application.actions', 'layouts/emundus/script/application/actions.js', [], ['defer' => true], ['layout.emundus.fieldtohtmlfactory']);
	Text::script('CANCEL');
	Text::script('CONFIRM');
	Text::script('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DELETE_CONFIRM');

	?>
	<div class="tw-relative emundus-application-file-actions-wrapper" data-fnum="<?= $fnum; ?>">
         <span class="emundus-application-file-actions material-symbols-outlined tw-cursor-pointer !tw-flex tw-justify-self-center" >
             more_vert
         </span>
		<div class="emundus-application-file-actions-container tw-fixed tw-bg-white tw-shadow-md tw-rounded-coordinator tw-p-2 tw-hidden tw-flex tw-flex-col tw-z-50" data-fnum="<?= $fnum; ?>">
			<?php
            foreach ($actions as $action)
			{
                if (!($action instanceof CustomApplicationFileAction)) {
				?>
                    <div id="<?= $action->getActionType()->value ?>-action" data-actionid="<?= $action->getActionType()->value ?>" tabindex=0 data-fnum="<?= $fnum; ?>" class="file-action tw-flex tw-flex-row tw-items-center tw-justify-start tw-cursor-pointer tw-gap-2 tw-p-2 tw-rounded tw-transition-all hover:tw-bg-neutral-200">
                        <span class="material-symbols-outlined <?= $action->getActionType() === ApplicationFileActionsEnum::DELETE ? 'tw-text-red-500' : '' ?>">
                            <?= $action->getActionType()->getIcon() ?>
                        </span>
                        <p class="tw-whitespace-nowrap <?= $action->getActionType() === ApplicationFileActionsEnum::DELETE ? 'tw-text-red-500' : '' ?>"><?= $action->getActionType()->getLabel() ?></p>
                    </div>
                <?php
                } else {
                ?>
                    <div id="<?= $action->getId() ?>-action" data-actionid="<?= $action->getId() ?>" tabindex=0 data-fnum="<?= $fnum; ?>" class="file-action tw-flex tw-flex-row tw-items-center tw-justify-start tw-cursor-pointer tw-gap-2 tw-p-2 tw-rounded tw-transition-all hover:tw-bg-neutral-200">
                    <span class="material-symbols-outlined">
                        <?= $action->getIcon() ?>
                    </span>
                        <p class="tw-whitespace-nowrap"><?= $action->getLabel() ?></p>
                    </div>
                <?php
                }
			}
			?>
		</div>
	</div>
	<?php
}
