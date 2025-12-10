<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

Text::script('BACK');
Text::script('COM_EMUNDUS_ONBOARD_EXPORTS_LIST');
Text::script('SEARCH');

Text::script('COM_EMUNDUS_ONBOARD_EXPORTS_LIST_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NOEXPORTS');
Text::script('COM_EMUNDUS_ONBOARD_EXPORTS_DOWNLOAD');
Text::script('COM_EMUNDUS_EXPORTS_STATUS');
Text::script('COM_EMUNDUS_EXPORTS_STATUS_IN_PROGRESS');
Text::script('COM_EMUNDUS_EXPORTS_STATUS_COMPLETED');
Text::script('COM_EMUNDUS_EXPORTS_FORMAT');
Text::script('COM_EMUNDUS_EXPORTS_HITS');
Text::script('COM_EMUNDUS_REGISTRANTS_FILE_READY');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_EXPORT_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

$app = Factory::getApplication();
$lang = $app->getLanguage();

$short_lang   = substr($lang->getTag(), 0, 2);
?>

<div id="em-component-vue"
     component="Exports/ExportsList"
     shortlang="<?php echo $short_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
