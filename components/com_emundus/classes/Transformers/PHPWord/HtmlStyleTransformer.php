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
 * Strips the inline css PhpWord turns into invalid or nonsensical OOXML.
 *
 * Content pasted from Word carries measurements the html parser mis-reads: it looks for an integer,
 * so "width: 38.9858%" becomes 9858% (the maximum Word accepts is 100%) and "width: 105.6pt" becomes
 * 6pt, while any height becomes a fixed row height Word refuses when it is not a round number. Fonts
 * and colors are copied verbatim, so "'Calibri',sans-serif" and "black" reach Word as unknown values.
 * Everything removed here is layout the editor decided on its own; the structure, the text and its
 * emphasis are untouched.
 */
class HtmlStyleTransformer
{
	/**
	 * Declarations that carry no meaning in a Word document, or that PhpWord cannot translate at all.
	 * A property is dropped as soon as it starts with one of these.
	 */
	private const DROPPED_PROPERTIES = ['mso-', 'tab-stops', 'text-autospace', 'height', 'padding', 'margin'];

	/** Properties Word only accepts as a full hexadecimal color. */
	private const COLOR_PROPERTIES = ['color', 'background-color', 'border-color'];

	/** Attributes holding the same editor measurements as the styles above. */
	private const DROPPED_ATTRIBUTES = ['width', 'height'];

	/**
	 * Elements whose width is dropped altogether. PhpWord hands the width of a table down to every
	 * cell that has none of its own, so a table width fills each column with the width of the whole
	 * table; Word lays the table out from its cells anyway.
	 */
	private const WIDTHLESS_ELEMENTS = ['table', 'colgroup', 'col'];

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

		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//*[@style]') as $element)
		{
			/** @var DOMElement $element */
			$style = self::cleanStyle($element->getAttribute('style'), strtolower($element->nodeName));

			if ($style === '')
			{
				$element->removeAttribute('style');
			}
			else
			{
				$element->setAttribute('style', $style);
			}
		}

		foreach (self::DROPPED_ATTRIBUTES as $attribute)
		{
			foreach ($xpath->query('//*[@' . $attribute . ']') as $element)
			{
				/** @var DOMElement $element */
				$element->removeAttribute($attribute);
			}
		}

		$result = '';
		foreach ($body->childNodes as $child)
		{
			$result .= $dom->saveHTML($child);
		}

		return $result;
	}

	private static function cleanStyle(string $style, string $tagName): string
	{
		$declarations = [];

		foreach (explode(';', $style) as $declaration)
		{
			if (!str_contains($declaration, ':'))
			{
				continue;
			}

			[$property, $value] = explode(':', $declaration, 2);

			$property = strtolower(trim($property));
			$value    = trim($value);

			if ($property === '' || $value === '')
			{
				continue;
			}

			if (self::isDropped($property))
			{
				continue;
			}

			if ($property === 'width')
			{
				$value = in_array($tagName, self::WIDTHLESS_ELEMENTS, true) ? '' : self::roundedSize($value);
			}
			elseif ($property === 'font-family')
			{
				$value = self::firstFontFamily($value);
			}
			elseif (in_array($property, self::COLOR_PROPERTIES, true))
			{
				$value = self::hexColor($value);
			}

			if ($value === '')
			{
				continue;
			}

			$declarations[] = $property . ': ' . $value;
		}

		return implode('; ', $declarations);
	}

	private static function isDropped(string $property): bool
	{
		foreach (self::DROPPED_PROPERTIES as $dropped)
		{
			if (str_starts_with($property, $dropped))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Rounds a size to the integer PhpWord looks for, keeping its unit: it reads "38.9858%" as 9858%
	 * and "105.6pt" as 6pt.
	 */
	private static function roundedSize(string $value): string
	{
		if (!preg_match('/^([0-9]*\.?[0-9]+)\s*([a-z%]*)$/i', $value, $matches))
		{
			return '';
		}

		$size = (int) round((float) $matches[1]);

		// A width rounded down to nothing is not a width anymore.
		if ($size < 1)
		{
			return '';
		}

		return $size . strtolower($matches[2]);
	}

	/**
	 * Word looks up the font by name, so it must be given one name, without the css quotes.
	 */
	private static function firstFontFamily(string $value): string
	{
		$families = explode(',', $value);
		$family   = trim(trim(trim($families[0]), '"\''));

		return $family;
	}

	/**
	 * Word reads a color as 6 hexadecimal digits; a css name reaches it as an unknown value.
	 */
	private static function hexColor(string $value): string
	{
		$value = ltrim(trim($value), '#');

		if (preg_match('/^[0-9a-f]{3}$/i', $value))
		{
			// Word does not accept the shortened form.
			$value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
		}

		if (!preg_match('/^[0-9a-f]{6}$/i', $value))
		{
			return '';
		}

		return '#' . $value;
	}
}
