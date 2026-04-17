<?php
/**
 * @package     Tchooz\Enums\Menus
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

declare(strict_types=1);

namespace Tchooz\Enums\Menus;

// Enum used only for backward compatibility
enum MenuHeadingEnum: string
{
	case ADD    = '2014-09-25-11-03-23';
	case EDIT   = '2014-09-25-11-03-52';
	case SEND   = '2014-09-25-11-04-10';
	case EXPORT = '2014-09-25-11-04-41';

	public function getAlias(): string
	{
		return $this->value;
	}

	public function getLabelFr(): string
	{
		return match ($this)
		{
			self::ADD    => 'Ajouter',
			self::EDIT   => 'Modifier',
			self::SEND   => 'Envoyer',
			self::EXPORT => 'Exporter',
		};
	}

	public function getLabelEn(): string
	{
		return match ($this)
		{
			self::ADD    => 'Add',
			self::EDIT   => 'Edit',
			self::SEND   => 'Send',
			self::EXPORT => 'Export',
		};
	}

	public function getLabel(string $locale): string
	{
		return match (strtolower($locale))
		{
			'en', 'en-gb', 'en-us' => $this->getLabelEn(),
			default                => $this->getLabelFr(),
		};
	}

	public function getOrdering(): int
	{
		return match ($this)
		{
			self::ADD    => 0,
			self::EDIT   => 1,
			self::SEND   => 2,
			self::EXPORT => 3,
		};
	}

	public static function fromAlias(string $alias): ?self
	{
		return self::tryFrom($alias);
	}

	/**
	 * Get all headings sorted by ordering.
	 *
	 * @return  array<MenuHeadingEnum>
	 */
	public static function sorted(): array
	{
		$cases = self::cases();
		usort($cases, fn(self $a, self $b) => $a->getOrdering() <=> $b->getOrdering());

		return $cases;
	}
}
