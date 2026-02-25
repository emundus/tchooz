<?php
/**
 * @package     classes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

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

		echo json_encode($response);
		exit;
	}
}