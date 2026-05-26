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
				$data                  = [
					'menutype'     => 'usermenu',
					'title'        => 'Paramètres d\'accessibilité',
					'alias'        => 'parametres-accessibilite',
					'path'         => 'parametres-accessibilite',
					'link'         => 'index.php?option=com_emundus&view=accessibility&layout=user',
					'type'         => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'params'       => [
						'menu_show' => 0
					],
				];
				$accessibilityUserMenu = \EmundusHelperUpdate::addJoomlaMenu($data)['id'];
				\EmundusHelperUpdate::insertFalangTranslation(1, $accessibilityUserMenu, 'menu', 'title', 'Accessibility settings');
			}

			$query->clear()
				->select('params')
				->from('#__modules')
				->where('module = ' . $this->db->quote('mod_emundus_applications'))
				->andWhere('published = 1');

			$this->db->setQuery($query);
			$params = $this->db->loadResult();

			if (!empty($params))
			{
				$params = json_decode($params, true);

				if (!empty($params['mod_emundus_applications_actions']))
				{
					$config = ComponentHelper::getComponent('com_emundus')->getParams();

					if (in_array('rename', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_rename', 1);
					}
					else
					{
						$config->set('action_rename', 0);
					}

					if (in_array('copy', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_copy', 1);
					}
					else
					{
						$config->set('action_copy', 0);
					}

					if (in_array('documents', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_documents', 1);
					}
					else
					{
						$config->set('action_documents', 0);
					}

					if (in_array('history', $params['mod_emundus_applications_actions']))
					{
						$config->set('action_history', 1);
					}
					else
					{
						$config->set('action_history', 0);
					}

					$config->set('action_delete', 1);
					$config->set('action_transaction', 1);

					$componentId = ComponentHelper::getComponent('com_emundus')->id;

					$query->clear()
						->update($this->db->quoteName('#__extensions'))
						->set($this->db->quoteName('params') . ' = ' . $this->db->quote($config->toString()))
						->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($componentId));

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
