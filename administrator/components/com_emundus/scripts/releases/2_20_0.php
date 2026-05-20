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

class Release2_20_0Installer extends ReleaseInstaller
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
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('usermenu'))
				->andWhere($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=accessibility&layout=user'));
			$this->db->setQuery($query);
			$accessibilityUserMenu = $this->db->loadResult();

			if (empty($accessibilityUserMenu))
			{
				$data               = [
					'menutype'          => 'usermenu',
					'title'             => 'Paramètres d\'accessibilité',
					'alias'             => 'parametres-accessibilite',
					'path'              => 'parametres-accessibilite',
					'link'              => 'index.php?option=com_emundus&view=accessibility&layout=user',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'params'            => [
						'menu_show' => 0
					],
				];
				$accessibilityUserMenu = \EmundusHelperUpdate::addJoomlaMenu($data)['id'];
				\EmundusHelperUpdate::insertFalangTranslation(1, $accessibilityUserMenu, 'menu', 'title', 'Accessibility settings');
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
