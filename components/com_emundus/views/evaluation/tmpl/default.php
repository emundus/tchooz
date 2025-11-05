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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

if ($this->open_file_in_modal)
{
	Text::script('COM_EMUNDUS_FILES_EVALUATION');
	Text::script('COM_EMUNDUS_FILES_TO_EVALUATE');
	Text::script('COM_EMUNDUS_FILES_EVALUATED');
	Text::script('COM_EMUNDUS_ONBOARD_FILE');
	Text::script('COM_EMUNDUS_ONBOARD_STATUS');
	Text::script('COM_EMUNDUS_FILES_APPLICANT_FILE');
	Text::script('COM_EMUNDUS_FILES_ATTACHMENTS');
	Text::script('COM_EMUNDUS_FILES_COMMENTS');
	Text::script('COM_EMUNDUS_FILES_MESSENGER');
	Text::script('COM_EMUNDUS_ONBOARD_NOFILES');
	Text::script('COM_EMUNDUS_FILES_ELEMENT_SELECTED');
	Text::script('COM_EMUNDUS_FILES_ELEMENTS_SELECTED');
	Text::script('COM_EMUNDUS_FILES_UNSELECT');
	Text::script('COM_EMUNDUS_FILES_OPEN_IN_NEW_TAB');
	Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS');
	Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC');
	Text::script('COM_EMUNDUS_FILES_DISPLAY_PAGE');
	Text::script('COM_EMUNDUS_FILES_NEXT_PAGE');
	Text::script('COM_EMUNDUS_FILES_PAGE');
	Text::script('COM_EMUNDUS_FILES_TOTAL');
	Text::script('COM_EMUNDUS_FILES_ALL');
	Text::script('COM_EMUNDUS_FILES_ADD_COMMENT');
	Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_COMMENTS');
	Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_COMMENTS_DESC');
	Text::script('COM_EMUNDUS_FILES_COMMENT_TITLE');
	Text::script('COM_EMUNDUS_FILES_COMMENT_BODY');
	Text::script('COM_EMUNDUS_FILES_VALIDATE_COMMENT');
	Text::script('COM_EMUNDUS_FILES_COMMENT_DELETE');
	Text::script('COM_EMUNDUS_FILES_ASSOCS');
	Text::script('COM_EMUNDUS_FILES_TAGS');
	Text::script('COM_EMUNDUS_FILES_PAGE_ON');
	Text::script('COM_EMUNDUS_ERROR_OCCURED');
	Text::script('COM_EMUNDUS_ACTIONS_CANCEL');
	Text::script('COM_EMUNDUS_OK');
	Text::script('COM_EMUNDUS_FILES_FILTER_NO_ELEMENTS_FOUND');
	Text::script('COM_EMUNDUS_EVALUATION_NO_FORM_FOUND');
}
?>

