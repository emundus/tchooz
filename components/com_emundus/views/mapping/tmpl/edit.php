<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Factories\Mapping\MappingFactory;
use Tchooz\Repositories\Mapping\MappingRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Automation\ConditionRegistry;use Tchooz\Services\Mapping\MappingTransformationsRegistry;

$app = Factory::getApplication();
$mappingId = $app->getInput()->getInt('id', 0);

if (!empty($mappingId))
{
	$repository = new MappingRepository();
	$mapping = $repository->getById($mappingId);
}
else
{
	$mapping = new MappingEntity(0, Text::_('COM_EMUNDUS_NEW_MAPPING'), 0, '', [], []);
}

$mappingFactory = new MappingFactory();

$synchronizersRepository = new SynchronizerRepository();
$synchronizers = $synchronizersRepository->getAll(['published' => 1, 'enabled' => 1], 0);

$mappingTransformationsRegistry = new MappingTransformationsRegistry();
$conditionsRegistry = new ConditionRegistry();

$storedValuesByType = [];
foreach ($mapping->getRows() as $row)
{
    if (!isset($storedValuesByType[$row->getSourceType()->value]))
    {
        $storedValuesByType[$row->getSourceType()->value] = [];
    }
    $storedValuesByType[$row->getSourceType()->value][] = $row->getSourceField();
}

$dataResolvers = array_filter($conditionsRegistry->getAvailableConditionSchemas([
    'storedValues' => $storedValuesByType,
]), function ($resolver) {
	return $resolver['targetType'] !== 'context_data' && $resolver['targetType'] !== 'group_data';
});
$dataResolvers = array_values($dataResolvers);

$defaultData = LayoutFactory::prepareVueData();
$datas = [
    'mapping' 		     => $mapping->serialize(),
    'fields'             => array_map(function ($field) {
        assert($field instanceof Field);
        return $field->toSchema();
    }, $mappingFactory->getFormFields($mapping)),
    'dataResolvers'      => $dataResolvers,
    'transformers'       => $mappingTransformationsRegistry->getTransformersSchemas(),
    ...$defaultData
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
Text::script('COM_EMUNDUS_MAPPING_OTHER_PARAMETERS');
Text::script('COM_EMUNDUS_MAPPING_DELETE_CONFIRM');
?>

<div id="em-component-vue"
     component="Mapping/MappingEdit"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>


<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
