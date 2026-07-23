<?php

namespace Tchooz\Entities\Fields;

use Tchooz\Services\Field\FieldResearch;

class WysiwygField extends Field
{
	private ?int $minLength = null;
	private ?int $maxLength = null;
	private ?string $placeholder = null;
	private string $preset = 'basic';
	private string $editorContentHeight = '20em';

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
		return 'wysiwig';
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

	public function getPlaceholder(): ?string
	{
		return $this->placeholder;
	}

	public function setPlaceholder(?string $placeholder): self
	{
		$this->placeholder = $placeholder;

		return $this;
	}

	public function getPreset(): string
	{
		return $this->preset;
	}

	public function setPreset(string $preset): self
	{
		$this->preset = $preset;

		return $this;
	}

	public function getEditorContentHeight(): string
	{
		return $this->editorContentHeight;
	}

	public function setEditorContentHeight(string $editorContentHeight): self
	{
		$this->editorContentHeight = $editorContentHeight;

		return $this;
	}

	public function toSchema(): array
	{
		$schema = $this->defaultSchema();
		$schema['minLength'] = $this->minLength;
		$schema['maxLength'] = $this->maxLength;
		$schema['placeholder'] = $this->placeholder;
		$schema['preset'] = $this->preset;
		$schema['editorContentHeight'] = $this->editorContentHeight;

		return $schema;
	}
}
