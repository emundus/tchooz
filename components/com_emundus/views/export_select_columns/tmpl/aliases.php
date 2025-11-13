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

use Joomla\CMS\Language\Text;

Text::script('BACK');
Text::script('COM_EMUNDUS_ONBOARD_ALIAS_LIST');
Text::script('SEARCH');
Text::script('COM_EMUNDUS_ALIAS');
Text::script('COM_EMUNDUS_ALIAS_ELEMENTS');
Text::script('COM_EMUNDUS_ALIAS_COPIED');
Text::script('COM_EMUNDUS_ONBOARD_ALIASES_FILTER_PROFILES');
Text::script('COM_EMUNDUS_ALIAS_PROFILE_ALL');
Text::script('COM_EMUNDUS_ONBOARD_NOALIAS');
Text::script('COM_EMUNDUS_ONBOARD_ALIASES_EXPORTS_EXCEL');
Text::script('COM_EMUNDUS_ONBOARD_COPY_ALIAS');

Text::script('COM_EMUNDUS_ONBOARD_FORM_TAGS_LIST');
Text::script('COM_EMUNDUS_ONBOARD_NOTAGS');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_CAMPAIGN_ALL');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE_ALL');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE_APPLICANT');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE_MANAGEMENT');
Text::script('COM_EMUNDUS_ONBOARD_LABEL_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_STEPS');
Text::script('COM_EMUNDUS_ONBOARD_TAGS_FILTER_STEPS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_COPY_TAG');
Text::script('COM_EMUNDUS_TAG_COPIED');
Text::script('COM_EMUNDUS_ONBOARD_ALIAS_TAGS_LIST');
Text::script('COM_EMUNDUS_ONBOARD_GENERAL_TAGS_LIST');
Text::script('COM_EMUNDUS_TAG_DESCRIPTION');
Text::script('COM_EMUNDUS_REGISTRANTS_FILE_READY');
Text::script('COM_EMUNDUS_ONBOARD_ALIAS_TAGS_LIST_INTRO');

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em-component-vue"
     component="Aliases"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
