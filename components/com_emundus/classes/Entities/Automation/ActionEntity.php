<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
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

	abstract public function execute(ActionTargetEntity $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum;

	abstract public function getParameters(): array;

	abstract public function getLabelForLog(): string;

	/**
	 * Retourne la valeur d’un paramètre d’instance
	 */
	public function getParameterValue(string $name): mixed
	{
		return $this->parameterValues[$name] ?? null;
	}

	public function setParameterValues(string $name, mixed $value): void
	{
		$parameter = null;
		foreach ($this->getParameters() as $param) {
			if ($param->getName() === $name) {
				$parameter = $param;
				break;
			}
		}
		if (!empty($parameter)) {
			if ($parameter instanceof ChoiceField) {
				$availableValues = array_map(fn($choice) => $choice->getValue(), $parameter->getChoices());

				if ($parameter->getMultiple()) {
					if (!is_array($value)) {
						$value = [$value];
					}
					foreach ($value as $val) {
						if (!in_array($val, $availableValues)) {
							Log::add('Invalid value "' . $val . '" for parameter "' . $name . '". Allowed values are: ' . implode(', ',  array_map(fn($choice) => $choice->toSchema(), $parameter->getChoices())), Log::ERROR, 'automation');
							$value = array_filter($value, fn($v) => $v !== $val);
						}
					}
				} else {
					if (!in_array($value, $availableValues)) {
						Log::add('Invalid value "' . $value . '" for parameter "' . $name . '". Allowed values are: ' . implode(', ',  array_map(fn($choice) => $choice->toSchema(), $parameter->getChoices())), Log::ERROR, 'automation');
						$value = null;
					}
				}
			}
		} else {
			throw new \InvalidArgumentException("Parameter '$name' not found for action type '" . static::getType() . "'");
		}

		$this->parameterValues[$name] = $value;
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
			if ($parameter->isRequired() && !isset($this->parameterValues[$parameter->getName()])) {
				throw new \RuntimeException(Text::_('MISSING_REQUIRED_PARAMETER') . $parameter->getName());
			}
		}
	}
}