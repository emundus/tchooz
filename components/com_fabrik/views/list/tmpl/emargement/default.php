<?php
/**
 * Bootstrap List Template - Default
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$language = $app->getLanguage();
$current_lang = $language->getTag();

$language->load('com_emundus', JPATH_SITE.'/components/com_emundus', $current_lang, true);

/* GET LOGO */
require_once JPATH_SITE . '/components/com_emundus/helpers/emails.php';
$logo = EmundusHelperEmails::getLogo(true);

$type = pathinfo($logo, PATHINFO_EXTENSION);
$data = file_get_contents(JPATH_SITE . '/images/custom/' . $logo);
$logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
/* END LOGO */
?>

<style type="text/css">
    @font-face {
        font-family: Inter;
        src: url('/media/com_emundus/css/fonts/google/Inter/Inter_18pt-Regular.ttf');
        font-weight: normal;
    }
    body{
        font-family: "Inter", Calibri,Candara,Segoe,Segoe UI,Optima,Arial,sans-serif;
    }
    .body table {
        width: 100%;
        border-collapse: collapse;
    }
    .body th, .body td {
        border: 1px solid black;
        padding: 8px;
        text-wrap: pretty;
        white-space: pre-wrap;
        word-wrap: break-word;
        word-break: break-all;
    }
</style>

<header>
    <table style="width: 100%">
        <tr>
            <td><img src="<?= $logo_base64; ?>" width="auto" height="60"/></td>
        </tr>
    </table>
</header>

<h1><?php echo Text::_('COM_EMUNDUS_EVENTS_EMARGEMENT'); ?></h1>

<?php foreach ($this->rows as $groupedBy => $group) : ?>
<div class="body">
    <table>
        <thead>
        <tr>
            <th colspan="2"><?php echo Text::_('COM_EMUNDUS_EVENTS_SLOT'); ?></th>
            <th colspan="2"><?php echo Text::_('COM_EMUNDUS_EVENTS_NAME'); ?></th>
            <th colspan="4"><?php echo Text::_('COM_EMUNDUS_EVENTS_SIGN'); ?></th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ($group as $row) : ?>
            <tr>
                <td colspan="2"><?php echo $row->data->jos_emundus_registrants___availability; ?></td>
                <td colspan="2" width="150"><?php echo $row->data->jos_emundus_registrants___user; ?></td>
                <td colspan="4" width="300"></td>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endforeach; ?>


