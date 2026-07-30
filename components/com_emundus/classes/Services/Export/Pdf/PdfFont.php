<?php
/**
 * @package     Tchooz
 * @subpackage  Services.Export.Pdf
 *
 * Centralises Unicode font configuration for every Dompdf export so that
 * non-Latin scripts (Cyrillic, CJK …) are rendered instead of producing
 * blank / "tofu" glyphs.
 *
 * Two hard constraints drive this design:
 *   1. Dompdf performs NO glyph-level font fallback. A single font family must
 *      cover every glyph used in the document.
 *   2. Dompdf's subsetter (php-font-lib) only supports TrueType (`glyf`)
 *      outlines. CFF / OpenType-PostScript fonts such as "Noto Sans CJK" or
 *      "Source Han Sans" CANNOT be used — the shipped font MUST be a TrueType
 *      font that covers Latin + Cyrillic + CJK (e.g. Sarasa Gothic,
 *      WenQuanYi Zen Hei, Droid Sans Fallback).
 *
 * When the Unicode font file is absent the configuration degrades gracefully
 * to the bundled "DejaVu Sans" (covers Latin + Cyrillic, but not CJK).
 */

namespace Tchooz\Services\Export\Pdf;

use Dompdf\Options;

defined('_JEXEC') or die;

final class PdfFont
{
	/** Font family name exposed to CSS and Dompdf's defaultFont. */
	public const FAMILY = 'emundus-unicode';

	/** Bundled fallback family that at least covers Cyrillic. */
	private const FALLBACK_FAMILY = 'dejavu sans';

	/**
	 * Absolute path to the TrueType Unicode font shipped with the component.
	 */
	public static function fontPath(): string
	{
		return JPATH_LIBRARIES . '/emundus/fonts/emundus-unicode.ttf';
	}

	/**
	 * Whether the wide-coverage Unicode font is actually available on disk.
	 */
	public static function isAvailable(): bool
	{
		return is_file(self::fontPath());
	}

	/**
	 * Apply Unicode-friendly settings to a Dompdf Options instance.
	 *
	 * @param   Options  $options  Options instance to mutate.
	 *
	 * @return  Options  The same instance, configured.
	 */
	public static function configureOptions(Options $options): Options
	{
		$options->set('isFontSubsettingEnabled', true);

		if (!self::isAvailable())
		{
			// No CJK font shipped: fall back to a Cyrillic-capable bundled font.
			$options->set('defaultFont', self::FALLBACK_FAMILY);

			return $options;
		}

		$options->set('defaultFont', self::FAMILY);

		// Allow Dompdf to read the local font file referenced by @font-face.
		$chroot   = (array) $options->getChroot();
		$chroot[] = dirname(self::fontPath());
		$options->setChroot(array_values(array_unique(array_filter($chroot))));

		return $options;
	}

	/**
	 * Inject the @font-face declaration and force the document font-family so
	 * the Unicode font is actually used for the rendered text.
	 *
	 * @param   string  $html  The HTML document to render.
	 *
	 * @return  string  The HTML with the font declaration injected.
	 */
	public static function injectFontFace(string $html): string
	{
		if (!self::isAvailable())
		{
			return $html;
		}

		$fontUrl = 'file://' . self::fontPath();

		$style = '<style>'
			. '@font-face { font-family: "' . self::FAMILY . '"; font-style: normal; font-weight: normal;'
			. ' src: url("' . $fontUrl . '") format("truetype"); }'
			. 'body, table, td, th, div, p, span, h1, h2, h3, h4, h5, h6, li, a, b, strong, i, em'
			. ' { font-family: "' . self::FAMILY . '", "' . self::FALLBACK_FAMILY . '", sans-serif; }'
			. '</style>';

		if (stripos($html, '</head>') !== false)
		{
			return preg_replace('/<\/head>/i', $style . '</head>', $html, 1);
		}

		if (stripos($html, '<body') !== false)
		{
			return preg_replace('/(<body[^>]*>)/i', '$1' . $style, $html, 1);
		}

		return $style . $html;
	}
}
