<?php
// Check to ensure this file is included in Joomla!
use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die('Restricted access');

JLoader::discover('Translator', FALANG_ADMINPATH . DS . 'classes' . DS . 'translator');

class translatorFactory
{
	private static $translator;

	static public function getTranslator($target_language_id)
	{
		if (translatorFactory::$translator != null)
		{
			return translatorFactory::$translator;
		}
		$params = ComponentHelper::getParams('com_falang');

		$service    = 'Translator' . $params->get('translator');
		$translator = new $service();

		$falangManager  = FalangManager::getInstance();
		$languageParams = ComponentHelper::getParams('com_languages');

		$from = $translator->languageCodeToISO($languageParams->get('site'));
		$to   = $translator->languageCodeToISO($falangManager->activeLanguagesCacheByID[$target_language_id]->lang_code);

		$translator->installScripts($from, $to);
		translatorFactory::$translator = $translator;

        return $translator;
	}
}