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

?>
<input type="hidden" id="view" name="view" value="decision">
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 side-panel">
            <div class="panel panel-info em-containerFilter" id="em-files-filters">
                <div class="panel-heading em-containerFilter-heading !tw-bg-profile-full">
                    <div>
                        <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_FILTERS') ?></h3> &ensp;&ensp;
                    </div>
                    <div class="buttons" style="float:right; margin-top:0px">
                        <label for="clear-search">
                            <img src="<?= JURI::base(); ?>media/com_emundus/images/icones/clear-filters.png"
                                 style="width: 25px;filter: invert(1);"/>
                        </label>

                        <input type="button" style="display: none" id="clear-search"
                               title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_CLEAR_BTN'); ?>"/>
                    </div>
                </div>
                <div class="panel-body em-containerFilter-body">
					<?php echo @$this->filters ?>
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
                <div class="panel-heading  em-hide-heading">
                    <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_APPLICATION_SYNTHESIS') ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-assoc-files">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_ACCESS_LINKED_APPLICATION_FILES') ?></h3>
                </div>
                <div class="panel-body em-hide-body">

                </div>
            </div>


            <div class="clearfix"></div>
            <div class="panel panel-info em-hide" id="em-last-open">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_APPLICATION_LAST_OPEN_FILES') ?></h3>
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
				<span class=" glyphicon glyphicon-chevron-left
            "></span>
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
        <div class="panel panel-default em-container-data"></div>
    </div>
</div>
</div>


<script type="text/javascript">

    var $ = jQuery.noConflict();

    var itemId = <?php echo $this->itemId;?>;
    var cfnum = '<?php echo @$this->cfnum;?>';
    var filterName = '<?php echo JText::_('COM_EMUNDUS_FILTERS_FILTER_NAME');?>';
    var filterEmpty = '<?php echo JText::_('COM_EMUNDUS_FILTERS_ALERT_EMPTY_FILTER');?>';
    var nodelete = '<?php echo JText::_('COM_EMUNDUS_FILTERS_CAN_NOT_DELETE_FILTER');?>';
    var jtextArray = ['<?php echo JText::_('COM_EMUNDUS_COMMENTS_ENTER_COMMENT')?>',
        '<?php echo JText::_('COM_EMUNDUS_FORM_TITLE')?>',
        '<?php echo JText::_('COM_EMUNDUS_COMMENTS_SENT')?>'];
    var loading = '<?php echo JURI::base() . 'media/com_emundus/images/icones/loader.gif'?>';
    var loadingLine = '<?php echo JURI::base() . 'media/com_emundus/images/icones/loader-line.gif'?>';
    $(document).ready(function () {
        $('.chzn-select').chosen({width: '75%'});
        refreshFilter();
        //search();
        //reloadData('evaluation');
        reloadActions();

        $('#rt-mainbody-surround').children().addClass('mainemundus');
        $('#rt-main').children().addClass('mainemundus');
        $('#rt-main').children().children().addClass('mainemundus');

        $(document).on("hidden.bs.modal", function (e) {
            $(e.target).removeData("bs.modal").find("#basicModal .modal-content").empty();
            $('#basicModal .modal-content').replaceWith('<div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button><h4 class="modal-title" id="myModalLabel"><?php echo JText::_("COM_EMUNDUS_LOADING");?></h4></div><div class="modal-body"><img src="<?php echo JURI::base(); ?>media/com_emundus/images/icones/loader-line.gif"></div><div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo JText::_("COM_EMUNDUS_ACTIONS_CANCEL")?></button></div></div>');
        });
    })

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

</script>
