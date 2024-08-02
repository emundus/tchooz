# Créer un plugin
## Ressources
- [Documentation officielle Joomla!](https://manual.joomla.org/docs/next/building-extensions/plugins/basic-content-plugin)

## Introduction
Les plugins sont déclenchés par des événements dans Joomla! et peuvent être utilisés pour modifier le comportement de Joomla! ou pour ajouter des fonctionnalités supplémentaires. Les plugins sont des extensions qui sont installées dans le répertoire `plugins` de Joomla! et sont généralement associées à des événements spécifiques.

## Créer un plugin
### Définition du plugin
Prenons l'exemple d'un plugin ayant pour objectif de demander à l'utilisateur de réinitialiser son mot de passe tous les 6 mois. Ce plugin étant lié à la connexion de l'utilisateur nous allons le placer dans le dossier `plugins/user/ask_password_reset`. Une fois le dossier crée, on ajoute un fichier `ask_password_reset.xml` afin de décrire le plugin et de définir des paramètres. Voici un exemple de contenu pour le fichier `ask_password_reset.xml` :
```xml
<?xml version="1.0" encoding="utf-8"?>
<extension method="upgrade" type="plugin" group="user">
    <name>PLG_USER_ASK_PASSWORD_RESET</name>
    <version>1.0</version>
    <description>PLG_USER_ASK_PASSWORD_RESET_DESCRIPTION</description>
    <author>dev@emundus.fr</author>
    <creationDate>02/08/2024</creationDate>
    <copyright>(C) 2024 Open Source Matters, Inc.</copyright>
    <license>GNU General Public License version 2 or later</license>
    <namespace path="src">Emundus\Plugin\User\AskPasswordReset</namespace>
    <files>
        <folder plugin="ask_password_reset">services</folder>
        <folder>src</folder>
    </files>
    <languages>
        <language tag="en-GB">language/en-GB/plg_user_ask_password_reset.ini</language>
        <language tag="en-GB">language/en-GB/plg_user_ask_password_reset.sys.ini</language>
    </languages>
    <config>
        <fields name="params">
            <fieldset name="basic">
                <field name="delay"
                       type="text"
                       default="60"
                       label="PLG_ASK_PASSWORD_RESET_PARAMS_DELAY_LABEL"
                       description="PLG_ASK_PASSWORD_RESET_PARAMS_DELAY_DESC"/>
            </fieldset>
        </fields>
    </config>
</extension>
```
Vous pouvez consulter la liste des types de paramètres disponible [ici](https://manual.joomla.org/docs/general-concepts/forms-fields/standard-fields/)
Quelques points d'attention sur le fichier XML :
- `group="user"` : le groupe de plugin auquel appartient le plugin, si le plugin est lié à un élément Fabrik, le groupe sera `fabrik_element`
- `namespace path="src"` : le namespace du plugin, comme le groupe de plugin, le namespace dépend du type de plugin

### Créer le plugin
Maintenant que le plugin est défini, nous allons créer le fichier `AskPasswordReset.php` dans un dossier `src/Extension`. Voici un exemple de contenu pour le fichier `AskPasswordReset.php` :
```php
<?php
namespace Emundus\Plugin\Users\AskPasswordReset\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

/**
 * Emundus AskPasswordReset User plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  User.ask_password_reset
 * @since       2.0.0
 */
class AskPasswordReset extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
	use UserFactoryAwareTrait;
}
```

Comme évoqué plus haut les plugins s'éxécutent sur des évènements Joomla!. Ces évènements sont déclenchés dans le code par des `trigger` et sont écoutés par les plugins. Pour écouter un évènement, il faut implémenter l'interface `SubscriberInterface` et définir la méthode `getSubscribedEvents`. Voici un exemple de cette méthode pour le fichier `AskPasswordReset.php` :
```php
<?php
public static function getSubscribedEvents(): array
{
    return [
        'onUserLogin' => 'checkPasswordReset'
    ];
}
```
Pour notre plugin nous allons écouter l'évènement `onUserLogin` et appeler la méthode `checkPasswordReset` à chaque fois que cet évènement est déclenché.
Il ne restera plus qu'à implémenter la méthode `checkPasswordReset` pour demander à l'utilisateur de réinitialiser son mot de passe si le délai est dépassé.

### Charger le plugin
Tout comme les modules il faut indiquer à Joomla ce qui doit être chargé pour faire fonctionner le plugin. Pour cela il faut ajouter un fichier `provider.php` dans un dossier `services` du plugin. Voici un exemple de contenu pour le fichier `provider.php` :
```php
<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Emundus\Plugin\User\AskPasswordReset\Extension\AskPasswordReset;

return new class () implements ServiceProviderInterface {
    
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new AskPasswordReset(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('user', 'ask_password_reset')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));
                $plugin->setUserFactory($container->get(UserFactoryInterface::class));

                return $plugin;
            }
        );
    }
};
```
