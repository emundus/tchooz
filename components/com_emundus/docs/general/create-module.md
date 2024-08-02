# Créer un module
## Ressources
- [Documentation officielle Joomla!](https://manual.joomla.org/docs/next/building-extensions/modules/module-development-tutorial/)
- [Types de paramètres](https://manual.joomla.org/docs/general-concepts/forms-fields/standard-fields/)

## Introduction
Un module Joomla est une extension qui affiche des informations sur les pages du site. 
Les modules sont généralement utilisés pour afficher des informations sur les pages du site, telles que des menus, des articles, des formulaires de recherche, etc. Les modules sont stockés dans le dossier `modules` du site.
Les modules sont associés à des positions qui sont définis par le template utilisé.

## Créer un module
### Définition du module
Prenons l'exemple d'un module `hello`. Pour créer un module `hello`, vous devez créer un dossier `hello` dans le dossier `modules` de votre site. Ensuite, créez un fichier `mod_hello.xml` dans ce dossier afin de décrire le module et de définir des paramètres. Voici un exemple de contenu pour le fichier `mod_hello.xml` :
```xml
<?xml version="1.0" encoding="UTF-8"?>
<extension type="module" client="site" method="upgrade">
    <name>My Hello module</name>
    <version>1.0.0</version>
    <author>dev@emundus.fr</author>
    <creationDate>01/08/2024</creationDate>
    <description>A module to display a hello text</description>
    <namespace path="src">Emundus\Module\Hello</namespace>
    <files>
        <folder module="mod_hello">services</folder>
        <folder>src</folder>
    </files>
    <config>
        <fields name="params">
            <fieldset name="basic">
            <field name="message"
                   type="text"
                   default="Hello dear"
                   label="MOD_HELLO_PARAMS_TEXT_LABEL"
                   description="MOD_HELLO_PARAMS_TEXT_DESC"/>
            </fieldset>
        </fields>
    </config>
</extension>
```
Vous pouvez consulter la liste des types de paramètres disponible [ici](https://manual.joomla.org/docs/general-concepts/forms-fields/standard-fields/)

### Préparer le module
Ensuite, créez un fichier `Dispatcher.php` dans un dossier `src/Dispatcher`. Voici un exemple de contenu pour le fichier `Dispatcher.php` :
```php
<?php
namespace Emundus\Module\Hello\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_hello
 *
 * @since  4.4.0
 */
class Dispatcher extends AbstractModuleDispatcher
{

    /**
     * Check context and define data to pass to the layout
     *
     * @return  array
     *
     * @since   2.0.0
     */
    protected function getLayoutData(): array
    {
        $data   = parent::getLayoutData();
        
        // Get the module parameters
        $params = $data['params'];

        return $data;
    }
}
```

### Créer le module
Vous pouvez maintenant créer un fichier `default.php` dans un dossier `tmpl`. Celui-ci va contenir le contenu HTML du module. Voici un exemple de contenu pour le fichier `default.php` :
```php
<?php
?>
<h4><?php echo $params->get('message');?></h4>
```

### Chargement du module
Afin que Joomla sache comment charger le module, vous devez créer un dossier `services` avec un fichier nommé `provider.php`. Voici un exemple de contenu pour le fichier `provider.php` :
```php
<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The articles category module service provider.
 *
 * @since  4.4.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.4.0
     */
    public function register(Container $container)
    {
        // Register Dispatcher.php file
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Emundus\\Module\\Hello'));
        // Register the helper factory (if any) located in src/Helper/HelloHelper.php
        $container->registerServiceProvider(new HelperFactory('\\Emundus\\Module\\Hello\\Site\\Helper'));
        // Register this folder as a Joomla! module
        $container->registerServiceProvider(new Module());
    }
};
```