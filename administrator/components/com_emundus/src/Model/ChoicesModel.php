<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Tchooz\Enums\ApplicationFile\ChoicesState;
use Tchooz\Factories\ApplicationFile\ApplicationChoicesFactory;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of choices records.
 *
 * @since  1.6
 */
class ChoicesModel extends ListModel
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [];

			if (Associations::isEnabled()) {
				$config['filter_fields'][] = 'association';
			}
		}

		parent::__construct($config, $factory);
	}

	public function getFilterForm($data = [], $loadData = true): Form|null
	{
		return parent::getFilterForm($data, $loadData);
	}

	protected function populateState($ordering = 'a.id', $direction = 'desc'): void
	{
		$app   = Factory::getApplication();
		$input = $app->getInput();

		$forcedLanguage = $input->get('forcedLanguage', '', 'cmd');

		// Adjust the context to support modal layouts.
		if ($layout = $input->get('layout')) {
			$this->context .= '.' . $layout;
		}

		// Adjust the context to support forced languages.
		if ($forcedLanguage) {
			$this->context .= '.' . $forcedLanguage;
		}

		// List state information.
		parent::populateState($ordering, $direction);

		// Force a language
		if (!empty($forcedLanguage)) {
			$this->setState('filter.language', $forcedLanguage);
			$this->setState('filter.forcedLanguage', $forcedLanguage);
		}
	}

	protected function getStoreId($id = ''): string
	{
		// Compile the store id.
		//$id .= ':' . $this->getState('filter.search');

		return parent::getStoreId($id);
	}

	protected function getListQuery(): QueryInterface
	{
		$fnum = trim($this->getState('filter.fnum'));
		$state = trim($this->getState('filter.state'));
		if(!empty($state)) {
			$state = ChoicesState::isValidState($state);
		}
		else {
			$state = null;
		}

		$applicationChoicesRepository = new ApplicationChoicesRepository();
		return $applicationChoicesRepository->buildQuery($fnum, [], $state);
	}

	public function getItems(): array
	{
		$items = parent::getItems();

		if (!empty($items)) {
			$factory = new ApplicationChoicesFactory();
			foreach ($items as &$item) {
				$application_choices_entity  = $factory->fromDbObject($item);
				$item = (object)$application_choices_entity->__serialize();

				$item->typeAlias = 'com_emundus.choices';

				if (isset($item->metadata)) {
					$registry       = new Registry($item->metadata);
					$item->metadata = $registry->toArray();
				}
			}
		}

		return $items;
	}
}
