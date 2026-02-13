<?php

namespace Tchooz\Entities\Mapping;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;
use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Api\ApiMethodEnum;

class SynchronizerMappingObjectDefinition
{
	private string $name;

	private string $label;

	private string $route;

	/**
	 * @var array<ApiMethodEnum>
	 */
	private array $methods;

	/**
	 * This will be used to know the structure of the external reference mapping
	 * @var ExternalReferenceEntity
	 */
	private ExternalReferenceEntity $externalReference;

	/**
	 * @var array<Field>
	 */
	private array $requiredFields = [];

	private array $metadata;

	/**
	 * @var array<AssociationDefinition>
	 */
	private array $associations = [];


	public function __construct(string $name, string $label, string $route, ExternalReferenceEntity $externalReference, array $methods = [ApiMethodEnum::GET, ApiMethodEnum::POST], array $requiredFields = [], array $metadata = [], array $associations = [])
	{
		$this->name = $name;
		$this->label = Text::_($label);
		$this->route = $route;
		$this->externalReference = $externalReference;
		$this->methods = $methods;
		$this->requiredFields = $requiredFields;
		$this->metadata = $metadata;
		$this->associations = $associations;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getRoute(): string
	{
		return $this->route;
	}

	public function getMethods(): array
	{
		return $this->methods;
	}

	public function getRequiredFields(): array
	{
		return $this->requiredFields;
	}

	public function setRequiredFields(array $requiredFields): self
	{
		$this->requiredFields = $requiredFields;

		return $this;
	}

	public function getMetadata(): array
	{
		return $this->metadata;
	}

	public function getExternalReference(): ExternalReferenceEntity
	{
		return $this->externalReference;
	}

	public function setAssociations(array $associations): self
	{
		$this->associations = $associations;

		return $this;
	}

	public function getAssociations(): array
	{
		return $this->associations;
	}

	public function serialize(): array
	{
		return [
			'name' => $this->name,
			'label' => $this->label,
			'route' => $this->route,
			'methods' => array_map(fn($method) => $method->value, $this->methods),
			'externalReference' => $this->externalReference->serialize(),
			'requiredFields' => array_map(fn($field) => $field->toSchema(), $this->getRequiredFields()),
			'metadata' => $this->metadata,
		];
	}
}