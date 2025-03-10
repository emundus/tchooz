<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

if (version_compare(JVERSION, '4.0', '>')) {
	Factory::getApplication()->getSession()->set('application_layout', 'tag');
}
else {
	Factory::getSession()->set('application_layout', 'tag');
}
?>


<div class="tags">
    <div class="row">
        <div class="panel panel-default widget em-container-tag">
            <div class="panel-heading em-container-tag-heading !tw-bg-profile-full">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-tags"></span>
					<?php echo JText::_('COM_EMUNDUS_TAGS'); ?>
                    <span class="label label-info" style="float:unset; border-radius: 999px;"><?php echo count($this->tags); ?></span>
                    <div class="em-flex-row em-w-40-vw">
						<?php if (EmundusHelperAccess::asAccessAction(14, 'c', $this->_user->id, $this->fnum)) : ?>
                            <select class="chzn-select" multiple id="mytags">
                                <?php if($this->displayTagCategories == 1) : ?>
                                    <?php foreach ($this->groupedTags as $category => $value) : ?>
                                        <optgroup value="<?php echo $category; ?>"
                                                  label="<?= empty($category) ? JText::_('UNCATEGORIZED_TAGS') : JText::_($category); ?>">
                                            <?php foreach ($value as $tag) : ?>
                                                <option value="<?php echo $tag['id']; ?>"><?php echo $tag['label']; ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <?php foreach ($this->groupedTags as $tag) : ?>
                                        <option value="<?php echo $tag['id']; ?>"><?php echo $tag['label']; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>&ensp;&ensp;
                            <button class="btn btn-success btn-xs" id="add-tags" onclick="addTag()">
								<?php echo JText::_('COM_EMUNDUS_ADD'); ?>
                            </button>
						<?php endif; ?>
                    </div>
                </h3>&ensp;&ensp;


                <div class="btn-group pull-right">
                    <button id="em-prev-file" class="btn btn-info btn-xxl"><span
                                class="material-symbols-outlined">arrow_back</span></button>
                    <button id="em-next-file" class="btn btn-info btn-xxl"><span
                                class="material-symbols-outlined">arrow_forward</span></button>
                </div>
            </div>
            <div class="panel-body em-container-tag-body">
                <ul class="list-group">
					<?php
					if (count($this->tags) > 0) {
						$i = 0;
						foreach ($this->tags as $tag) {
							$color = str_replace('label-', '', $tag['class']);
							?>
                            <li class="list-group-item" id="<?php echo $tag['id_tag']; ?>"
                                fnum="<?php echo $this->fnum; ?>">
                                <div class="row">
                                    <div class="col-xs-10 col-md-11">
                                        <div>

                                            <div class="mic-info em-tags-date">
                                                <a href="#"><?php echo $tag['name']; ?></a>
                                                - <?php echo JHtml::_('date', $tag['date_time'], JText::_('DATE_FORMAT_LC2')); ?>
												<?php if ($this->_user->id == $tag['user_id'] || EmundusHelperAccess::asAccessAction(14, 'd', $this->_user->id, $this->fnum)): ?>
                                                    <button type="button" class="btn btn-danger btn-xs"
                                                            onclick="deleteTag('<?php echo $tag['id_tag'] ?>', '<?php echo $this->fnum; ?>' )"
                                                            title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_DELETE'); ?>">
                                                        <span class="material-symbols-outlined">delete_outline</span>
                                                    </button>
												<?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="comment-text em-tags-action">
                                            <div class="tw-border tw-flex tw-items-center tw-gap-2 sticker label-<?php echo $color ?>" title="<?= $tag['label']; ?>">
                                                <span class="circle tw-bg-white"></span>
                                                <span class="tw-text-white tw-font-semibold"
                                                      style="float:unset"><?php echo $tag['label']; ?></span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </li>
							<?php
							$i++;
						}
					}
					else { ?>
                        <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
                        <p class="tw-text-center"><?php echo JText::_('COM_EMUNDUS_TAGS_NO_TAG'); ?></p>
					<?php } ?>
                </ul>
            </div>

			<?php if (EmundusHelperAccess::asAccessAction(14, 'c', $this->_user->id, $this->fnum)): ?>
                <div class="form" id="form"></div>
			<?php endif; ?>

        </div>
    </div>
</div>

<script type="text/javascript">
    //$(".tags .comment-text .btn.btn-danger.btn-xs").unbind("click");
    $(document).off('click', '.tags .comment-text .btn.btn-danger.btn-xs');

    function deleteTag(id, fnum) {
        if (id && fnum) {
            Swal.fire({
                title: "<?php echo JText::_('COM_EMUNDUS_APPLICATION_DELETE_TAG'); ?>",
                text: "<?php echo JText::_('COM_EMUNDUS_APPLICATION_DELETE_TAG_CONFIRM'); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?php echo JText::_('COM_EMUNDUS_DELETE_ITEM'); ?>',
                cancelButtonText: '<?php echo JText::_('CANCEL'); ?>',
                reverseButtons: true,
                customClass: {
                    title: 'em-swal-title',
                    cancelButton: 'em-swal-cancel-button',
                    confirmButton: 'em-swal-confirm-button',
                }
            }).then((result) => {
                if (result.value) {
                    let formData = new FormData();
                    formData.append('fnum', fnum);
                    formData.append('id_tag', id);

                    fetch('index.php?option=com_emundus&controller=application&task=deletetag', {
                        method: 'POST',
                        body: formData
                    }).then((response) => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw new Error('Server response wasn\'t OK');
                        }
                    }).then((result) => {
                        if (result.status) {
                            document.querySelectorAll('.tags li[id="' + id + '"]').forEach(el => el.remove());

                            Swal.fire({
                                title: "<?php echo JText::_('COM_EMUNDUS_APPLICATION_DELETE_TAG_SUCCESS'); ?>",
                                text: "",
                                icon: 'success',
                                showConfirmButton: false,
                                customClass: {
                                    title: 'em-swal-title',
                                }
                            });
                        } else {
                            $('#form').append('<p class="text-danger"><strong>' + result.msg + '</strong></p>');
                        }
                    }).catch((error) => {
                        console.error('Error:', error);
                    });
                }
            });
        }
    }

    $(document).ready(function () {
        $('.chzn-select').chosen({
            placeholder_text_multiple: Joomla.JText._('COM_EMUNDUS_FILES_PLEASE_SELECT_TAG'),
            width: '50%'
        });
        $('.chzn-select').trigger("chosen:updated");
    })

    function addTag() {

        var tags = $("#mytags").val();

        url = 'index.php?option=com_emundus&controller=' + $('#view').val() + '&task=tagfile';
        $.ajax(
            {
                type: 'POST',
                url: url,
                dataType: 'json',
                data: ({fnums: '{"1":"<?php echo $this->fnum; ?>"}', tag: tags}),
                success: function (result) {
                    if (result.status) {
                        var url = "index.php?option=com_emundus&view=application&format=raw&layout=tag&fnum=<?php echo $this->fnum; ?>";
                        $.ajax({
                            type: 'get',
                            url: url,
                            dataType: 'html',
                            success: function (result) {
                                $('#em-appli-block').empty();
                                $('#em-appli-block').append(result);
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                console.log(jqXHR.responseText);
                            }

                        });
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR.responseText);
                }
            });
    }
</script>
