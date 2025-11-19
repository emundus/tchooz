<?php

namespace Component\Emundus\Helpers;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizerSingleton
{
	private static ?self $instance = null;
	private HtmlSanitizer $sanitizer;

	private function __construct(?HtmlSanitizerConfig $config = null)
	{
		if (empty($config)) {
			$config = (new HtmlSanitizerConfig())
				->allowSafeElements()
				->allowRelativeLinks()
				->forceHttpsUrls()
				->allowElement('span', ['class', 'id', 'style'])
				->allowAttribute('span', '*')
				->allowElement('div', ['class', 'id', 'style', 'data-type', 'data-plugin'])
				->allowAttribute('div', ['class', 'id', 'style', 'data-type', 'data-plugin'])
				->allowRelativeMedias(true);
		}

		$this->sanitizer = new HtmlSanitizer($config);
	}

	public static function getInstance(?HtmlSanitizerConfig $config = null): self
	{
		if (self::$instance === null) {
			self::$instance = new self($config);
		}
		return self::$instance;
	}

	public function sanitize(?string $input): string
	{
		if (empty($input)) {
			return '';
		}

		return $this->sanitizer->sanitize($input);
	}

	public function sanitizeNoHtml(?string $input): string
	{
		if (empty($input)) {
			return '';
		}

		// For excel we need to remove = signs if they are at the start of the string to prevent formula injection
		if (str_starts_with($input, '=')) {
			$input = substr($input, 1);
		}

		// Strip all HTML tags, remove all content between script/style tags
		$input = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $input);

		return strip_tags($input);
	}

	public function sanitizeFor(?string $section, string $input): string
	{
		if (empty($input)) {
			return '';
		}

		return $this->sanitizer->sanitizeFor($section, $input);
	}

	/**
	 * Les méthodes __clone() et __wakeup() sont utilisées pour empêcher la duplication d'une instance du singleton
	 */
	private function __clone() {}
	public function __wakeup() {}
}