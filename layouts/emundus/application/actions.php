<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Services\ApplicationFile\ApplicationFileRegistry;

defined('_JEXEC') or die;

$fnum = $this->getOptions()->get('fnum', '');

if (empty($fnum))
{
	return;
}

$app = Factory::getApplication();
$lang = $app->getLanguage();
$lang->setDefault('fr-FR');
$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus', 'fr-FR');
$registry = new ApplicationFileRegistry();
$applicationRepository = new ApplicationFileRepository();
$application = $applicationRepository->getByFnum($fnum);
$actions = $registry->getAvailableActions($application);

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
	<div class="tw-relative">
                    <span class="emundus-application-file-actions material-symbols-outlined tw-cursor-pointer !tw-flex tw-justify-self-center">
                        more_vert
                    </span>
		<!-- todo: move to js script to create div somewhere else on the dom to make sure it is above everyone -->
		<div class="emundus-application-file-actions-container tw-absolute tw-bg-white tw-shadow-md tw-rounded-coordinator tw-p-2 tw-hidden tw-transition-all tw-right-0 tw-flex tw-flex-col tw-z-10">
			<?php
			foreach ($actions as $action)
			{
				?>
				<div id="<?= $action->getActionType()->value ?>" data-fnum="<?= $fnum; ?>" class="file-action tw-flex tw-flex-row tw-items-center tw-justify-start tw-cursor-pointer tw-gap-2 tw-p-2 tw-rounded tw-transition-all hover:tw-bg-neutral-200">
                                <span class="material-symbols-outlined <?= $action->getActionType() === ApplicationFileActionsEnum::DELETE ? 'tw-text-red-500' : '' ?>">
                                    <?= $action->getActionType()->getIcon() ?>
                                </span>
					<p class="tw-whitespace-nowrap <?= $action->getActionType() === ApplicationFileActionsEnum::DELETE ? 'tw-text-red-500' : '' ?>"><?= $action->getActionType()->getLabel() ?></p>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}