<input type="hidden" id="view" name="view" value="evaluation">
<div class="tw-h-full">
    <div class="tw-h-full">
        <div class="col-md-3 side-panel" style="height: calc(100vh - 139px);overflow-y: auto;">
            <div class="panel panel-info em-containerFilter" id="em-files-filters">
                <div class="panel-heading em-containerFilter-heading !tw-bg-profile-full">
                    <div>
                        <h3 class="panel-title"><?php echo Text::_('COM_EMUNDUS_FILTERS') ?></h3> &ensp;&ensp;
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

                    <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-appli-menu">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h1 class="panel-title"><?php echo Text::_('COM_EMUNDUS_APPLICATION_ACTIONS') ?></h1>
                </div>
                <div class="panel-body em-hide-body">
                    <div class="list-group">
                    </div>
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-synthesis">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo Text::_('COM_EMUNDUS_APPLICATION_SYNTHESIS') ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                </div>
            </div>

            <div class="panel panel-info em-hide" id="em-assoc-files">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo Text::_('COM_EMUNDUS_ACCESS_LINKED_APPLICATION_FILES'); ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                </div>
            </div>


            <div class="clearfix"></div>
            <div class="panel panel-info em-hide" id="em-last-open">
                <div class="panel-heading em-hide-heading !tw-bg-profile-full">
                    <h3 class="panel-title"><?php echo Text::_('COM_EMUNDUS_APPLICATION_LAST_OPEN_FILES'); ?></h3>
                </div>
                <div class="panel-body em-hide-body">
                    <div class="list-group">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9 main-panel tw-h-full">
            <div id="em-hide-filters" class="em-close-filter" data-toggle="tooltip" data-placement="top"
                 title=<?php echo Text::_('COM_EMUNDUS_FILTERS_HIDE_FILTER'); ?>">
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
        <div class="panel panel-default"></div>

		<?php

		if ($this->open_file_in_modal)
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
			$hash = EmundusHelperCache::getCurrentGitHash();

			$applicant = !\EmundusHelperAccess::asPartnerAccessLevel($this->_user->id);
			if (!$applicant)
			{
				$emundusUser     = Factory::getApplication()->getSession()->get('emundusUser');
				$current_profile = $emundusUser->profile;

				if (!class_exists('EmundusModelProfile'))
				{
					require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
				}
				$m_profile          = new \EmundusModelProfile();
				$applicant_profiles = $m_profile->getApplicantsProfilesArray();

				if (in_array($current_profile, $applicant_profiles))
				{
					$applicant = true;
				}
			}

			$datas = [
				'context'   => 'files',
				'user'      => $this->_user->id,
				'fullname'  => $this->_user->name,
				'applicant' => $applicant,
				'ratio'     => $this->modal_ratio,
				'tabs'      => $this->modal_tabs,
				'type'      => 'evaluation',
				'base'      => Uri::base(),
                'shortLang' => substr(Factory::getLanguage()->getTag(), 0, 2),
			];
			?>
            <div id="em-files"
                 component="ApplicationSingle"
                 data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
            >
            </div>

            <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
            <script>
                function clickOpenfile(fnum, fnums = '') {
                    fnums = fnums.split('|');

                    var event = new CustomEvent('openSingleApplicationWithFnum', {detail: {fnum: fnum, fnums: fnums}});
                    window.dispatchEvent(event);
                }
            </script>
			<?php
		}
		?>
    </div>
</div>
</div>


<script type="text/javascript">
    var $ = jQuery.noConflict();

    var itemId = '<?php echo $this->itemId;?>';
    var cfnum = '<?php echo $this->cfnum;?>';
    var filterName = '<?php echo Text::_('COM_EMUNDUS_FILTERS_FILTER_NAME'); ?>';
    var filterEmpty = '<?php echo Text::_('COM_EMUNDUS_FILTERS_ALERT_EMPTY_FILTER'); ?>';
    var nodelete = '<?php echo Text::_('COM_EMUNDUS_FILTERS_CAN_NOT_DELETE_FILTER'); ?>';
    var jtextArray = ['<?php echo Text::_('COM_EMUNDUS_COMMENTS_ENTER_COMMENT'); ?>',
        '<?php echo Text::_('COM_EMUNDUS_FORM_TITLE'); ?>',
        '<?php echo Text::_('COM_EMUNDUS_COMMENTS_SENT'); ?>'];
    var loading = '<?php echo JURI::base() . 'media/com_emundus/images/icones/loader.gif'; ?>';
    var loadingLine = '<?php echo JURI::base() . 'media/com_emundus/images/icones/loader-line.gif'; ?>';

    function checkurl() {
        var url = $(location).attr('href');

        url = url.split("#");
        $('.alert.alert-warning').remove();

        if (url[1] != null && url[1].length >= 20) {
            // Check if the url contains open keyword
            if(url[1].indexOf("open") === -1) {
                $('.em-close-minimise').remove();
                return;
            }

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
        $('.chzn-select').chosen({width: '75%'});
        refreshFilter();
        reloadActions();
    })

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

</script>