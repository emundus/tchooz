<?php
/**
 * @package     SecuritycheckPro.Plugins
 * @subpackage  Task.Cron
 *
 * @copyright   Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license     GNU General Public License version 3, or later
 */

declare(strict_types=1);

namespace Joomla\Plugin\Task\Securitycheckprocron\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use LogicException;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class Securitycheckprocron extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;

    /**
     * @var array<string, array{
     *   langConstPrefix: string,
     *   form?: string,
     *   method: string
     * }>
     */
    protected const TASKS_MAP = [
        'securitycheckpro.cron' => [
            'langConstPrefix' => 'PLG_TASK_SECURITYCHECKPROCRON_TASK',
            'form'            => 'cron',
            'method'          => 'launchCron',
        ],
    ];

    /**
     * @var bool
     */
    protected $autoloadLanguage = true;

    private ?BaseModel $baseModel = null;
    private ?FilemanagerModel $filemanagerModel = null;

    /** Evita reintentos innecesarios */
    private bool $modelsInitialized = false;

    /**
     * Constructor.
     *
     * OJO: NO inicializamos modelos aquí para no romper pantallas como
     * la lista de tareas del scheduler (se instancia el plugin en backend).
     *
     * @param DispatcherInterface      $dispatcher
     * @param array<string,mixed>      $config
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
		/** @phpstan-ignore-next-line */
        parent::__construct($dispatcher, $config);
    }

    /**
     * @return array<string,string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    /**
     * Inicializa los modelos del componente (lazy).
     *
     * @throws LogicException
     */
    private function initModels(): void
    {
        if ($this->modelsInitialized) {
            return;
        }

        $this->modelsInitialized = true;

        $app = $this->getApplication();
        if ($app === null) {
            // Como fallback extremo, usamos Factory (pero con provider corregido no debería ocurrir)
            $app = Factory::getApplication();
        }

        $component = $app->bootComponent('com_securitycheckpro');

		if (!$component instanceof MVCFactoryServiceInterface) {
			throw new LogicException('com_securitycheckpro does not provide an MVC Factory (MVCFactoryServiceInterface expected).');
		}

		$mvcFactory = $component->getMVCFactory();

        $base = $mvcFactory->createModel('Base', 'Administrator');
        $fm   = $mvcFactory->createModel('Filemanager', 'Administrator');

        if (!$base instanceof BaseModel) {
            throw new LogicException('Could not create BaseModel from com_securitycheckpro MVC factory.');
        }

        if (!$fm instanceof FilemanagerModel) {
            throw new LogicException('Could not create FilemanagerModel from com_securitycheckpro MVC factory.');
        }

        $this->baseModel = $base;
        $this->filemanagerModel = $fm;
    }

    private function acciones(): void
    {
        $this->initModels();

        if ($this->baseModel === null || $this->filemanagerModel === null) {
            throw new LogicException('Models not initialized.');
        }

        $timestamp = $this->baseModel->get_Joomla_timestamp();

        $this->filemanagerModel->setCampoFilemanager('last_check', $timestamp);
        $this->filemanagerModel->setCampoFilemanager('estado', 'IN_PROGRESS');

        $fm = $this->filemanagerModel;
        register_shutdown_function(static function () use ($fm): void {
            try {
                if ($fm->GetCampoFilemanager('estado') === 'IN_PROGRESS') {
                    $fm->setCampoFilemanager('estado', 'ENDED');
                    $fm->setCampoFilemanager('files_scanned', 100);
                }
            } catch (\Throwable) {}
        });

        $this->filemanagerModel->scan('permissions');
        $this->filemanagerModel->setCampoFilemanager('last_task', 'PERMISSIONS');
    }

    private function acciones_integrity(): void
    {
        $this->initModels();

        if ($this->baseModel === null || $this->filemanagerModel === null) {
            throw new LogicException('Models not initialized.');
        }

        $timestamp = $this->baseModel->get_Joomla_timestamp();
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $lookForMalware = (bool) $params->get('look_for_malware', 0);

        $this->filemanagerModel->setCampoFilemanager('last_check_integrity', $timestamp);
        $this->filemanagerModel->setCampoFilemanager('estado_integrity', 'IN_PROGRESS');

        $fm = $this->filemanagerModel;
        register_shutdown_function(static function () use ($fm, $lookForMalware): void {
            try {
                if ($fm->GetCampoFilemanager('estado_integrity') === 'IN_PROGRESS') {
                    $fm->setCampoFilemanager('estado_integrity', 'ENDED');
                    $fm->setCampoFilemanager('files_scanned_integrity', 100);
                }
                if ($lookForMalware && $fm->GetCampoFilemanager('estado_malwarescan') === 'IN_PROGRESS') {
                    $fm->setCampoFilemanager('estado_malwarescan', 'ENDED');
                    $fm->setCampoFilemanager('files_scanned_malwarescan', 100);
                }
            } catch (\Throwable) {}
        });

        $this->filemanagerModel->scan('integrity');
        $this->filemanagerModel->setCampoFilemanager('last_task', 'INTEGRITY');

        if ($lookForMalware) {
            $this->filemanagerModel->scan('malwarescan_modified');
        }

        [$badIntegrity, $suspicious] = $this->consulta_resultado_scan();

        $sendEmail    = (bool) $params->get('send_email_on_wrong_integrity', 1);
        $emailSubject = (string) $params->get('email_subject_on_wrong_integrity', '');

        $config   = Factory::getConfig();
        $isOnline = (bool) $config->get('mailonline', 1);

        if ($isOnline && $sendEmail && $badIntegrity > 0) {
            $this->mandar_correo($badIntegrity, $suspicious, $lookForMalware, $emailSubject);
        }
    }

    /**
     * @param int    $withBadIntegrity
     * @param int    $withSuspiciousPatterns
     * @param bool   $lookForMalware
     * @param string $subject
     */
    protected function mandar_correo(
        int $withBadIntegrity,
        int $withSuspiciousPatterns,
        bool $lookForMalware,
        string $subject
    ): void {
        $this->initModels();

        if ($this->baseModel === null) {
            throw new LogicException('Base model not initialized.');
        }

        $emailActive = (bool) $this->baseModel->getValue('email_active', 0, 'pro_plugin');
        if (!$emailActive) {
            return;
        }

        $emailToRaw = (string) $this->baseModel->getValue('email_to', '', 'pro_plugin');
        $to = array_values(array_filter(
            array_map('trim', explode(',', $emailToRaw)),
            static fn (string $v): bool => $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL) !== false
        ));

        if ($to === []) {
            $this->logTask('Email enabled but no valid recipients configured.', 'warning');
            return;
        }

        $emailFromDomain = (string) $this->baseModel->getValue('email_from_domain', '', 'pro_plugin');
        $emailFromName   = (string) $this->baseModel->getValue('email_from_name', '', 'pro_plugin');

        /** @var array{0:string,1:string} $from */
        $from = [$emailFromDomain, $emailFromName];

        $config   = Factory::getConfig();
        $sitename = (string) $config->get('sitename', '');

        // Asegura strings del componente en admin
        $lang = $this->getApplication()?->getLanguage();
        if ($lang !== null) {
            $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
        }

        $finalSubject = $subject !== ''
            ? $subject
            : Text::sprintf('COM_SECURITYCHECKPRO_EMAIL_SITENAME', $sitename);

        $body = $lookForMalware
            ? Text::sprintf('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY', $withBadIntegrity, $withSuspiciousPatterns)
            : Text::sprintf('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY_NO_MALWARE_SCAN', $withBadIntegrity);

        $body .= '<br><br>' . Text::_('COM_SECURITYCHECKPRO_EMAIL_ALERT_BODY_ALERT');

        try {
            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

            $mailer->setSender($from);
            $mailer->addRecipient($to);
            $mailer->setSubject($finalSubject);
            $mailer->setBody($body);

            $mailer->isHtml(true);
            $mailer->Encoding = 'base64';

            $mailer->send();
        } catch (\Throwable $e) {
            $this->logTask('Mailer error (' . $e::class . '): ' . $e->getMessage(), 'error');
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private function consulta_resultado_scan(): array
    {
        $this->initModels();

        if ($this->filemanagerModel === null) {
            throw new LogicException('Filemanager model not initialized.');
        }

        $badIntegrity = (int) $this->filemanagerModel->loadStack('fileintegrity_resume', 'files_with_bad_integrity');
        $suspicious   = (int) $this->filemanagerModel->loadStack('malwarescan_resume', 'suspicious_files');

        return [$badIntegrity, $suspicious];
    }

    private function resolveTaskToBeLaunched(ExecuteTaskEvent $event): string
    {
        $params = $event->getArgument('params');

        $task = 'alternate';

        if ($params instanceof Registry) {
            $task = (string) $params->get('task_to_be_launched', 'alternate');
        } elseif (is_object($params) && isset($params->task_to_be_launched)) {
            /** @var mixed $raw */
            $raw  = $params->task_to_be_launched;
            $task = (string) $raw;
        }

        $task = trim($task);

        $allowed = [
            'alternate'   => true,
            'permissions' => true,
            'integrity'   => true,
            'both'        => true,
        ];

        if (!isset($allowed[$task])) {
            $this->logTask('Invalid task_to_be_launched value: ' . $task, 'warning');
            return 'alternate';
        }

        return $task;
    }

    protected function launchCron(ExecuteTaskEvent $event): int
    {
        try {
            $task = $this->resolveTaskToBeLaunched($event);

            switch ($task) {
                case 'alternate':
                    $this->initModels();
                    if ($this->filemanagerModel === null) {
                        throw new LogicException('Filemanager model not initialized.');
                    }

                    $lastTask = (string) $this->filemanagerModel->GetCampoFilemanager('last_task');

                    if ($lastTask === 'INTEGRITY') {
                        $this->acciones();
                    } elseif ($lastTask === 'PERMISSIONS') {
                        $this->acciones_integrity();
                    } else {
                        $this->acciones_integrity();
                    }
                    break;

                case 'permissions':
                    $this->acciones();
                    break;

                case 'integrity':
                    $this->acciones_integrity();
                    break;

                case 'both':
                    $this->acciones();
                    $this->acciones_integrity();
                    break;
            }
        } catch (\Throwable $e) {
            $this->logTask($e->getMessage(), 'error');
            return TaskStatus::KNOCKOUT;
        }

        return TaskStatus::OK;
    }
}