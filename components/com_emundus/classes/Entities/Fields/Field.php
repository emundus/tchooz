<?php

namespace Tchooz\Entities\Fields;

use Tchooz\Services\Field\DisplayRule;
use Tchooz\Services\Field\FieldResearch;
use Tchooz\Services\Field\FieldWatcher;

abstract class Field
{
	public function __construct(
		protected string $name,
		protected string $label,
		protected bool $required = false,
		protected ?FieldGroup $group = null,
		private ?FieldResearch $research = null,
		private array $displayRules = [],
		private array $watchers = [],
		private ?string $originalType = null
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

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function setGroup(?FieldGroup $group): void
	{
		$this->group = $group;
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

	public function addWatcher(FieldWatcher $watcher): self
	{
		$this->watchers[] = $watcher;

		return $this;
	}

	public function getWatchers(): array
	{
		return $this->watchers;
	}

	public function setOriginalType(?string $type): self
	{
		$this->originalType = $type;
		return $this;
	}

	public function getOriginalType(): ?string
	{
		return $this->originalType;
	}

	public function defaultSchema(): array
	{
		return [
			'name' => $this->name,
			'label' => $this->label,
			'type' => static::getType(),
			'required' => $this->required,
			'group' => $this->getGroup()?->toSchema(),
			'research' => $this->getResearch()?->toSchema(),
			'displayRules' => array_map(fn($rule) => $rule->toSchema(), $this->getDisplayRules()),
			'watchers' => array_map(fn($watcher) => $watcher->toSchema(), $this->getWatchers()),
			'originalType' => $this->originalType,
		];
	}
}
