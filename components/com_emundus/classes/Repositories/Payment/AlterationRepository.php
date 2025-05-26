<?php

namespace Tchooz\Repositories\Payment;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\ProductEntity;
use Joomla\Database\DatabaseDriver;

class AlterationRepository
{
	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.alteration.php'], Log::ALL, ['com_emundus.repository.alteration']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function getAlterationById(int $alteration_id): ?AlterationEntity
	{
		$alteration = null;

		if (!empty($alteration_id)) {
			$query = $this->db->createQuery();

			$query->select('*')
				->from('#__emundus_price_alteration')
				->where('id = ' . $alteration_id);

			$this->db->setQuery($query);
			$alteration_row = $this->db->loadObject();

			if (!empty($alteration_row)) {
				if (!empty($alteration_row->product_id)) {
					$product = new ProductEntity($alteration_row->product_id);
				} else {
					$product = null;
				}

				if (!empty($alteration_row->discount_id))
				{
					$discount_repository = new DiscountRepository();
					$discount            = $discount_repository->getDiscountById($alteration_row->discount_id);
				} else {
					$discount = null;
				}

				$alteration = new AlterationEntity($alteration_id, $alteration_row->cart_id, $product, $discount, $alteration_row->description, $alteration_row->amount, AlterationType::from($alteration_row->type), $alteration_row->created_by, new \DateTime($alteration_row->created_at),  $alteration_row->updated_by, new \DateTime($alteration_row->updated_at));
			}
		}

		return $alteration;
	}

	public function flush(AlterationEntity $alteration, int $user_id): bool
	{
		$flushed = false;

		if (!empty($alteration->getId())) {
			$query = $this->db->createQuery();

			$query->update('#__emundus_price_alteration')
				->set('cart_id = ' . $alteration->getCartId())
				->set('description = ' . $this->db->quote($alteration->getDescription()))
				->set('amount = ' . $alteration->getAmount())
				->set('type = ' . $this->db->quote($alteration->getType()->value))
				->set('created_by = ' . $alteration->getCreatedBy())
				->set('created_at = ' . $this->db->quote($alteration->getCreatedAt()->format('Y-m-d H:i:s')))
				->set('updated_by = ' . $user_id)
				->set('updated_at = ' . $this->db->quote(date('Y-m-d H:i:s')));

			if (!empty($alteration->getDiscount())) {
				$query->set('discount_id = ' . $alteration->getDiscount()->getId());
			} else {
				$query->set('discount_id = NULL');
			}

			if (!empty($alteration->getProduct())) {
				$query->set('product_id = ' . $alteration->getProduct()->getId());
			} else {
				$query->set('product_id = NULL');
			}

			$query->where('id = ' . $alteration->getId());

			$this->db->setQuery($query);
			$flushed = $this->db->execute();
		}

		return $flushed;
	}
}