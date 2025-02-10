<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Joomla\CMS\Factory::getApplication();
$ids = $app->getInput()->get('ids', array(), 'array');

foreach ($ids as $myid)
{
	$row = $model->getRow($myid);

	if ($row->jos_emundus_campaign_candidature___status_raw != 0)
	{
		$statusMsg = Text::_('COM_EMUNDUS_CAMPAIGN_CANDIDATURE_CANNOT_DELETE');

		return false;
	}

	$fnum = $row->jos_emundus_campaign_candidature___fnum_raw;
	$row_id = $row->jos_emundus_btob_1237_repeat___id_raw;

	if (!empty($fnum))
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->clear()
			->delete('jos_emundus_btob_inscription_1244_repeat')
			->where('fnum LIKE ' . $db->quote($fnum));
		$db->setQuery($query);
		$db->execute();

		if(!empty($row_id))
		{
			$query->clear()
				->delete('jos_emundus_btob_1237_repeat')
				->where('id = ' . $db->quote($row_id));
			$db->setQuery($query);
			$db->execute();
		}

		$query->clear()
			->delete('jos_emundus_campaign_candidature')
			->where('fnum LIKE ' . $db->quote($fnum))
			->andWhere('status = 0');
		$db->setQuery($query);
		$db->execute();
	}
}

