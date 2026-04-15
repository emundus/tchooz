<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class PaymentAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		return $this->applyState(true);
	}

	public function onDeactivate(): bool
	{
		return $this->applyState(false);
	}

	private function applyState(bool $state): bool
	{
		$updates = [];

		try
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$payment_repository = new PaymentRepository();
			$payment_action_id  = $payment_repository->getActionId();

			$intState = $state ? 1 : 0;

			$query->clear()
				->update($db->quoteName('#__emundus_setup_step_types'))
				->set($db->quoteName('published') . ' = ' . $db->quote($intState))
				->where($db->quoteName('action_id') . ' = ' . $db->quote($payment_action_id));
			$db->setQuery($query);
			$updates[] = $db->execute();

			$query->clear()
				->update($db->quoteName('#__emundus_setup_actions'))
				->set($db->quoteName('status') . ' = ' . $intState)
				->where($db->quoteName('id') . ' = ' . $db->quote($payment_action_id));
			$db->setQuery($query);
			$updates[] = $db->execute();

			$query->clear()
				->update($db->quoteName('jos_emundus_plugin_events'))
				->set($db->quoteName('available') . ' = ' . $intState)
				->where($db->quoteName('label') . ' IN (' . implode(',', $db->quote(['onAfterEmundusCartUpdate', 'onBeforeEmundusCartRender', 'onAfterEmundusTransactionUpdate', 'onAfterLoadEmundusPaymentStep'])) . ')');
			$db->setQuery($query);
			$updates[] = $db->execute();

			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $intState)
				->where($db->quoteName('alias') . ' IN (' . implode(',', $db->quote(['cart', 'transactions', 'products', 'modify-cart-products'])) . ')');
			$db->setQuery($query);
			$updates[] = $db->execute();

			$query->clear()
				->select('value')
				->from($db->quoteName('#__emundus_setup_config'))
				->where($db->quoteName('namekey') . ' = ' . $db->quote('payment'));
			$db->setQuery($query);
			$payment_config = json_decode($db->loadResult(), true);

			$payment_config['enabled'] = $intState;

			$query->clear()
				->update($db->quoteName('#__emundus_setup_config'))
				->set($db->quoteName('value') . ' = ' . $db->quote(json_encode($payment_config)))
				->where($db->quoteName('namekey') . ' = ' . $db->quote('payment'));

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