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
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;

class EmundusViewCampaigns extends HtmlView
{
	protected string $tabs_to_display = 'global,more,steps,attachments,emails,history';

	function display($tpl = null): void
	{
		$app    = Factory::getApplication();
		$jinput = $app->input;
		$user   = $app->getIdentity();

		$layout = $jinput->getString('layout', '');
		if ($layout == 'add')
		{
			$this->id = $jinput->getString('cid', 0);
		}

		if ($layout === 'addnextcampaign')
		{
			$this->id = $jinput->getString('cid', 0);

			$campaignRepository = new CampaignRepository();
			$actionRepository   = new ActionRepository();
			$campaignAction     = $actionRepository->getByName('campaign');
			$campaignEditAccess = EmundusHelperAccess::asCoordinatorAccessLevel($user->id) || EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::UPDATE->value, $user->id);
			if (!$campaignEditAccess)
			{
				// I can update if I am creator of the campaign even if I don't have the update right on all campaigns
				$campaign = $campaignRepository->getById($this->id);
				if ($campaign->getCreatedBy() != $user->id)
				{
					$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
					$app->redirect('/campaigns');
				}
			}
		}

		$menu         = Factory::getApplication()->getMenu();
		$current_menu = $menu->getActive();
		$menu_params  = $menu->getParams($current_menu->id);
		if ($menu_params->get('tabs_to_display'))
		{
			if (!empty($menu_params->get('tabs_to_display')))
			{
				$this->tabs_to_display = implode(',', $menu_params->get('tabs_to_display'));
			}
		}

		parent::display($tpl);
	}
}
