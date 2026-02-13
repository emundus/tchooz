<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Enums\List\ListDisplayEnum;
use Tchooz\Factories\Mapping\MappingFactory;
use Tchooz\Factories\Synchronizer\SynchronizerFactory;
use Tchooz\Repositories\Mapping\MappingRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Response;
use Tchooz\Services\Mapping\ApiMapDataInterface;
use Tchooz\Traits\TraitResponse;

class EmundusControllermapping extends BaseController
{
	use TraitResponse;

	private MappingRepository $repository;

	const MAX_LIMIT = 100;

	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->repository = new MappingRepository();
	}

	public function getMappings(): void
	{
		$response = ['status' => false, 'data' => null, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if(EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$filters = $this->input->get('filter', [], 'array');
			$limit = $this->input->getInt('lim', 10);
			$page = $this->input->getInt('page', 1);

			if ($limit < 1) {
				$limit = 10;
			} elseif ($limit > self::MAX_LIMIT) {
				$limit = self::MAX_LIMIT;
			}

			$count = $this->repository->count($filters);
			$mappings = !empty($count) ? $this->repository->getAll($filters, $limit, $page) : [];
			$synchronizerRepository = new SynchronizerRepository();

			$response = [
				'status'  => true,
				'data'    => [
					'count' => $count,
					'datas' => array_map(function($mapping) {
						assert($mapping instanceof MappingEntity);

						$serializedMapping = $mapping->serialize();
						$serializedMapping['label'] = [
							'fr' => $mapping->getLabel(),
							'en' => $mapping->getLabel()
						];

						$synchronizerLabel = '';
						if (!empty($mapping->getSynchronizerId()) && $mapping->getSynchronizerId() > 0) {
							$synchronizerRepository = new SynchronizerRepository();
							$synchronizer = $synchronizerRepository->getById($mapping->getSynchronizerId());
							if (!empty($synchronizer)) {
								$synchronizerLabel = $synchronizer->getName();
							}
						}

						$serializedMapping['additional_columns'][] = new AdditionalColumn(
							Text::_('COM_EMUNDUS_MAPPING_SYNCHRONIZER_SERVICE'),
							'',
							ListDisplayEnum::ALL,
							'synchronizer_id',
							$synchronizerLabel
						);

						return $serializedMapping;
					}, $mappings)
				],
				'message' => Text::_('COM_EMUNDUS_MAPPINGS_RETRIEVED_SUCCESSFULLY'),
				'code'    => 200
			];
		}

		$this->sendJsonResponse($response);
	}

	/**
	 * Flush (create or update) a mapping
	 *
	 * @return void
	 */
	public function flush(): void
	{
		$response = ['status' => false, 'data' => null, 'message' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$json = $this->app->input->getString('mapping');
			if (!empty($json))
			{
				$mappingEntity = MappingFactory::fromJson($json);

				if (!empty($mappingEntity))
				{
					$flushed = $this->repository->flush($mappingEntity);
					if ($flushed)
					{
						$response = [
							'status'  => true,
							'data'    => null,
							'message' => Text::_('COM_EMUNDUS_MAPPING_SAVED_SUCCESSFULLY'),
							'code'    => 200,
							'redirect' => Route::_('/index.php?option=com_emundus&view=mapping&layout=edit&id=' . $mappingEntity->getId())
						];
					}
					else
					{
						$response = [
							'status'  => false,
							'data'    => null,
							'message' => Text::_('COM_EMUNDUS_MAPPING_SAVE_FAILED'),
							'code'    => 500
						];
					}
				}
			}
			else
			{
				$response = [
					'status'  => false,
					'data'    => null,
					'message' => Text::_('MISSING_PARAMETERS'),
					'code'    => 400
				];
			}
		}

		$this->sendJsonResponse($response);
	}

	/**
	 * @return void
	 */
	public function getDatabaseJoinElementColumnsOptions(): void
	{
		$response = new Response(false, Text::_('ACCESS_DENIED'), 403);

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$sourceType = $this->input->getString('source_type', '');
			$sourceField = $this->input->getString('source_field', '');

			if ($sourceType === \Tchooz\Enums\Automation\ConditionTargetTypeEnum::FORMDATA->value)
			{
				list($formId, $elementId) = explode('.', $sourceField);
				$elements = \EmundusHelperEvents::getFormElements((int)$formId, (int)$elementId, true, [], []);
				$element = $elements[0] ?? null;
			} else if
			($sourceType === \Tchooz\Enums\Automation\ConditionTargetTypeEnum::ALIASDATA->value)
			{
				$fabrikHelper = new \EmundusHelperFabrik();
				$elements = $fabrikHelper::getElementsByAlias($sourceField);
				$element = $elements[0] ?? null;
			}

			if (!empty($element) && $element->plugin === 'databasejoin')
			{
				$options = [];
				$params = json_decode($element->params);

				// select columns from $params['join_db_name'] table
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = "SHOW COLUMNS FROM " . $db->quoteName($params->join_db_name);
				$db->setQuery($query);
				$columns = $db->loadObjectList();

				foreach ($columns as $column)
				{
					$options[] = new ChoiceFieldValue(
						$column->Field,
						$column->Field
					);
				}

				$response = new Response(
					true,
					Text::_('COM_EMUNDUS_MAPPING_DATABASEJOIN_ELEMENT_COLUMNS_RETRIEVED_SUCCESSFULLY'),
					200,
					array_map(function ($option) {
						return $option->toSchema();
					}, $options)
				);
			}
			else
			{
				$response = new Response(false, Text::_('COM_EMUNDUS_MAPPING_INVALID_DATABASEJOIN_ELEMENT'), 400);
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getMappingObjectsOptions(): void
	{
		$response = new Response(false, Text::_('ACCESS_DENIED'), 403);

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$synchronizerId = $this->input->getInt('synchronizer_id', 0);
			$synchronizerRepository = new SynchronizerRepository();
			$synchronizerEntity = $synchronizerRepository->getById($synchronizerId);

			if (!empty($synchronizerEntity))
			{
				$factory = new SynchronizerFactory();
				$synchronizer = $factory->getApiInstance($synchronizerEntity);

				if (!empty($synchronizer) && $synchronizer instanceof ApiMapDataInterface)
				{
					$objectDefinitions = $synchronizer->getMappingObjectsDefinitions();
					$options = [];

					foreach ($objectDefinitions as $objectDefinition)
					{
						$options[] = [
							'value' => $objectDefinition->getName(),
							'label' => $objectDefinition->getLabel(),
							'requiredFields' => array_map(fn($field) => $field->toSchema(), $objectDefinition->getRequiredFields()),
						];
					}

					$response = new Response(
						true,
						Text::_('COM_EMUNDUS_MAPPING_OBJECTS_RETRIEVED_SUCCESSFULLY'),
						200,
						$options
					);
				}
				else
				{
					$response = new Response(false, Text::_('COM_EMUNDUS_MAPPING_SYNCHRONIZER_DOES_NOT_SUPPORT_MAPPING'), 400);
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function delete(): void
	{
		$response = new Response(false, Text::_('ACCESS_DENIED'), 403);

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->app->getIdentity()->id))
		{
			$mappingId = $this->input->getInt('id', 0);

			$deleted = $this->repository->delete($mappingId);

			if ($deleted)
			{
				$response = new Response(true, Text::_('COM_EMUNDUS_MAPPING_DELETED_SUCCESSFULLY'), 200);
			}
			else
			{
				$response = new Response(false, Text::_('COM_EMUNDUS_MAPPING_DELETE_FAILED'), 500);
			}
		}

		$this->sendJsonResponse($response);
	}
}
