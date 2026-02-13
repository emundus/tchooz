<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Traits\TraitAutomatedTask;

abstract class ActionEntity
{
	use TraitAutomatedTask;

	private ?int $id = null;

	private string $name;

	private ?string $description;

	protected array $parameters = [];

	protected array $parameterValues = [];

	/**
	 * @var array<TargetEntity>
	 */
	private array $targets = [];

	private array $withEntities = [];

	private string|array|null $result = null;

	/**
	 * @var array<ActionExecutionMessage>
	 */
	private array $executionMessages = [];

	public function __construct(array $parameterValues = [])
	{
		$this->parameterValues = $parameterValues;

		Log::addLogger(['text_file' => 'com_emundus.action.log.php'], Log::ALL, ['com_emundus.action']);
	}

	abstract public static function getIcon(): ?string;

	/**
	 * @return ActionCategoryEnum|null
	 */
	abstract public static function getCategory(): ?ActionCategoryEnum;

	/**
	 * Indicates if the action is executed asynchronously (i.e. in a queue) or synchronously
	 * Asynchronous actions are better for long-running tasks (ex: sending emails, generating documents, etc.)
	 * They improve the responsiveness of the system but introduce a slight delay before the action is completed
	 * @return bool
	 */
	abstract public static function isAsynchronous(): bool;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	/**
	 * Returns a unique identifier for the action (ex: update_status, send_email)
	 */
	abstract public static function getType(): string;

	/**
	 * Returns the label of the action
	 */
	abstract public static function getLabel(): string;

	/**
	 * Returns the supported target types for the action (ex email action can target users, files, contacts, etc. but a generate letter action can only target files)
	 * @return array<TargetTypeEnum>
	 */
	abstract public static function supportTargetTypes(): array;

	public static function getDescription(): string
	{
		return '';
	}

	/**
	 * Execute the action
	 *
	 * @param   ActionTargetEntity|ActionTargetEntity[]  $context           The context entity or an array of context entities, some actions may NOT support an array of context entities
	 * @param   AutomationExecutionContext|null          $executionContext  The execution context
	 *
	 * @return ActionExecutionStatusEnum
	 */
	abstract public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum;

	abstract public function getParameters(): array;

	abstract public function getLabelForLog(): string;

	/**
	 * Associate an entity to the action instance
	 * @param   object  $entity
	 *
	 * @return $this
	 */
	public function with(object $entity): self
	{
		if (!empty($entity) && !in_array($entity, $this->withEntities, true))
		{
			$this->withEntities[] = $entity;
		}

		return $this;
	}

