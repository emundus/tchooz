<?php
/**
 * @package     Tchooz\Repositories\Analytics
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Analytics;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Analytics\PageAnalyticsEntity;
use Tchooz\Factories\PageAnalyticsFactory;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_page_analytics')]
class PageAnalyticsRepository
{
	use TraitTable;

	private DatabaseInterface $db;

	private PageAnalyticsFactory $factory;

	private const COLUMNS = [
		't.id',
		't.date',
		'SUM(t.count) AS count',
		't.link'
	];

	public function __construct($withRelations = true)
	{
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
		$this->factory = new PageAnalyticsFactory();
	}

	public function flush(PageAnalyticsEntity $entity): bool
	{
		if(!empty($entity->getId()))
		{
			$update = (object)[
				'id' => $entity->getId(),
				'date' => $entity->getDate()->format('Y-m-d'),
				'count' => $entity->getCount(),
				'link' => $entity->getLink()
			];
			return $this->db->updateObject($this->getTableName(self::class), $update, 'id');
		}
		else {
			$insert = (object)[
				'date' => $entity->getDate()->format('Y-m-d'),
				'count' => $entity->getCount(),
				'link' => $entity->getLink()
			];
			return $this->db->insertObject($this->getTableName(self::class), $insert);
		}
	}

	public function get(
		int $id = 0,
		string $link = '',
		\DateTime $date = null,
		\DateTime $start_date = null,
		\DateTime $end_date = null,
		?string $group_by = 't.date'
	): ?PageAnalyticsEntity
	{
		$analytics_entity = null;

		$query = $this->buildQuery(
			$id,
			$link,
			$date,
			$start_date,
			$end_date,
			$group_by
		);

		$this->db->setQuery($query);
		$analytics = $this->db->loadObject();

		if(!empty($analytics))
		{
			$analytics_entity = $this->factory->fromDbObject($analytics);
		}

		return $analytics_entity;
	}

	public function buildQuery(
		int $id = 0,
		string $link = '',
		\DateTime $date = null,
		\DateTime $start_date = null,
		\DateTime $end_date = null,
		?string $group_by = 't.date'
	): QueryInterface
	{
		$query = $this->db->getQuery(true);

		$query->select(self::COLUMNS)
			->from($this->getTableName(self::class) . ' AS t');
		if(!empty($date))
		{
			$date = $date->format('Y-m-d');
			$query->where('t.date = :date')
				->bind(':date', $date);
		}
		elseif (!empty($start_date) || !empty($end_date))
		{
			if(!empty($start_date))
			{
				$start_date_formatted = $start_date->format('Y-m-d');
				$query->where('t.date >= :start_date')
					->bind(':start_date', $start_date_formatted);
			}
			if(!empty($end_date))
			{
				$end_date_formatted = $end_date->format('Y-m-d');
				$query->where('t.date <= :end_date')
					->bind(':end_date', $end_date_formatted);
			}
		}
		if(!empty($link))
		{
			$query->where('t.link = :link')
				->bind(':link', $link);
		}
		if(!empty($id))
		{
			$query->where('t.id = :id')
				->bind(':id', $id, DatabaseInterface::PARAM_INT);
		}

		if(!empty($group_by) && in_array($group_by, ['t.date', 't.link']))
		{
			$query->group($group_by);
		}

		$query->order('t.date DESC');

		return $query;
	}
}