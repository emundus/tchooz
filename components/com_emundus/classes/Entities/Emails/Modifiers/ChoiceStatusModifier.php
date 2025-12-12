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

class ChoiceStatusModifier implements TagModifierInterface
{
	private array $params = [];

	public function getName(): string
	{
		return 'STATUS';
	}

	public function getLabel(): string
	{
		return 'Status';
	}

	public function transform(string $value, array $params = []): string
	{
		return $value;
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