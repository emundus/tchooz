<?php

namespace Tchooz\Services\Field;

class FieldOptionProvider
{
	/**
	 * @param   string          $controller
	 * @param   string          $methodName
	 * @param   array<string>   $dependencies Field names that this provider depends on
	 *
	 * @throws \Exception
	 */
	public function __construct(
		private string $controller,
		private string $methodName,
		private array $dependencies = [],
	)
	{
		if (empty($this->controller))
		{
			throw new \Exception('Class name is required for FieldOptionProvider.');
		}

		if (empty($this->methodName))
		{
			throw new \Exception('Method name is required for FieldOptionProvider.');
		}
	}

	public function getController(): string
	{
		return $this->controller;
	}

	public function getMethodName(): string
	{
		return $this->methodName;
	}

	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	public function toSchema(): array
	{
		return [
			'controller' => $this->getController(),
			'method' => $this->getMethodName(),
			'dependencies' => $this->getDependencies(),
		];
	}
}