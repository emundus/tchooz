<?php

namespace Tchooz\Entities\Fields;

use Joomla\CMS\Language\Text;

class FieldResearch
{
	private string $controllerName;

	private string $methodName;

	private string $searchInputKey;

	private array $params = [];

	public function __construct(string $controllerName, string $methodName, string $searchInputKey = 'search_query', array $params = [])
	{
		if (empty($controllerName) || empty($methodName))
		{
			throw new \Exception(Text::_('MISSING_REQUIRED_FIELDS'));
		}

		$this->controllerName = $controllerName;
		$this->methodName = $methodName;
		$this->searchInputKey = $searchInputKey;
		$this->params = $params;
	}

	public function getControllerName(): string { return $this->controllerName; }

	public function getMethodName(): string { return $this->methodName; }

	public function getSearchInputKey(): string { return $this->searchInputKey; }

	public function getParams(): array { return $this->params; }

	public function toSchema(): array
	{
		return [
			'controller' => $this->getControllerName(),
			'method' => $this->getMethodName(),
			'input' => $this->getSearchInputKey(),
			'params' => $this->getParams(),
		];
	}
}