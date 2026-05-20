<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class PaymentAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		$this->ensureApplicationCartMenu();
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
				->where($db->quoteName('alias') . ' IN (' . implode(',', $db->quote(['cart', 'transactions', 'products', 'modify-cart-products', 'manager-cart'])) . ')');
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

	private function ensureApplicationCartMenu(): void
	{
		$cartLink = 'index.php?option=com_emundus&view=application&layout=cart&format=raw';

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id')
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('link') . ' = ' . $db->quote($cartLink))
			->where($db->quoteName('menutype') . ' = ' . $db->quote('application'));
		$db->setQuery($query);
		$existingMenu = $db->loadResult();

		if (!empty($existingMenu))
		{
			return;
		}

		if (!class_exists('EmundusHelperUpdate'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		}

		$action = (new ActionRepository())->getByName(ActionEnum::PAYMENT->value);

		# Création du menu
		\EmundusHelperUpdate::addJoomlaMenu([
			'menutype'          => 'application',
			'title'             => 'Panier',
			'alias'             => 'manager-cart',
			'path'              => 'manager-cart',
			'link'              => $cartLink,
			'type'              => 'component',
			'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
			'template_style_id' => 0,
			'note'              => $action->getId() . '|r'
		], 1, 1);

		$emundusCmptConfig = ComponentHelper::getParams('com_emundus');
		$allRightsGrp = $emundusCmptConfig->get('all_rights_group', 1);
		$group = (new GroupRepository())->getById($allRightsGrp);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
			->from($db->quoteName('jos_emundus_acl'))
			->where($db->quoteName('group_id') . ' = ' . $db->quote($group->getId()))
			->where($db->quoteName('action_id') . ' = ' . $db->quote($action->getId()));
		$db->setQuery($query);
		$existingId = (int) ($db->loadResult() ?? 0);

		# Ajout de la permission au rôle "Administrateur de la plateforme"
		$groupAccessEntity = new GroupAccessEntity(
			$existingId,
			$group,
			$action,
			new CrudEntity(0, 1, 1, 1, 1)
		);

		(new GroupAccessRepository())->flush($groupAccessEntity);
	}
}
