<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_6_6Installer extends ReleaseInstaller
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
			// Update keycloak attributes
			$query->select('extension_id, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('emundus_oauth2'));;
			$extension = $this->db->setQuery($query)->loadObject();
			
			if(!empty($extension->extension_id))
			{
				$params = json_decode($extension->params, true);
				
				foreach ($params['configurations'] as $key => $configuration)
				{
					if($configuration['source'] == 0)
					{
						// Update firstname and lastname attributes
						foreach ($configuration['attributes'] as $attributeKey => $attribute)
						{
							if($attribute['column_name'] == 'firstname')
							{
								$attribute['attribute_name'] = 'given_name';
							}

							if($attribute['column_name'] == 'lastname')
							{
								$attribute['attribute_name'] = 'family_name';
							}

							$params['configurations'][$key]['attributes'][$attributeKey] = $attribute;
						}
					}
				}

				$extension->params = json_encode($params);
				$tasks[] = $this->db->updateObject('#__extensions', $extension, ['extension_id']);
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