<?php

namespace Tchooz\Enums\Payment;

enum WorldlineEnvironmentEnum: string
{
	case PREPROD    = 'PREPROD';
	case PRODUCTION = 'PRODUCTION';

	/**
	 * Sandbox accounts run on the pre-production endpoint, there is no dedicated sandbox host.
	 *
	 * @return string
	 */
	public function getEndpoint(): string
	{
		return match ($this)
		{
			self::PREPROD    => 'https://api.preprod.connect.worldline-solutions.com',
			self::PRODUCTION => 'https://api.connect.worldline-solutions.com',
		};
	}

	public static function fromProductionFlag(mixed $productionMode): self
	{
		return !empty($productionMode) ? self::PRODUCTION : self::PREPROD;
	}
}
