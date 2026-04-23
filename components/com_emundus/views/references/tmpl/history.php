<?php
/**
 * @package
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Reference\InternalReferenceEntity;

?>

<div class="tw-p-6">
    <h2><?php echo Text::_('COM_EMUNDUS_REFERENCES_HISTORY'); ?></h2>
    <p><?php echo Text::_('COM_EMUNDUS_REFERENCES_HISTORY_INTRO'); ?></p>
    <?php if(!empty($this->references)): ?>
    <div
        class="tw-relative tw-mb-6 tw-mt-3 tw-max-h-dvh tw-overflow-scroll tw-rounded-coordinator tw-border tw-border-neutral-300"
    >
        <!-- header -->
        <div
            class="tw-grid tw-bg-neutral-100 tw-p-3"
            style="grid-template-columns: repeat(2, minmax(0, 1fr))"
        >
            <label class="!tw-mb-0 tw-font-medium"><?php echo Text::_('COM_EMUNDUS_REFERENCE'); ?></label>
            <label class="!tw-mb-0 tw-font-medium"><?php echo Text::_('COM_EMUNDUS_REFERENCE_CREATED_AT'); ?></label>
        </div>

        <div>
            <?php foreach ($this->references as $reference): ?>
                <?php
                     assert($reference instanceof InternalReferenceEntity);
                ?>
            <div
                class="tw-grid tw-p-3 hover:tw-bg-neutral-200"
                style="grid-template-columns: repeat(2, minmax(0, 1fr))"
            >
               <label class="tw-flex tw-items-center tw-gap-2">
                   <?php echo $reference->getReference(); ?>
                   <div>
                       <?php echo $reference->getActiveHtml(); ?>
                   </div>
               </label>
               <label><?php echo $reference->getCreatedAt()->format('d/m/Y H:i'); ?></label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else : ?>
        <div class="tw-mt-4 tw-text-center">
            <h3><?php echo Text::_('COM_EMUNDUS_REFERENCES_HISTORY_NO_REFERENCE'); ?></h3>
        </div>
    <?php endif; ?>
</div>
