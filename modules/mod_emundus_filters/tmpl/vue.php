<?php
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;

Text::script('MOD_EMUNDUS_FILTERS');
Text::script('MOD_EMUNDUS_FILTERS_SELECT_FILTER');
Text::script('MOD_EMUNDUS_FILTERS_SELECT_FILTER_LABEL');
Text::script('MOD_EMUNDUS_FILTERS_ADD_FILTER');
Text::script('MOD_EMUNDUS_FILTERS_SELECT_VALUE');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS_NOT');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_CONTAINS');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_DOES_NOT_CONTAIN');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS_ONE_OF');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS_NOT_ONE_OF');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_AND');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_OR');
Text::script('MOD_EMUNDUS_FILTERS_PLEASE_SELECT');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_SEARCH');
Text::script('MOD_EMUNDUS_FILTERS_APPLY_FILTERS');
Text::script('MOD_EMUNDUS_FILTERS_CLEAR_FILTERS');
Text::script('MOD_EMUNDUS_FILTERS_SAVE_FILTERS');
Text::script('MOD_EMUNDUS_FILTERS_SAVE_FILTER_NAME');
Text::script('MOD_EMUNDUS_FILTERS_SAVED_FILTERS');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_SUPERIOR_TO');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_SUPERIOR_OR_EQUAL_TO');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_INFERIOR_TO');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_INFERIOR_OR_EQUAL_TO');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_BETWEEN');
Text::script('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_NOT_BETWEEN');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_IN');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_ALL');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_FIRSTNAME');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_LASTNAME');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_USERNAME');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_EMAIL');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_FNUM');
Text::script('MOD_EMUNDUS_FILTERS_SCOPE_ID');
Text::script('MOD_EMUNDUS_FILTERS_GLOBAL_SEARCH_PLACEHOLDER');
Text::script('MOD_EMUNDUS_FILTERS_MORE_VALUES');

?>
<div
        id="em-filters-vue"
        data-module-id="<?= $module->id ?>"
        data-applied-filters='<?= base64_encode(json_encode($applied_filters)) ?>'
        data-filters='<?= base64_encode(json_encode($filters)) ?>'
        data-quick-search-filters='<?= base64_encode(json_encode($quick_search_filters)) ?>'
        data-count-filter-values='<?= $params->get('count_filter_values') ?>'
></div>

<script src="media/mod_emundus_filters/app.js"></script>
