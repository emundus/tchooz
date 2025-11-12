<?php

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Payment\PaymentRepository;

class PaymentAddonHandler implements AddonHandlerInterface
{

	private AddonEntity $addon;

	public function __construct(AddonEntity $addon)
	{
		$this->addon = $addon;
	}

	public function toggle(bool $state): bool
	{
		$updates = [];

		try
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$payment_repository = new PaymentRepository();
			$payment_action_id  = $payment_repository->getActionId();

			$query->clear()
				->update($db->quoteName('#__emundus_setup_step_types'));
			if ($state)
			{
				$query->set($db->quoteName('published') . ' = ' . $db->quote(1));
			}
			else
			{
				$query->set($db->quoteName('published') . ' = ' . $db->quote(0));
			}
			$query->where($db->quoteName('action_id') . ' = ' . $db->quote($payment_action_id));

			$db->setQuery($query);
			$updates[] = $db->execute();

			$query->clear()
				->update($db->quoteName('#__emundus_setup_actions'))
				->set($db->quoteName('status') . ' = ' . (int) $state)
				->where($db->quoteName('id') . ' = ' . $db->quote($payment_action_id));

			$db->setQuery($query);
			$updates[] = $db->execute();

			$query->clear()
				->update($db->quoteName('jos_emundus_plugin_events'))
				->set($db->quoteName('available') . ' = ' . (int) $state)
				->where($db->quoteName('label') . ' IN (' . implode(',', $db->quote(['onAfterEmundusCartUpdate', 'onBeforeEmundusCartRender', 'onAfterEmundusTransactionUpdate', 'onAfterLoadEmundusPaymentStep'])) . ')');

			$db->setQuery($query);
			$updates[] = $db->execute();

			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . (int) $state)
				->where($db->quoteName('alias') . ' IN (' . implode(',', $db->quote(['cart', 'transactions', 'products', 'modify-cart-products'])) . ')');

			$db->setQuery($query);
			$updates[] = $db->execute();
		}
		catch (\Exception $e)
		{
			$updates[] = false;
		}

		return !in_array(false, $updates);
	}
}