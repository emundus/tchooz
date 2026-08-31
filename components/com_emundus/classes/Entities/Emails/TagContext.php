<?php
/**
 * @package     Tchooz\Entities\Emails
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails;

/**
 * Immutable carrier for the inputs a tag provider needs to compute its values.
 *
 * Passing a context object (rather than a long parameter list) lets new inputs
 * be added later without changing every provider signature.
 */
class TagContext
{
	public function __construct(
		private int $userId = 0,
		private ?string $fnum = null,
		private ?array $post = null,
		private string $passwd = '',
		private string $content = '',
		private bool $base64 = false,
		/**
		 * Modifiers written on the tag occurrence being resolved, as TagEntity parses them:
		 * a list of ['modifier' => TagModifierInterface, 'params' => array].
		 * A provider whose value depends on them reads this instead of relying on transform(),
		 * which can only rework an already computed string.
		 */
		private array $modifiers = []
	) {}

	/**
	 * @return array<array{modifier: \Tchooz\Interfaces\TagModifierInterface, params: array}>
	 */
	public function getModifiers(): array
	{
		return $this->modifiers;
	}

	/**
	 * Parameters of the first occurrence of a modifier, by modifier class.
	 *
	 * @return array
	 */
	public function getModifierParams(string $modifierClass): array
	{
		foreach ($this->modifiers as $modifier)
		{
			if ($modifier['modifier'] instanceof $modifierClass)
			{
				return $modifier['params'] ?? [];
			}
		}

		return [];
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getFnum(): ?string
	{
		return $this->fnum;
	}

	public function getPost(): ?array
	{
		return $this->post;
	}

	public function getPasswd(): string
	{
		return $this->passwd;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function isBase64(): bool
	{
		return $this->base64;
	}
}
