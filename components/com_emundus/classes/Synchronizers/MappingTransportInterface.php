<?php

namespace Tchooz\Synchronizers;

/**
 * Transport contract a synchronizer must fulfil to be driven by the mapping executor.
 *
 * A transport authenticates and carries requests (GET/POST/PATCH) to the remote API. It is
 * ignorant of business objects and routes: it receives an already-built route + payload and
 * transports it. Business knowledge (which route, which payload) lives in the mapping objects.
 *
 * The signatures mirror Tchooz\api\Api so any synchronizer extending Api satisfies this contract
 * for free.
 */
interface MappingTransportInterface
{
	public function getBaseUrl(): string;

	public function get(string $url, array $params = [], array $headers = []);

	public function post($url, $body = null, $headers = array(), $asMultipart = false);

	public function patch($url, $body = null);
}
