<?php

namespace Tchooz\Entities\Fields;

use Tchooz\Services\Field\FieldResearch;

class TextAreaField extends Field
{
	private ?int $minLength = null;
	private ?int $maxLength = null;
	private int $rows = 3;
	private ?string $placeholder = null;
	private bool $resize = true;

	public function __construct(
		string $name,
		string $label,
		bool $required = false,
		?FieldGroup $group = null,
		?int $minLength = null,
		?int $maxLength = null,
		?FieldResearch $research = null,
		array $displayRules = []
	) {
		parent::__construct($name, $label, $required, $group, $research, $displayRules);
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;
	}

	public static function getType(): string
	{
		return 'textarea';
	}

	public function getMinLength(): ?int
	{
		return $this->minLength;
	}

	public function setMinLength(?int $minLength): self
	{
		$this->minLength = $minLength;

		return $this;
	}

	public function getMaxLength(): ?int
	{
		return $this->maxLength;
	}

	public function setMaxLength(?int $maxLength): self
	{
		$this->maxLength = $maxLength;

		return $this;
	}

	public function getRows(): int
	{
		return $this->rows;
	}

	public function setRows(int $rows): self
	{
		$this->rows = $rows;

		return $this;
	}

	public function getPlaceholder(): ?string
	{
		return $this->placeholder;
	}

	public function setPlaceholder(?string $placeholder): self
	{
		$this->placeholder = $placeholder;

		return $this;
	}

	public function getResize(): bool
	{
		return $this->resize;
	}

	public function setResize(bool $resize): self
	{
		$this->resize = $resize;

		return $this;
	}

	public function toSchema(): array
	{
		$schema = $this->defaultSchema();
		$schema['minLength'] = $this->minLength;
		$schema['maxLength'] = $this->maxLength;
		$schema['rows'] = $this->rows;
		$schema['placeholder'] = $this->placeholder;
		$schema['resize'] = $this->resize;

		return $schema;
	}
}
