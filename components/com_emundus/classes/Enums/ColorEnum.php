<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums;

enum ColorEnum: string
{
	case LIGHT_PURPLE = '#FBE8FF';
	case PURPLE       = '#EBE9FE';
	case DARK_PURPLE  = '#663399';
	case LIGHT_BLUE   = '#E0F2FE';
	case BLUE         = '#D1E9FF';
	case DARK_BLUE    = '#D1E0FF';
	case LIGHT_GREEN  = '#CCFBEF';
	case GREEN        = '#C4F0E1';
	case DARK_GREEN   = '#BEDBD0';
	case LIGHT_YELLOW = '#FFFD7E';
	case YELLOW       = '#FDF7C3';
	case DARK_YELLOW  = '#FEF0C7';
	case LIGHT_ORANGE = '#FFEDCF';
	case ORANGE       = '#FCEAD7';
	case DARK_ORANGE  = '#FFE5D5';
	case LIGHT_RED    = '#EC644B';
	case RED          = '#FEE4E2';
	case LIGHT_PINK   = '#ffeaea';
	case PINK         = '#FCE7F6';
	case DARK_PINK    = '#FFE4E8';
	case DEFAULT      = '#EBECF0';

	/**
	 * Get a ColorEnum case from its label (e.g. 'lightpurple').
	 */
	public static function fromLabel(string $label): self
	{
		$map = [
			'lightpurple' => self::LIGHT_PURPLE,
			'purple'      => self::PURPLE,
			'darkpurple'  => self::DARK_PURPLE,
			'lightblue'   => self::LIGHT_BLUE,
			'blue'        => self::BLUE,
			'darkblue'    => self::DARK_BLUE,
			'lightgreen'  => self::LIGHT_GREEN,
			'green'       => self::GREEN,
			'darkgreen'   => self::DARK_GREEN,
			'lightyellow' => self::LIGHT_YELLOW,
			'yellow'      => self::YELLOW,
			'darkyellow'  => self::DARK_YELLOW,
			'lightorange' => self::LIGHT_ORANGE,
			'orange'      => self::ORANGE,
			'darkorange'  => self::DARK_ORANGE,
			'lightred'    => self::LIGHT_RED,
			'red'         => self::RED,
			'darkred'     => self::RED,
			'lightpink'   => self::LIGHT_PINK,
			'pink'        => self::PINK,
			'darkpink'    => self::DARK_PINK,
			'default'     => self::DEFAULT,
		];

		return $map[strtolower($label)] ?? self::DEFAULT;
	}
}
