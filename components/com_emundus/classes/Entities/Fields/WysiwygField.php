<?php

namespace Tchooz\Entities\Fields;

use Joomla\CMS\Filter\InputFilter;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Tchooz\Services\Field\FieldResearch;

class WysiwygField extends Field
{
	private ?int $minLength = null;
	private ?int $maxLength = null;
	private ?string $placeholder = null;
	private string $preset = 'basic';
	private string $editorContentHeight = '20em';

	/**
	 * When true, the editor holds a single line: it is one line high and refuses line breaks.
	 */
	private bool $singleLine = false;

	/**
	 * Markup the stored value is reduced to. Left empty, the value is stored as typed.
	 *
	 * @var array<string>
	 */
	private array $allowedTags = [];

	/**
	 * @var array<string>
	 */
	private array $allowedAttributes = [];

	/**
	 * @var array<string>
	 */
	private array $allowedLinkSchemes = [];

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

	public function isSingleLine(): bool
	{
		return $this->singleLine;
	}

	public function setSingleLine(bool $singleLine): self
	{
		$this->singleLine = $singleLine;

		return $this;
	}

	/**
	 * @return array<string>
	 */
	public function getAllowedTags(): array
	{
		return $this->allowedTags;
	}

	/**
	 * @return array<string>
	 */
	public function getAllowedAttributes(): array
	{
		return $this->allowedAttributes;
	}

	/**
	 * @return array<string>
	 */
	public function getAllowedLinkSchemes(): array
	{
		return $this->allowedLinkSchemes;
	}

	/**
	 * @param   array<string>  $allowedTags
	 * @param   array<string>  $allowedAttributes
	 * @param   array<string>  $allowedLinkSchemes
	 */
	public function setAllowedMarkup(array $allowedTags, array $allowedAttributes = [], array $allowedLinkSchemes = []): self
	{
		$this->allowedTags        = $allowedTags;
		$this->allowedAttributes  = $allowedAttributes;
		$this->allowedLinkSchemes = $allowedLinkSchemes;

		return $this;
	}

	/**
	 * Reduces a value to the markup this field accepts.
	 * An unlisted tag is dropped and its wording kept. Without a declared markup the value is left as typed.
	 */
	public function sanitizeValue(?string $value): string
	{
		$value = (string) $value;

		if (empty($this->allowedTags) || trim($value) === '')
		{
			return $value;
		}

		// Dropped with their content, which the tag filter would otherwise leave behind as text
		$value = preg_replace('#<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $value);

		if ($this->singleLine)
		{
			// Block boundaries become spaces, otherwise dropping their tags glues the words together
			$value = preg_replace('#<\s*(br|/p|/div|/li|/h[1-6]|/blockquote)\b[^>]*>#i', ' ', $value);
			$value = str_replace(["\r", "\n", "\t"], ' ', $value);
		}

		$filter = new InputFilter(
			$this->allowedTags,
			$this->allowedAttributes,
			InputFilter::ONLY_ALLOW_DEFINED_TAGS,
			InputFilter::ONLY_ALLOW_DEFINED_ATTRIBUTES
		);

		$value = $filter->clean($value, 'html');

		if (!empty($this->allowedLinkSchemes))
		{
			$value = $this->filterLinkTargets($value);
		}

		// A link without a target is no longer a link, unwrapped so the wording survives
		$value = preg_replace('#<a(?![^>]*\bhref\s*=)[^>]*>(.*?)</a>#is', '$1', $value);

		return trim(preg_replace('/ {2,}/', ' ', $value));
	}

	/**
	 * Strips the target of every link that does not use an allowed scheme, leaving the anchor empty
	 * to be unwrapped. Relative targets are refused: they are ambiguous once the text is rendered
	 * outside the platform.
	 */
	private function filterLinkTargets(string $value): string
	{
		if (!class_exists(HtmlSanitizer::class))
		{
			require_once JPATH_LIBRARIES . '/emundus/vendor/autoload.php';
		}

		$config = (new HtmlSanitizerConfig())
			->allowLinkSchemes($this->allowedLinkSchemes)
			->allowRelativeLinks(false);

		foreach ($this->allowedTags as $tag)
		{
			$config = $config->allowElement($tag, $this->allowedAttributes);
		}

		return (new HtmlSanitizer($config))->sanitize($value);
	}

	public function toSchema(): array
	{
		$schema = $this->defaultSchema();
		$schema['minLength'] = $this->minLength;
		$schema['maxLength'] = $this->maxLength;
		$schema['placeholder'] = $this->placeholder;
		$schema['preset'] = $this->preset;
		$schema['editorContentHeight'] = $this->editorContentHeight;
		$schema['singleLine'] = $this->singleLine;

		return $schema;
	}
}