	/**
	 * Vérifie si une entité d’un type donné est associée à l’action
	 */
	public function isExecutedWith(string $entityClass): bool
	{
		foreach ($this->withEntities as $entity) {
			if ($entity instanceof $entityClass) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<object>
	 */
	public function getWithEntities(): array
	{
		return $this->withEntities;
	}

	/**
	 * @return array<object>
	 */
	public function getWithOfType(string $entityClass): array
	{
		$entities = [];
		foreach ($this->withEntities as $entity) {
			if ($entity instanceof $entityClass) {
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	/**
	 * @param   string  $name
	 *
	 * @return Field|null
	 */
	public function getParameter(string $name): ?Field
	{
		foreach ($this->getParameters() as $param) {
			if ($param->getName() === $name) {
				return $param;
			}
		}

		return null;
	}

	/**
	 * @param   string    $name
	 * @param   int|null  $row
	 *
	 * @return mixed
	 */
	public function getParameterValue(string $name, ?int $row = null): mixed
	{
		$value = null;

		$parameter = $this->getParameter($name);

		if (!empty($parameter))
		{
			if (!empty($parameter->getGroup()) && $parameter->getGroup()->isRepeatable())
			{
				// parameter will be stored in group name entry as array of rows
				$groupName = $parameter->getGroup()->getName();
				if (isset($this->parameterValues[$groupName]) && is_array($this->parameterValues[$groupName]))
				{
					$groupValues = $this->parameterValues[$groupName];

					if (!empty($row))
					{
						// specific row requested
						$value = $groupValues[$row][$name] ?? null;
					}
					else
					{
						// return all rows
						$allValues = [];
						foreach ($groupValues as $rowValues) {
							$allValues[] = $rowValues[$name] ?? null;
						}
						$value = $allValues;
					}
				}
			} else
			{
				$value = $this->parameterValues[$name] ?? null;
			}
		}

		return $value;
	}

	/**
	 * @param   string    $name
	 * @param   mixed     $value
	 * @param   int|null  $row used for repeatable group parameters
	 *
	 * @return void
	 */
	public function setParameterValues(string $name, mixed $value, ?int $row = null): void
	{
		$parameter = $this->getParameter($name);

		if (!empty($parameter)) {
			if ($parameter instanceof ChoiceField) {
				if (!empty($parameter->getOptionsProvider()))
				{
					try {
						if (!empty($parameter->getOptionsProvider()->getDependencies()))
						{
							foreach ($parameter->getOptionsProvider()->getDependencies() as $dependency)
							{
								$parameter->getOptionsProvider()->addRepositoryMethodArg($this->getParameterValue($dependency));
							}
						}

						$parameter->provideOptions();
					} catch (\Exception $exception)
					{
						Log::add('Error providing options for parameter "' . $name . '": ' . $exception->getMessage(), Log::ERROR, 'com_emundus.action');
					}
				}

				if (empty($parameter->getResearch()))
				{
					$availableValues = array_map(fn($choice) => $choice->getValue(), $parameter->getChoices());
					if ($parameter->getMultiple()) {
						if (!is_array($value)) {
							$value = [$value];
						}
						foreach ($value as $val) {
							if (!in_array($val, $availableValues)) {
								Log::add('Invalid value "' . $val . '" for parameter "' . $name . '". Allowed values are: ' . implode(', ',  array_map(fn($choice) => $choice->toSchema(), $parameter->getChoices())), Log::ERROR, 'com_emundus.action');
								$value = array_filter($value, fn($v) => $v !== $val);
							}
						}
					} else {
						if (!in_array($value, $availableValues)) {
							Log::add('Invalid value "' . $value . '" for parameter "' . $name . '". Allowed values are: ' . implode(', ',  array_map(fn($choice) => $choice->toSchema(), $parameter->getChoices())), Log::ERROR, 'com_emundus.action');
							$value = null;
						}
					}
				}
			}

			if (!empty($parameter->getGroup()) && $parameter->getGroup()->isRepeatable())
			{
				$groupName = $parameter->getGroup()->getName();
				if (!isset($this->parameterValues[$groupName]) || !is_array($this->parameterValues[$groupName])) {
					$this->parameterValues[$groupName] = [];
				}
				if (!isset($row)) {
					throw new \InvalidArgumentException("Row must be specified for repeatable group parameter '$name'");
				}
				if (!isset($this->parameterValues[$groupName][$row]) || !is_array($this->parameterValues[$groupName][$row])) {
					$this->parameterValues[$groupName][$row] = [];
				}
				$this->parameterValues[$groupName][$row][$name] = $value;
			}
			else
			{
				$this->parameterValues[$name] = $value;
			}
		} else {
			throw new \InvalidArgumentException("Parameter '$name' not found for action type '" . static::getType() . "'");
		}
	}

	/**
	 * @param   array  $parameterValues
	 *
	 * @return void
	 */
	public function setParametersValuesFromArray(array $parameterValues = []): void
	{
		$parameters = $this->getParameters();

		foreach($parameters as $parameter)
		{
			if ($parameter->getGroup() && $parameter->getGroup()->isRepeatable())
			{
				$groupName = $parameter->getGroup()->getName();
				if (isset($parameterValues[$groupName]) && is_array($parameterValues[$groupName]))
				{
					foreach ($parameterValues[$groupName] as $rowIndex => $rowValues)
					{
						if (isset($rowValues[$parameter->getName()]))
						{
							$this->setParameterValues($parameter->getName(), $rowValues[$parameter->getName()], $rowIndex);
						}
					}
				}
			}
			else
			{
				if (isset($parameterValues[$parameter->getName()]))
				{
					$this->setParameterValues($parameter->getName(), $parameterValues[$parameter->getName()]);
				}
			}
		}
	}

	public function getParameterValues(): array
	{
		return $this->parameterValues;
	}

	public function getParametersSchema(): array
	{
		return array_map(fn($param) => $param->toSchema(), $this->getParameters());
	}

	public function addTarget(TargetEntity $target): void
	{
		$this->targets[] = $target;
	}

	/**
	 * @param   array<TargetEntity>  $targets
	 *
	 * @return void
	 */
	public function setTargets(array $targets): void
	{
		foreach ($targets as $target) {
			assert($target instanceof TargetEntity);
		}

		$this->targets = $targets;
	}

	/**
	 * @return TargetEntity[]
	 */
	public function getTargets(): array
	{
		return $this->targets;
	}

	/**
	 * @param   ActionTargetEntity  $context
	 *
	 * @return array<ActionTargetEntity>
	 */
	public function getExecutionTargets(ActionTargetEntity $context): array
	{
		$newActionTargets = [];

		if (empty(static::supportTargetTypes())) {
			$newActionTargets = [$context];
		} else {
			if (!empty($this->getTargets())) {
				$context->setOriginalContext($context);
				foreach ($this->getTargets() as $target)
				{
					$newActionTargets = array_merge($newActionTargets, $target->resolve($context));
				}
			}
		}

		return $newActionTargets;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->getId(),
			'type' => static::getType(),
			'category' => static::getCategory()?->value,
			'label' => static::getLabel(),
			'description' => static::getDescription(),
			'icon' => static::getIcon(),
			'parameters' => $this->getParametersSchema(),
			'parameter_values' => $this->getParameterValues(),
			'targets' => array_map(fn($target) => $target->serialize(), $this->getTargets()),
			'supported_target_types' => array_map(fn($type) => $type->value, static::supportTargetTypes()),
		];
	}

	public function verifyRequiredParameters(): void
	{
		foreach ($this->getParameters() as $parameter) {
			assert($parameter instanceof Field);
			if (!empty($parameter->getDisplayRules()))
			{
				foreach ($parameter->getDisplayRules() as $rule)
				{
					// todo: handle more operators than equal
					if ($this->getParameterValue($rule->getField()->getName()) != $rule->getValue())
					{
						// parameter is not displayed, skip required check
						continue 2;
					}
				}
			}

			if ($parameter->getGroup() && $parameter->getGroup()->isRepeatable()) {
				$groupName = $parameter->getGroup()->getName();

				if ($parameter->isRequired()) {
					if (!isset($this->parameterValues[$groupName]) || !is_array($this->parameterValues[$groupName]) || empty($this->parameterValues[$groupName])) {
						throw new \RuntimeException(Text::_('MISSING_REQUIRED_PARAMETER') . $parameter->getName());
					}
					$hasValue = false;
					foreach ($this->parameterValues[$groupName] as $rowValues) {
						if (isset($rowValues[$parameter->getName()]) && !empty($rowValues[$parameter->getName()])) {
							$hasValue = true;
							break;
						}
					}
					if (!$hasValue) {
						throw new \RuntimeException(Text::_('MISSING_REQUIRED_PARAMETER') . $parameter->getName());
					}
				}
			} else if ($parameter->isRequired() && !isset($this->parameterValues[$parameter->getName()])) {
				throw new \RuntimeException(Text::_('MISSING_REQUIRED_PARAMETER') . $parameter->getName());
			}
		}
	}

	public function verifyParameterValueIsValid(string $name): bool
	{
		$parameter = $this->getParameter($name);

		if (empty($parameter)) {
			throw new \InvalidArgumentException("Parameter '$name' not found for action type '" . static::getType() . "'");
		}

		$value = $this->getParameterValue($name);

		if ($parameter instanceof ChoiceField) {
			if (empty($parameter->getResearch()))
			{
				$availableValues = array_map(fn($choice) => $choice->getValue(), $parameter->getChoices());
				if ($parameter->getMultiple()) {
					if (!is_array($value)) {
						$value = [$value];
					}
					foreach ($value as $val) {
						if (!in_array($val, $availableValues)) {
							Log::add('Invalid value "' . $val . '" for parameter "' . $name, Log::ERROR, 'com_emundus.action');
							throw new \InvalidArgumentException("Invalid value $val for parameter $name");
						}
					}
				} else {
					if (!in_array($value, $availableValues)) {
						Log::add('Invalid value "' . $value . '" for parameter "' . $name, Log::ERROR, 'com_emundus.action');
						throw new \InvalidArgumentException("Invalid value $value for parameter $name");
					}
				}
			}
		}

		return true;
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function getResult(): array|string|null
	{
		return $this->result;
	}

	public function setResult(array|string|null $result): void
	{
		$this->result = $result;
	}

	public function addExecutionMessage(ActionExecutionMessage $message): void
	{
		$this->executionMessages[] = $message;
	}

	public function getExecutionMessages(?ActionMessageTypeEnum $type = null): array
	{
		if (!empty($type))
		{
			return array_filter($this->executionMessages, fn($msg) => $msg->getType() === $type);
		}

		return $this->executionMessages;
	}
}