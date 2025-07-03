<?php
/**
 * @version     1.34.0: emundusisevaluatedbyme 2022-12-02 Brice HUBINET
 * @package     Fabrik
 * @copyright   Copyright (C) 2022 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * @description Check how can the connected user can access to an evaluation
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');


/**
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */
class PlgFabrik_FormEmundusverifymandatoryfields extends plgFabrik_Form
{
	public function onBeforeStore()
	{
		$ids = $this->getParams()->get('fabrik_element_ids', '');

		if (!empty($ids)) {
			$ids = explode(',', $ids);

			$formModel = $this->getModel();
			$data      = $this->getProcessData();
			$table_name = $formModel->getListModel()->getTable()->db_table_name;

			if (!empty($data[$table_name . '___fnum'])) {
				$fnum = $data[$table_name . '___fnum'];
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();

				try {
					foreach ($ids as $id) {
						$query->clear()
							->select('jfe.name, jfe.label, jffg.form_id, jff.label as form_label, jfl.db_table_name')
							->from($db->quoteName('#__fabrik_elements', 'jfe'))
							->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jfe.group_id = jffg.group_id')
							->leftJoin($db->quoteName('#__fabrik_forms', 'jff') . ' ON jffg.form_id = jff.id')
							->leftJoin($db->quoteName('#__fabrik_lists', 'jfl') . ' ON jff.id = jfl.form_id')
							->where($db->quoteName('jfe.id') . ' = ' . $db->quote($id));

						$db->setQuery($query);
						$element = $db->loadObject();

						if (!empty($element)) {
							$query->clear()
								->select($element->name)
								->from($db->quoteName($element->db_table_name))
								->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

							$db->setQuery($query);
							$value = $db->loadResult();

							if ($value === '' || is_null($value)) {
								if (!class_exists('EmundusModelWorkflow')) {
									require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
								}
								$m_workflow = new EmundusModelWorkflow();
								$step = $m_workflow->getCurrentWorkflowStepFromFile($fnum);

								if (empty($step)) {
									$query->clear()
										->select('esc.profile_id')
										->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
										->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $db->quoteName('esc.id') . ' =  ' . $db->quoteName('ecc.campaign_id'))
										->where($db->quoteName('ecc.fnum') . ' = ' . $db->quote($fnum));

									$db->setQuery($query);
									$profile_id = $db->loadResult();
								} else {
									$profile_id = $step->profile_id;
								}

								if (!empty($profile_id)) {
									$query->clear()
										->select('path')
										->from($db->quoteName('#__menu'))
										->where($db->quoteName('menutype') . ' = ' . $db->quote('menu-profile' . $profile_id))
										->andWhere($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_fabrik&view=form&formid=' . $element->form_id));

									$db->setQuery($query);
									$form_url = $db->loadResult();

									$form_url = JRoute::_('/' . $form_url);
								} else {
									$form_url = JRoute::_('index.php?option=com_fabrik&view=form&formid=' . $element->form_id);
								}

								$app = Factory::getApplication();
								$app->enqueueMessage('Veuillez compléter la page <strong>' . Text::_($element->form_label) . '</strong> et réenregistrer. ', 'error');
								$app->redirect($form_url);

								return false;
							}
						}
					}

				} catch (Exception $e) {
					var_dump($e->getMessage());exit;
				}
			}
		}
	}

	/**
	 * Load params
	 *
	 * @return  Registry  params
	 */
	public function getParams()
	{
		if (!isset($this->params))
		{
			$row          = $this->getRow();
			$this->params = new Registry($row->params);
		}

		return $this->params;
	}
}