<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Traits\TraitResponse;

class EmundusControllerCondition extends BaseController
{
	use TraitResponse;

	public function getConditionFieldValues(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->app->getIdentity()->id))
		{
			$response = ['code' => 400, 'status' => false, 'msg' => Text::_('MISSING_REQUIRED_PARAMETER'), 'data' => []];
			$search = $this->input->getString('search_query', '');
			$field = $this->input->getString('properties', '');

			if (!empty($field) && !empty($search))
			{
				// todo: make a support system in condition registry to handle big loads of field options for other cases than form fields ?
				// each resolver would declare if it supports or not the field given, if it does, then we search options through this resolver
				$resolver = new FormDataConditionResolver();
				$choices = $resolver->searchFieldValues($field, $search);

				$response = [
					'code' => 200,
					'status' => true,
					'data' => array_map(function ($choice) {
						return [
							'value' => $choice->getValue(),
							'label' => $choice->getLabel(),
						];
					}, $choices),
				];
			}
		}

		$this->sendJsonResponse($response);
	}
}
