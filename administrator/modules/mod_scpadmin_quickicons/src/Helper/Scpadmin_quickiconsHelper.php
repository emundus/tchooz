<?php
/**
 * @Scpadmin_quickicions module
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license GNU GPL v3 or later
 */

declare(strict_types=1);

namespace Joomla\Module\Scpadmin_quickicons\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Extension\MVCComponentInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\CpanelModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;

final class Scpadmin_quickiconsHelper
{
    /**
     * Cache de botones por key
     *
     * @var array<string, list<array{link:string,image:string,text:string,id:string,access:bool}>>
     */
    protected static array $buttons = [];

    /**
     * Helper method to return button list.
     *
     * @param Registry $params The module parameters.
     * @return list<array{link:string,image:string,text:string,id:string,access:bool}>
     */
    public static function getButtons(Registry $params): array
    {
        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();

        // Key estable
        $key = md5((string) json_encode($params->toArray(), JSON_THROW_ON_ERROR));

        if (isset(self::$buttons[$key])) {
            return self::$buttons[$key];
        }

        // Inicializamos
        self::$buttons[$key] = [];

        // 1) Comprobar instalación
        $providerPath = JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/services/provider.php';
        if (!is_file($providerPath)) {
            return self::$buttons[$key];
        }

        // 2) Comprobar que el componente esté habilitado
        if (!ComponentHelper::isEnabled('com_securitycheckpro')) {
			Log::add('Scpadmin_quickiconsHelper. getButtons function info: SecuritycheckPro is not enabled.', Log::INFO, 'mod_scpadmin_quickicons');
            $app->enqueueMessage(Text::_('MOD_SECURITYCHECKPRO_NOT_ENABLED'), 'error');
            return self::$buttons[$key];
        }

        // Parámetros por defecto (tipados como int/bool de facto)
        $params->def('check_vulnerable_extensions', 1);
        $params->def('check_not_readed_logs', 1);
        $params->def('check_file_permissions', 1);
        $params->def('check_file_integrity', 1);
        $params->def('check_malwarescan', 1);

        // Idiomas
        $lang = $app->getLanguage();
        $lang->load('mod_scpadmin_quickicons', JPATH_ADMINISTRATOR, 'en-GB', true);
        $lang->load('mod_scpadmin_quickicons', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
        $lang->load('mod_scpadmin_quickicons', JPATH_ADMINISTRATOR, null, true);
		
		$component = $app->bootComponent('com_securitycheckpro');		

		if (!method_exists($component, 'getMVCFactory')) {
			Log::add('Scpadmin_quickiconsHelper. getButtons function error: getMVCFactory method does not exists.', Log::ERROR, 'mod_scpadmin_quickicons');	
			$app->setUserState('exists_filemanager', false);
			return self::$buttons[$key];
		}

		/** @var \Joomla\CMS\Extension\MVCComponent $component */
		$mvcFactory = $component->getMVCFactory();
		
		$cpanelModel = $mvcFactory->createModel('Cpanel', 'Administrator');
		$fileModel   = $mvcFactory->createModel('Filemanager', 'Administrator');

        if (!$cpanelModel instanceof CpanelModel || !$fileModel instanceof FilemanagerModel) {
            $app->setUserState('exists_filemanager', false);
            return self::$buttons[$key];
        }
		
        $app->setUserState('exists_filemanager', true);

        $document = $app->getDocument();

        // Carga de idioma por contexto (sin comparación laxa)
        $context = (string) $params->get('context', 'mod_scpadmin_quickicons');
        if ($context === 'mod_scpadmin_quickicons') {
            $lang->load('mod_scpadmin_quickicons');
        }

        // Helper local para marcar iconos con clase (evita duplicación de JS y reduce errores)
        $addStatusScript = static function (string $elementId, string $statusClass) use ($document): void {
            $elementIdJs = json_encode($elementId, JSON_THROW_ON_ERROR);
            $statusJs    = json_encode($statusClass, JSON_THROW_ON_ERROR);

            $document->addScriptDeclaration(
                "document.addEventListener('DOMContentLoaded', function () {
                    window.setTimeout(function () {
                        var link = document.getElementById($elementIdJs);
                        if (!link) { return; }
                        link.classList.add($statusJs);
                    }, 2000);
                });"
            );
        };
		
		$withToken = static function (string $url): string {
			$token = Session::getFormToken();

			// Si ya trae '?', usamos '&', si no, '?'
			$sep = str_contains($url, '?') ? '&' : '?';

			return $url . $sep . $token . '=1';
		};
		
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // -------- Vulnerable extensions --------
        if ((int) $params->get('check_vulnerable_extensions', 1) === 1) {
            $cpanelModel->buscarQuickIcons();

            $countByStatus = static function (string $status) use ($db): int {
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__securitycheckpro'))
                    ->where($db->quoteName('Vulnerable') . ' = ' . $db->quote($status));
                $db->setQuery($query);

                return (int) $db->loadResult();
            };

            $vuln = $countByStatus('Si');
            $undef = $countByStatus('Indefinido');

            if ($vuln > 0) {
                $image = 'fa fa-exclamation';
                $text  = Text::_('MOD_SECURITYCHECKPRO_VULNERABLE_EXTENSIONS');
                $addStatusScript('plg_quickicon_scp_vuln_extensions', 'danger');
            } elseif ($undef > 0) {
                $image = 'fa fa-question-circle';
                $text  = Text::_('MOD_SECURITYCHECKPRO_VULNERABLE_EXTENSIONS');
                $addStatusScript('plg_quickicon_scp_vuln_extensions', 'warning');
            } else {
                $image = 'fa fa-check-circle';
                $text  = Text::_('MOD_SECURITYCHECKPRO_NO_VULNERABLE_EXTENSIONS');
                $addStatusScript('plg_quickicon_scp_vuln_extensions', 'success');
            }
			
			$link = $withToken('index.php?option=com_securitycheckpro&view=securitycheckpro');

            self::$buttons[$key][] = [
                'link'   => Route::_($link),
                'image'  => $image,
                'text'   => $text,
                'id'     => 'plg_quickicon_scp_vuln_extensions',
                'access' => true,
            ];
        }

