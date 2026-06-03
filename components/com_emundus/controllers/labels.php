<?php

use Joomla\CMS\Language\Text;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Label\LabelRepository;

defined('_JEXEC') or die('Restricted access');

if (!class_exists('EmundusHelperAccess'))
{
	require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
}

class EmundusControllerLabels extends EmundusController
{
	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function save(): EmundusResponse
	{
		$this->checkToken();

		$id = $this->input->getInt('id', 0);
		$label = $this->input->getString('label', '');
		$color = $this->input->getString('color', 'label-default');
		$ordering = $this->input->getInt('ordering', 0);
		$category = $this->input->getString('category', '');

		if (!empty($label))
		{
			$label = new LabelEntity($label, $color, $ordering, $id, $category);

			$labelRepository = new LabelRepository();

			if ($labelRepository->flush($label))
			{
				$response = EmundusResponse::ok(['id' => $label->getId()]);
			} else
			{
				$response = EmundusResponse::fail(Text::_('ERROR_CANNOT_SAVE_LABEL'));
			}
		} else {
			$response = EmundusResponse::fail(Text::_('ERROR_LABEL_NAME_CANNOT_BE_EMPTY'));
		}

		return $response;
	}

	/**
	 * Returns the tag associations (with author + date + label data) attached to a single application.
	 */
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::TAG, 'mode' => CrudEnum::READ]
	])]
	public function getapplicationtags(): EmundusResponse
	{
		$fnum = $this->input->getString('fnum', '');

		if (empty($fnum))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'));
		}

		if (!EmundusHelperAccess::asAccessAction(ActionEnum::TAG->value, CrudEnum::READ->value, $this->user->id, $fnum))
		{
			return EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$labelRepository = new LabelRepository();
		$associations    = $labelRepository->getLabelAssociationsByFnum($fnum);

		$data = array_map(static fn($association) => $association->__serialize(), $associations);

		return EmundusResponse::ok($data);
	}

	/**
	 * Returns the list of available tag definitions (LabelEntity) that the user can attach.
	 */
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::TAG, 'mode' => CrudEnum::READ]
	])]
	public function getavailabletags(): EmundusResponse
	{
		$labelRepository = new LabelRepository();
		$labels          = $labelRepository->get();

		$data = array_map(static fn($label) => $label->__serialize(), $labels);

		return EmundusResponse::ok($data);
	}
}