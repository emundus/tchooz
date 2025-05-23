<?php
// Check to ensure this file is included in Joomla!
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined( '_JEXEC' ) or die( 'Restricted access' );

class TranslatorLingvanex extends TranslatorDefault {
	
	function __construct()
	{
		$params = ComponentHelper::getParams('com_falang');
		if (strlen($params->get('translator_lingvanex')) < 60){
			Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_INVALID_LINGVANEX_KEY'), 'error');
			return;
		}

		if(!function_exists('curl_init')) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_CURL_LINGVANEX_MESSAGE'), 'error');
			return;
		}

		//region non necessary for global endpoint
		$script = "var LingvanexKey = '".$params->get('translator_lingvanex')."';\n";

		$document = Factory::getDocument();
		$document->addScriptDeclaration($script,'text/javascript');
		
		$this->script = 'translatorLingvanex.js';
	}

	//override function
	//lingvanex don't need lowercase format for language
	public function installScripts ($from, $to) {
		$script = "var translator = {'from' : '".$from. "','to' : '".$to. "'};\n";

		$document = Factory::getDocument();
		$document->addScriptDeclaration($script,'text/javascript');


		if ($this->script != NULL){
			$document = Factory::getDocument();
			$document->addScript('components/com_falang/assets/js/'.$this->script);
		}
	}
	//return the language code in specific format aa_AA
	//The language code is represented only in lowercase letters, the country code only in uppercase letters
	//example en_GB, es_ES, ru_RU
	public function languageCodeToISO ($language){
		$lang_code = substr($language,0,strpos($language, '-'));
		$country_code = strtoupper(substr($language,strpos($language,'-')+1));
		return $lang_code.'_'.$country_code;
	}
}