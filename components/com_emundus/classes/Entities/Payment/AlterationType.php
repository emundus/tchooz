<?php
namespace Tchooz\Entities\Payment;

enum AlterationType: string {
	case PERCENTAGE = 'percentage';
	case FIXED = 'fixed';
	case ADJUST_BALANCE = 'adjust_balance';
	case ALTER_ADVANCE_AMOUNT = 'alter_advance_amount';

	public function getSymbol(): string
	{
		return match($this) {
			self::PERCENTAGE => '%',
			self::FIXED, self::ALTER_ADVANCE_AMOUNT => '$',
			self::ADJUST_BALANCE => '±',
		};
	}

	public static function getInstance(string $type): AlterationType
	{
		return match($type) {
			'percentage' => self::PERCENTAGE,
			'fixed' => self::FIXED,
			'adjust_balance' => self::ADJUST_BALANCE,
			'alter_advance_amount' => self::ALTER_ADVANCE_AMOUNT,
			default => throw new \InvalidArgumentException('Invalid alteration type'),
		};
	}
}
