<?php
/**
 * @package     Tchooz\Entities\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Addons;

class AddonEntity
{
	private string $namekey;

	private AddonValue $value;

	public function __construct(string $namekey, AddonValue $value)
	{
		$this->namekey = $namekey;
		$this->value = $value;
	}

	public function getNamekey(): string
	{
		return $this->namekey;
	}

	public function setNamekey(string $namekey): void
	{
		$this->namekey = $namekey;
	}

	public function getValue(): AddonValue
	{
		return $this->value;
	}

	public function setValue(AddonValue $value): void
	{
		$this->value = $value;
	}


}