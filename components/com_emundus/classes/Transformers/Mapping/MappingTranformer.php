<?php

namespace Tchooz\Transformers\Mapping;

use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Entities\Fields\Field;
use Tchooz\Services\Field\FieldWatcher;

abstract class MappingTranformer
{
	private MappingTransformersEnum $type;

	/**
	 * @var array<Field>
	 */
	private array $parameters;

	private array $parametersValues = [];

	/**
	 * @var array<object>
	 */
	private array $withEntities = [];


	public function __construct(
		MappingTransformersEnum $type,
		array $parameters = [],
	)
	{
		$this->type = $type;
		$this->parameters = $parameters;
	}

	/**
	 * @param   mixed                  $value
	 *
	 * @return mixed Transformed value
	 */
	abstract public function transform(mixed $value): mixed;

	public function getType(): MappingTransformersEnum
	{
		return $this->type;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}

	public function setParametersValues(array $values): void
	{
		$this->parametersValues = $values;
	}

	public function getParameterValues(): array
	{
		return $this->parametersValues;
	}

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
				if (isset($this->parametersValues[$groupName]) && is_array($this->parametersValues[$groupName]))
				{
					$groupValues = $this->parametersValues[$groupName];

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
			}
			else
			{
				$value = $this->parametersValues[$name] ?? null;
			}
		}

		return $value;
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

	public function addWatcher(string $field, FieldWatcher $watcher): void
	{
		$parameter = $this->getParameter($field);

		if ($parameter) {
			$parameter->addWatcher($watcher);
		}
	}

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


	public function serialize(): array
	{
		return [
			'type' => $this->type->value,
			'label' => $this->type->getLabel(),
			'parameters' => array_map(
				function (Field $field) {
					return $field->toSchema();
				},
				$this->parameters
			),
			'parameterValues' => $this->parametersValues,
		];
	}
}