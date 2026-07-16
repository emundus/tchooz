<?php
/**
 * @package     classes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

use Joomla\CMS\Log\Log;
use Tchooz\EmundusResponse;

trait TraitResponse
{
	public function sendJsonResponse(array|EmundusResponse|null $response): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if(empty($response))
		{
			$response = EmundusResponse::fail('An unknown error occurred.', 500);
		}
		else
		{
			$code = 400;
			if ($response instanceof EmundusResponse)
			{
				$code = $response->code;
			}
			else
			{
				if (isset($response['code']))
				{
					$code = $response['code'];
				}
			}

			$header = 'HTTP/1.1 ' . $code . ' ' . EmundusResponse::$statusTexts[$code];
			header($header);
		}

		$json = json_encode($response);

		if ($json === false)
		{
			Log::add('classes/Traits/TraitResponse | json_encode failed: ' . json_last_error_msg(), Log::ERROR, 'com_emundus');

			header('HTTP/1.1 500 ' . EmundusResponse::$statusTexts[500]);
			$json = json_encode(EmundusResponse::fail('Failed to encode response: ' . json_last_error_msg(), 500));
		}

		echo $json;
		exit;
	}
}