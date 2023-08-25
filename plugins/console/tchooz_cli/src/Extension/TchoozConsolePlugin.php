<?php
namespace Emundus\Plugin\Console\Tchooz\Extension;

\defined('_JEXEC') or die;

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
        $db = Factory::getDbo();
        $app->addCommand(new TchoozUserAddCommand($db));
    }
}