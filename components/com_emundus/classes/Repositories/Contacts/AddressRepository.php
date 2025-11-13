<?php
/**
 * @package     Tchooz\Repositories\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Contacts;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Factories\Contacts\AddressFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Traits\TraitTable;

if (!class_exists('AddressEntity'))
{
	require_once JPATH_SITE . '/components/com_emundus/classes/Entities/Contacts/AddressEntity.php';
}

require_once JPATH_SITE . '/components/com_emundus/classes/Traits/TraitTable.php';

#[TableAttribute(table: '#__emundus_addresses')]
class AddressRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitTable;

	private AddressFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'address');
		$this->factory = new AddressFactory();
	}

	/**
	 * @throws \Exception
	 */
	public function flush(AddressEntity $entity): bool
	{
		$address_object = $entity->__serialize();
		$address_object = (object) $address_object;

		if (empty($entity->getId()))
		{
			if ($this->db->insertObject($this->getTableName(self::class), $address_object))
			{
				$address_id = $this->db->insertid();
				$entity->setId($address_id);
			}
			else
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ADDRESS_INSERT_FAILED'), 500);
			}
		}
		else
		{
			if (!$this->db->updateObject($this->getTableName(self::class), $address_object, 'id'))
			{
				throw new \Exception(Text::_('COM_EMUNDUS_ONBOARD_CRC_ADDRESS_UPDATE_FAILED'), 500);
			}
		}

		// If false, an exception is throw before
		return true;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$query->delete($this->getTableName(self::class))
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$deleted = (bool) $this->db->execute();
			}
			catch (\Exception $e)
			{
				Log::add('Error on delete address : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.address');
			}
		}

		return $deleted;
	}

	public function getById(int $id): ?AddressEntity
	{
		$address = null;

		if (!empty($id))
		{
			$query = $this->db->createQuery();

			$query->select('*')
				->from($this->getTableName(self::class))
				->where('id = ' . $id);

			try
			{
				$this->db->setQuery($query);
				$result = $this->db->loadObject();


				if ($result)
				{
					$address = $this->factory->fromDbObject($result);
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error on get address by id : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.address');
			}
		}

		return $address;
	}
}