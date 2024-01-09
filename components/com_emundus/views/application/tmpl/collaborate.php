<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */
use Joomla\CMS\Language\Text;

Text::script('COM_EMUNDUS_APPLICATION_SHARE_EMAILS');
Text::script('COM_EMUNDUS_APPLICATION_SHARE_READ');
Text::script('COM_EMUNDUS_APPLICATION_SHARE_UPDATE');
Text::script('COM_EMUNDUS_APPLICATION_SHARE_VIEW_HISTORY');
Text::script('COM_EMUNDUS_APPLICATION_SHARE_VIEW_OTHERS');
Text::script('COM_EMUNDUS_APPLICATION_SHARE_VIEW_REQUESTS');

?>

<div>
    <div>
        <label for="collab_emails" class="tw-text-black"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_EMAILS') ?></label>
        <input type="text" name="collab_emails" id="collab_emails" class="tw-mt-2" />
    </div>

    <div class="tw-mt-6">
        <label  class="tw-text-black">Droits</label>
        <div class="tw-mt-2">
            <input type="checkbox" name="rights" id="read" value="read" checked />
            <label for="read"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_READ') ?></label>
        </div>

        <div>
            <input type="checkbox" name="rights" id="update" value="update" />
            <label for="update"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_UPDATE') ?></label>
        </div>

        <div>
            <input type="checkbox" name="rights" id="view_history" value="view_history" />
            <label for="view_history"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_VIEW_HISTORY') ?></label>
        </div>

        <div>
            <input type="checkbox" name="rights" id="view_others" value="view_others" />
            <label for="view_others"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_VIEW_OTHERS') ?></label>
        </div>
    </div>

    <div class="tw-mt-6">
        <h3><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_VIEW_REQUESTS') ?></h3>
        <div class="tw-mt-2 tw-flex tw-flex-col tw-gap-2">
            <?php foreach ($this->collaborators as $collaborator) : ?>
                <div class="tw-flex tw-items-center tw-justify-between tw-py-4 tw-px-6 tw-border tw-border-neutral-500 tw-rounded-md tw-shadow-sm" id="collaborator_block_<?php echo $collaborator->id ?>">
                    <div class="tw-flex tw-items-center" style="max-width: 50%">
                        <span class="material-icons-outlined"
                              style="font-size: 48px"
                              alt="<?php echo JText::_('PROFILE_ICON_ALT') ?>">account_circle</span>
                        <div class="tw-ml-3">
                            <span class="tw-text-sm tw-mb-3">Envoyé le <?php echo EmundusHelperDate::displayDate($collaborator->time_date,'DATE_FORMAT_LC2',0)?></span>
                            <p><?php echo !empty($collaborator->lastname) ? $collaborator->lastname . ' ' . $collaborator->firstname : $collaborator->email; ?></p>
                        </div>
                    </div>

                    <div>
                        <?php if($collaborator->uploaded == 1) : ?>
                            <span class="label label-green-2 tw-text-white">Acceptée</span>
                        <?php else: ?>
                            <span class="label label-beige">Envoyée</span>
                        <?php endif; ?>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <span class="material-icons-outlined tw-cursor-pointer">send</span>
                        <span class="material-icons-outlined tw-cursor-pointer tw-text-red-500" onclick="removeShared('<?php echo $collaborator->id ?>','<?php echo $collaborator->ccid ?>','<?php echo $collaborator->fnum ?>')">person_remove</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
