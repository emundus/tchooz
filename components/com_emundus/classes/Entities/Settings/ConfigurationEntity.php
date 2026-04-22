<?php
/**
 * @package     Tchooz\Entities\Settings
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Settings;

class ConfigurationEntity
{
	private string $namekey;

	private array $value;

	private ?string $default;

	/**
	 * @param   string  $namekey
	 * @param   array  $value
	 * @param   string  $default
	 */
	public function __construct(string $namekey, array $value, ?string $default = '')
	{
		$this->namekey = $namekey;
		$this->value   = $value;
		$this->default = $default;
	}

	public function getNamekey(): string
	{
		return $this->namekey;
	}

	public function setNamekey(string $namekey): void
	{
		$this->namekey = $namekey;
	}

	public function getValue(): array
	{
		return $this->value;
	}

	public function setValue(array $value): void
	{
		$this->value = $value;
	}

	public function getDefault(): ?string
	{
		return $this->default;
	}

	public function setDefault(?string $default): void
	{
		$this->default = $default;
	}
}