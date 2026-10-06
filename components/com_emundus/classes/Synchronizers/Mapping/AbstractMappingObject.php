<?php

namespace Tchooz\Synchronizers\Mapping;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingResolution;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * Shared behaviour for mapping objects: lazily builds and caches the definition, derives
 * associations from it, and validates required fields against the mapping parameters.
 *
 * Concrete objects implement the connector- and object-specific parts (name, definition building,
 * existence resolution, payload shaping).
 */
abstract class AbstractMappingObject implements MappingObjectInterface
{
	private ?SynchronizerMappingObjectDefinition $definition = null;

	/**
	 * Build the descriptive definition once. Called lazily by getDefinition().
	 */
	abstract protected function buildDefinition(): SynchronizerMappingObjectDefinition;

	abstract public function resolveExistence(MappingEntity $mapping, ActionTargetEntity $context, MappingTransportInterface $transport): MappingResolution;

	abstract public function buildPayload(array $mappedData, MappingEntity $mapping, ActionTargetEntity $context): array;

	public function getName(): string
	{
		return $this->getDefinition()->getName();
	}

	public function getDefinition(): SynchronizerMappingObjectDefinition
	{
		if ($this->definition === null)
		{
			$this->definition = $this->buildDefinition();
		}

		return $this->definition;
	}

	public function getAssociations(): array
	{
		return $this->getDefinition()->getAssociations();
	}

	/**
	 * Default: every required field declared in the definition must have a non-empty value in the
	 * mapping parameters. Objects with richer rules override this.
	 *
	 * @throws \DomainException
	 */
	public function validate(MappingEntity $mapping): void
	{
		$params = $mapping->getParams() ?? [];

		foreach ($this->getDefinition()->getRequiredFields() as $field)
		{
			if (!$field->isRequired())
			{
				continue;
			}

			$name = $field->getName();

			if (!array_key_exists($name, $params) || $params[$name] === '' || $params[$name] === null)
			{
				throw new \DomainException(Text::sprintf('COM_EMUNDUS_MAPPING_REQUIRED_FIELD_MISSING', $field->getLabel(), $this->getName()));
			}
		}
	}

	/**
	 * Default: the object tracks no external reference on creation. Objects that do override this.
	 */
	public function buildCreatedReference(mixed $response, mixed $internalId, MappingEntity $mapping): ?ExternalReferenceEntity
	{
		return null;
	}
}
