<?php
/**
 * @version        $Id: default.php 14401 2014-09-16 14:10:00Z brivalland $
 * @package        Joomla
 * @subpackage     Emundus
 * @copyright      Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div class="tw-h-full">
    <div class="tw-h-full">
        <div class="col-md-3 side-panel" style="height: calc(100vh - 139px);overflow-y: auto;">
            <div class="panel panel-info em-containerFilter" id="em-files-filters">
                <div class="panel-heading em-containerFilter-heading !tw-bg-profile-full">
                    <div>
                        <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_FILTERS') ?></h3> &ensp;&ensp;
                    </div>
                    <div class="buttons" style="float:right; margin-top:0px">
                        <div class="em-flex-row">
                            <label for="save-filter" class="em-mr-8 em-flex-row" style="margin-bottom: 0;">
                                    <span class="material-symbols-outlined em-pointer em-color-white"
                                          title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_SAVE_BTN'); ?>">save</span>
                            </label>
                            <input type="button" style="display: none" id="save-filter"
                                   title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_SAVE_BTN'); ?>" />
                            <label for="clear-search" class="em-flex-row">
                                <span class="material-symbols-outlined em-pointer em-color-white"
                                      title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_CLEAR_BTN'); ?>">filter_alt_off</span>
                            </label>
                            <input type="button" style="display: none" id="clear-search"
                                   title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_CLEAR_BTN'); ?>"/>
                        </div>
                    </div>
                </div>

                <div class="panel-body em-containerFilter-body">

                    <div id="em_filters"
                         component="Filters"
                         data-module-id="<?= $this->itemId ?>"
                         data-menu-id="<?= $this->itemId ?>"
                         data-applied-filters='<?= base64_encode(json_encode($this->applied_filters)) ?>'
                         data-filters='<?= base64_encode(json_encode($this->filters)) ?>'
                         data-quick-search-filters='<?= base64_encode(json_encode($this->quick_search_filters)) ?>'
                         data-count-filter-values='<?= $this->count_filter_values ?>'
                         data-allow-add-filter='<?= $this->allow_add_filter ?>'
                    ></div>

                    <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash.rand(0,1000); ?>"></script>
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-appli-menu">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h1 class="panel-title"><?php echo JText::_('COM_EMUNDUS_APPLICATION_ACTIONS') ?></h1>
                </div>
                <div class="panel-body em-hide-body">
                    <div class="list-group">
                    </div>
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-synthesis">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_APPLICATION_SYNTHESIS') ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-assoc-files">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_ACCESS_LINKED_APPLICATION_FILES'); ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-collaborators">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_APPLICATION_COLLABORATORS'); ?></h3>
                </div>
                <div class="panel-body em-hide-body" style="padding: 2px 4px;">
                </div>
            </div>


            <div class="clearfix"></div>
            <div class="panel panel-info em-hide" id="em-last-open">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_APPLICATION_LAST_OPEN_FILES'); ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                    <div class="list-group">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9 main-panel tw-h-full">
            <div id="em-hide-filters" class="em-close-filter" data-toggle="tooltip" data-placement="top"
                 title=<?php echo JText::_('COM_EMUNDUS_FILTERS_HIDE_FILTER'); ?>">
				<span class="glyphicon glyphicon-chevron-left"></span>
        </div>
        <div class="navbar navbar-inverse em-menuaction !tw-bg-profile-full">
            <div class="navbar-header em-menuaction-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse"
                        data-target=".navbar-inverse-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>

        </div>
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

            <input type="hidden" id="view" name="view" value="files">
	        <?php if (is_array($this->datas)): ?>
                <div class="container-result">
                    <div class="em-ml-8 em-flex-row">
				        <?= $this->pagination->getResultsCounter(); ?>
                        <div class="em-ml-16">|</div>
                        <div class="em-ml-16 em-flex-row">
                            <label for="pager-select"
                                   class="em-mb-0-important em-mr-4"><?= Text::_('COM_EMUNDUS_DISPLAY') ?></label>
                            <select name="pager-select" id="pager-select" class="em-select-no-border">
                                <option value="0" <?php if ($this->pagination->limit == 0)
						        {
							        echo "selected=true";
						        } ?>><?= Text::_('COM_EMUNDUS_ACTIONS_ALL') ?></option>
                                <option value="5" <?php if ($this->pagination->limit == 5)
						        {
							        echo "selected=true";
						        } ?>>5
                                </option>
                                <option value="10" <?php if ($this->pagination->limit == 10)
						        {
							        echo "selected=true";
						        } ?>>10
                                </option>
                                <option value="15" <?php if ($this->pagination->limit == 15)
						        {
							        echo "selected=true";
						        } ?>>15
                                </option>
                                <option value="20" <?php if ($this->pagination->limit == 20)
						        {
							        echo "selected=true";
						        } ?>>20
                                </option>
                                <option value="25" <?php if ($this->pagination->limit == 25)
						        {
							        echo "selected=true";
						        } ?>>25
                                </option>
                                <option value="30" <?php if ($this->pagination->limit == 30)
						        {
							        echo "selected=true";
						        } ?>>30
                                </option>
                                <option value="50" <?php if ($this->pagination->limit == 50)
						        {
							        echo "selected=true";
						        } ?>>50
                                </option>
                                <option value="100" <?php if ($this->pagination->limit == 100)
						        {
							        echo "selected=true";
						        } ?>>100
                                </option>
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
					        <?php foreach ($this->keys_order as $kl => $order): ?>
                                <th title="<?= strip_tags(Text::_($this->datas[0][$kl])); ?>" id="<?= $kl; ?>" >
                                    <div class="em-cell">
								        <?php if ($kl == 'check'): ?>

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
                                            <div class="selectAll" id="selectAll">
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
								        <?php elseif ($this->lists['order'] == $kl): ?>
									        <?php if ($this->lists['order_dir'] == 'desc'): ?>
                                                <span class="glyphicon glyphicon-sort-by-attributes-alt"></span>
									        <?php else: ?>
                                                <span class="glyphicon glyphicon-sort-by-attributes"></span>
									        <?php endif; ?>
                                            <strong>
										        <?= strip_tags(Text::_($this->datas[0][$kl])); ?>
                                            </strong>
								        <?php else: ?>
									        <?= strip_tags(Text::_($this->datas[0][$kl])); ?>
								        <?php endif; ?>

                                    </div>
                                </th>
					        <?php endforeach; ?>
                        </tr>
                        </thead>

                        <tbody>
				        <?php foreach ($this->datas as $key => $line): ?>
					        <?php if ($key != 0): ?>

                                <tr>

							        <?php foreach ($this->keys_order as $k => $order) :
								        $value = $line[$k];
								        ?>

                                        <td <?php if ($k == 'check' && $value->class != null)
								        {
									        echo 'class="' . $value->class . '"';
								        }
								        if ($k == 'access' || $k == 'eta.id_tag' || $k == 'jecc___campaign_id')
								        {
									        echo 'class="em-cell-scroll"';
								        } ?>>
                                            <div class="em-cell">
										        <?php if ($k == 'check'): ?>
                                                    <label for="<?= $line['fnum']->val; ?>_check">
                                                        <input type="checkbox" name="<?= $line['fnum']->val; ?>_check"
                                                               id="<?= $line['fnum']->val; ?>_check" class='em-check'
                                                               style="width:20px !important;"/>
												        <?php
												        $tab = explode('-', $key);
												        echo $tab[1] + $this->pagination->limitstart;
												        ?>
                                                    </label>
										        <?php elseif ($k == 'status'): ?>
                                                    <span style="width: 100%" class="label label-<?= $value->status_class; ?>"
                                                          title="<?= $value->val; ?>"><?= $value->val; ?></span>
										        <?php elseif ($k == 'fnum'): ?>
                                                    <a href="#<?= $value->val; ?>|open" id="<?= $value->val; ?>"
                                                       class="em_file_open">
												        <?php if (isset($value->photo) && !$anonymize_data) : ?>
                                                            <div class="em_list_photo"><?= $value->photo; ?></div>
												        <?php endif; ?>
                                                        <div class="em_list_text">
													        <?php if ($anonymize_data) : ?>
                                                                <div class="em_list_fnum"><?= $value->val; ?></div>
													        <?php else : ?>
                                                                <span class="em_list_text tw-flex tw-items-center tw-justify-between"
                                                                      title="<?= $value->val; ?>">
                                                        <strong> <?= $value->user->name; ?></strong>
                                                        <?php if ($value->unread_messages) : ?>
	                                                        <?php echo $value->unread_messages; ?>
                                                        <?php endif; ?>
                                                    </span>
                                                                <div class="em_list_email"><?= $value->user->email; ?></div>
                                                                <div class="em_list_email"><?= $value->user->id; ?></div>
													        <?php endif; ?>
                                                        </div>
                                                    </a>
										        <?php elseif ($k == "access"): ?>
											        <?= $this->accessObj[$line['fnum']->val]; ?>
										        <?php elseif ($k == "eta.id_tag"): ?>
											        <?= @$this->colsSup['id_tag'][$line['fnum']->val] ?>
										        <?php elseif (array_key_exists($k, $this->colsSup)) : ?>
											        <?= @$this->colsSup[$k][$line['fnum']->val] ?>
										        <?php else : ?>
											        <?php if ($value->type == 'text') : ?>
												        <?= strip_tags(Text::_($value->val)); ?>
											        <?php elseif ($value->type == "textarea" && !empty($value->val) && strlen($value->val) > 200) : ?>
												        <?= substr(strip_tags($value->val), 0, 200) . " ..."; ?>
											        <?php elseif ($value->type == "date")  : ?>
                                                        <strong>
													        <?php if (!isset($value->val) || $value->val == "0000-00-00 00:00:00") : ?>
                                                                <span class="em-radio" id="<?= $value->id . '-' . $value->val; ?>"
                                                                      aria-hidden="true"></span>
													        <?php else: ?>
														        <?php
														        $formatted_date = DateTime::createFromFormat('Y-m-d H:i:s', $value->val);
														        echo JFactory::getDate($value->val)->format(Text::_('DATE_FORMAT_LC2'));
														        ?>
													        <?php endif; ?>
                                                        </strong>
											        <?php elseif ($value->type === 'birthday')  : ?>
                                                        <strong>
													        <?php if (empty($value->val) || $value->val === '0000-00-00 00:00:00') : ?>
                                                                <span class="em-radio" id="<?= $value->id . '-' . $value->val; ?>"
                                                                      aria-hidden="true"></span>
													        <?php else: ?>
														        <?php
														        $formatted_date = DateTime::createFromFormat('Y-m-d H:i:s', $value->val);
														        echo JFactory::getDate($value->val)->format(Text::_('COM_EMUNDUS_BIRTHDAY_FORMAT'));
														        ?>
													        <?php endif; ?>
                                                        </strong>
											        <?php else:
												        // Do not display the typical COM_EMUNDUS_PLEASE_SELECT text used for empty dropdowns.
												        if ($value->val !== 'COM_EMUNDUS_PLEASE_SELECT')
												        {
													        // value is saved as string '["value1", "value2"]' in the database
													        if ($value->type == 'checkbox' || ($value->type == 'dropdown' && json_decode($value->params, true)['multiple'] == 1))
													        {
														        $trimmed_string = trim($value->val, '[]');
														        $split_string   = preg_split('/,(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/', $trimmed_string);

														        $values_array = array_map(function ($element) {
															        return trim($element, '"');
														        }, $split_string);

														        echo '<ul>';
														        foreach ($values_array as $single_value)
														        {
															        echo '<li>' . Text::_($single_value) . '</li>';
														        }
														        echo '</ul>';
													        }
													        else
													        {
														        echo Text::_($value->val);
													        }
												        }
											        endif; ?>
										        <?php endif; ?>
                                            </div>
                                        </td>
							        <?php endforeach; ?>
                                </tr>
					        <?php endif; ?>
				        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
	        <?php else: ?>
                <div class="tw-text-center tw-mt-6">
                    <h1 class="tw-mb-8 !tw-text-neutral-600"><?= $this->datas ?></h1>
                    <div class="no-result tw-bg-no-repeat tw-w-64 tw-h-64 tw-my-0 tw-mx-auto"></div>
                </div>
	        <?php endif; ?>
            <script type="text/javascript">
                var $ = jQuery.noConflict();

                function checkurl() {
                    var url = $(location).attr('href');
                    var menuAction = null;
                    var headerNav = null;
                    var containerResult = null;

                    url = url.split("#");
                    $('.alert.alert-warning').remove();

                    if (url[1] != null && url[1].length >= 20) {
                        url = url[1].split("|");
                        url = url[0].split('%7C');
                        var fnum = {};
                        fnum.fnum = url[0];

                        if (fnum.fnum != null && fnum.fnum !== "close") {
                            addLoader();
                            $('#' + fnum.fnum + '_check').prop('checked', true);

                            $.ajax({
                                type: 'get',
                                url: '/index.php?option=com_emundus&controller=files&task=getfnuminfos',
                                async: true,
                                dataType: "json",
                                data: ({fnum: fnum.fnum}),
                                success: function (result) {
                                    if (result.status && result.fnumInfos != null) {
                                        var fnumInfos = result.fnumInfos;
                                        fnum.name = fnumInfos.name;
                                        fnum.label = fnumInfos.label;
                                        openFiles(fnum);
                                    } else {
                                        removeLoader();
                                        $(".panel.panel-default").prepend("<div class=\"alert alert-warning\"><?= Text::_('COM_EMUNDUS_APPLICATION_CANNOT_OPEN_FILE') ?></div>");
                                    }
                                },
                                error: function (jqXHR) {
                                    removeLoader();
                                    $("<div class=\"alert alert-warning\"><?= Text::_('COM_EMUNDUS_APPLICATION_CANNOT_OPEN_FILE') ?></div>").prepend($(".panel.panel-default"));
                                    console.log(jqXHR.responseText);
                                }
                            })

                        }
                    } else {
                        $('.em-close-minimise').remove();
                    }

                }

                $(document).ready(function () {
                    checkurl();
                    $('#rt-mainbody-surround').children().addClass('mainemundus');
                    $('#rt-main').children().addClass('mainemundus');
                    $('#rt-main').children().children().addClass('mainemundus');

                    menuAction = document.querySelector('.em-menuaction');
                    headerNav = document.querySelector('#g-navigation .g-container,#g-header .g-container');
                    containerResult = document.querySelector('.container-result');
                    if (containerResult) {
                        setTimeout(() => {
                            $('.container-result').css('top', (headerNav.offsetHeight + menuAction.offsetHeight) + 'px');
                            $('.em-double-scroll-bar').css('top', (headerNav.offsetHeight + menuAction.offsetHeight + containerResult.offsetHeight - 2) + 'px');
                            $('#em-data th').css('top', (headerNav.offsetHeight + menuAction.offsetHeight + containerResult.offsetHeight) + 'px');
                        }, 2000);
                    }

                    const dataContainer = document.querySelector('.em-data-container')
                    if (dataContainer) {
                        DoubleScroll(document.querySelector('.em-data-container'));
                    }
                });
                window.parent.$("html, body").animate({scrollTop: 0}, 300);
            </script>


            <script>
                var selectDropdownContainer = document.querySelector('.selectAll');
                var countFiles = document.querySelector('#countCheckedCheckbox');

                if (selectDropdownContainer) {
                    selectDropdownContainer.style.display = 'none';

                    $('.selectDropdown').click(function () {
                        if (selectDropdownContainer.style.display === 'none') {
                            selectDropdownContainer.style.display = 'flex';
                        } else {
                            selectDropdownContainer.style.display = 'none';
                        }
                    });
                }

                $(document).click(function (e) {
                    var container = $(".selectDropdown");

                    if (!container.is(e.target) && container.has(e.target).length === 0 && selectDropdownContainer) {
                        selectDropdownContainer.style.display = 'none';
                    }
                });

                /* Options de sélection de tous les dossiers */
                function checkAllFiles() {
                    $('#em-check-all-all').prop('checked', true);

                    selectAllFiles();
                }

                /* Option de désélection de tous les dossiers */
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

                        countFilesBeforeAction('all').then((result) => {
                            document.querySelector('#count-all-files').innerHTML = ' (' + result + ')';
                            document.querySelector('#count-all-files').classList.remove('tw-hidden');
                        })

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


                $('.selectAll>span').click(function () {
                    $('.selectAll').slideUp();
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
                })

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
                        let countBlock = document.getElementById('countCheckedCheckbox');
                        if (countBlock.style.display === 'block') {
                            document.querySelector('.em-data-container table thead').style.top = (window.scrollY - containerResult - 8 + countBlock.offsetHeight) + 'px';
                        } else {
                            document.querySelector('.em-data-container table thead').style.top = (window.scrollY - containerResult - 4) + 'px';
                        }
                    } else {
                        document.querySelector('.em-data-container table thead').style.position = 'static';
                        document.querySelector('.em-data-container table thead').style.top = '0px';
                    }
                });
		        <?php endif; ?>
            </script>

        </div>
    </div>
