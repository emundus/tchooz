<?php

namespace Tchooz\Entities\Fields;

abstract class Field
{
	public function __construct(
		protected string $name,
		protected string $label,
		protected bool $required = false,
		protected ?FieldGroup $group = null,
		private ?FieldResearch $research = null,
		private array $displayRules = []
	) {}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function getGroup(): ?FieldGroup
	{
		return $this->group;
	}

	abstract public static function getType(): string;

	/**
	 * Retourne un tableau utilisable côté front pour construire le formulaire
	 */
	abstract public function toSchema(): array;

	public function getResearch(): ?FieldResearch
	{
		return $this->research;
	}

	public function setResearch(?FieldResearch $research): self
	{
		$this->research = $research;

		return $this;
	}

	/**
	 * @return   array<DisplayRule>
	 */
	public function getDisplayRules(): array
	{
		return $this->displayRules;
	}

	/**
	 * @param   array<DisplayRule>  $displayRules
	 *
	 * @return $this
	 */
	public function setDisplayRules(array $displayRules): self
	{
		foreach ($displayRules as $rule)
		{
			assert($rule instanceof DisplayRule);
		}

		$this->displayRules = $displayRules;

		return $this;
	}
}
