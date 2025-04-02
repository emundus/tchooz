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
				->allowSafeElements() // Autorise uniquement les éléments sûrs (sans JS)
				->allowRelativeLinks() // Autorise les liens relatifs
				->forceHttpsUrls() // Force les URLs à être en HTTPS
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
			return $input;
		}

		return $this->sanitizer->sanitize($input);
	}

	public function sanitizeFor(?string $section, string $input): string
	{
		if (empty($input)) {
			return $input;
		}

		return $this->sanitizer->sanitizeFor($section, $input);
	}

	/**
	 * Les méthodes __clone() et __wakeup() sont utilisées pour empêcher la duplication d'une instance du singleton
	 */
	private function __clone() {}
	public function __wakeup() {}
}