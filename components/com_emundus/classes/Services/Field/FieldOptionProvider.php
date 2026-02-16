<?php

namespace Tchooz\Services\Field;

class FieldOptionProvider
{
	/**
	 * @param   string         $controller
	 * @param   string         $methodName
	 * @param   array<string>  $dependencies  Field names that this provider depends on
	 * @param   object|null    $repository
	 * @param   string         $repositoryMethod
	 * @param   array          $repositoryMethodArgs
	 *
	 * @throws \Exception
	 */
	public function __construct(
		private string $controller,
		private string $methodName,
		private array $dependencies = [],
		private ?object $repository = null,
		private string $repositoryMethod = '',
		private array $repositoryMethodArgs = [],
		private string $labelMethod = 'getLabel',
		private string $valueMethod = 'getId',
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

	public function getRepository(): ?object
	{
		return $this->repository;
	}

	public function setRepository(object $repository): self
	{
		$this->repository = $repository;

		return $this;
	}

	public function getRepositoryMethod(): string
	{
		return $this->repositoryMethod;
	}

	public function setRepositoryMethod(string $repositoryMethod): self
	{
		$this->repositoryMethod = $repositoryMethod;

		return $this;
	}

	public function getRepositoryMethodArgs(): array
	{
		return $this->repositoryMethodArgs;
	}

	public function addRepositoryMethodArg(mixed $arg): void
	{
		$this->repositoryMethodArgs[] = $arg;
	}

	public function getLabelMethod(): string
	{
		return $this->labelMethod;
	}

	public function getValueMethod(): string
	{
		return $this->valueMethod;
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