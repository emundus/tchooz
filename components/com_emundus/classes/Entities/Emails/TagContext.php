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
		private bool $base64 = false
	) {}

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
