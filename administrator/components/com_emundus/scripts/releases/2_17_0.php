<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Factories\Language\LanguageFactory;

class Release2_17_0Installer extends ReleaseInstaller
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
			$this->prepareMenus();

			$this->rebuildActions();

			$emundusParams = ComponentHelper::getParams('com_emundus');
			if (empty($emundusParams->get('create_groups_template')) && !empty($emundusParams->get('evaluator_group')) && !empty($emundusParams->get('program_manager_group')))
			{
				\EmundusHelperUpdate::updateExtensionParam('create_groups_template', $emundusParams->get('evaluator_group') . ',' . $emundusParams->get('program_manager_group'), '');
			}

			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_languages'))
				->where($this->db->quoteName('type') . ' <> ' . $this->db->quote('override'));
			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function prepareMenus(): void
	{
		$query = $this->db->getQuery(true);

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__fabrik_lists'))
			->where($this->db->quoteName('db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_groups'));
		$this->db->setQuery($query);
		$groupsList = $this->db->loadResult();

		if (!empty($groupsList))
		{
			// Get menus associated to update links
			$query->clear()
				->select('id, link, component_id, menutype')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_fabrik&view=list&listid=' . $groupsList));
			$this->db->setQuery($query);
			$menus = $this->db->loadObjectList();

			foreach ($menus as $menu)
			{
				$menu->link         = 'index.php?option=com_emundus&view=groups';
				$menu->component_id = ComponentHelper::getComponent('com_emundus')->id;
				$this->tasks[]      = $this->db->updateObject('#__menu', $menu, 'id');

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=groups&layout=form'))
					->where($this->db->quoteName('component_id') . ' = ' . (int) ComponentHelper::getComponent('com_emundus')->id)
					->where($this->db->quoteName('parent_id') . ' = ' . (int) $menu->id);
				$this->db->setQuery($query);
				$formMenuId = $this->db->loadResult();
				if (empty($formMenuId))
				{
					$datas = [
						'menutype'     => $menu->menutype,
						'title'        => 'Créer un groupe',
						'alias'        => 'add-group-' . $menu->id,
						'type'         => 'component',
						'component_id' => ComponentHelper::getComponent('com_emundus')->id,
						'link'         => 'index.php?option=com_emundus&view=groups&layout=form',
						'params'       => [
							'menu_show' => 0
						],
					];

					$this->tasks[] = \EmundusHelperUpdate::addJoomlaMenu($datas, $menu->id);
				}
			}
		}
	}

	private function rebuildActions(): void
	{
		$query = $this->db->getQuery(true);

		\EmundusHelperUpdate::addColumn(
			'jos_emundus_setup_actions',
			'type',
			'VARCHAR',
			20,
			0,
			'file'
		);

		$automated_user_id = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);
		LanguageFactory::translate('COM_EMUNDUS_ACCESS_FILE', [
			'fr' => 'Dossier de candidature',
			'en' => 'Application file'
		], '', 0, '', $automated_user_id);

		$actionToDisabled = [
			'export_trombinoscope',
			'export fiche de synthese',
			'mail_expert',
			'mail_evaluator'
		];
		$query->clear()
			->update($this->db->quoteName('#__emundus_setup_actions'))
			->set($this->db->quoteName('status') . ' = 0')
			->where($this->db->quoteName('name') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $actionToDisabled)) . ')');
		$this->db->setQuery($query);
		$this->tasks[] = $this->db->execute();
	}
}
