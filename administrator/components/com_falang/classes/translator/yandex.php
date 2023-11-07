<?php
// Check to ensure this file is included in Joomla!
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined( '_JEXEC' ) or die( 'Restricted access' );

class TranslatorYandex extends TranslatorDefault {
	
	function __construct()
	{
		$params = ComponentHelper::getParams('com_falang');
		$token = $params->get('translator_yandexkey');
		if (strlen($token) < 20){
			Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_INVALID_YANDEX_KEY'), 'error');
			return;
		}
		
		$script = "var YandexKey = '".$token."';\n";

		$document = Factory::getDocument();
		$document->addScriptDeclaration($script,'text/javascript');
		
		$this->script = 'translatorYandex.js';
	}
}