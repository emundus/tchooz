<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_21_1Installer extends ReleaseInstaller
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
			$this->tasks[] = \EmundusHelperUpdate::addCustomEvents([['label' => 'onBeforeCampaignsDelete', 'category' => 'Campaign', 'published' => 1]])['status'];
			$this->tasks[] = \EmundusHelperUpdate::addCustomEvents([['label' => 'onAfterCampaignsDelete', 'category' => 'Campaign', 'published' => 1]])['status'];

			$query->clear()
				->select('extension_id, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('custom_event_handler'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
			$this->db->setQuery($query);
			$custom_event_handler = $this->db->loadObject();

			if(!empty($custom_event_handler) && !empty($custom_event_handler->params))
			{
				$needToUpdate = false;
				$params = json_decode($custom_event_handler->params);
				if(!empty($params->event_handlers))
				{
					foreach ($params->event_handlers as $event_handler)
					{
						if($event_handler->event === 'onBeforeCampaignDelete')
						{
							$event_handler->event = 'onBeforeCampaignsDelete';
							$needToUpdate = true;
						}

						if($event_handler->event === 'onAfterCampaignDelete')
						{
							$event_handler->event = 'onAfterCampaignsDelete';
							$needToUpdate = true;
						}
					}
				}

				if($needToUpdate)
				{
					$custom_event_handler->params = json_encode($params);
					$this->db->updateObject('#__extensions', $custom_event_handler, 'extension_id');
				}
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
