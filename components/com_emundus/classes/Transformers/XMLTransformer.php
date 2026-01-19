<?php

namespace Tchooz\Transformers;

use Joomla\CMS\Log\Log;

class XMLTransformer
{
	public static function fromXMLtoObject(string $xmlContent): ?object
	{
		$object = null;
		if (!empty($xmlContent)) {
			libxml_use_internal_errors(true);

			$xmlObject = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);

			if ($xmlObject === false)
			{
				Log::addLogger(['text_file' => 'com_emundus.xmltransformer.php',], Log::ALL, ['com_emundus.xmltransformer']);
				Log::add('[XML PARSE ERROR]', Log::ERROR, 'com_emundus.xmltransformer');
				libxml_clear_errors();
				return null;
			}

			$object = json_decode(json_encode($xmlObject));
		}
		return $object;
	}
}