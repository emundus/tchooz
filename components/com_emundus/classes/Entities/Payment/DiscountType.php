<?php
namespace Tchooz\Entities\Payment;

enum DiscountType: string {
	case PERCENTAGE = 'percentage';
	case FIXED = 'fixed';

	public function getSymbol()
	{
		return match($this) {
			self::PERCENTAGE => '%',
			self::FIXED => '$',
		};
	}

	public static function getInstance(string $type)
	{
		return match($type) {
			'percentage' => self::PERCENTAGE,
			'fixed' => self::FIXED,
			default => throw new \InvalidArgumentException('Invalid discount type'),
		};
	}
}
