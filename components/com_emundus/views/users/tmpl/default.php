<?php
/**
 * Created by PhpStorm.
 * User: yoan
 * Date: 22/05/14
 * Time: 10:16
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

$app      = Factory::getApplication();
$document = $app->getDocument();
$wa       = $document->getWebAssetManager();
$wa->registerAndUseScript('com_emundus/jquery', 'jquery/jquery.min.js');

?>

<div>
    <div>
        <div class="col-md-3 side-panel">
            <div class="panel panel-info em-containerFilter" id="em-user-filters">
                <div class="panel-heading em-containerFilter-heading !tw-bg-profile-full">
                    <div>
                        <h3 class="panel-title"><?php echo JText::_('COM_EMUNDUS_FILTERS') ?></h3> &ensp;&ensp;
                    </div>
                    <div>
                        <label for="clear-search" class="tw-top-0.5 tw-relative">
                            <span class="material-symbols-outlined em-pointer em-color-white"
                                  title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_CLEAR_BTN'); ?>">filter_alt_off</span>
                        </label>

                        <input type="button" style="display: none" id="clear-search" name="clear-search"
                               title="<?php echo JText::_('COM_EMUNDUS_ACTIONS_CLEAR_BTN'); ?>"/>
                    </div>
                </div>
                <div class="panel-body em-containerFilter-body">

                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-appli-menu">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo JText::_('APPLICATIONS_ACTIONS') ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                    <div class="list-group">
                    </div>
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
        <div class="panel panel-default">
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="em-modal-actions" style="z-index:99999" tabindex="-1" role="dialog"
     aria-labelledby="em-modal-actions" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header em-flex-row-reverse em-flex-space-between">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"
                    id="em-modal-actions-title"><?php echo JText::_('COM_EMUNDUS_FORM_TITLE'); ?></h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger"
                        data-dismiss="modal"><?php echo JText::_('COM_EMUNDUS_ACTIONS_CANCEL') ?></button>
                <button type="button" class="btn btn-success"><?php echo JText::_('COM_EMUNDUS_OK'); ?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="em-modal-form" style="z-index:99999" tabindex="-1" role="dialog"
     aria-labelledby="em-modal-actions" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="em-modal-actions-title"><?php echo JText::_('COM_EMUNDUS_LOADING'); ?></h4>
            </div>
            <div class="modal-body">
                <img src="<?php echo JURI::base(); ?>media/com_emundus/images/icones/loader-line.gif" alt="Loading...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger"
                        data-dismiss="modal"><?php echo JText::_('COM_EMUNDUS_ACTIONS_CANCEL') ?></button>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    var $ = jQuery.noConflict();

    var itemId = "<?php echo $this->itemId;?>";
    var filterName = "<?php echo JText::_('COM_EMUNDUS_FILTERS_FILTER_NAME');?>";
    var filterEmpty = "<?php echo JText::_('COM_EMUNDUS_FILTERS_ALERT_EMPTY_FILTER');?>";
    var nodelete = "<?php echo JText::_('COM_EMUNDUS_FILTERS_CAN_NOT_DELETE_FILTER');?>";
    var jtextArray = ["<?php echo JText::_('COM_EMUNDUS_COMMENTS_ENTER_COMMENT')?>",
        "<?php echo JText::_('COM_EMUNDUS_FORM_TITLE')?>",
        "<?php echo JText::_('COM_EMUNDUS_COMMENTS_SENT')?>"];
    var loading = "<?php echo JURI::base() . 'media/com_emundus/images/icones/loader.gif'?>";
    var loadingLine = "<?php echo JURI::base() . 'media/com_emundus/images/icones/loader-line.gif'?>";
    $(document).ready(function () {
        $('#rt-mainbody-surround').children().addClass('mainemundus');
        $('#rt-main').children().addClass('mainemundus');
        $('#rt-main').children().children().addClass('mainemundus');

        $('.chzn-select').chosen({width: '75%'});
        $('body').on('hidden.bs.modal', '.modal', function () {
            var itemid = getCookie("application_itemid");
            $('#em-appli-menu .list-group-item#' + itemid).trigger('click');
            $(this).removeData('bs.modal');
            $('#em-modal-form .modal-content').html('<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button><h4 class="modal-title" id="em-modal-actions-title"><?php echo JText::_('COM_EMUNDUS_LOADING');?></h4></div><div class="modal-body"><img src="<?php echo JURI::base(); ?>media/com_emundus/images/icones/loader-line.gif"></div><div class="modal-footer"><button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo JText::_('COM_EMUNDUS_ACTIONS_CANCEL')?></button></div>');
        });
    });
    reloadActions('files');

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
</script>
