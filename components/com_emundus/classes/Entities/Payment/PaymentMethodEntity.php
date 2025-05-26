<?php

namespace Tchooz\Entities\Payment;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;

class PaymentMethodEntity
{
	private int $id;
	public string $name;
	public string $label;
	public ?string $description;
	public int $published;
	// TODO: define if the method is manual or handled by a payment gateway
	private DatabaseDriver $db;

	public function __construct(int $id = 0)
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->id = $id;

		if (!empty($this->id))
		{
			$this->load();
		}
	}
	public function getId(): int
	{
		return $this->id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function load(): void
	{
		$query = $this->db->createQuery();

		$query->select($this->db->quoteName(['id', 'name', 'label', 'description', 'published']))
			->from($this->db->quoteName('jos_emundus_setup_payment_method'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($this->id));

		try
		{
			$this->db->setQuery($query);
			$paymentMethod = $this->db->loadObject();

			if ($paymentMethod)
			{
				$this->name        = $paymentMethod->name;
				$this->label       = $paymentMethod->label;
				$this->description = $paymentMethod->description;
				$this->published   = $paymentMethod->published;
			}
			else
			{
				throw new \Exception('Payment method not found');
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error loading payment method: ' . $e->getMessage());
		}
	}

	public function serialize(): array
	{
		return [
			'id'          => $this->getId(),
			'name'        => $this->name,
			'label'       => $this->label,
			'description' => $this->description,
			'published'   => $this->published,
		];
	}
}