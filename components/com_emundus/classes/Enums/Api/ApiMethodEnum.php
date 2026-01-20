<?php

namespace Tchooz\Enums\Api;

enum ApiMethodEnum: string
{
	case GET = 'GET';
	case POST = 'POST';
	case PATCH = 'PATCH';
	case PUT = 'PUT';
	case DELETE = 'DELETE';
}
