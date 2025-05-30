<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

$index_form = 1;
$index_doc  = 1;

if ($itemid['id'] == $menuid && $show_mandatory_documents == 1) {
	$index_form = sizeof($forms) + 1;
}

foreach ($forms as $index => $form) {
	if ($form->id == $menuid) {
		$index_form = $index + 1;
		break;
	}
}

$pages_no = 0;
if ($show_forms == 1 && count($forms) > 0) {
	$pages_no = count($forms);
}

if ($show_mandatory_documents == 1 && count($mandatory_documents) > 0) {
	$pages_no++;
}
if ($show_optional_documents == 1 && count($optional_documents) > 0) {
	$pages_no++;
}
if (!empty($checkout_url)) {
	$pages_no++;
}

if ($show_preliminary_documents && !empty($preliminary_documents)): ?>
    <div class="mod_emundus_checklist em-mb-24 ">
        <div class="em-flex-row em-flex-space-between em-pointer mod_emundus_checklist_expand">
            <div class="em-flex-row">
                <h3><?php echo Text::_($preliminary_documents_title) ?></h3>
            </div>
            <span id="mod_emundus_checklist___expand_icon" class="material-symbols-outlined"
                  style="transform: rotate(-90deg);">expand_more</span>
        </div>
        <div id="mod_emundus_checklist___content" class="em-mt-24">
			<?php foreach ($preliminary_documents as $document): ?>
                <div class="em-flex-row em-mb-16 mod_emundus_campaign__details_file">
                    <span class="material-symbols-outlined mod_emundus_campaign__details_file_icon">insert_drive_file</span>
                    <a href="<?php echo $document->href ?>" target="_blank" rel="noopener noreferrer">
						<?php echo $document->title_file . "." . $document->ext; ?>
                    </a>
                </div>
			<?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<div class="mod_emundus_checklist tw-border tw-border-neutral-300">
    <div class="em-flex-row em-flex-space-between em-pointer mod_emundus_checklist_expand">
        <div class="em-flex-row">
            <h3> <?php echo Text::_($forms_title) . ' ' . $index_form . '/' . $pages_no ?></h3>
        </div>
        <span id="mod_emundus_checklist___expand_icon" class="material-symbols-outlined">expand_more</span>
    </div>

    <div id="mod_emundus_checklist___content" class="em-mt-24 tw-pl-1">
		<?php if ($show_forms == 1 && count($forms) > 0) : ?>
			<?php
			$index_doc     = !empty($mandatory_documents) && $show_mandatory_documents ? count($forms) + 1 : count($forms);
			$index_opt_doc = !empty($optional_documents) && $show_optional_documents ? $index_doc + 1 : $index_doc;
			$index_payment = !empty($checkout_url) ? $index_opt_doc + 1 : $index_opt_doc;
			?>
            <div class="tw-pt-1">
				<?php foreach ($forms as $index => $form) : ?>
					<?php
					$class      = $form->rowid == 0 ? 'need_missing' : 'need_ok';
					$step       = $index + 1;
					?>
                    <div id="mlf<?php echo $form->id; ?>"
                         class="<?php if ($form->id == $menuid) echo 'active' ?> mod_emundus_checklist_<?php echo $class; ?> mod_emundus_checklist___form_item tw-relative">
	                    <?php if ($class == 'need_ok' && $form->id != $menuid) : ?>
                            <span class="material-symbols-outlined mod_emundus_checklist___check_circle">check_circle</span>
	                    <?php endif; ?>
                        <div class="mod_emundus_checklist___grid tw-group">
                            <div class="mod_emundus_checklist___step_count group-hover:!tw-bg-blue-100 group-hover:!tw-border-blue-100">
								<?php if ($form->id == $menuid) {
									$color = 'var(--blue-900)';
									$border_color = 'var(--blue-200)';
                                    $title_color = 'var(--blue-900)';
								}
                                elseif ($class == 'need_missing') {
									$color = 'var(--neutral-900)';
	                                $border_color = 'var(--neutral-300)';
	                                $title_color = 'var(--neutral-900)';
								}
                                elseif ($class == 'need_ok') {
									$color = 'var(--neutral-0)';
	                                $border_color = 'var(--main-500)';
	                                $title_color = 'var(--main-500)';
								}
								?>
                                <span class="group-hover:!tw-text-blue-900" style="color: <?= $color ?>">
                                    <?php echo $index + 1 ?>
                                </span>
                            </div>
                            <a href="<?php echo $form->link ?>" class="group-hover:!tw-text-blue-900" style="color: <?php echo $title_color; ?>;" <?php if ($form->id == $menuid) : ?>class="tw-font-medium"<?php endif; ?>>
                                <?php echo Text::_($form->label); ?>
                            </a>
                        </div>
						<?php if ($index != (sizeof($forms) - 1) || ($show_mandatory_documents == 1 && !empty($mandatory_documents)) || ($show_optional_documents == 1 && !empty($optional_documents)) || !empty($checkout_url)) : ?>
                            <div class="mod_emundus_checklist___border_item" style="border-color: <?php echo $border_color; ?>"></div>
						<?php endif ?>
                    </div>
				<?php endforeach; ?>
            </div>
		<?php endif; ?>

		<?php if ($show_mandatory_documents == 1 && count($mandatory_documents) > 0) : ?>
			<?php
			if ($attachments_progress < 100) {
				$attachment_class = 'need_missing';
			}
			else {
				$attachment_class = 'need_ok';
			}
			?>
            <div class="<?php if ($itemid['id'] == $menuid) echo 'active' ?> mod_emundus_checklist_<?php echo $attachment_class; ?> mod_emundus_checklist___form_item tw-relative">
	            <?php if ($attachment_class == 'need_ok' && $itemid['id'] != $menuid) : ?>
                    <span class="material-symbols-outlined mod_emundus_checklist___check_circle">check_circle</span>
	            <?php endif; ?>
                <div class="mod_emundus_checklist___grid tw-group">
                    <div class="mod_emundus_checklist___step_count group-hover:!tw-bg-blue-100 group-hover:!tw-border-blue-100">
						<?php if ($itemid['id'] == $menuid) {
							$color = 'var(--blue-900)';
							$border_color = 'var(--blue-200)';
							$title_color = 'var(--blue-900)';
						}
                        elseif ($attachment_class == 'need_missing') {
							$color = 'var(--neutral-900)';
	                        $border_color = 'var(--neutral-300)';
	                        $title_color = 'var(--neutral-900)';
						}
                        elseif ($attachment_class == 'need_ok') {
							$color = 'var(--neutral-0)';
	                        $border_color = 'var(--main-500)';
	                        $title_color = 'var(--main-500)';
						}
						?>
                        <span class="group-hover:!tw-text-blue-900" style="color: <?= $color ?>">
                            <?php echo sizeof($forms) + 1 ?>
                        </span>
                    </div>
                    <a href="<?php echo $itemid['link'] . '&Itemid=' . $itemid['id'] ?>" class="group-hover:!tw-text-blue-900" style="color: <?php echo $title_color; ?>;" <?php if ($itemid['id'] == $menuid) : ?>class="tw-font-medium"<?php endif; ?>>
                        <?php echo Text::_($mandatory_documents_title) ?>
                    </a>
                </div>
                <div class="em-flex-row" style="align-items: stretch">
					<?php if (($show_optional_documents == 1 && !empty($optional_documents)) || !empty($checkout_url)) : ?>
                        <div class="mod_emundus_checklist___border_item em-h-auto" style="border-color: <?php echo $border_color; ?>"></div>
					<?php endif ?>
                    <div class="mod_emundus_checklist___attachment"
					     <?php if (($show_optional_documents == 1 && !empty($optional_documents)) || !empty($checkout_url)) : ?>style="margin-left: 24px"<?php endif; ?>
                    >
						<?php foreach ($uploads as $upload) : ?>
                            <div class="em-flex-row em-mb-8">
                                <span class="material-symbols-outlined"
                                      style="color:var(--main-500);font-size: 16px;">check_circle</span>
                                <a class="em-font-size-12 em-ml-8 mod_emundus_checklist___attachment_links"
                                   href="<?php echo $itemid['link'] . '&Itemid=' . $itemid['id'] . '#a' . $upload->attachment_id ?>">
									<?php echo $upload->attachment_name ?>
									<?php if ($upload->filesize > 0) : ?>
                                        <span class="em-ml-4 em-text-neutral-600 em-font-size-12"><?php echo $upload->filesize ?></span>
									<?php endif; ?>
                                </a>
                            </div>
						<?php endforeach; ?>
                    </div>
                </div>
            </div>
		<?php endif; ?>

		<?php if ($show_optional_documents == 1 && count($optional_documents) > 0) : ?>
            <div class="<?php if ($itemid['id'] == $menuid) echo 'active' ?> mod_emundus_checklist_<?php echo $attachment_class; ?> mod_emundus_checklist___form_item tw-relative">
	            <?php if ($attachment_class == 'need_ok' && $itemid['id'] != $menuid) : ?>
                    <span class="material-symbols-outlined mod_emundus_checklist___check_circle">check_circle</span>
	            <?php endif; ?>
                <div class="mod_emundus_checklist___grid tw-group">
                    <div class="mod_emundus_checklist___step_count group-hover:!tw-bg-blue-100 group-hover:!tw-border-blue-100">
						<?php if ($itemid['id'] == $menuid) {
							$color = 'var(--blue-900)';
							$border_color = 'var(--blue-200)';
							$title_color = 'var(--blue-900)';
						}
                        elseif ($attachment_class == 'need_missing') {
	                        $color = 'var(--neutral-900)';
	                        $border_color = 'var(--neutral-300)';
	                        $title_color = 'var(--neutral-900)';
						}
                        elseif ($attachment_class == 'need_ok') {
	                        $color = 'var(--neutral-0)';
	                        $border_color = 'var(--main-500)';
	                        $title_color = 'var(--main-500)';
						}
						?>
                        <span class="group-hover:!tw-text-blue-900" style="color: <?= $color ?>">
                            <?php
                            if ($show_mandatory_documents == 1 && count($mandatory_documents) > 0) {
	                            echo sizeof($forms) + 2;
                            }
                            else {
	                            echo sizeof($forms) + 1;
                            }
                            ?>
                        </span>
                    </div>
                    <a href="<?php echo $itemid['link'] . '&Itemid=' . $itemid['id'] ?>#attachment_list_opt" class="group-hover:!tw-text-blue-900" style="color: <?php echo $title_color; ?>;" <?php if ($itemid['id'] == $menuid) : ?>class="tw-font-medium"<?php endif; ?>>
                        <?php echo Text::_($optional_documents_title) ?>
                    </a>
                </div>
				<?php if (!empty($checkout_url)) : ?>
                    <div class="mod_emundus_checklist___border_item"></div>
				<?php endif ?>
            </div>
		<?php endif; ?>

        <?php if (!empty($payment_step)) :
	        if (!$paid) {
		        $paid_class = 'need_missing';
	        }
	        else {
		        $paid_class = 'need_ok';
	        }
            ?>
            <div class="mod_emundus_checklist___border_item"></div>
            <div class="<?php if ($view === 'payment') echo 'active' ?> mod_emundus_checklist_<?php echo $paid_class; ?>  mod_emundus_checklist___form_item tw-cursor-pointer">
                <div class="mod_emundus_checklist___grid tw-group">
                    <div class="mod_emundus_checklist___step_count group-hover:!tw-bg-blue-100 group-hover:!tw-border-blue-100">
	                    <?php if ($paid_class == 'need_missing') : ?>
                            <span class="material-symbols-outlined !tw-text-neutral-900 group-hover:!tw-text-blue-900">shopping_cart</span>
	                    <?php elseif ($paid_class == 'need_ok') : ?>
                            <span class="material-symbols-outlined tw-text-white">done</span>
	                    <?php endif; ?>

                    </div>
                    <a href="/cart?fnum=<?= $user->fnum ?>" class="group-hover:!tw-text-blue-900"><?php echo Text::_('MOD_EMUNDUS_CHECKLIST_PAYMENT') ?></a>
                </div>
            </div>

        <?php endif; ?>

		<?php if (!empty($checkout_url)) : ?>
			<?php
			if (!$paid) {
				$paid_class = 'need_missing';
			}
			else {
				$paid_class = 'need_ok';
			}
			?>
            <div class="mod_emundus_checklist_<?php echo $paid_class; ?> mod_emundus_checklist___form_item">
                <div class="mod_emundus_checklist___grid tw-group">
                    <div class="mod_emundus_checklist___step_count group-hover:!tw-bg-blue-100 group-hover:!tw-border-blue-100">
						<?php if ($paid_class == 'need_missing') : ?>
                            <span class="material-symbols-outlined">close</span>
						<?php elseif ($paid_class == 'need_ok') : ?>
                            <span class="material-symbols-outlined tw-text-white">done</span>
						<?php endif; ?>
                    </div>
                    <a href="<?php echo $confirm_form_url; ?>"><?php echo Text::_('MOD_EMUNDUS_CHECKLIST_PAYMENT') ?></a>
                </div>
            </div>
		<?php endif; ?>
    </div>
