<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;

class Release2_6_4Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$tags = [
				['tag' => 'LAST_COMMENT', 'request' => '[LAST_COMMENT]', 'description' => 'Dernier commentaire déposé sur ce dossier', 'published' => 1],
				['tag' => 'LAST_COMMENT_DATE', 'request' => '[LAST_COMMENT_DATE]', 'description' => 'Date de dépôt du dernier commentaire', 'published' => 1],
				['tag' => 'LAST_COMMENT_AUTHOR', 'request' => '[LAST_COMMENT_AUTHOR]', 'description' => 'Auteur du commentaire', 'published' => 1],
				['tag' => 'LAST_COMMENT_TARGET', 'request' => '[LAST_COMMENT_TARGET]', 'description' => 'Fil d\'ariane du commentaire', 'published' => 1],
			];

			// add new default tags
			foreach ($tags as $tag) {
				$query->clear()
					->insert($this->db->quoteName('#__emundus_setup_tags'))
					->columns($this->db->quoteName(['tag', 'request', 'description', 'published']))
					->values(
						$this->db->quote($tag['tag']) . ', ' .
						$this->db->quote($tag['request']) . ', ' .
						$this->db->quote($tag['description']) . ', ' .
						$this->db->quote($tag['published'])
					);

				$tasks[] = $this->db->setQuery($query)->execute();
			}

			$tasks[] = EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ACTIONS_DELETE', 'Supprimer');
			$tasks[] = EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ACTIONS_DELETE', 'Delete', 'override', 0, null, null, 'en-GB');

			$query->clear()
				->select('id, published, params')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=application&layout=history'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('applicantmenu'));
			$menuItem = $this->db->setQuery($query)->loadAssoc();

			if(empty($menuItem)){
				$datas       = [
					'menutype'     => 'applicantmenu',
					'title'        => 'Voir mon dossier',
					'alias'        => 'applicantmenu-voir-mon-dossier',
					'link'         => 'index.php?option=com_emundus&view=application&layout=history',
					'type'         => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'params' => [
						'menu_show' => 0,
						'tabs'      => ['history', 'forms', 'attachments']
					]
				];
				$history_menu = EmundusHelperUpdate::addJoomlaMenu($datas);
				$tasks[] = $history_menu['status'];
			}
			else
			{
				$menuParams = json_decode($menuItem['params'], true);
				if ($menuItem['published'] == 0 || $menuParams['menu_show'] == 1)
				{
					$menuParams['menu_show'] = 0;
					if (empty($menuParams['tabs']))
					{
						$menuParams['tabs'] = ['history', 'forms', 'attachments'];
					}
					$menuItem['params']    = json_encode($menuParams);
					$menuItem['published'] = 1;

					$menuItem = (object) $menuItem;
					$tasks[]  = $this->db->updateObject('#__menu', $menuItem, 'id');
				}
			}

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}