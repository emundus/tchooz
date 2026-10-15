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
use Joomla\CMS\Log\Log;

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

	/**
	 * Absolute path to the Dompdf font cache directory where registered fonts and
	 * installed-fonts.json are written.
	 */
	private static function fontCacheDir(): string
	{
		return JPATH_LIBRARIES . '/emundus/vendor/dompdf/dompdf/lib/fonts';
	}

	/**
	 * Drop the cached Unicode font when it no longer matches the current deployment.
	 *
	 * Dompdf names the cache file after md5() of the @font-face `src` URL (see
	 * FontMetrics::registerFont), and that URL embeds the absolute font path. The
	 * hash therefore changes whenever the deployment path changes (local vs Docker,
	 * one site/database vs another). A cache left over from another environment
	 * points installed-fonts.json at a file that does not exist here, so Dompdf
	 * cannot load the font and renders "??" for non-Latin glyphs. Clearing the
	 * stale cache lets Dompdf rebuild it for the current path on the next render.
	 */
	public static function ensureFontCacheIsFresh(): void
	{
		if (!self::isAvailable())
		{
			return;
		}

		$fontDir       = self::fontCacheDir();
		$installedFile = $fontDir . '/installed-fonts.json';
		$expectedEntry = 'emundus_unicode_normal_' . md5('file://' . self::fontPath());

		if (self::isFontCacheValid($installedFile, $fontDir, $expectedEntry))
		{
			return;
		}

		self::clearFontCache($fontDir, $installedFile);
	}

	/**
	 * The cache is valid only when installed-fonts.json points the Unicode family
	 * at the entry expected for the current path AND the matching .ufm / .ttf files
	 * are present on disk.
	 */
	private static function isFontCacheValid(string $installedFile, string $fontDir, string $expectedEntry): bool
	{
		if (!is_file($installedFile)
			|| !is_file($fontDir . '/' . $expectedEntry . '.ufm')
			|| !is_file($fontDir . '/' . $expectedEntry . '.ttf'))
		{
			return false;
		}

		$installed = json_decode((string) @file_get_contents($installedFile), true);

		return isset($installed[self::FAMILY]['normal']) && $installed[self::FAMILY]['normal'] === $expectedEntry;
	}

	/**
	 * Remove installed-fonts.json and every emundus_unicode_normal_* artefact so
	 * Dompdf regenerates them for the current environment.
	 */
	private static function clearFontCache(string $fontDir, string $installedFile): void
	{
		try
		{
			if (is_file($installedFile))
			{
				unlink($installedFile);
			}

			foreach (glob($fontDir . '/emundus_unicode_normal_*') ?: [] as $file)
			{
				if (is_file($file))
				{
					unlink($file);
				}
			}
		}
		catch (\Throwable $e)
		{
			Log::add('Could not clear stale Dompdf font cache: ' . $e->getMessage(), Log::WARNING, 'com_emundus.export.pdf');
		}
	}
}
