<?php
namespace Tchooz\Entities\Payment;

enum AlterationType: string {
	case PERCENTAGE = 'percentage';
	case FIXED = 'fixed';
	case ADJUST_BALANCE = 'adjust_balance';

	public function getSymbol()
	{
		return match($this) {
			self::PERCENTAGE => '%',
			self::FIXED => '$',
			self::ADJUST_BALANCE => '±',
		};
	}

	public static function getInstance(string $type)
	{
		return match($type) {
			'percentage' => self::PERCENTAGE,
			'fixed' => self::FIXED,
			'adjust_balance' => self::ADJUST_BALANCE,
			default => throw new \InvalidArgumentException('Invalid alteration type'),
		};
	}
}
