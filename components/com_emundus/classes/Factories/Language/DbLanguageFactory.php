<?php
namespace Tchooz\Factories\Language;

use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageFactory;
use Tchooz\Services\Language\DbLanguage;

class DbLanguageFactory extends LanguageFactory
{
	public function createLanguage($lang, $debug = false, Language $langInstance = null): Language
	{
		return new DbLanguage($lang, $debug, $langInstance);
	}
}
