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

class Release2_8_1Installer extends ReleaseInstaller
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
			$query->clear()
				->select('id')
				->from('#__emundus_setup_actions')
				->where('name = ' . $this->db->quote('import'));
			$this->db->setQuery($query);
			$import_acl = $this->db->loadResult();

			if (empty($import_acl))
			{
				$query->clear()
					->select('MAX(ordering)')
					->from('#__emundus_setup_actions')
					->where('ordering <> 999');
				$this->db->setQuery($query);
				$ordering = $this->db->loadResult();

				$import_acl     = [
					'name'        => 'import',
					'label'       => 'COM_EMUNDUS_ACL_IMPORT',
					'multi'       => 0,
					'c'           => 1,
					'r'           => 0,
					'u'           => 0,
					'd'           => 0,
					'ordering'    => $ordering + 1,
					'status'      => 1,
					'description' => 'COM_EMUNDUS_ACL_IMPORT_DESC'
				];
				$import_acl     = (object) $import_acl;
				$tasks[]        = $this->db->insertObject('#__emundus_setup_actions', $import_acl);
				$import_acl->id = $this->db->insertid();

				// Give all rights to all rights group
				$all_rights_group  = ComponentHelper::getParams('com_emundus')->get('all_rights_group', 1);
				$import_acl_rights = [
					'group_id'  => $all_rights_group,
					'action_id' => $import_acl->id,
					'c'         => 1,
					'r'         => 1,
					'u'         => 1,
					'd'         => 1,
					'time_date' => date('Y-m-d H:i:s')
				];
				$import_acl_rights = (object) $import_acl_rights;
				$tasks[]           = $this->db->insertObject('#__emundus_acl', $import_acl_rights);
			}

			// Insert emails templates
			$query->clear()
				->select('value')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('import'));
			$this->db->setQuery($query);
			$params = json_decode($this->db->loadResult(), true);

			// Account created
			$query->clear()
				->select('id')
				->from('#__emundus_setup_emails')
				->where('lbl = ' . $this->db->quote('import_account_created'));
			$this->db->setQuery($query);
			$import_account_created = $this->db->loadResult();

			if (empty($import_account_created))
			{
				$query->clear()
					->select('id')
					->from('#__emundus_email_templates')
					->where('lbl = ' . $this->db->quote('account_creation'));
				$this->db->setQuery($query);
				$account_creation_template = $this->db->loadResult();

				$import_account_email = [
					'lbl'        => 'import_account_created',
					'subject'    => 'Création d’un compte suite à un import sur la plateforme [SITE_URL]',
					'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/import/import_account_created.html'),
					'type'       => 1,
					'published'  => $params['enabled'] ?? 0,
					'email_tmpl' => $account_creation_template,
					'category'   => 'Import',
					'button'     => 'Finaliser mon compte / Complete my account'
				];
				$import_account_email = (object) $import_account_email;
				$tasks[] = $this->db->insertObject('#__emundus_setup_emails', $import_account_email);
			}

			// File created
			$query->clear()
				->select('id')
				->from('#__emundus_setup_emails')
				->where('lbl = ' . $this->db->quote('import_file_created'));
			$this->db->setQuery($query);
			$import_file_created = $this->db->loadResult();

			if (empty($import_file_created))
			{
				$query->clear()
					->select('id')
					->from('#__emundus_email_templates')
					->where('lbl = ' . $this->db->quote('account_creation'));
				$this->db->setQuery($query);
				$account_creation_template = $this->db->loadResult();

				$import_file_created_email = [
					'lbl'        => 'import_file_created',
					'subject'    => 'Création d’un nouveau dossier suite à un import sur la plateforme [SITE_URL]',
					'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/import/import_file_created.html'),
					'type'       => 1,
					'published'  => $params['enabled'] ?? 0,
					'email_tmpl' => $account_creation_template,
					'category'   => 'Import',
					'button'     => 'Consulter mon dossier / View my application file'
				];
				$import_file_created_email = (object) $import_file_created_email;
				$tasks[] = $this->db->insertObject('#__emundus_setup_emails', $import_file_created_email);
			}

			// File updated
			$query->clear()
				->select('id')
				->from('#__emundus_setup_emails')
				->where('lbl = ' . $this->db->quote('import_file_updated'));
			$this->db->setQuery($query);

			$import_file_updated = $this->db->loadResult();

			if (empty($import_file_updated))
			{
				$query->clear()
					->select('id')
					->from('#__emundus_email_templates')
					->where('lbl = ' . $this->db->quote('account_creation'));
				$this->db->setQuery($query);
				$account_creation_template = $this->db->loadResult();

				$import_file_updated_email = [
					'lbl'        => 'import_file_updated',
					'subject'    => 'Mise à jour de votre dossier suite à un import sur la plateforme [SITE_URL]',
					'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/import/import_file_updated.html'),
					'type'       => 1,
					'published'  => $params['enabled'] ?? 0,
					'email_tmpl' => $account_creation_template,
					'category'   => 'Import',
					'button'     => 'Consulter mon dossier / View my application file'
				];
				$import_file_updated_email = (object) $import_file_updated_email;
				$tasks[] = $this->db->insertObject('#__emundus_setup_emails', $import_file_updated_email);
			}

			$query->clear()
				->update('#__menu')
				->set('link = ' . $this->db->quote('index.php?option=com_emundus&view=accessibility'))
				->set('component_id = ' . ComponentHelper::getComponent('com_emundus')->id)
				->where('alias = ' . $this->db->quote('accessibilite'));
			$this->db->setQuery($query);
			$tasks[] = $this->db->execute();

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=evaluation'));
			$this->db->setQuery($query);
			$menuItems = $this->db->loadObjectList();

			foreach ($menuItems as $menuItem)
			{
				$params = json_decode($menuItem->params);
				$params->filter_evaluators = 1;

				$menuItem->params = json_encode($params);
				$tasks[] = $this->db->updateObject('#__menu', $menuItem, 'id');
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