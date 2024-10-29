# Créer une vue/page
## Ressources
- [Documentation officielle Joomla!](https://manual.joomla.org/docs/next/building-extensions/components/mvc/mvc-overview#view)

## Introduction
Une vue Joomla peut être considérée comme une page. Elle est responsable de l'affichage des données à l'utilisateur. Les vues sont généralement des fichiers PHP qui contiennent du code HTML et PHP. Les vues sont stockées dans le dossier `views` du composant.

Le dossier `views` contient un dossier pour chaque vue. Chaque dossier de vue peut contenir des fichiers par type de vue. Par exemple, si vous avez une vue `hello`, vous aurez un dossier `hello` dans le dossier `views` et un fichier `view.html.php` dans ce dossier.

## Types de vues
Il existe plusieurs types de vues dans Joomla. Les types de vues les plus courants sont les suivants :
- `view.html.php` : Type le plus courant, utilisé pour afficher une page HTML **avec les modules associés** au menu appelant cette vue.
- `view.json.php` : Utilisé pour afficher des données au format JSON.
- `view.raw.php` : Utilisé pour afficher des données brutes. Ces vues afficheront du HTML **sans les modules associés** au menu appelant cette vue.

## Créer une vue
Prenons l'exemple d'une vue `hello`. Pour créer une vue `hello`, vous devez créer un dossier `hello` dans le dossier `views` de votre composant. Ensuite, créez un fichier `view.html.php` dans ce dossier. Voici un exemple de contenu pour le fichier `view.html.php` :

```php
<?php
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * HTML Hello View class for the Emundus component
 *
 * @package    Emundus
 */
class EmundusViewHello extends HtmlView
{
    private $app;
    
    // Define variables to pass in template
    protected $hello_text;
    
    function __construct($config = array())
	{
	    require_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');
	    $this->app = Factory::getApplication();
	    
	    parent::__construct($config);
	}
	
	function display($tpl = null)
	{
	    // Define minimal access for access to this view
	    if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}
		
		$this->hello_text = 'Hello dear';
		
		parent::display();
	}
}
```

Ce fichier va permettre :
- d'initialiser des variables en fonction du contexte et de les utiliser ensuite dans la vue HTML
- de vérifier si l'appel à cette vue est valide : l'utilisateur actuel a-t-il les droits nécessaires par exemple

Maintenant, nous devons créer le fichier qui va accueillir notre contenu HTML. Pour cela il faut créer un dossier `tmpl` dans le dossier `views/hello`. Dans ce dossier nous allons créer 2 fichiers : 
- `default.php` : ce fichier va contenir le contenu HTML
- `default.xml` : ce fichier va nous permettre d'appeler notre vue depuis un lien de menu Joomla! et de décrire l'usage de celle-ci

Voici un exemple de contenu pour `default.php` :
```php
<?php
?>
<div>
    <p><?php echo $this->hello_text; ?></p>
</div>
<script type="application/javascript">
</script>
```

Voici un exemple de contenu pour `default.xml` : 
```xml
<?xml version="1.0" encoding="UTF-8"?>
<metadata>
	<layout title="COM_EMUNDUS_HELLO_VIEW_DEFAULT_TITLE">
		<message>
			<![CDATA[COM_EMUNDUS_HELLO_VIEW_DEFAULT_DESC]]>
		</message>
	</layout>
</metadata>
```

`COM_EMUNDUS_HELLO_VIEW_DEFAULT_TITLE` et `COM_EMUNDUS_HELLO_VIEW_DEFAULT_DESC` sont des balises de traductions qui doivent être ajoutés dans les fichiers de langues situés dans le dossier `administrator/components/com_emundus/language/`

## Tester ma vue
Maintenant que votre vue est prête vous pouvez [créer un lien de menu Joomla!](https://docs.joomla.org/J3.x:Adding_a_new_menu/fr) afin de la visualiser.