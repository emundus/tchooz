<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Joomla\Database\DatabaseDriver;

abstract class TargetPredefinitionEntity
{
	private string $name;

	private string $label;

	/**
	 * @var TargetTypeEnum
	 *     the category to which this target resolution belongs
	 */
	private TargetTypeEnum $category;

	/**
	 * @var array<TargetTypeEnum>
	 *     the categories from which this target predefinition can be used
	 */
	private array $fromCategories;

	public function __construct(string $name, string $label, TargetTypeEnum $category, array $fromCategories = [])
	{
		$this->name = $name;
		$this->label = $label;
		$this->category = $category;
		$this->fromCategories = $fromCategories;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getCategory(): TargetTypeEnum
	{
		return $this->category;
	}

	/**
	 * @return array<TargetTypeEnum>
	 */
	public function getFromCategories(): array
	{
		return $this->fromCategories;
	}

	/**
	 * @param   ActionTargetEntity  $context
	 *
	 * @return array<ActionTargetEntity>
	 */
	abstract public function resolve(ActionTargetEntity $context): array;

	public function serialize(): array
	{
		return [
			'name' => $this->name,
			'label' => Text::_($this->label),
			'category' => $this->category->value,
			'fromCategories' => array_map(fn($cat) => $cat->value, $this->fromCategories)
		];
	}
}