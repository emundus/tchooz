<?php
/**
 * @package     Tchooz\Interfaces
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Interfaces;

use Tchooz\Entities\Emails\TagContext;

/**
 * Contract for a class that resolves one cohesive group of constant email tags.
 *
 * Each provider owns its own tags end to end: it declares which tags it knows,
 * whether the current context lets it run, and how to compute their values.
 * Adding a new constant tag means adding (or extending) a provider, never
 * editing a monolithic method.
 */
interface TagProviderInterface
{
	/**
	 * Unique key for the provider (registry indexing, debugging).
	 */
	public function getName(): string;

	/**
	 * Tag names this provider can resolve, without brackets (e.g. ['LAST_CONFIRMED_TRANSACTION_AMOUNT']).
	 * Used to skip the provider when none of its tags appear in the content.
	 *
	 * @return string[]
	 */
	public function getProvidedTags(): array;

	/**
	 * Whether the provider can run given the current context (e.g. requires a fnum).
	 */
	public function supports(TagContext $context): bool;

	/**
	 * Compute the tag values for the given context.
	 *
	 * @return array<string, string|int|null> map of tag name (no brackets) => value
	 */
	public function provide(TagContext $context): array;
}
