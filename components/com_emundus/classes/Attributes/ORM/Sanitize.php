<?php
/**
 * @package     Tchooz\Attributes\ORM
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Attributes\ORM;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Sanitize
{
	/**
	 * Sanitize a full HTML string using the default safe configuration.
	 */
	public const MODE_HTML    = 'html';

	/**
	 * Strip all HTML tags and prevent formula injection.
	 */
	public const MODE_NO_HTML = 'noHtml';

	/**
	 * Sanitize a string for a specific HTML5 section (e.g. "body", "head").
	 * Requires the {@see $section} property to be set.
	 */
	public const MODE_FOR     = 'for';

	/**
	 * @param string      $mode    One of self::MODE_*
	 * @param string|null $section HTML5 section used when $mode === self::MODE_FOR (e.g. "body")
	 */
	public function __construct(
		public readonly string $mode = self::MODE_HTML,
		public readonly ?string $section = null
	) {
	}
}
