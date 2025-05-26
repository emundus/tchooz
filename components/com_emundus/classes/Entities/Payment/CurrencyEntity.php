<?php

namespace Tchooz\Entities\Payment;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;

class CurrencyEntity
{
	public int $id = 0;
	public string $name;
	public string $symbol;
	public string $iso3;
	private ?string $iso4217;
	public int $published;
	private DatabaseDriver $db;

	public function __construct(
		int $id,
		string $name = '',
		string $symbol = '',
		string $iso3 = '',
		int $published = 1
	)
	{
		Log::addLogger(['text_file' => 'com_emundus.entity.currency.php'], Log::ALL, ['com_emundus.entity.currency']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->id = $id;

		if (!empty($this->id))
		{
			$this->load();
		} else {
			$this->name = $name;
			$this->symbol = $symbol;
			$this->iso3 = $iso3;
			$this->published = $published;
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function load(): void
	{
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->db->quoteName('data_currency'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($this->id));

		try {
			$this->db->setQuery($query);
			$currency = $this->db->loadObject();

			if ($currency)
			{
				$this->name = $currency->name;
				$this->symbol = $currency->symbol;
				$this->iso3 = $currency->iso3;
				$this->iso4217 = $currency->iso4217;
				$this->published = $currency->published;
			}
		} catch (\Exception $e) {
			Log::add('Error loading currency: ' . $e->getMessage(), Log::ERROR, 'com_emundus.entity.currency');
		}
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getIso3(): string
	{
		return $this->iso3;
	}

	public function getIso4217(): ?string
	{
		return $this->iso4217;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function getSymbol(): string
	{
		return $this->symbol;
	}

	public function serialize()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'symbol' => $this->symbol,
			'iso3' => $this->iso3,
			'iso4217' => $this->iso4217,
			'published' => $this->published
		];
	}
}