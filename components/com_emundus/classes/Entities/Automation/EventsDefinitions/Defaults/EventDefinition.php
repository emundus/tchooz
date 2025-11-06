<?php

namespace Tchooz\Entities\Automation\EventsDefinitions\Defaults;

use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Traits\TraitAutomatedTask;

abstract class EventDefinition
{
	use TraitAutomatedTask;

	/**
	 * @param   string  $name
	 * @param   array<Field>   $parameters
	 */
	public function __construct(private readonly string $name, private readonly array $parameters)
	{
	}

	public function getName(): string
	{
		return $this->name;
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