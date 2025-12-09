<?php
/**
 * @package     classes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

use Tchooz\Response;

trait TraitResponse
{
	public function sendJsonResponse(array|Response $response): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$code = 400;
		if ($response instanceof Response) {
			$code = $response->code;
		}
		else {
			if (isset($response['code'])) {
				$code = $response['code'];
			}
		}

		$header = 'HTTP/1.1 ' . $code . ' ' . Response::$statusTexts[$code];
		header($header);

		echo json_encode($response);
		exit;
	}
}