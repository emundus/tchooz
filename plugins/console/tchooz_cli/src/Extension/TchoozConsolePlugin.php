<?php
namespace Emundus\Plugin\Console\Tchooz\Extension;

\defined('_JEXEC') or die;

use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;

use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozConfigCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozKeycloakCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozMigrateCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozMigrateCheckReqsCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozResetFabrikConnectionCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozUpdateCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozVanillaCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozUserAddCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozMigrateChecklistCommand;

class TchoozConsolePlugin extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_EXECUTE => 'registerCommands',
        ];
    }

    public function registerCommands(): void
    {
        $app = Factory::getApplication();
		$db = Factory::getContainer()->get('DatabaseDriver');

        $app->addCommand(new TchoozUserAddCommand($db));
        $app->addCommand(new TchoozUpdateCommand($db));
	    $app->addCommand(new TchoozMigrateCommand($db));
		$app->addCommand(new TchoozResetFabrikConnectionCommand($db));
		$app->addCommand(new TchoozVanillaCommand($db));
		$app->addCommand(new TchoozConfigCommand($db));
		$app->addCommand(new TchoozKeycloakCommand($db));
		$app->addCommand(new TchoozMigrateCheckReqsCommand($db));
	    $app->addCommand(new TchoozMigrateChecklistCommand($db));
    }
}