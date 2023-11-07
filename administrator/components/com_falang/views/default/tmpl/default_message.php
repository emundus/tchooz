<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

$state			= $this->get('State');
$message1		= $state->get('message');
$message2		= $state->get('extension.message');

//use for message
$document = Factory::getApplication()->getDocument();
$wa = $document->getWebAssetManager();
$wa->registerAndUseScript('toast', 'components/com_falang/assets/js/toast.js', [], ['defer' => true], ['core'])

?>
    <script type="text/javascript">
        toastr.options = { "progressBar": true, "positionClass": "toast-top-center","showDuration": "300","hideDuration": "500","timeOut": "3500"};

    </script>
<?php if($message1) { ?>
    <script type="text/javascript">
        toastr.success('<?php echo Text::_($message1) ?>');
    </script>
<?php } ?>
<?php if($message2) { ?>
    <script type="text/javascript">
        toastr.success('<?php echo Text::_($message2) ?>');
    </script>
<?php } ?>