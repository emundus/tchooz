<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Factories\Mapping\MappingFactory;
use Tchooz\Repositories\Mapping\MappingRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Automation\ConditionRegistry;use Tchooz\Services\Mapping\MappingTransformationsRegistry;

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = LanguageHelper::getLanguages();

if (count($languages) > 1)
{
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang   = $m_translations->getDefaultLanguage()->lang_code;
}
else
{
	$many_languages = '0';
	$default_lang   = $current_lang;
}
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($this->user->id);

$app = Factory::getApplication();
$mappingId = $app->getInput()->getInt('id', 0);

if (!empty($mappingId))
{
	$repository = new MappingRepository();
	$mapping = $repository->getById($mappingId);
}
else
{
	$mapping = new MappingEntity(0, Text::_('COM_EMUNDUS_NEW_MAPPING'), 0, '', []);
}

$mappingFactory = new MappingFactory();

$synchronizersRepository = new SynchronizerRepository();
$synchronizers = $synchronizersRepository->getAll(['published' => 1, 'enabled' => 1], 0);

$mappingTransformationsRegistry = new MappingTransformationsRegistry();
$conditionsRegistry = new ConditionRegistry();
$dataResolvers = array_filter($conditionsRegistry->getAvailableConditionSchemas([
    'storedValues' => array_map(function ($row) {
        return $row->getSourceField();
    }, $mapping->getRows()),
]), function ($resolver) {
	return $resolver['targetType'] !== 'context_data' && $resolver['targetType'] !== 'group_data';
});
$dataResolvers = array_values($dataResolvers);

$datas = [
	'mapping' 		     => $mapping->serialize(),
	'fields'             => array_map(function ($field) {
		assert($field instanceof Field);
		return $field->toSchema();
	}, $mappingFactory->getFormFields()),
    'dataResolvers'      => $dataResolvers,
    'transformers'       => $mappingTransformationsRegistry->getTransformersSchemas(),
	'shortLang'          => $short_lang,
	'currentLanguage'    => $current_lang,
	'defaultLang'        => $default_lang,
	'manyLanguages'      => $many_languages,
	'coordinatorAccess'  => $coordinator_access,
	'sysadminAccess'     => $sysadmin_access,
];

Text::script('COM_EMUNDUS_MAPPINGS');
Text::script('COM_EMUNDUS_MAPPING');
Text::script('COM_EMUNDUS_MAPPING_ADD');
Text::script('COM_EMUNDUS_MAPPING_EDIT');
Text::script('COM_EMUNDUS_MAPPING_EDIT_INTRO');
Text::script('COM_EMUNDUS_MAPPING_FIELD_TARGET_OBJECT_LABEL');
Text::script('COM_EMUNDUS_MAPPING_SOURCE_HEADER');
Text::script('COM_EMUNDUS_MAPPING_TARGET_HEADER');
Text::script('COM_EMUNDUS_MAPPING_ADD_ROW_BUTTON');
Text::script('COM_EMUNDUS_TRANSFORMATION_TYPE_SELECT_LABEL');
Text::script('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_VALUES_PARAMETERS_GROUP_LABEL');
Text::script('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_VALUES_PARAMETER_MAP_FROM_LABEL');
Text::script('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_VALUES_PARAMETER_MAP_TO_LABEL');
Text::script('COM_EMUNDUS_MAPPING_ROW_TRANSFORMATIONS_MODAL_TITLE');
Text::script('COM_EMUNDUS_BTN_ADD_TRANSFORMATION');
Text::script('COM_EMUNDUS_MAPPING_SAVED_SUCCESSFULLY');
Text::script('COM_EMUNDUS_MAPPING_ROW_NO_TRANSFORMATIONS_DEFINED');
Text::script('COM_EMUNDUS_MAPPING_ROW_EDIT_TRANSFORMATIONS_TOOLTIP');
Text::script('COM_EMUNDUS_MAPPING_ROW_TRANSFORMATIONS_MODAL_DESCRIPTION');
Text::script('COM_EMUNDUS_MAPPING_SAVE_ERROR');
?>

<div id="em-component-vue"
     component="Mapping/MappingEdit"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>


<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
