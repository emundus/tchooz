<?php

namespace Tchooz\Entities\Fields;

use Tchooz\Services\Field\DisplayRule;

class FieldGroup
{
	private string $name;
	private string $label;
	private bool $isRepeatable = false;


	/** @var DisplayRule[] */
	private array $displayRules = [];

	public function __construct(string $name, string $label, bool $isRepeatable = false, array $displayRules = [])
	{
		$this->name = $name;
		$this->label = $label;
		$this->isRepeatable = $isRepeatable;
		$this->displayRules = $displayRules;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function isRepeatable(): bool
	{
		return $this->isRepeatable;
	}

	/**
	 * @return DisplayRule[]
	 */
	public function getDisplayRules(): array
	{
		return $this->displayRules;
	}

	/**
	 * @param DisplayRule[] $displayRules
	 *
	 * @return $this
	 */
	public function setDisplayRules(array $displayRules): self
	{
		$this->displayRules = $displayRules;
		return $this;
	}

	public function toSchema(): array
	{
		return [
			'name' => $this->name,
			'label' => $this->label,
			'isRepeatable' => $this->isRepeatable,
			'displayRules' => array_map(fn(DisplayRule $rule) => $rule->toSchema(), $this->displayRules)
		];
	}
}