<?php

namespace Tchooz\Services\Automation;

use EmundusHelperCache;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Automation\AutomationRepository;
use Tchooz\Services\Automation\Condition\ConditionTargetResolverInterface;

class ConditionRegistry
{
	private CONST CONDITIONS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Services/Automation/Condition';

	/** @var ConditionTargetResolverInterface[] */
	private array $resolvers = [];

	private EmundusHelperCache $cache;

	public function __construct()
	{
		$this->cache = new EmundusHelperCache();
		$this->autoRegisterConditions();
	}

	public function autoRegisterConditions(): void
	{
		$resolvers = $this->cache->get('automation_condition_resolvers');

		if (empty($resolvers))
		{
			$files = glob(self::CONDITIONS_DIRECTORY . '/*ConditionResolver.php');
			if ($files) {
				foreach ($files as $file) {
					$className = 'Tchooz\\Services\\Automation\\Condition\\' . pathinfo($file, PATHINFO_FILENAME);
					if (class_exists($className)) {
						$reflection = new \ReflectionClass($className);
						if (!$reflection->isAbstract() && $reflection->implementsInterface(ConditionTargetResolverInterface::class)) {
							$instance = $reflection->newInstance();
							$this->register($instance);
						}
					}
				}

				$this->cache->set('automation_condition_resolvers', $this->resolvers);
			}
		} else {
			$this->resolvers = $resolvers;
		}
	}

	public function register(ConditionTargetResolverInterface $resolver): void
	{
		$this->resolvers[$resolver::getTargetType()] = $resolver;
	}

	public function getResolver(string $targetType): ?ConditionTargetResolverInterface
	{
		return $this->resolvers[$targetType] ?? null;
	}

	public function getAvailableConditionSchemas(array $contextFilters = []): array
	{
		$schemas = [];

		$availableResolvers = $this->resolvers;
		if (!empty($contextFilters) && !empty($contextFilters['eventName']))
		{
			$evtDefRegistry = new EventDefinitionRegistry();
			$eventDefinition = $evtDefRegistry->getEventDefinitionInstance($contextFilters['eventName']);

			if ($eventDefinition)
			{
				foreach ($availableResolvers as $key => $resolver)
				{
					if ($key === ConditionTargetTypeEnum::CONTEXTDATA->value) {
						if (empty($eventDefinition->getParameters()))
						{
							unset($availableResolvers[$key]);
						}

						continue;
					}

					$eventTargetTypes    = $eventDefinition->supportTargetPredefinitionsCategories();
					$resolverTargetTypes = $resolver->getAllowedActionTargetTypes();

					$eventTargetTypes = array_map(function ($targetType) {
						return $targetType->value;
					}, $eventTargetTypes);
					$resolverTargetTypes = array_map(function ($targetType) {
						return $targetType->value;
					}, $resolverTargetTypes);

					$intersect = array_intersect($eventTargetTypes, $resolverTargetTypes);
					if (empty($intersect))
					{
						unset($availableResolvers[$key]);
					}
				}
			}
			else
			{
				$availableResolvers = [];
			}
		}

		if (!empty($contextFilters['automationId']))
		{
			$automationRepo = new AutomationRepository();
			$automation = $automationRepo->getById($contextFilters['automationId']);

			foreach ($automation->getConditionsGroups() as $conditionGroup)
			{
				foreach ($conditionGroup->getConditions() as $condition)
				{
					assert($conditionGroup instanceof ConditionGroupEntity);
					foreach ($conditionGroup->getSubGroups() as $subGroup)
					{
						foreach ($subGroup->getConditions() as $subCondition)
						{
							if (!isset($contextFilters['storedValues'][$subCondition->getTargetType()->value])) {
								$contextFilters['storedValues'][$subCondition->getTargetType()->value] = [];
							}

							$contextFilters['storedValues'][$subCondition->getTargetType()->value][] = $subCondition->getField();
						}
					}

					if (!isset($contextFilters['storedValues'][$condition->getTargetType()->value])) {
						$contextFilters['storedValues'][$condition->getTargetType()->value] = [];
					}
					$contextFilters['storedValues'][$condition->getTargetType()->value][] = $condition->getField();
				}
			}
		}

		foreach ($availableResolvers as $type => $resolver) {
			$resolverFilters = $contextFilters;
			$resolverFilters['storedValues'] = $contextFilters['storedValues'][$type] ?? [];

			$schemas[] = [
				'targetType' => $type,
				'label' => Text::_(ConditionTargetTypeEnum::from($type)->getLabel()),
				'fields' => array_map(function ($field) {
					return $field->toSchema();
				}, $resolver->getAvailableFields($resolverFilters)),
				'allowedActionTargetTypes' => array_map(function ($targetType) {
					return $targetType->value;
				}, $resolver->getAllowedActionTargetTypes()),
				'searchable' => $resolver->searchable(),
			];
		}

		// context data should always be first
		usort($schemas, function ($a, $b) {
			if ($a['targetType'] === ConditionTargetTypeEnum::CONTEXTDATA->value) {
				return -1;
			}
			if ($b['targetType'] === ConditionTargetTypeEnum::CONTEXTDATA->value) {
				return 1;
			}
			return 0;
		});


		return $schemas;
	}
}