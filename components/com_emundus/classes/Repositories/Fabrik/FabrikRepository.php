<?php
/**
 * @package     Tchooz\Repositories\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Fabrik;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Factories\Fabrik\FabrikFactory;

class FabrikRepository
{
	private FabrikFactory $factory;

	private bool $withRelations;

	private DatabaseInterface $db;
	
	const FABRIK_FORM_COLUMNS = [
		'ff.*',
		'fl.id AS list_id',
		'fl.db_table_name',
	];

	public function __construct($withRelations = true)
	{
		$this->withRelations = $withRelations;
		$this->factory       = new FabrikFactory();
		$this->db            = Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * @param   int  $profileId
	 *
	 * @return array<FabrikFormEntity>
	 */
	public function getFormsByProfileId(int $profileId): array
	{
		$forms = [];
		
		$query = $this->db->getQuery(true);

		try
		{
			$query->select(self::FABRIK_FORM_COLUMNS)
				->from($this->db->quoteName('#__menu', 'm'))
				->innerJoin($this->db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->db->quoteName('esp.menutype') . ' = ' . $this->db->quoteName('m.menutype') . ' AND ' . $this->db->quoteName('esp.id') . ' = ' . $this->db->quote($profileId))
				->innerJoin($this->db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->db->quoteName('ff.id') . ' = SUBSTRING_INDEX(SUBSTRING(m.link, LOCATE("formid=",m.link)+7, 4), "&", 1)')
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
				->where($this->db->quoteName('m.published') . ' = 1')
				->where($this->db->quoteName('m.parent_id') . ' != 1')
				->order('m.lft');

			$this->db->setQuery($query);
			$lists = $this->db->loadObjectList();

			if(!empty($lists))
			{
				$forms = $this->factory->fromDbObjects($lists, $this->withRelations);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}
		
		return $forms;
	}
}