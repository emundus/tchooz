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
use DOMElement;
use DOMXPath;

defined('_JEXEC') or die('Restricted access');

/**
 * Flattens HTML lists (<ul>/<ol>) into paragraphs whose bullet or number is rendered as literal text,
 * indenting nested levels with non-breaking spaces.
 */
class HtmlListTransformer
{
	/** Bullet glyphs per nesting depth (cycles when nesting goes deeper than the list). */
	private const BULLETS = ['•', '◦', '▪'];

	/** Indentation prepended once per nesting level. */
	private const INDENT = "\u{00A0}\u{00A0}\u{00A0}\u{00A0}";

	/** Block-level children of an <li> that must become their own paragraph rather than nest inside one. */
	private const BLOCK_TAGS = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'];

	public static function transform(string $html): string
	{
		// Tolerance: nothing to flatten when the content holds no list at all.
		if (stripos($html, '<ul') === false && stripos($html, '<ol') === false)
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

		$xpath = new DOMXPath($dom);
		// Only the outermost lists; nested ones are expanded by recursion.
		$topLists = $xpath->query('//ul[not(ancestor::ul) and not(ancestor::ol)] | //ol[not(ancestor::ul) and not(ancestor::ol)]');

		foreach ($topLists as $list)
		{
			$paragraphs = [];
			self::expandList($dom, $list, 0, $paragraphs);

			foreach ($paragraphs as $paragraph)
			{
				$list->parentNode->insertBefore($paragraph, $list);
			}
			$list->parentNode->removeChild($list);
		}

		// Serialize the body's inner HTML back to a fragment.
		$result = '';
		foreach ($body->childNodes as $child)
		{
			$result .= $dom->saveHTML($child);
		}

		return $result;
	}

	/**
	 * Appends one or more <p> per <li> to $paragraphs, recursing into nested lists so they keep their order.
	 *
	 * $counter holds the running number of the enclosing ordered hierarchy. It is shared by reference so a
	 * nested <ol> continues its parent's numbering (1…7, then 8, 9, then 10) instead of restarting at 1 —
	 * matching how the rich-text editor renders multi-level ordered lists. It stays null until the first
	 * ordered list is reached, so an unordered hierarchy never allocates one.
	 */
	private static function expandList(DOMDocument $dom, DOMElement $list, int $depth, array &$paragraphs, ?int &$counter = null): void
	{
		$ordered = strtolower($list->nodeName) === 'ol';

		// The outermost ordered list seeds the shared counter (honouring its start attribute); deeper
		// ordered lists keep counting from where their parent left off.
		if ($ordered && $counter === null)
		{
			$counter = self::startIndex($list);
		}

		foreach ($list->childNodes as $child)
		{
			if (!($child instanceof DOMElement))
			{
				continue;
			}

			$name = strtolower($child->nodeName);

			// A list nested directly inside another list (sibling of <li>) is invalid HTML some editors
			// still emit; recurse instead of dropping its items on the floor.
			if (in_array($name, ['ul', 'ol'], true))
			{
				self::expandList($dom, $child, $depth + 1, $paragraphs, $counter);
				continue;
			}

			if ($name !== 'li')
			{
				continue;
			}

			if ($ordered)
			{
				$marker = $counter . '. ';
				$counter++;
			}
			else
			{
				$marker = self::BULLETS[$depth % count(self::BULLETS)] . ' ';
			}

			self::expandItem($dom, $child, $depth, $marker, $paragraphs, $counter);
		}
	}

	/**
	 * Renders one <li> as one or more <p>: the first carries the marker, block children (<p>, <div>, …)
	 * start their own continuation paragraph instead of nesting <p> inside <p>, and nested lists expand after.
	 */
	private static function expandItem(DOMDocument $dom, DOMElement $item, int $depth, string $marker, array &$paragraphs, ?int &$counter = null): void
	{
		$indent  = str_repeat(self::INDENT, $depth);
		$content = [];
		$current = $dom->createElement('p');
		$nested  = [];

		foreach ($item->childNodes as $node)
		{
			if ($node instanceof DOMElement && in_array(strtolower($node->nodeName), ['ul', 'ol'], true))
			{
				$nested[] = $node;
				continue;
			}

			// Lifting the inline children avoids the invalid <p><p>…</p></p> that splits the marker from its text.
			if ($node instanceof DOMElement && in_array(strtolower($node->nodeName), self::BLOCK_TAGS, true))
			{
				if ($current->hasChildNodes())
				{
					$content[] = $current;
					$current   = $dom->createElement('p');
				}

				foreach ($node->childNodes as $inline)
				{
					$current->appendChild($inline->cloneNode(true));
				}

				$content[] = $current;
				$current   = $dom->createElement('p');

				continue;
			}

			$current->appendChild($node->cloneNode(true));
		}

		if ($current->hasChildNodes())
		{
			$content[] = $current;
		}

		// An empty <li> still renders its marker on one line.
		if (empty($content))
		{
			$content[] = $dom->createElement('p');
		}

		// The marker lands on the first line; continuation lines keep the item indent, aligned past the marker.
		foreach ($content as $i => $paragraph)
		{
			$prefix = $i === 0 ? ($indent . $marker) : ($indent . self::INDENT);
			$paragraph->insertBefore($dom->createTextNode($prefix), $paragraph->firstChild);
			$paragraphs[] = $paragraph;
		}

		// A nested list renders as further paragraphs right after its parent item; the shared counter
		// flows in so a nested <ol> continues this item's numbering instead of restarting.
		foreach ($nested as $nestedList)
		{
			self::expandList($dom, $nestedList, $depth + 1, $paragraphs, $counter);
		}
	}

	private static function startIndex(DOMElement $list): int
	{
		$start = $list->getAttribute('start');

		return ($start !== '' && is_numeric($start)) ? (int) $start : 1;
	}
}