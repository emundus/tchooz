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
use Tchooz\Enums\Import\ImportErrorCodeEnum;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Services\Import\EntityImporterRegistry;

Text::script('BACK');
Text::script('SEARCH');

Text::script('COM_EMUNDUS_ONBOARD_IMPORTS_LIST');
Text::script('COM_EMUNDUS_ONBOARD_IMPORTS_LIST_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NOIMPORTS');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');

Text::script('COM_EMUNDUS_IMPORTS_STATUS');
Text::script('COM_EMUNDUS_IMPORTS_STATUS_PROCESSING');
Text::script('COM_EMUNDUS_IMPORTS_STATUS_COMPLETED');
Text::script('COM_EMUNDUS_IMPORTS_STATUS_FAILED');
Text::script('COM_EMUNDUS_IMPORTS_STATUS_CANCELLED');

Text::script('COM_EMUNDUS_IMPORTS_SHOW_REPORT');
Text::script('COM_EMUNDUS_IMPORT_CANCEL');
Text::script('COM_EMUNDUS_IMPORTS_CANCEL_CONFIRM');
Text::script('COM_EMUNDUS_IMPORTS_NO_REPORT');
Text::script('COM_EMUNDUS_IMPORT_REPORT_TITLE_FOR');

Text::script('COM_EMUNDUS_IMPORT_REPORT_TITLE');
Text::script('COM_EMUNDUS_IMPORT_ROWS_VALID');
Text::script('COM_EMUNDUS_IMPORT_ROWS_CREATED');
Text::script('COM_EMUNDUS_IMPORT_ROWS_UPDATED');
Text::script('COM_EMUNDUS_IMPORT_ROWS_SKIPPED');
Text::script('COM_EMUNDUS_IMPORT_ROWS_FAILED');
Text::script('COM_EMUNDUS_IMPORT_ERRORS_TITLE');
Text::script('COM_EMUNDUS_IMPORT_ERRORS_GROUP_COUNT');
Text::script('COM_EMUNDUS_IMPORT_ERRORS_SAMPLE_ROW');
Text::script('COM_EMUNDUS_IMPORT_ERRORS_SAMPLE_ROWS');
Text::script('COM_EMUNDUS_IMPORT_ERROR_EXAMPLE_VALUE');
Text::script('COM_EMUNDUS_IMPORT_ROW_NUMBER');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_VALID');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_TO_BE_CREATED');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_CREATED');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_TO_BE_UPDATED');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_UPDATED');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_SKIPPED');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_FAILED_ONE_ELEMENT');
Text::script('COM_EMUNDUS_IMPORT_SUMMARY_FAILED_SEVERAL_ELEMENTS');
Text::script('COM_EMUNDUS_IMPORT_FAILED_TRUNCATED');
Text::script('COM_EMUNDUS_IMPORT_UNKNOWN_HEADERS_WARNING');
Text::script('COM_EMUNDUS_IMPORT_DOWNLOAD_REPORT');
foreach (ImportErrorCodeEnum::cases() as $importErrorCode)
{
	Text::script($importErrorCode->value);
}

// Field labels are translation keys carried in the error params; register them
// so the frontend can resolve them when rendering the failed-row messages.
$importRegistry = EntityImporterRegistry::default();
foreach ($importRegistry->getTypes() as $importType)
{
	$importColumnMap = $importRegistry->get($importType)->getColumnMap();
	foreach ($importColumnMap->canonicalFields() as $importCanonical)
	{
		$importFieldLabel = $importColumnMap->getDescriptor($importCanonical)?->label;
		if (!empty($importFieldLabel))
		{
			Text::script($importFieldLabel);
		}
	}
}

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="Imports/ImportsList"
     shortlang="<?php echo $data['short_lang'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>