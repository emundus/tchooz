<?php
/**
 * @package     Tchooz\Entities\Emails\Modifiers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails\Modifiers;

use Tchooz\Interfaces\TagModifierInterface;

class LowercaseModifier implements TagModifierInterface
{
	private array $params = [];


	public function getName(): string
	{
		return 'LOWERCASE';
	}

	public function getLabel(): string
	{
		return 'Lowercase';
	}

	public function transform(string $value, array $params = []): string
	{
		return strtolower($value);
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	public function getParams(): array
	{
		return $this->params;
	}
}