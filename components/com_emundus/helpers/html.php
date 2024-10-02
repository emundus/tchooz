<?php
/**
 * @package     Joomla.Administrator
 *              com_emundus
 *              helpers
 *              html.php
 *              - Helper for HTML
 *              - This file is part of the Emundus component
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Content Component Query Helper
 *
 * @static
 * @package        Joomla
 * @subpackage     Content
 * @since          1.5
 */
class EmundusHelperHtml
{

	public static function getConfig($override_config = [])
	{
		return (new HtmlSanitizerConfig())
			->allowSafeElements()

			// Allow all static elements and attributes from the W3C Sanitizer API
			// standard. All scripts will be removed but the output may still contain
			// other dangerous behaviors like CSS injection (click-jacking), CSS
			// expressions, ...
			->allowStaticElements()

			// Allow the "div" element and no attribute can be on it
			->allowElement('div')

			// Allow the "div" element and no attribute can be on it
			->allowElement('img', ['src'])

			// Allow the "a" element, and the "title" attribute to be on it
			->allowElement('a', ['title'])

			// Allow the "span" element, and any attribute from the Sanitizer API is allowed
			// (see https://wicg.github.io/sanitizer-api/#default-configuration)
			->allowElement('span', '*')

			// Block the "section" element: this element will be removed but
			// its children will be retained
			->blockElement('section')

			// Drop the "div" element: this element will be removed, including its children
			->dropElement('div')

			// Allow the attribute "title" on the "div" element
			->allowAttribute('title', ['div'])

			// Allow the attribute "data-custom-attr" on all currently allowed elements
			->allowAttribute('data-custom-attr', '*')

			// Drop the "data-custom-attr" attribute from the "div" element:
			// this attribute will be removed
			->dropAttribute('data-custom-attr', ['div'])

			// Drop the "data-custom-attr" attribute from all elements:
			// this attribute will be removed
			->dropAttribute('data-custom-attr', '*')

			// Forcefully set the value of all "rel" attributes on "a"
			// elements to "noopener noreferrer"
			->forceAttribute('a', 'rel', 'noopener noreferrer')

			// Transform all HTTP schemes to HTTPS
			->forceHttpsUrls()

			// Configure which schemes are allowed in links (others will be dropped)
			->allowLinkSchemes(['https', 'http', 'mailto'])

			// Configure which hosts are allowed in links (by default all are allowed)
			->allowLinkHosts(['symfony.com', 'example.com'])

			// Allow relative URL in links (by default they are dropped)
			->allowRelativeLinks()

			// Configure which schemes are allowed in img/audio/video/iframe (others will be dropped)
			->allowMediaSchemes(['https', 'http'])

			// Configure which hosts are allowed in img/audio/video/iframe (by default all are allowed)
			->allowMediaHosts(['symfony.com', 'example.com'])

			// Allow relative URL in img/audio/video/iframe (by default they are dropped)
			->allowRelativeMedias();
	}

	public static function sanitize($input, $config = [])
	{
		$config = self::getConfig($config);
		$sanitizer = new HtmlSanitizer($config);
		return $sanitizer->sanitize($input);
	}
}