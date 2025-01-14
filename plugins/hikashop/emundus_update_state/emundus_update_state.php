<?php
/**
 * @version   1
 * @author    LEGENDRE Jérémy
 * @package   Hikashop
 * @copyright Copyright (C) 2024 emundus.fr. All rigths reserved
 * @license   GNU/GPL
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die('Restricted access');

class PlgHikashopEmundus_update_state extends CMSPlugin
{

	private $m_payment = null;

	public function __construct(&$subject, $config)
	{
		Log::addLogger(array('text_file' => 'plugin_hikashop.emundus_update_state.php'), Log::ALL, array('plugin_hikashop.emundus_update_state'));

		require_once(JPATH_ROOT . '/components/com_emundus/models/payment.php');
		$this->m_payment = new EmundusModelPayment();

		parent::__construct($subject, $config);
	}

	public function onAfterOrderUpdate(&$order): void
	{
		$fnum    = $this->m_payment->getFnumByOrder($order->order_id);
		$mapping = $this->params->get('mapping', null);

		if (!empty($fnum) && !empty($mapping))
		{
			$configurations = json_decode(json_encode($mapping), true);
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->clear()
				->select('order_payment_method')
				->from($db->quoteName('#__hikashop_order'))
				->where('order_id = ' . $db->quote($order->order_id));

			$db->setQuery($query);
			$current_order_payment_method = $db->loadResult();

			foreach ($configurations as $config)
			{
				$file_entry_status             = $config['file_entry_status'];
				$hikashop_order_status         = $config['hikashop_order_status'];
				$hikashop_order_payment_method = !empty($config['hikashop_order_payment_method']) ? $config['hikashop_order_payment_method'] : [];
				$file_output_status            = $config['file_output_status'];

				if ($order->order_status == $hikashop_order_status && in_array($current_order_payment_method, $hikashop_order_payment_method))
				{
					$query->clear()
						->select('status')
						->from($db->quoteName('#__emundus_campaign_candidature'))
						->where('fnum = ' . $db->quote($fnum));

					$db->setQuery($query);
					$current_file_status = $db->loadResult();
					if ($current_file_status == $file_entry_status)
					{
						$app       = Factory::getApplication();
						$em_config = ComponentHelper::getParams('com_emundus');
						require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
						$m_files = new EmundusModelFiles();
						$updated = $m_files->updateState([$fnum], $file_output_status, $em_config->get('automated_task_user', 1));

						if ($updated !== false)
						{
							Log::add('Updated file ' . $fnum . ' sucessfully to state ' . $file_output_status . ' after order id ' . $order->order_id . ' order_status was updated to ' . $order->order_status, Log::INFO, 'plugin_hikashop.emundus_update_state');
							$app->enqueueMessage(Text::_('PLG_HIKASHOP_EMUNDUS_UPDATE_STATE_SUCCESS'));

							if ($config['re_open_file_after_update_status'] && empty($config['custom_redirect_url_after_update_status'])) {
								$app->redirect('/component/emundus/?task=openfile&fnum=' . $fnum);
							} else if (!empty($config['custom_redirect_url_after_update_status'])) {
								$config['custom_redirect_url_after_update_status'] = str_replace('{fnum}', $fnum, $config['custom_redirect_url_after_update_status']);
								$app->redirect($config['custom_redirect_url_after_update_status']);
							}
						}
						else
						{
							Log::add('Failed to update file ' . $fnum . ' to state ' . $file_output_status . ' after order id ' . $order->order_id . ' order_status was updated to ' . $order->order_status, Log::ERROR, 'plugin_hikashop.emundus_update_state');
							$app->enqueueMessage(Text::_('PLG_HIKASHOP_EMUNDUS_UPDATE_STATE_FAILED'), 'error');
						}
					}
				}
			}
		}
	}
}