<?php

namespace Tchooz\Entities\Mapping;

use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;
use Tchooz\Enums\Api\ApiMethodEnum;

class SynchronizerMappingObjectDefinition
{
	private string $name;

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

	private array $metadata;


	public function __construct(string $name, string $route, ExternalReferenceEntity $externalReference, array $methods = [ApiMethodEnum::GET, ApiMethodEnum::POST], array $metadata = [])
	{
		$this->name = $name;
		$this->route = $route;
		$this->externalReference = $externalReference;
		$this->methods = $methods;
		$this->metadata = $metadata;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getRoute(): string
	{
		return $this->route;
	}

	public function getMethods(): array
	{
		return $this->methods;
	}

	public function getMetadata(): array
	{
		return $this->metadata;
	}

	public function getExternalReference(): ExternalReferenceEntity
	{
		return $this->externalReference;
	}
}