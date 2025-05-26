<?php
/**
 * @package     classes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

trait TraitResponse
{
	public function sendJsonResponse(array $response): void
	{
		header('Content-Type: application/json; charset=utf-8');

		if ($response['code'] === 400)
		{
			header('HTTP/1.1 400 Bad Request');
		}
		elseif ($response['code'] === 401)
		{
			header('HTTP/1.1 401 Unauthorized');
		}
		elseif ($response['code'] === 402)
		{
			header('HTTP/1.1 402 Payment Required');
		}
		elseif ($response['code'] === 403)
		{
			header('HTTP/1.1 403 Forbidden');
		}
		elseif ($response['code'] === 404)
		{
			header('HTTP/1.1 404 Not Found');
		}
		elseif ($response['code'] === 500)
		{
			header('HTTP/1.1 500 Internal Server Error');
		}

		echo json_encode($response);
		exit;
	}
}