<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;


class Release2_11_2Installer extends ReleaseInstaller
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
			$query->select('form_id')
				->from($this->db->quoteName('#__emundus_setup_formlist'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('profile'));
			$this->db->setQuery($query);
			$profile_form_id = $this->db->loadResult();

			if(!empty($profile_form_id))
			{
				$query->clear()
					->select('id, access, params')
					->from($this->db->quoteName('#__fabrik_lists'))
					->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($profile_form_id));
				$this->db->setQuery($query);
				$profile_list = $this->db->loadObject();
				
				if(!empty($profile_list) && !empty($profile_list->id))
				{
					$profile_list->access = 6;

					$params = json_decode($profile_list->params);
					$params->allow_view_details = 6;

					$profile_list->params = json_encode($params);

					$this->tasks[] = $this->db->updateObject('#__fabrik_lists', $profile_list, 'id');
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