<?php

namespace Tchooz\Entities\Payment;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class ProductEntity
{
	private int $id;
	public string $label = '';
	public string $description = '';
	public float $price = 0.0;
	public ?CurrencyEntity $currency = null;
	public bool $illimited = true;
	public int $quantity = 0;
	public ?ProductCategoryEntity $category = null;
	public \DateTime|null $available_from = null;
	public \DateTime|null $available_to = null;
	public array $campaigns = [];
	public bool $published = true;
	private ?int $mandatory = null;
	private DatabaseDriver $db;

	public function __construct(int $id = 0)
	{
		Log::addLogger(['text_file' => 'com_emundus.entity.product.php'], Log::ALL, ['com_emundus.entity.product']);

		$this->id = $id;
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		if (!empty($this->id))
		{
			$this->load();
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

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setPrice(float $price): void
	{
		$this->price = $price;
	}

	public function getPrice(): float
	{
		return $this->price;
	}

	public function getMandatory(): ?int
	{
		return $this->mandatory;
	}

	public function setMandatory(int $mandatory): void
	{
		$this->mandatory = $mandatory;
	}

	public function setCurrency(CurrencyEntity $currency): void
	{
		$this->currency = $currency;
	}

	public function getCurrency(): ?CurrencyEntity
	{
		return $this->currency;
	}

	public function setCategory(ProductCategoryEntity $category): void
	{
		$this->category = $category;
	}

	public function getDisplayedPrice(): string
	{
		return number_format($this->price, 2, '.', ' ') . ' ' . $this->currency->symbol;
	}

	public function setCampaigns(array $campaigns): void
	{
		$this->campaigns = $campaigns;
	}

	public function getCampaigns(): array
	{
		return $this->campaigns;
	}

	public function setIllimited(bool $illimited): void
	{
		$this->illimited = $illimited;
	}

	public function setQuantity(int $quantity): void
	{
		$this->quantity = $quantity;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	private function load(): void
	{
		$query = $this->db->createQuery();

		$query->select('product.*, GROUP_CONCAT(product_campaigns.campaign_id SEPARATOR ",") as campaigns')
			->from($this->db->quoteName('jos_emundus_product', 'product'))
			->leftJoin($this->db->quoteName('jos_emundus_product_campaigns', 'product_campaigns') . ' ON product_campaigns.product_id = product.id')
			->where($this->db->quoteName('product.id') . ' = ' . $this->db->quote($this->id));

		try {
			$this->db->setQuery($query);
			$product = $this->db->loadObject();
		} catch (\Exception $e) {
			Log::add('Failed to load entity ' . $e->getMessage(), Log::ERROR, 'com_emundus.entity.product');
		}

		if (!empty($product) && !empty($product->id))
		{
			$this->label = !empty($product->label) ? $product->label : '';
			$this->description = !empty($product->description) ? $product->description : '';
			$this->price = !empty($product->price) ? $product->price : 0.0;
			$this->currency = !empty($product->currency_id) ? new CurrencyEntity($product->currency_id) : new CurrencyEntity(1);
			$this->quantity = $product->quantity ?? -1;
			$this->illimited = $product->illimited == 1;
			$this->available_from = !empty($product->available_from) ? new \DateTime($product->available_from) : null;
			$this->available_to =  !empty($product->available_to) ? new \DateTime($product->available_to) : null;
			$this->category = !empty($product->category_id) ? new ProductCategoryEntity($product->category_id) : null;
			$this->campaigns = !empty($product->campaigns) ? explode(',', $product->campaigns) : [];
			$this->published = $product->published == 1;
		} else {
			throw new \Exception('COM_EMUNDUS_PRODUCT_NOT_FOUND');
		}
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'label' => $this->label,
			'description' => $this->description,
			'price' => $this->getPrice(),
			'displayed_price' => $this->getDisplayedPrice(),
			'currency' => $this->currency?->serialize(),
			'illimited' => $this->illimited ? 1 : 0,
			'quantity' => $this->quantity,
			'category' => $this->category?->serialize(),
			'available_from' => !empty($this->available_from) ? $this->available_from->format('Y-m-d H:i:s') : '',
			'available_to' => !empty($this->available_to) ? $this->available_to->format('Y-m-d H:i:s') : '',
			'campaigns' => $this->campaigns,
			'published' => $this->published ? 1 : 0,
			'mandatory' => $this->mandatory,
		];
	}
}