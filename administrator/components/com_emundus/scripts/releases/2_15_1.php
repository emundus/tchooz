<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use scripts\ReleaseInstaller;
use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\Actions\ActionRepository;

class Release2_15_1Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__fabrik_lists'))
				->where($this->db->quoteName('db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_exceptions'));
			$this->db->setQuery($query);
			$exceptionsList = $this->db->loadResult();

			if (!empty($exceptionsList))
			{
				// Get menus associated to update links
				$query->clear()
					->select('id, link, component_id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_fabrik&view=list&listid=' . $exceptionsList));
				$this->db->setQuery($query);
				$menus = $this->db->loadObjectList();

				foreach ($menus as $menu)
				{
					$menu->link         = 'index.php?option=com_emundus&view=users&layout=exceptions';
					$menu->component_id = ComponentHelper::getComponent('com_emundus')->id;
					$this->tasks[]      = $this->db->updateObject('#__menu', $menu, 'id');
				}
			}

			// Sync id_applicants parameter with emundus_setup_exceptions table
			$parameters   = ComponentHelper::getParams('com_emundus');
			$idApplicants = $parameters->get('id_applicants', '');
			$idApplicants = explode(',', $idApplicants);

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_exceptions'));
			$this->db->setQuery($query);
			$exceptions = $this->db->loadObjectList();

			foreach ($exceptions as $exception)
			{
				if (!in_array($exception->user, $idApplicants))
				{
					$query->clear()
						->delete($this->db->quoteName('#__emundus_setup_exceptions'))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($exception->id));
					$this->db->setQuery($query);
					$this->tasks[] = $this->db->execute();
				}
			}

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