</div>
</div>


<script type="text/javascript">
    var $ = jQuery.noConflict();

    var itemId = '<?php echo @$this->itemId;?>';
    var cfnum = '<?php echo @$this->cfnum;?>';
    var filterName = '<?php echo JText::_('COM_EMUNDUS_FILTERS_FILTER_NAME'); ?>';
    var filterEmpty = '<?php echo JText::_('COM_EMUNDUS_FILTERS_ALERT_EMPTY_FILTER'); ?>';
    var nodelete = '<?php echo JText::_('COM_EMUNDUS_FILTERS_CAN_NOT_DELETE_FILTER'); ?>';
    var jtextArray = ['<?php echo JText::_('COM_EMUNDUS_COMMENTS_ENTER_COMMENT'); ?>',
        '<?php echo JText::_('COM_EMUNDUS_FORM_TITLE'); ?>',
        '<?php echo JText::_('COM_EMUNDUS_COMMENTS_SENT'); ?>'];
    var loading = '<?php echo JURI::base() . 'media/com_emundus/images/icones/loader.gif'; ?>';
    var loadingLine = '<?php echo JURI::base() . 'media/com_emundus/images/icones/loader-line.gif'; ?>';
    $(document).ready(function () {
        $('.chzn-select').chosen({width: '75%'});
        //refreshFilter();
        reloadActions();
    })

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

</script>


