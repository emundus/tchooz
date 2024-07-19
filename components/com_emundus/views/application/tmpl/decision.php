<?php
/**
 * @package      Joomla
 * @subpackage   eMundus
 * @link         http://www.emundus.fr
 * @copyright    Copyright (C) 2008 - 2014 eMundus SAS. All rights reserved.
 * @license      GNU/GPL
 * @author       eMundus SAS - Yoan Durand
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

Factory::getApplication()->getSession()->set('application_layout', 'comment');

?>
<div class="row">
    <div class="panel panel-default widget em-container-decision">
        <div class="panel-heading em-container-decision-heading">
            <h3 class="panel-title">
                <span class="glyphicon glyphicon-check"></span>
				<?php echo Text::_('COM_EMUNDUS_DECISION'); ?>
				<?php if (EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $this->fnum)): ?>
                    <a class="  clean" target="_blank"
                       href="<?php echo Uri::base(); ?>index.php?option=com_emundus&controller=evaluation&task=pdf_decision&user=<?php echo $this->student->id; ?>&fnum=<?php echo $this->fnum; ?>">
                        <button class="btn btn-default"
                                data-title="<?php echo Text::_('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF'); ?>"
                                data-toggle="tooltip" data-placement="bottom"
                                title="<?= Text::_('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF'); ?>"><span
                                    class="material-icons">file_download</span></button>
                    </a>
				<?php endif; ?>
                <div class="em-flex-row">
					<?php if (!empty($this->url_form)): ?>
                        <a href="<?php echo $this->url_form; ?>" target="_blank" class="em-flex-row"
                           title="<?php echo Text::_('COM_EMUNDUS_DECISION_OPEN_DECISION_FORM_IN_NEW_TAB_DESC'); ?>"><span
                                    class="material-icons">open_in_new</span></a>
					<?php endif; ?>
                </div>
            </h3>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-icons">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-icons">arrow_forward</span>
                </button>
            </div>
        </div>
        <div class="panel-body em-container-decision-body">
            <div class="content em-container-decision-body-content">
                <div class="embed-responsive">
                    <div class="form" id="form">
						<?php if (!empty($this->url_form)): ?>
                            <div class="em-w-100 em-flex-row" style="justify-content: center">
                                <div class="em-loader"></div>
                            </div>
                            <iframe id="iframe" class="embed-responsive-item" src="<?php echo $this->url_form; ?>"
                                    height="600" width="100%" onload="onLoadIframe(this)"></iframe>
						<?php else: ?>
                            <div class="em_no-form"><?php echo Text::_('COM_EMUNDUS_DECISION_NO_DECISION_FORM_SET'); ?></div>
						<?php endif; ?>
                    </div>
                </div>
                <div class="decisions" id="decisions" style="display: none">-----------</div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var iframeEl =  document.getElementById('iframe');
    if (iframeEl) {
        iframeEl.addEventListener('mouseleave', function () {
            resizeIframe(document.getElementById('iframe'));
        });

        iframeEl.addEventListener('mouseover', function () {
            resizeIframe(document.getElementById('iframe'));
        });
    }

    function onLoadIframe(iframe) {
        document.querySelector('.em-loader').classList.add('hidden');
        resizeIframe(iframe);

        var iframe = $('#iframe').contents();

        iframe.find("body").click(function(){
            if (!$('ul.dropdown-menu.open').hasClass('just-open')) {
                $('ul.dropdown-menu.open').hide();
                $('ul.dropdown-menu.open').removeClass('open');
            }
        });
    }

    function resizeIframe(obj) {
        obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
    }

    window.ScrollToTop = function () {
        $('html,body', window.document).animate({
            scrollTop: '0px'
        }, 'slow');
    };

    var url_evaluation = '<?php echo $this->url_evaluation; ?>';

    if (url_evaluation !== '') {
        $.ajax({
            type: "GET",
            url: url_evaluation,
            dataType: 'html',
            success: function (data) {
                $("#decisions").empty();
                $("#decisions").append(data);
            },
            error: function (jqXHR) {
                console.log(jqXHR.responseText);
            }
        });
    }

</script>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
