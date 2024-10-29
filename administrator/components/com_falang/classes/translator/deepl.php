<?php
// Check to ensure this file is included in Joomla!
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined( '_JEXEC' ) or die( 'Restricted access' );

class TranslatorDeepl extends TranslatorDefault {

    /*
     * @update 5.8 fix DeeplLangauge code for Norwegian
     * */
	function __construct()
	{
		$params = ComponentHelper::getParams('com_falang');
		if (strlen($params->get('translator_deeplkey')) < 20){
			Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_INVALID_DEEPL_KEY'), 'error');
			return;
		}

		if(!function_exists('curl_init')) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_FALANG_CURL_DEEPL_MESSAGE'), 'error');
			return;
		}

        $this->setServiceLanguage();

		$this->script = 'translatorDeepl.js';
	}

    /*
     * Add or replace language specific to a translation service
     * @from 5.8
     * */
    private function setServiceLanguage(){
        $this->addServiceLanguage('nb-no','nb');
    }

    /*
     * Translate text with the deppl api
     *
     * @from 5.6
     * @update 5.7 add free/paid url support
     * */
    public function getServiceTranslation(){

        $input = Factory::getApplication()->getInput();
        $sourceLanguageCode        = $input->getString('source');
        $targetLanguageCode        = $input->getString('target');
        $text = $input->getRaw('text');

        //getKey from config
        $serviceKey = ComponentHelper::getParams('com_falang')->get('translator_deeplkey');

        //get pro of free url
        $url = "https://api.deepl.com/v2/translate";
        $serviceFree = ComponentHelper::getParams('com_falang')->get('translator_deepl_free',0);
        if ($serviceFree){
            $url = "https://api-free.deepl.com/v2/translate";
        }


        $postfields = array();
        $postfields['source_lang'] = strtoupper($sourceLanguageCode);
        $postfields['target_lang'] = strtoupper($targetLanguageCode);
        $postfields['text'] = $text;
        $postfields['tag_handling'] = 'html';


        $header = array();
        $header[] = 'Content-Type: application/json';
        $header[] = 'Authorization: DeepL-Auth-Key '.$serviceKey;

        $data = json_encode($postfields);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //Set curl options relating to SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);


        $response          = new \stdClass();

        if( ! $result = curl_exec($ch))
        {
            $error = curl_error($ch);
            $response->success = false;
            $response->data[]  = $error;//allow to display error in the input result
            return $response;
        }
        curl_close($ch);

        $response->success = true;
        $response->data = $result;

        return $response;

    }
}