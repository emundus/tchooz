<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$user = $app->getSession()->get('emundusUser');

if (!empty($user->id) && EmundusHelperAccess::asAccessAction(1, 'r', $user->id)) {
	if (!empty($params)) {
		$layout = $params->get('layout', '');
		$filter_on_fnums = $params->get('filter_on_fnums', 0);

		if ($filter_on_fnums == 1) {
			require_once JPATH_ROOT . '/components/com_emundus/classes/filters/EmundusFiltersFiles.php';

			try {
				$m_filters = new EmundusFiltersFiles($params->toArray());
			} catch (Exception $e) {
				$app->enqueueMessage($e->getMessage());
				$app->redirect('/');
			}
		} else {
			$fabrik_element_id = $params->get('element_id', 0);
			if (!empty($fabrik_element_id)) {
				require_once JPATH_ROOT . '/components/com_emundus/classes/filters/EmundusFilters.php';

				try {
					$m_filters = new EmundusFilters(['element_id' => $fabrik_element_id]);
				} catch (Exception $e) {
					$app->enqueueMessage($e->getMessage());
					$app->redirect('/');
				}
			} else {
				$app->enqueueMessage(Text::_('MOD_EM_FILTER_FABRIK_MISSING_CONFIGURATION'));
			}
		}

		if (!empty($m_filters)) {
			$document 	= $app->getDocument();
			$document->addScript('media/mod_emundus_filters/chunk-vendors.js');
			$document->addStyleSheet('media/mod_emundus_filters/app.css');

			$filters = $m_filters->getFilters();
			$applied_filters = $m_filters->getAppliedFilters();
			$quick_search_filters = $m_filters->getQuickSearchFilters();

			require JModuleHelper::getLayoutPath('mod_emundus_filters', $layout);
		}
	}
} else {
	$app->enqueueMessage(Text::_('ACCESS_DENIED'));
	$app->redirect('/');
}