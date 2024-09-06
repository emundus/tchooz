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
    <?php if($this->_user->applicant == 1) : ?>
        <div id="collab_emails_block">
            <label for="collab_emails" class="tw-text-black"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_EMAILS') ?></label>
            <input type="text" name="collab_emails" id="collab_emails" class="tw-mt-2 <?php if (sizeof($this->collaborators) > 0) { echo 'tw-mb-6';} ?>" />
        </div>
    <?php endif; ?>

	<div id="collaborators_block">
		<?php if(sizeof($this->collaborators) > 0) : ?>
            <?php if($this->_user->applicant == 1) : ?>
                <div class="tw-flex tw-items-center tw-justify-between tw-cursor-pointer" onclick="toggleRequests()">
                    <h3><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_VIEW_REQUESTS') ?></h3>
                    <span class="material-icons" id="requests_icon">expand_less</span>
                </div>
            <?php endif; ?>
			<div class="tw-mt-2 tw-flex tw-flex-col tw-gap-2 <?php if($this->_user->applicant == 1) : ?>tw-hidden<?php endif; ?>" id="collaborators_requests">
				<?php foreach ($this->collaborators as $collaborator) : ?>
					<div class="tw-py-4 tw-px-6 tw-border tw-border-neutral-500 tw-rounded-md tw-shadow-sm" id="collaborator_block_<?php echo $collaborator->id ?>">
						<div class="tw-flex tw-items-center tw-justify-between">
							<div class="tw-flex tw-items-center" style="max-width: 50%">
								<?php if(empty($collaborator->profile_picture)) : ?>
									<span class="material-symbols-outlined"
									      style="font-size: 48px"
									      alt="<?php echo JText::_('PROFILE_ICON_ALT') ?>">account_circle</span>
								<?php else : ?>
									<div class="em-profile-picture tw-cursor-pointer em-user-dropdown-button tw-flex-none"
									     style="background-image:url('<?php echo $collaborator->profile_picture ?>');">
									</div>
								<?php endif; ?>
								<div class="tw-ml-3">
									<?php if($this->_user->applicant == 1) : ?>
									    <span class="tw-text-sm tw-mb-3">Envoyé le <?php echo EmundusHelperDate::displayDate($collaborator->time_date,'DATE_FORMAT_LC2',0)?></span>
                                    <?php endif; ?>
									<p><?php echo !empty($collaborator->user_id) ? $collaborator->user_lastname . ' ' . $collaborator->user_firstname : $collaborator->email; ?></p>
								</div>
							</div>

							<?php if($this->_user->applicant == 1) : ?>
                                <div class="tw-flex tw-items-center tw-gap-3">
                                    <div>
                                        <?php if($collaborator->uploaded == 1) : ?>
                                            <span class="label label-green-2 tw-text-white"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_ACCEPTED_STATUS') ?></span>
                                        <?php else: ?>
                                            <span class="label label-beige"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_SENT_STATUS') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tw-flex tw-items-center tw-justify-end tw-gap-3" style="min-width: 50px">
                                        <?php if ($collaborator->uploaded == 0) : ?>
                                            <span class="send-new-email-icon material-symbols-outlined tw-cursor-pointer" id="email_icon_<?php echo $collaborator->id ?>" onclick="sendNewEmail('<?php echo $collaborator->id ?>','<?php echo $collaborator->ccid ?>','<?php echo $collaborator->fnum ?>')">send</span>
                                        <?php endif; ?>
                                        <span class="remove-shared-icon material-symbols-outlined tw-cursor-pointer tw-text-red-500" onclick="removeShared('<?php echo $collaborator->id ?>','<?php echo $collaborator->ccid ?>','<?php echo $collaborator->fnum ?>')">person_remove</span>
                                    </div>
                                </div>
                            <?php endif; ?>
						</div>

						<hr/>

						<div class="tw-flex <?php if($this->_user->applicant == 1) : ?>tw-items-center tw-justify-between tw-flex-wrap <?php else : ?>tw-flex-col<?php endif;?>">
							<div class="tw-flex tw-items-center tw-gap-2">
								<input class="!tw-mt-0" type="checkbox" name="rights_"<?= $collaborator->id; ?>" id="read_<?php echo $collaborator->id; ?>" value="r" onchange="updateRight('<?php echo $collaborator->id ?>','<?php echo $collaborator->ccid ?>','<?php echo $collaborator->fnum ?>',this.value, this.checked)" <?php if($collaborator->r == 1) : ?>checked<?php endif; ?> />
								<label class="!tw-mb-0" for="read_<?php echo $collaborator->id; ?>"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_READ') ?></label>
							</div>

							<div class="tw-flex tw-items-center tw-gap-2">
								<input class="!tw-mt-0" type="checkbox" name="rights_"<?= $collaborator->id; ?>" id="update_<?php echo $collaborator->id; ?>" value="u" onchange="updateRight('<?php echo $collaborator->id ?>','<?php echo $collaborator->ccid ?>','<?php echo $collaborator->fnum ?>',this.value, this.checked)" <?php if($collaborator->u == 1) : ?>checked<?php endif; ?> />
								<label class="!tw-mb-0" for="update_<?php echo $collaborator->id; ?>"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_UPDATE') ?></label>
							</div>

							<div class="tw-flex tw-items-center tw-gap-2">
								<input class="!tw-mt-0" type="checkbox" name="rights_"<?= $collaborator->id; ?>" id="view_history_<?php echo $collaborator->id; ?>" value="show_history" onchange="updateRight('<?php echo $collaborator->id ?>','<?php echo $collaborator->ccid ?>','<?php echo $collaborator->fnum ?>',this.value, this.checked)" <?php if($collaborator->show_history == 1) : ?>checked<?php endif; ?> />
								<label class="!tw-mb-0" for="view_history_<?php echo $collaborator->id; ?>"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_VIEW_HISTORY') ?></label>
							</div>

							<div class="tw-flex tw-items-center tw-gap-2">
								<input class="!tw-mt-0" type="checkbox" name="rights_"<?= $collaborator->id; ?>" id="view_others_<?php echo $collaborator->id; ?>" value="show_shared_users" onchange="updateRight('<?php echo $collaborator->id ?>','<?php echo $collaborator->ccid ?>','<?php echo $collaborator->fnum ?>',this.value, this.checked)" <?php if($collaborator->show_shared_users == 1) : ?>checked<?php endif; ?> />
								<label class="!tw-mb-0" for="view_others_<?php echo $collaborator->id; ?>"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SHARE_VIEW_OTHERS') ?></label>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>