</div>

<?php
$details_view = false;
$uri          = Uri::getInstance();
$url          = explode('&', $uri->toString());
if (is_array($url)) {
	$details_view = in_array('view=details', $url);
}

$layout = Factory::getApplication()->input->getString('layout');
if ($layout !== 'cart' || $paid) {
?>
    <div class="mod_emundus_checklist___buttons">
		<?php if ($show_send && $details_view === false && $is_confirm_url === false) : ?>
            <a class="btn btn-success btn-xs em-w-100"
				<?php if ((int) ($attachments_progress) >= 100 && (int) ($forms_progress) >= 100 && ((in_array($application->status, $status_for_send) && (!$is_dead_line_passed || ($is_dead_line_passed && $can_edit_after_deadline))) || in_array($user->id, $exceptions))) : ?>
                    href="<?php echo $confirm_form_url; ?>" style="opacity: 1"
				<?php else: ?>
                    style="opacity: 0.6; cursor: not-allowed"
				<?php endif; ?>
				<?php if ($application_fee && !$paid) : ?>
                    title="<?php echo Text::_('MOD_EMUNDUS_CHECKLIST_PROCESS_TO_PAYMENT'); ?>"
				<?php else : ?>
                    title="<?php echo Text::_('MOD_EMUNDUS_CHECKLIST_SEND_APPLICATION'); ?>"
				<?php endif ?>
            >
				<?php if ($application_fee && !$paid) : ?>
					<?php echo Text::_('MOD_EMUNDUS_CHECKLIST_PROCESS_TO_PAYMENT'); ?>
				<?php else : ?>
					<?php echo Text::_('MOD_EMUNDUS_CHECKLIST_SEND_APPLICATION'); ?>
				<?php endif ?>
            </a>
		<?php endif; ?>
    </div>

<?php
}
?>

