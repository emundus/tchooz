<?php
/**
 * @package     Joomla\Plugin\Emundus\Parcoursup\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup\Helper;

class ArrayHelper
{
	public function getNestedValue(array $array, string $path, string $delimiter = '.')
	{
		$keys = explode($delimiter, $path);
		foreach ($keys as $key) {
			if($key == '[]') {
				// If the key is '[]', return an array of all the values corresponding to the next key
				$values = [];
				foreach ($array as $subArray) {
					$values[] = $subArray[$keys[array_search($key, $keys) + 1]];
				}
				return $values;
			}
			if (!isset($array[$key])) {
				return null; // Return null if the key does not exist
			}
			$array = $array[$key]; // Move deeper into the array
		}
		
		return $array;
	}

	public function setNestedValue(array $array, string $path, mixed $value, string $delimiter = '.'): array
	{
		$keys = explode($delimiter, $path);
		$lastKey = array_pop($keys);
		$temp = &$array; // Temporaire pour la modification

		foreach ($keys as $key) {
			if (!isset($temp[$key]) || !is_array($temp[$key])) {
				$temp[$key] = [];
			}
			$temp = &$temp[$key]; // On avance sans modifier `$array`
		}

		$temp[$lastKey] = $value;

		return $array; // Retourne le tableau modifié
	}
}