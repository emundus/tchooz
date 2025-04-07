<?php

namespace classes\Entities\Settings;

Class AddonEntity
{
	public string $name = '';
	public string $type = '';
	public string $icon = '';
	public string $description = '';
	public string $configuration = '';
	public int $enabled = 0;
	public int $displayed = 1;

	public function __construct(
		string $name,
		string $type,
		string $icon,
		string $description,
		string $configuration = '',
		int $enabled = 0,
		int $displayed = 1
	) {
		$this->name = $name;
		$this->type = $type;
		$this->icon = $icon;
		$this->description = $description;
		$this->configuration = $configuration;
		$this->enabled = $enabled;
		$this->displayed = $displayed;
	}

	public function setConfiguration(array $configuration): void
	{
		$this->configuration = json_encode($configuration);
	}

	public function setEnabled(int $enabled): void
	{
		$this->enabled = $enabled;
	}

	public function setDisplayed(int $displayed): void
	{
		$this->displayed = $displayed;
	}

	public function getConfiguration(): array
	{
		return json_decode($this->configuration, true);
	}
}