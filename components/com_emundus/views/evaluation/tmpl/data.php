<?php
/**
 * @version        $Id: data.php 14401 2016-06-16 14:10:00Z brivalland $
 * @package        Joomla
 * @subpackage     Emundus
 * @copyright      Copyright (C) 2016 eMundus. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;


$anonymize_data = EmundusHelperAccess::isDataAnonymized(Factory::getApplication()->getIdentity()->id);
$limits         = [0 => Text::_('COM_EMUNDUS_ACTIONS_ALL'), 5 => 5, 10 => 10, 15 => 15, 20 => 20, 25 => 25, 30 => 30, 50 => 50, 100 => 100];

$fnums = [];
if (is_array($this->datas))
{
	foreach ($this->datas as $line)
	{
		if (!empty($line['fnum']) && !empty($line['fnum']->val))
		{
			$fnums[] = $line['fnum']->val;
		}
	}
}

$eMConfig   = JComponentHelper::getParams('com_emundus');
$fix_header = $eMConfig->get('fix_file_header', 0);
?>
<div class="panel panel-default em-data <?php if (!is_array($this->datas)) : ?>bg-transparent<?php endif; ?>">
    <style>
        .em-double-scroll-bar {
            position: sticky;
            padding: 0 !important;
            z-index: 1;
        }

        div.top-scrollbars::-webkit-scrollbar, .em-double-scroll-bar::-webkit-scrollbar {
            -webkit-appearance: none;
            width: 7px;
            height: 10px;
            background-color: white !important;
        }

        div.top-scrollbars::-webkit-scrollbar-thumb, .em-double-scroll-bar::-webkit-scrollbar-thumb {
            border-radius: 8px;
            background-color: var(--neutral-400);
            box-shadow: 0 0 1px rgba(255, 255, 255, .5);
        }
    </style>

    <input type="hidden" id="view" name="view" value="evaluation">
	<?php if (is_array($this->datas)): ?>
        <div class="container-result">
            <div class="em-ml-8 em-flex-row">
				<?= $this->pagination->getResultsCounter(); ?>
                <div class="em-ml-16">|</div>
                <div class="em-ml-16 em-flex-row">
                    <label for="pager-select"
                           class="em-mb-0-important em-mr-4"><?= Text::_('COM_EMUNDUS_DISPLAY') ?></label>
                    <select name="pager-select" id="pager-select" class="em-select-no-border">
						<?php foreach ($limits as $limit => $limit_text): ?>
                            <option value="<?= $limit ?>" <?= $this->pagination->limit == $limit ? "selected=true" : '' ?>><?= $limit_text ?></option>
						<?php endforeach; ?>
                    </select>
                </div>
            </div>
			<?php echo $this->pageNavigation ?>
            <div id="countCheckedCheckbox" class="countCheckedCheckbox" style="display: none"></div>
        </div>
        <div class="em-data-container top-scrollbars" style="padding-bottom: unset">
            <table class="table table-striped table-hover" id="em-data">
                <thead>
                <tr>
					<?php foreach ($this->datas[0] as $kl => $v) : ?>
                        <th title="<?= strip_tags(Text::_($v)); ?>" id="<?= $kl; ?>">
                            <div class="em-cell">
								<?php if (@$this->lists['order'] == $kl) : ?>
									<?php if (@$this->lists['order_dir'] == 'desc') : ?>
                                        <span class="glyphicon glyphicon-sort-by-attributes-alt"></span>
									<?php else : ?>
                                        <span class="glyphicon glyphicon-sort-by-attributes"></span>
									<?php endif; ?>
                                    <strong>
										<?= strip_tags(Text::_($v)); ?>
                                    </strong>

								<?php elseif ($kl == 'check' && empty($this->cfnum)) : ?>
                                    <div class="selectContainer" id="selectContainer">
                                        <div class="selectPage">
                                            <input type="checkbox" value="-1" id="em-check-all"
                                                   class="em-hide em-check">
                                            <label for="em-check-all" class="check-box"></label>
                                        </div>
                                        <div class="selectDropdown" id="selectDropdown">
                                            <span class="material-symbols-outlined">keyboard_arrow_down</span>
                                        </div>

                                    </div>
                                    <div class="selectAll" id="selectAll_evaluation">
                                        <label>
                                            <input value="-1" id="em-check-all-page" class="em-check-all-page"
                                                   type="checkbox"/>
                                            <span id="span-check-all" class="tw-cursor-pointer" onclick="onCheckPage()"><?= Text::_('COM_EMUNDUS_FILTERS_CHECK_ALL'); ?></span>
                                        </label>
                                        <label class="em-check-all-all" for="em-check-all-all">
                                            <input value="all" id="em-check-all-all" type="checkbox"
                                                   class="em-check-all-all"/>
                                            <span id="span-check-all-all" class="tw-cursor-pointer"><?= Text::_('COM_EMUNDUS_FILTERS_CHECK_ALL_ALL'); ?></span>
                                        </label>
                                        <label class="em-check-none" for="em-check-none">
                                            <span id="span-check-none" class="tw-cursor-pointer"><?= Text::_('COM_EMUNDUS_FILTERS_CHECK_NONE'); ?></span>
                                        </label>
                                    </div>
								<?php else: ?>
									<?= strip_tags(Text::_($v)); ?>
								<?php endif; ?>
                            </div>
                        </th>
					<?php endforeach; ?>

                </tr>
                </thead>
                <tbody>
				<?php foreach ($this->datas as $key => $line): ?>
					<?php if ($key != 0):?>
                        <tr>
							<?php foreach (array_keys($this->datas[0]) as $k):
                                $value = $line[$k];
                                ?>
								<?php if ($k != 'evaluation_id' && $k != 'evaluations_step_id'): ?>

                                    <td <?php if ($k == 'check' && $value->class != null)
									{
										echo 'class="' . $value->class . '"';
									}
									if ($k == 'jecc___campaign_id')
									{
										echo 'class="em-cell-scroll"';
									} ?>>
                                        <div class="em-cell">
											<?php if ($k == 'check'): ?>
                                                <label for="<?php echo $line['fnum']->val ?>_check">
                                                    <input type="checkbox"
                                                           data-evalid="<?php echo $line['evaluation_id']->val; ?>"
                                                           name="<?php echo $line['fnum']->val; ?>_check"
                                                           id="<?php echo $line['fnum']->val ?>_check" class='em-check'
                                                           style="width:20px !important;"/>
													<?php
													$tab = explode('-', $key);
													echo($tab[1] + 1 + $this->pagination->limitstart);
													?>
                                                </label>
											<?php elseif ($k == 'status'): ?>
                                                <span class="label label-<?php echo $value->status_class ?>"
                                                      title="<?php echo $value->val ?>"><?php echo $value->val ?></span>
											<?php elseif ($k == 'fnum'): ?>
												<?php if ($this->open_file_in_modal) : ?>
                                                    <div id="<?php echo $value->val ?>"
                                                         class="em-pointer evaluation-open-modal-file"
                                                         onclick="clickOpenfile('<?= $value->val ?>', '<?= implode('|', $fnums) ?>')">
														<?php if (isset($value->photo) && !$anonymize_data) : ?>
                                                            <div class="em_list_photo"><?= $value->photo; ?></div>
														<?php endif; ?>
                                                        <div class="em_list_text">
															<?php if ($anonymize_data) : ?>
                                                                <div class="em_list_fnum"><?= $value->val; ?></div>
															<?php else : ?>
                                                                <span class="em_list_text" title="<?= $value->val; ?>">
                                                                    <strong> <?= $value->user->name; ?></strong>
                                                                </span>
                                                                <div class="em_list_email"><?= $value->user->email; ?></div>
                                                                <div class="em_list_email"><?= $value->user->id; ?></div>
															<?php endif; ?>
                                                        </div>
                                                    </div>
												<?php else : ?>
                                                    <a href="#<?php echo $value->val ?>|open"
                                                       id="<?php echo $value->val ?>"
                                                       class="em_file_open">
														<?php if (isset($value->photo) && !$anonymize_data) : ?>
                                                            <div class="em_list_photo"><?= $value->photo; ?></div>
														<?php endif; ?>
                                                        <div class="em_list_text">
															<?php if ($anonymize_data) : ?>
                                                                <div class="em_list_fnum"><?= $value->val; ?></div>
															<?php else : ?>
                                                                <span class="em_list_text" title="<?= $value->val; ?>">
                                                                    <strong> <?= $value->user->name; ?></strong>
                                                                </span>
                                                                <div class="em_list_email"><?= $value->user->email; ?></div>
                                                                <div class="em_list_email"><?= $value->user->id; ?></div>
															<?php endif; ?>
                                                        </div>
                                                    </a>
												<?php endif; ?>
											<?php elseif ($k == "access") : ?>
												<?= $this->accessObj[$line['fnum']->val] ?>
											<?php elseif ($k == "id_tag") : ?>
												<?= $this->colsSup['id_tag'][$line['fnum']->val] ?>
											<?php elseif (isset($this->colsSup) && array_key_exists($k, $this->colsSup)) : ?>
												<?= @$this->colsSup[$k][$line['fnum']->val] ?>
											<?php else : ?>
												<?php if ($value->type == 'text') : ?>
													<?= strip_tags(Text::_($value->val)); ?>
												<?php elseif ($value->type == "textarea" && !empty($value->val) && strlen($value->val) > 200) : ?>
													<?= substr(strip_tags($value->val), 0, 200) . " ..."; ?>
												<?php elseif ($value->type == "date")  : ?>
                                                    <strong>
														<?php if (!isset($value->val) || $value->val == "0000-00-00 00:00:00") : ?>
														<?php else: ?>
															<?php
															$formatted_date = DateTime::createFromFormat('Y-m-d H:i:s', $value->val);
															echo JFactory::getDate($value->val)->format(Text::_('DATE_FORMAT_LC2'));
															?>
														<?php endif; ?>
                                                    </strong>
												<?php else: ?>
													<?php
													// Do not display the typical COM_EMUNDUS_PLEASE_SELECT text used for empty dropdowns.
													if ($value->val !== 'COM_EMUNDUS_PLEASE_SELECT')
													{
														echo Text::_($value->val);
													}
													?>
												<?php endif; ?>
											<?php endif; ?>
                                        </div>

                                    </td>
								<?php endif; ?>
							<?php endforeach; ?>
                        </tr>
					<?php endif; ?>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
	<?php else: ?>
        <div class="text-center tw-mt-6">
            <h1 class="tw-mb-8 !tw-text-neutral-600"><?= $this->datas ?></h1>
            <div class="no-result tw-bg-no-repeat tw-w-64 tw-h-64 tw-my-0 tw-mx-auto"></div>
        </div>
	<?php endif; ?>

    <script type="text/javascript">
        // todo: maybe try to reload actions here ?

        $(document).ready(function () {
            $('#rt-mainbody-surround').children().addClass('mainemundus');
            $('#rt-main').children().addClass('mainemundus');
            $('#rt-main').children().children().addClass('mainemundus');

            menuAction = document.querySelector('.em-menuaction');
            headerNav = document.querySelector('#g-navigation .g-container, #g-header .g-container');
            containerResult = document.querySelector('.container-result');

            setTimeout(() => {
                if (!menuAction) {
                    menuAction = document.querySelector('.em-menuaction');
                }

                if (!headerNav) {
                    headerNav = document.querySelector('#g-navigation .g-container, #g-header .g-container');
                }

                if (!containerResult) {
                    containerResult = document.querySelector('.container-result');
                }

                if (headerNav && menuAction) {
                    if (containerResult) {
                        $('.container-result').css('top', (headerNav.offsetHeight + menuAction.offsetHeight) + 'px');
                        $('.em-double-scroll-bar').css('top', (headerNav.offsetHeight + menuAction.offsetHeight + containerResult.offsetHeight - 2) + 'px');
                    }

                    if ($('#em-data th')) {
                        if (containerResult) {
                            $('#em-data th').css('top', (headerNav.offsetHeight + menuAction.offsetHeight + containerResult.offsetHeight) + 'px');
                        } else {
                            $('#em-data th').css('top', (headerNav.offsetHeight + menuAction.offsetHeight) + 'px');
                        }
                    }
                }
            }, 2000);

            const dataContainer = document.querySelector('.em-data-container')
            if (dataContainer) {
                DoubleScroll(document.querySelector('.em-data-container'));
            }
        });
        window.parent.$("html, body").animate({scrollTop: 0}, 300);

    </script>

    <script>
        var countFiles = document.querySelector('#countCheckedCheckbox');

        if (document.querySelector('#selectAll_evaluation')) {
            var selectDropdownContainer = document.querySelector('#selectAll_evaluation')
            selectDropdownContainer.style.display = 'none';

            $('.selectDropdown').click(function () {
                if (selectDropdownContainer.style.display === 'none') {
                    selectDropdownContainer.style.display = 'flex';
                } else {
                    selectDropdownContainer.style.display = 'none';
                }
            });

            $(document).click(function (e) {
                var container = $(".selectDropdown");

                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    selectDropdownContainer = document.querySelector('.selectAll');

                    if (selectDropdownContainer) {
                        selectDropdownContainer.style.display = 'none';
                    }
                }
            });
        }

        function checkAllFiles() {
            $('#em-check-all-all').prop('checked', true);

            selectAllFiles();
        }

        function uncheckAllFiles() {
            hideCount();

            $('.em-check').prop('checked', false);
            $('.em-check-all-all').prop('checked', false);
            reloadActions('files', undefined, false);
        }

        function displayCount() {
            countFiles.style.display = 'block';
            countFiles.style.backgroundColor = '#EDEDED';


            if (!containerResult) {
                containerResult = document.querySelector('.container-result');
            }

            if (!headerNav) {
                const headerNav = document.querySelector('#g-navigation .g-container, #g-header .g-container');
            }

            $('#em-data th').css('top', (headerNav.offsetHeight + menuAction.offsetHeight + containerResult.offsetHeight) + 'px');
        }

        function hideCount() {
            countFiles.style.display = 'none';

            if (!containerResult) {
                containerResult = document.querySelector('.container-result');
            }

            $('#em-data th').css('top', (headerNav.offsetHeight + menuAction.offsetHeight + containerResult.offsetHeight) + 'px');
            countFiles.style.backgroundColor = 'transparent';
            $('.em-close-minimise').remove();
        }

        function selectAllFiles() {
            let allCheck = $('.em-check-all-all#em-check-all-all').is(':checked');

            if (allCheck === true) {
                $('.em-check-all-page#em-check-all-page').prop('checked', false);
                $('.em-check').prop('checked', true);

                displayCount();
                countFiles.innerHTML = '<p>' + Joomla.Text._('COM_EMUNDUS_FILTERS_YOU_HAVE_SELECT') + Joomla.Text._('COM_EMUNDUS_FILTERS_SELECT_ALL') + Joomla.Text._('COM_EMUNDUS_FILES_FILES') + '<span id="count-all-files" class="tw-hidden"></span>. <a class="em-pointer em-text-underline" style="color: var(--red-500);" onclick="uncheckAllFiles()">' + Joomla.Text._('COM_EMUNDUS_FILES_UNSELECT_ALL_FILES_2') + '</a></p>';

                document.querySelector('.selectContainer').style.backgroundColor = '#F3F3F3';

                reloadActions('files', undefined, true);
            } else {
                $('.em-check').prop('checked', false);

                hideCount();
                countFiles.innerHTML = '';

                document.querySelector('.selectContainer').style.backgroundColor = 'transparent';

                reloadActions('files', undefined, false);
            }
        }


        $('#selectAll_evaluation>span').click(function () {
            $('#selectAll_evaluation').slideUp();
        });

        $('#span-check-none').click(function () {
            $('#em-check-all-all').prop('checked', false);
            $('.em-check#em-check-all').prop('checked', false);
            $('.em-check-all#em-check-all').prop('checked', false);
            $('.em-check').prop('checked', false);
            $('.nav.navbar-nav').hide();
            hideCount();
            countFiles.innerHTML = '';
            reloadActions('files', undefined, false);

            document.querySelector('.selectContainer').style.backgroundColor = 'transparent';
        });

        $(document).on('change', '.em-check-all-all', function (e) {
            selectAllFiles();
        });

        $(document).on('change', '.em-check', function (e) {
            let countCheckedCheckbox = $('.em-check').not('#em-check-all.em-check,#em-check-all-all.em-check').filter(':checked').length;
            let files = countCheckedCheckbox === 1 ? Joomla.Text._('COM_EMUNDUS_FILES_FILE') : Joomla.Text._('COM_EMUNDUS_FILES_FILES');

            if (countCheckedCheckbox !== 0) {
                displayCount();
                countFiles.innerHTML = '<p>' + Joomla.Text._('COM_EMUNDUS_FILTERS_YOU_HAVE_SELECT') + countCheckedCheckbox + ' ' + files + '. <a class="em-pointer em-text-underline em-profile-color" onclick="checkAllFiles()">' + Joomla.Text._('COM_EMUNDUS_FILES_SELECT_ALL_FILES') + '</a> ' + Joomla.Text._('COM_EMUNDUS_FILES_OR_CONNECTOR') + ' <a class="em-pointer em-text-underline em-profile-color" style="color: var(--red-700);" onclick="uncheckAllFiles()">' + Joomla.Text._('COM_EMUNDUS_FILES_UNSELECT_ALL_FILES') + '</a>' + '</p>';
            } else {
                hideCount();
                countFiles.innerHTML = '';
            }
        });

        function onCheckPage() {
            document.querySelector('.selectPage label[for="em-check-all"]').click();
        }

		<?php if($fix_header == 1): ?>
        document.addEventListener('scroll', function (e) {
            if (window.scrollY > document.querySelector('.em-data-container table thead').offsetHeight) {
                document.querySelector('.em-data-container table thead').style.position = 'relative';
                let containerResult = document.querySelector('.container-result').offsetHeight;
                document.querySelector('.em-data-container table thead').style.top = (window.scrollY - containerResult - 4) + 'px';
            } else {
                document.querySelector('.em-data-container table thead').style.position = 'static';
                document.querySelector('.em-data-container table thead').style.top = '0px';
            }
        });
		<?php endif; ?>
    </script>
</div>