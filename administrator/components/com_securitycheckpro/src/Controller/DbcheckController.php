<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// Protección frente a accesos no autorizados
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\BaseController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\DbcheckModel;

class DbcheckController extends BaseController
{
    
    public function optimize(): void
	{
		$app   = Factory::getApplication();
		$input = $app->getInput();

		$user = $app->getIdentity();
		if (!$user->authorise('core.manage', 'com_securitycheckpro')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = $input->post->getString('table', '');
		if ($table === '') {
			throw new \RuntimeException(Text::_('COM_SECURITYCHECKPRO_DB_OPTIMIZE_BAD_REQUEST'), 400);
		}

		/** @var DbcheckModel $model */
		$model = $this->getModel('Dbcheck');
		if (!$model instanceof DbcheckModel) {
			throw new \RuntimeException('Dbcheck model not found', 500);
		}

		try {
			$result = $model->optimizeTable($table);

			// Mensaje consistente
			$msg = $result['message'] ?? Text::sprintf(
				'COM_SECURITYCHECKPRO_DB_OPTIMIZE_RESULT',
				(string) ($result['optimize'] ?? ''),
				(string) ($result['repair'] ?? '')
			);
			
			echo new JsonResponse(['result' => $result, 'message' => $msg], $msg, false);
			$app->close();
		} catch (\Throwable $e) {
			// Mensaje real del error (o del modelo si lo has seteado)
			$msg = (string) $model->getError();
			if ($msg === '') {
				$msg = $e->getMessage() ?: Text::_('JERROR_AN_ERROR_HAS_OCCURRED');
			}
			throw new \RuntimeException($msg, 500);
		}
	}

    /* Redirecciona las peticiones al Panel de Control */
    function redireccion_control_panel():void
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }
}