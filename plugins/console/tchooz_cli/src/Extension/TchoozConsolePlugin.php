<?php
namespace Emundus\Plugin\Console\Tchooz\Extension;

\defined('_JEXEC') or die;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozConfigCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozKeycloakCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozMigrateCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozResetFabrikConnectionCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozUpdateCommand;
use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozVanillaCommand;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Application\ApplicationEvents;
use Joomla\CMS\Factory;
use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozUserAddCommand;

class TchoozConsolePlugin extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            \Joomla\Application\ApplicationEvents::BEFORE_EXECUTE => 'registerCommands',
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
    }
}