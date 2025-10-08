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

class Release2_10_1Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('id, type, published')
				->from($this->db->quoteName('#__emundus_setup_emails'))
				->where($this->db->quoteName('lbl') . ' = ' . $this->db->quote('anonym_token_email'));
			$this->db->setQuery($query);
			$anonym_email = $this->db->loadObject();

			if(!empty($anonym_email) && !empty($anonym_email->id))
			{
				$update = [
					'id' => (int) $anonym_email->id,
					'type' => 1
				];

				$query->clear()
					->select('value')
					->from($this->db->quoteName('#__emundus_setup_config'))
					->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('anonymous'));
				$this->db->setQuery($query);
				$anonymous_module = $this->db->loadResult();

				if (!empty($anonymous_module))
				{
					$params = json_decode($anonymous_module, true);

					if($params['enabled'] == 0)
					{
						$update['published'] = 0;
					}
				}

				$update = (object) $update;
				$tasks[] = $this->db->updateObject('#__emundus_setup_emails', $update, 'id');
			}

			$str_query = 'alter table jos_menu_types modify title varchar(150) not null;';
			$this->db->setQuery($str_query);
			$tasks[] = $this->db->execute();

			// Delete module-ldap-cas-saml-mise-en-place and retroplanning articles
			$query->clear()
				->delete($this->db->quoteName('#__content'))
				->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('module-ldap-cas-saml-mise-en-place'))
				->orWhere($this->db->quoteName('alias') . ' = ' . $this->db->quote('retroplanning'));
			$this->db->setQuery($query);
			$tasks[] = $this->db->execute();

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