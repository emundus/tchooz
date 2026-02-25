<?php


/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use scripts\ReleaseInstaller;

class Release2_15_0Installer extends ReleaseInstaller
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
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'campaign', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'program', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'email', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'form', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'workflow', published: 1);
			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'event', published: 1);

			$createdByColumn = new \EmundusTableColumn('created_by', \EmundusColumnTypeEnum::INT, 11, true);
			$this->tasks[]   = \EmundusHelperUpdate::addColumn('#__emundus_setup_emails', $createdByColumn->getName(), $createdByColumn->getType()->value, $createdByColumn->getLength(), ($createdByColumn->isNullable() ? 1 : 0));


			$query->select('id')
				->from($this->db->quoteName('#__viewlevels'))
				->where($this->db->quoteName('title') . ' = ' . $this->db->quote('Partner'));
			$this->db->setQuery($query);
			$partnerLevel = $this->db->loadResult();

			if (!empty($partnerLevel))
			{
				$query->clear()
					->select('id, params, access')
					->from($this->db->quoteName('#__fabrik_lists'))
					->where($this->db->quoteName('label') . ' = ' . $this->db->quote('TABLE_SETUP_PROGRAMS'));
				$this->db->setQuery($query);
				$programsList = $this->db->loadObject();
				if (!empty($programsList))
				{
					$params                        = json_decode($programsList->params, true);
					$params['allow_view_details']  = $partnerLevel;
					$params['allow_edit_details']  = $partnerLevel;
					$params['allow_add']           = $partnerLevel;
					$params['allow_delete']        = $partnerLevel;
					$programsList->access          = $partnerLevel;

					$programsList->params = json_encode($params);
					$this->tasks[]        = $this->db->updateObject('#__fabrik_lists', $programsList, 'id');
				}
			}

			$addedColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_form_rules_js_conditions', 'params', 'JSON' );
			$this->tasks[] = $addedColumn['status'];
			if (!$addedColumn['status'])
			{
				$result['message'] .= $addedColumn['message'];
			}

			$installed = \EmundusHelperUpdate::installExtension('plg_fabrik_element_orderlist', 'orderlist', null, 'plugin', 1, 'fabrik_element');
			$this->tasks[] = $installed;
			if (!$installed)
			{
				$result['message'] .= 'Failed to install orderlist plugin. ';
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
