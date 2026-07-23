<?php
/**
 * @package     Joomla
 * @subpackage  com_emunudus_onboard
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Enums\ColorEnum;
use Tchooz\Factories\Poll\PollFactory;
use Tchooz\Repositories\Poll\PollRepository;

class EmundusViewPolls extends HtmlView
{
	protected ?PollEntity $poll = null;

	protected array $fields = [];

	function display($tpl = null): void
	{
		$app    = Factory::getApplication();

		$layout = $app->input->getString('layout', '');

		if(in_array($layout, ['add', 'edit'])) {
			$pollFactory = new PollFactory();

			$this->fields = array_map(function ($field) {
				assert($field instanceof Field);

				return $field->toSchema();
			}, $pollFactory->getFormFields());

			if(empty($this->fields))
			{
				throw new InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_VIEW_NO_FIELDS'));
			}

			$this->poll = new PollEntity(
				0,
				'',
				'',
				ColorEnum::DARK_BLUE
			);

			if($layout === 'edit')
			{
				$id = $app->input->getInt('id', 0);
				if(empty($id))
				{
					throw new InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_RUN_NO_IDS'));
				}

				$pollRepository = new PollRepository();
				$this->poll = $pollRepository->getItemByField('id', $id, true, $pollRepository->getTableColumns(PollRepository::class));

				if(empty($this->poll))
				{
					throw new InvalidArgumentException(Text::sprintf('COM_EMUNDUS_POLLS_ERROR_NOT_FOUND', $id));
				}
			}
		}

		// Init user state
		$app->setUserState('com_emundus.poll.layout', $layout);

		parent::display($tpl);
	}
}
