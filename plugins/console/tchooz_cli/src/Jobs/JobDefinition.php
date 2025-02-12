<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs;

class JobDefinition
{
	private string $description;

	public function __construct(
		private readonly string $class,
		private readonly ?array $services = [],
		private readonly ?array $attributes = []
	)
	{
		$this->description = $class::getJobDescription();
	}

	public function instantiate(object $logger): object
	{
		return new $this->class($logger, ...$this->services, ...$this->attributes);
	}

	// Getters
	public function getClass(): string
	{
		return $this->class;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getServices(): array
	{
		return $this->services;
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function toArray(): array
	{
		return [
			'class'       => $this->class,
			'description' => $this->description,
			'services'    => $this->services,
			'attributes'  => $this->attributes,
		];
	}
}