        // -------- Unread logs --------
        if ((int) $params->get('check_not_readed_logs', 1) === 1) {
            $logsPending = (int) $cpanelModel->LogsPending();

            if ($logsPending === 0) {
                $image = 'fa fa-file';
                $text  = Text::_('MOD_SECURITYCHECKPRO_NOT_UNREAD_LOGS');
                $addStatusScript('plg_quickicon_scp_logs', 'success');
            } else {
                $image = 'fa fa-file-alt';
                $text  = Text::_('MOD_SECURITYCHECKPRO_UNREAD_LOGS');
                $addStatusScript('plg_quickicon_scp_logs', 'danger');
            }

			$link = $withToken('index.php?option=com_securitycheckpro&task=securitycheckpro.view_logs');

            self::$buttons[$key][] = [
                'link'   => Route::_($link),
                'image'  => $image,
                'text'   => $text,
                'id'     => 'plg_quickicon_scp_logs',
                'access' => true,
            ];
        }

        // -------- File permissions --------
        if ((int) $params->get('check_file_permissions', 1) === 1) {
            $badPerms = (int) $fileModel->loadStack('filemanager_resume', 'files_with_incorrect_permissions');

            if ($badPerms === 0) {
                $image = 'fa fa-check-square';
                $text  = Text::_('MOD_SECURITYCHECKPRO_FILE_PERMISSIONS_OK');
                $addStatusScript('plg_quickicon_scp_permissions', 'success');
            } else {
                $image = 'fa fa-square';
                $text  = Text::_('MOD_SECURITYCHECKPRO_FILE_PERMISSIONS_WRONG');
                $addStatusScript('plg_quickicon_scp_permissions', 'danger');
            }
			
			$link = $withToken('index.php?option=com_securitycheckpro&view=filemanager');

            self::$buttons[$key][] = [
                'link'   => Route::_($link),
                'image'  => $image,
                'text'   => $text,
                'id'     => 'plg_quickicon_scp_permissions',
                'access' => true,
            ];
        }

        // -------- File integrity --------
        if ((int) $params->get('check_file_integrity', 1) === 1) {
            $badIntegrity = (int) $fileModel->loadStack('fileintegrity_resume', 'files_with_bad_integrity');

            if ($badIntegrity === 0) {
                $image = 'fa fa-lock';
                $text  = Text::_('MOD_SECURITYCHECKPRO_FILE_INTEGRITY_OK');
                $addStatusScript('plg_quickicon_scp_integrity', 'success');
            } else {
                $image = 'fa fa-unlock';
                $text  = Text::_('MOD_SECURITYCHECKPRO_FILE_INTEGRITY_WRONG');
                $addStatusScript('plg_quickicon_scp_integrity', 'danger');
            }

			$link = $withToken('index.php?option=com_securitycheckpro&task=cpanel.go_to_fileintegrity');
            
            self::$buttons[$key][] = [
                'link'   => Route::_($link),
                'image'  => $image,
                'text'   => $text,
                'id'     => 'plg_quickicon_scp_integrity',
                'access' => true,
            ];
        }

        // -------- Malware scan --------
        if ((int) $params->get('check_malwarescan', 1) === 1) {
            $suspicious = (int) $fileModel->loadStack('malwarescan_resume', 'suspicious_files');

            if ($suspicious === 0) {
                $image = 'fa fa-thumbs-up';
                $text  = Text::_('MOD_SECURITYCHECKPRO_MALWARESCAN_OK');
                $addStatusScript('plg_quickicon_scp_malware', 'success');
            } else {
                $image = 'fa fa-bug';
                $text  = Text::_('MOD_SECURITYCHECKPRO_MALWARESCAN_WRONG');
                $addStatusScript('plg_quickicon_scp_malware', 'danger');
            }
			
			$link = $withToken('index.php?option=com_securitycheckpro&task=cpanel.go_to_malware');

            self::$buttons[$key][] = [
                'link'   => Route::_($link),
                'image'  => $image,
                'text'   => $text,
                'id'     => 'plg_quickicon_scp_malware',
                'access' => true,
            ];
        }

        return self::$buttons[$key];
    }

    /**
     * Get the alternate title for the module
     *
     * @param Registry $params The module parameters.
     * @param object{title:string} $module The module object.
     */
    public static function getTitle(Registry $params, object $module): string
    {
        $key = (string) $params->get('context', 'mod_scpadmin_quickicons') . '_title';

        if (Factory::getApplication()->getLanguage()->hasKey($key)) {
            return Text::_($key);
        }

        return (string) $module->title;
    }
}