<script>
    addEventListener("resize", (event) => {
        let content = document.getElementById('mod_emundus_checklist___content');
        if (innerWidth <= 767) {
            content.classList.add('mod_emundus_checklist___content_closed');
        } else {
            content.classList.remove('mod_emundus_checklist___content_closed');
        }
    });

    document.addEventListener('click', function (e) {
        if (window.innerWidth < 767 && e.target.closest('.mod_emundus_checklist_expand')) {
            expandForms(e);
        }
    });

    function expandForms(e) {
        let content = e.target.closest('.mod_emundus_checklist').querySelector('#mod_emundus_checklist___content');
        let icon = e.target.closest('.mod_emundus_checklist').querySelector('#mod_emundus_checklist___expand_icon');

        if (typeof content !== 'undefined') {
            if (!content.classList.contains('mod_emundus_checklist___content_closed')) {
                content.classList.add('mod_emundus_checklist___content_closed');
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.classList.remove('mod_emundus_checklist___content_closed');
                icon.style.transform = 'rotate(180deg)';
            }
        }

    }

    let button = document.querySelector(".mod_emundus_checklist___buttons");

    if (button) {
        let button_prime = button.cloneNode(true);

        if (window.innerWidth < 767) {
            let container = document.querySelector("#g-main-mainbody .g-content .container .row .col > div");
            if (container) {
                container.appendChild(button_prime);
            }

            let form = document.querySelector(".emundus-form.applicant-form");
            form.style.marginBottom = "0px";
        }
    }
</script>
