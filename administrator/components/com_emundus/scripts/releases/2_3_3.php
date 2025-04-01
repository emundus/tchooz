<?php

namespace scripts;

class Release2_3_3Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			$query->clear()
				->select('id, params')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' like ' . $this->db->quote('mod_emundus_applications'));
			$this->db->setQuery($query);
			$app_modules = $this->db->loadObjectList();

			foreach ($app_modules as $app_module)
			{
				$params = json_decode($app_module->params);
				if(is_array($params->mod_emundus_applications_actions)) {
					// Add documents
					$params->mod_emundus_applications_actions[] = 'documents';
					$app_module->params = json_encode($params);
					$this->db->updateObject('#__modules', $app_module, 'id');
				}
			}

			$query->clear()
				->update($this->db->quoteName('#__menu'))
				->set($this->db->quoteName('published') . ' = 1')
				->where($this->db->quoteName('link') . ' like ' . $this->db->quote('index.php?option=com_emundus&view=application&layout=history'));
			$this->db->setQuery($query);
			$tasks['history'] = $this->db->execute();

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}
}