<?php
/**
 * @package     Tchooz\Services\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Fabrik;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Transformers\CurrencyTransformer;

class CurrencyStorageFormatter
{
	private DatabaseInterface $db;

	private CurrencyTransformer $transformer;

	public function __construct(?DatabaseInterface $db = null)
	{
		$this->db          = $db ?? Factory::getContainer()->get(DatabaseInterface::class);
		$this->transformer = new CurrencyTransformer();
	}

	/**
	 * Reformats currency element values in a [column => value] map to the format the currency
	 * element itself stores ("<number> <symbol> (<iso3>)"). Non-currency columns are left untouched.
	 * Parsing each value through the CurrencyTransformer makes it idempotent and tolerant of the
	 * incoming separators.
	 */
	public function format(array $data): array
	{
		$columnNames = array_values(array_filter(array_keys($data), fn($key) => is_string($key) && $key !== ''));
		if (empty($columnNames))
		{
			return $data;
		}

		$elements = (new FabrikRepository(false))->getElements([
			'plugin' => 'currency',
			'name'   => $columnNames,
		], max(100, count($columnNames)));

		foreach ($elements as $element)
		{
			$column = $element->getName();
			if (!array_key_exists($column, $data) || $data[$column] === null || $data[$column] === '')
			{
				continue;
			}

			$data[$column] = $this->formatValue($data[$column], $element->getParams());
		}

		return $data;
	}

	private function formatValue(mixed $value, object $params): string
	{
		$options = $params->all_currencies_options ?? null;
		if (empty($options))
		{
			return (string) $value;
		}

		// all_currencies_options is a Joomla repeatable field: {all_currencies_options0: {...}, ...}
		$options = (array) $options;
		$option  = reset($options);
		if (!is_object($option))
		{
			return (string) $value;
		}

		$iso3              = $option->iso3 ?? '';
		$decimalNumbers    = isset($option->decimal_numbers) && $option->decimal_numbers !== '' ? (int) $option->decimal_numbers : 2;
		$decimalSeparator  = !empty($option->decimal_separator) ? $option->decimal_separator : ',';
		$thousandSeparator = isset($option->thousand_separator) && $option->thousand_separator !== '' ? $option->thousand_separator : ' ';

		$number = (float) $this->transformer->transform((string) $value);

		return number_format($number, $decimalNumbers, $decimalSeparator, $thousandSeparator) . ' ' . $this->getSymbol($iso3) . ' (' . $iso3 . ')';
	}

	private function getSymbol(string $iso3): string
	{
		if (empty($iso3))
		{
			return '';
		}

		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('symbol'))
			->from($this->db->quoteName('data_currency'))
			->where($this->db->quoteName('iso3') . ' = ' . $this->db->quote($iso3))
			->where($this->db->quoteName('published') . ' = 1');
		$this->db->setQuery($query);

		return (string) $this->db->loadResult();
	}
}