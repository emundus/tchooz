<?php
/**
 * @version     2: emunduscampaign 2019-04-11 Hugo Moracchini
 * @package     Fabrik
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Création de dossier de candidature automatique.
 */

// No direct access
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Traits\TraitTable;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

class PlgFabrik_FormApplicationChoicesMore extends plgFabrik_Form
{
	use TraitTable;

	public function onBeforeLoad()
	{
		/**
		 * @var FabrikFEModelForm $formModel
		 */
		$formModel = $this->getModel();
		$data      = $formModel->data;

		$db_table_name = $formModel->getTable()->db_table_name;

		$parent_id = $data[$db_table_name . '___parent_id_raw'] ?? null;
		if (empty($parent_id))
		{
			$parent_id = $data[$db_table_name . '___parent_id'] ?? null;
		}

		//return $this->checkAccess($parent_id);
	}

	public function onBeforeProcess()
	{
		/**
		 * @var FabrikFEModelForm $formModel
		 */
		$formModel = $this->getModel();
		$data      = $formModel->formData;

		$db_table_name = $formModel->getTable()->db_table_name;

		$parent_id = $data[$db_table_name . '___parent_id_raw'] ?? null;
		if (empty($parent_id))
		{
			$parent_id = $data[$db_table_name . '___parent_id'] ?? null;
		}

		try
		{
			$result = $this->checkAccess($parent_id);
		}
		catch (Exception $e)
		{
			$this->app->enqueueMessage($e->getMessage(), 'error');

			$formModel->errors[$db_table_name . '___parent_id'][] = Text::_($e->getMessage());

			return false;
		}

		return $result;
	}

	public function onAfterProcess()
	{
		die("<script type='text/javascript'>window.parent.postMessage('CloseApplicationChoicesMoreModal', '*');</script>");
	}

	private function checkAccess($parent_id): bool
	{
		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		}

		$user      = $this->app->getIdentity();
		$parent_id = (int) $parent_id;
		if (empty($parent_id))
		{
			throw new Exception(Text::_('PLG_FABRIK_FORM_EMUNDUSCAMPAIGNMORE_ERROR_NO_PARENT_ID'));
		}

		// Check if parent_id is mine or if i have application choices rights on this fnum
		$applicationChoicesRepository = new ApplicationChoicesRepository();
		$choice                       = $applicationChoicesRepository->getById($parent_id);
		$fnum                         = !empty($choice) ? $choice->getFnum() : '';
		if (empty($fnum))
		{
			throw new Exception(Text::_('PLG_FABRIK_FORM_EMUNDUSCAMPAIGNMORE_ERROR_NO_PARENT_ID'));
		}

		$actionRepository         = new ActionRepository();
		$applicationChoicesAction = $actionRepository->getByName('application_choices');

		$query = $this->_db->getQuery(true);
		$query->select('applicant_id')
			->from($this->_db->quoteName($this->getTableName(ApplicationFileRepository::class)))
			->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum));
		$this->_db->setQuery($query);
		$applicant_id = (int) $this->_db->loadResult();

		if ($applicant_id !== (int) $user->id)
		{
			if (EmundusHelperAccess::asAccessAction($applicationChoicesAction->getId(), 'u', $user->id, $fnum) === false)
			{
				throw new Exception(Text::_('PLG_FABRIK_FORM_EMUNDUSCAMPAIGNMORE_ERROR_CANNOT_ACCESS'));
			}

			// Managers only fill in the complementary form of choices within the programs they manage
			if (!EmundusHelperAccess::canManageProgram($user->id, $choice->getCampaign()?->getProgram()?->getCode()))
			{
				throw new Exception(Text::_('PLG_FABRIK_FORM_EMUNDUSCAMPAIGNMORE_ERROR_CANNOT_ACCESS'));
			}
		}

		return true;
	}
}
