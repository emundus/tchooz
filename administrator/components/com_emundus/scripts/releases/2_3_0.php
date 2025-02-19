<?php


/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;

class Release2_3_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('applicantmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('mes-reservations') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=events&layout=mybooking'));
			$this->db->setQuery($query);
			$list_reservations = $this->db->loadResult();

			if(empty($list_reservations)) {
				$data = [
					'menutype' => 'applicantmenu',
					'title' => 'Mes réservations',
					'alias' => 'mes-reservations',
					'path' => 'mes-reservations',
					'link' => 'index.php?option=com_emundus&view=events&layout=mybooking',
					'type' => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params' => [],
				];
				$reservations_menu = EmundusHelperUpdate::addJoomlaMenu($data, 1, 0);
				EmundusHelperUpdate::insertFalangTranslation(1, $reservations_menu['id'], 'menu', 'title', 'My reservations');

				EmundusHelperUpdate::addCustomEvents([
					['label' => 'onAfterUnsubscribeRegistrant', 'category' => 'Booking'],
					['label' => 'onAfterBookingRegistrant', 'category' => 'Booking']
				]);
			}

			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();

			return $result;
		}


		return $result;
	}
}