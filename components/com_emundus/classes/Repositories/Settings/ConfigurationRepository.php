<?php
/**
 * @package     Tchooz\Repositories\Settings
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Settings;

use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Settings\ConfigurationEntity;
use Tchooz\Factories\Settings\ConfigurationFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_setup_config',
	alias: 'esc',
	columns: [
		'namekey',
		'value',
		'default'
	]
)]
class ConfigurationRepository extends EmundusRepository implements RepositoryInterface
{
	protected ?CacheController $cache = null;

	private ConfigurationFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'configuration', self::class);

		$this->cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus.configuration']);

		$this->factory = new ConfigurationFactory();
	}

	public function flush(ConfigurationEntity $entity): bool
	{
		if (empty($entity->getNamekey()))
		{
			throw new \InvalidArgumentException('Configuration namekey is required to flush ConfigurationEntity');
		}

		$data = (object)[
			'namekey' => $entity->getNamekey(),
			'value' => json_encode($entity->getValue()),
			'default' => $entity->getDefault(),
		];

		$existing_configuration = $this->getByName($entity->getNamekey());
		if ($existing_configuration) {
			if(!$this->db->updateObject($this->tableName, $data, 'namekey'))
			{
				throw new \RuntimeException('Failed to update ConfigurationEntity');
			}
		}
		else
		{
			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Failed to insert ConfigurationEntity');
			}
		}

		// Clear cache after flush
		$cacheKey = 'configuration_' . $entity->getNamekey();
		if ($this->cache->contains($cacheKey))
		{
			$this->cache->remove($cacheKey);
		}

		return true;
	}

	public function delete(int $id): bool
	{
		// TODO: Implement delete() method.
	}

	/**
	 * @param   int  $id
	 *
	 * @return mixed
	 *
	 * @deprecated Configuration does not have an ID, use getByName instead
	 */
	public function getById(int $id): mixed
	{
		return null;
	}

	public function getByName(string $namekey): ?ConfigurationEntity
	{
		$configuration = null;

		$cacheKey = 'configuration_' . $namekey;
		if($this->cache->contains($cacheKey))
		{
			$object = $this->cache->get($cacheKey);
		}

		if(empty($object))
		{
			$object = $this->getItemByField('namekey', $namekey);
			if ($object)
			{
				$this->cache->store($object, $cacheKey);
			}
		}

		if(!empty($object))
		{
			$configuration = $this->factory->fromDbObject($object, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $configuration;
	}
}