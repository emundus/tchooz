<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\Language\Text;
use Tchooz\Enums\Automation\EventCategoryEnum;

class EventEntity
{
	private int $id;

	private string $name = '';

	private string $label = '';

	private string $description = '';

	private bool $isPublished = true;

	private ?EventCategoryEnum $category = null;

	public function __construct(int $id = 0, string $name = '', string $description = '', ?EventCategoryEnum $category = null)
	{
		$this->id = $id;
		$this->name = $name;
		$this->label = Text::_( 'COM_EMUNDUS_EVENT_' . strtoupper( str_replace(' ', '_', $name) ) );
		$this->description = !empty($description) ? Text::_($description) : Text::_( 'COM_EMUNDUS_EVENT_' . strtoupper( str_replace(' ', '_', $name) ) . '_DESC' );
		$this->category = $category;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

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

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getCategory(): ?EventCategoryEnum
	{
		return $this->category;
	}

	public function setCategory(EventCategoryEnum $category): void
	{
		$this->category = $category;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'label' => $this->label,
			'description' => $this->description,
			'isPublished' => $this->isPublished,
			'category' => [
				'value' => $this->category?->value,
				'label' => $this->category?->getLabel(),
				'icon' => $this->category?->getIcon(),
			],
		];
	}
}