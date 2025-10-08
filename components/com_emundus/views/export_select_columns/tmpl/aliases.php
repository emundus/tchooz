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

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em-component-vue"
     component="Aliases"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
