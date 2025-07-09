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

class Release2_7_1Installer extends ReleaseInstaller
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
			$tasks[] = EmundusHelperUpdate::installExtension('eMundus- InternetExplorer', 'mod_emundus_internet_explorer', null, 'module');

			$query->clear()
				->select('id, published')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_emundus_internet_explorer'))
				->where($this->db->quoteName('client_id') . ' = 0');
			$this->db->setQuery($query);
			$module = $this->db->loadObject();

			if(empty($module->id))
			{
				$datas   = [
					'title'    => 'Browser compatiblity',
					'note'     => '',
					'content'  => '',
					'position' => 'top-b',
					'module'   => 'mod_emundus_internet_explorer',
					'access'   => 1,
					'params'   => [
						'message' => 'TEXT_DEFAULT',
						'layout'          => '_:simple',
						'module_tag' => 'div',
						'bootstrap_size'           => 0,
						'header_tag'      => 'h3',
						'header_class'       => '',
						'style'       => 0,
					]
				];
				$tasks[] = EmundusHelperUpdate::addJoomlaModule($datas, 1, true);
			}
			else {
				$module->published = 1;
				$tasks[] = $this->db->updateObject('#__modules', $module, 'id');
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