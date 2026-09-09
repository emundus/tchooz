<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   Copyright (C) 2024 eMundus. All rights reserved.
 * @license     GNU/GPL
 */

namespace Tchooz\Transformers\PHPWord;

use DOMDocument;

defined('_JEXEC') or die('Restricted access');

/**
 * Rewrites an html fragment as the well-formed xml PhpWord's html parser requires.
 *
 * PhpWord reads html with DOMDocument::loadXML, so anything a rich-text editor legitimately emits but
 * xml rejects — an unclosed void tag (<col>, <br>, <img>) or a named entity — breaks the parse. The
 * failure is silent: no element is produced at all, and the caller ends up writing nothing.
 */
class HtmlXmlTransformer
{
	public static function transform(string $html): string
	{
		if (trim($html) === '')
		{
			return $html;
		}

		$dom      = new DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		// The xml encoding hint forces UTF-8 interpretation of the fragment.
		$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		$body = $dom->getElementsByTagName('body')->item(0);
		if ($body === null)
		{
			return $html;
		}

		$result = '';
		foreach ($body->childNodes as $child)
		{
			$result .= $dom->saveXML($child);
		}

		return $result;
	}
}
