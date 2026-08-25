<?php

namespace Tchooz\Entities\Automation\EventsDefinitions\Defaults;

use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Automation\EventCategoryEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Traits\TraitAutomatedTask;

abstract class EventDefinition
{
	use TraitAutomatedTask;

	/**
	 * @param   string                  $name
	 * @param   array<Field>            $parameters
	 * @param   EventCategoryEnum|null  $category
	 */
	public function __construct(private readonly string $name, private readonly array $parameters, private readonly ?EventCategoryEnum $category = null)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getCategory(): ?EventCategoryEnum
	{
		return $this->category;
	}

	/**
	 * @return array<Field>
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * @return array<TargetTypeEnum>
	 */
	abstract public function supportTargetPredefinitionsCategories(): array;
}