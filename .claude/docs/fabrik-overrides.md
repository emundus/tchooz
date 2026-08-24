# Surcharges Tchooz sur Fabrik — à réappliquer après chaque mise à jour

Fabrik est du code vendor modifié en place. Chaque montée de version l'écrase et supprime nos
surcharges **silencieusement** : rien ne casse au build, ça casse en production.

## Procédure après une maj de com_fabrik

```bash
.claude/scripts/check-fabrik-overrides.sh   # exit 1 s'il manque quelque chose
```

Réappliquer ce qui manque depuis ce document, puis relancer le script jusqu'à exit 0.

**Attention aux merges autant qu'aux majs.** Le second passage de 4.7.1 est arrivé par un
`Merge hotfix into master` (`8627d5804a`, parent unique) qui a re-déposé les 258 fichiers vendor
et re-supprimé les surcharges. Le merge master → hotfix a ensuite propagé la perte. Lancer le
script après tout merge qui touche `com_fabrik`, pas seulement après un `tchooz:update`.

**Ne pas se fier au `git blame`** pour retrouver une surcharge : elles sont parfois réappliquées
*à l'intérieur* d'un commit de maj vendor (les classes Tailwind du filtre date étaient attribuées
à `ce09df1dc3 security: Update to Fabrik 4.6.2`). Ce document est la source de vérité.

---

## 1. Gardes CLI — 6 emplacements

**Cause commune** : `ConsoleApplication extends Application implements CMSApplicationInterface` — il
**n'étend pas** `CMSApplication`. Toute méthode propre au web y est absente, et Fabrik les appelle
sans garde. En CLI (`tchooz:update`, jobs de migration) ça donne un
`Error: Call to undefined method ConsoleApplication::<méthode>()`.

| Méthode appelée | Définie sur |
|---|---|
| `getTemplate()` | `CMSApplication`, `SiteApplication`, `AdministratorApplication` |
| `getMenu()` | `CMSApplication` |
| `getParams()` | **`SiteApplication` uniquement** |

### 1a. `templateOverride()` — 1 garde

**Fichier** `administrator/components/com_fabrik/com_fabrik.manifest.class.php` (~ligne 379)
**Origine** `96485afdd5 fix: Fix Fabrik update via CLI`
**Sans elle** `tchooz:update` échoue sur l'étape com_fabrik.

`postflight('update')` → `templateOverride()` → `Factory::getApplication()->getTemplate()`.

Première ligne du corps de `protected function templateOverride($install = true)` :

```php
if (Factory::getApplication()->isClient('cli'))
{
    /* We are running from the command line, so we don't need to do anything */
    return true;
}
```

### 1b. `Helpers/Worker.php` — 3 gardes

**Fichier** `libraries/fabrik/fabrik/fabrik/Helpers/Worker.php` (~lignes 2730, 2805, 2837)
**Origine** `f29c2a5ed9 fix: check if !isCli in Fabrik Worker when launched from migration job #1626`
**Sans elles** crash sur `$app->getMenu()` dès qu'un job de migration passe par Fabrik.

Les trois blocs protègent un `$app->getMenu()` : `itemId()` (~2730) et deux branches de
`getMenuOrRequestVar()` (~2805 dans `if ($priority === 'menu')`, ~2837 dans le `else`).
L'amont écrit `if (!$app->isClient('administrator'))`. Ajouter `&& !$app->isCli()` aux **trois** :

```php
if (!$app->isClient('administrator') && !$app->isCli())
```

### 1c. `populateState()` de list et form — 2 gardes

**Fichiers** `components/com_fabrik/models/list.php` (~ligne 11232),
`components/com_fabrik/models/form.php` (~ligne 5071)
**Origine** `a3a4d49f8c feat: Add a job to migrate old workflows to the new workflow builder system #66`
**Sans elles** crash sur `$this->app->getParams()`, qui n'existe **que** sur `SiteApplication`.

Dans `protected function populateState()`, la garde autour du bloc
« Load the menu item / component parameters » :

```php
if (!$this->app->isClient('administrator') && !$this->app->isCli())
```

> **Ne pas confondre.** `list.php` contient **4** gardes `isCli()` : celle de `populateState()`
> (~11232) est la nôtre, les trois autres (~809, ~4573, ~9420) viennent de l'amont 4.7.1 — confirmé
> par deux dépôts vendor indépendants (`d58de9d934` et `8627d5804a`) qui les produisent tous les
> deux. Le script les ignore et ne cible que `populateState()`.

---

## 2. Garde `is_array()` avant `count($validations->plugin)`

**Fichier** `administrator/components/com_fabrik/models/elements.php` (~ligne 239)
**Origine** `1b1800c6b6 fix: Check if validations->plugin is an array`
**Sans elle** `count()` sur un non-array → `TypeError` PHP 8 dans la liste des éléments en admin.

```php
if (is_object($validations) && property_exists($validations,'plugin') && is_array($validations->plugin))
```

L'amont écrit la même ligne sans `&& is_array($validations->plugin)`.

---

## 3. `getFilterForm()` — filtre « groupe » scopé au formulaire

**Fichier** `administrator/components/com_fabrik/models/elements.php` (fin de classe, ~ligne 446)
**Origine** `1f52daac3c`
**Sans elle** le filtre « groupe » de la liste des éléments liste *tous* les groupes de la plateforme.

Va de pair avec la surcharge 4 : `getFilterForm()` pose le `sql_where`, `grouplist` le consomme.
Restaurer les deux ou aucune — l'une sans l'autre est du code mort.

```php
/**
 * Get the filter form
 *
 * Scopes the "group" filter to the groups of the currently filtered form, by feeding the
 * grouplist field the sql_where it reads in JFormFieldGroupList::getOptions().
 *
 * @param   array    $data      data
 * @param   boolean  $loadData  load current data
 *
 * @return  \Joomla\CMS\Form\Form|boolean The Form object or false on error
 *
 * @since   4.0.0
 */
public function getFilterForm($data = [], $loadData = true)
{
    $form = parent::getFilterForm($data, $loadData);

    $id = (int) $this->getState('filter.form');

    if ($form && $id) {
        $where = $this->getDatabase()->quoteName('fg.form_id') . ' = ' . $id;

        $form->setFieldAttribute('group', 'sql_where', $where, 'filter');
    }

    return $form;
}
```

---

## 4. Support de `sql_where` sur le champ `grouplist`

**Fichier** `administrator/components/com_fabrik/models/fields/grouplist.php` (~lignes 56 et 65)
**Consommateur** surcharge 3.

Après `$query = $db->getQuery(true);` :

```php
$sql_where   = (string) $this->element['sql_where'];
```

Après `$query->order('f.label, g.name');` et avant `// Get the options.` :

```php
if(!empty($sql_where)) {
    $query->where($sql_where);
}
```

---

## 5. Chargement de `custom_list_<tmpl>.js`

**Fichier** `components/com_fabrik/models/list.php`, `getCustomJsAction()` (~ligne 11989)
**Origine** `7c7d62d3b2`
**Sans elle** `components/com_fabrik/js/custom_list_emundus_card.js` existe mais **plus rien ne le charge**.

Branche `elseif` à ajouter après le `if` sur `list_<id>.js` :

```php
elseif (File::exists(COM_FABRIK_FRONTEND . '/js/custom_list_'.$this->getTmpl().'.js'))
{
    $scripts[$scriptKey] = 'components/com_fabrik/js/custom_list_'.$this->getTmpl().'.js';
}
```

---

## 6. Layout `addoptions` — listener icône add/close

**Fichier** `components/com_fabrik/layouts/element/fabrik-element-addoptions.php` (fin de fichier)
**Origine** `197eb2d119`
**Sans elle** l'icône ne bascule plus, **et** le fichier se termine sur un `<script>` non fermé
(l'amont tronque le bloc au milieu).

Fin de fichier, après `let iconButton = ...` :

```js
    addButton.addEventListener('click', function(event) {
        if (iconButton.textContent.trim() === 'add') {
            iconButton.textContent = 'close';
        } else {
            iconButton.textContent = 'add';
        }
    });
</script>
```

---

## 7. Filtre date « range » — markup Tailwind

**Fichier** `plugins/fabrik_element/jdate/layouts/fabrik-element-jdate-list-filter-range.php` (~ligne 25)
**Origine** `cc267274a3` / `3555710114` / `17cfa48769`
**Sans elle** retour au markup Bootstrap (`col-2 text-end`, `w-auto`), filtre déformé.

Le bloc de la branche `else :` doit utiliser :

```php
<div class="fabrikDateListFilterRange tw-flex tw-flex-col tw-gap-2" >
    <div class="row">
        <div class="tw-w-1/4 tw-p-0 tw-flex tw-items-center">
            <label for="<?php echo $from->id; ?>"><?php echo Text::_('COM_FABRIK_DATE_RANGE_BETWEEN') . ' '; ?>
            </label></div>
        <div class="tw-w-3/4 tw-p-0"><?php echo $d->jCalFrom; ?></div>
    </div>
    <div class="row">
        <div class="tw-w-1/4 tw-p-0 tw-flex tw-items-center">
            <label for="<?php echo $to->id; ?>">	<?php echo Text::_('COM_FABRIK_DATE_RANGE_AND') . ' '; ?>
            </label></div>
        <div class="tw-w-3/4 tw-p-0"><?php echo $d->jCalTo; ?></div>
    </div>
</div>
```

---

## 8. Checkbox / radio dans les groupes répétables

**Fichiers** `plugins/fabrik_element/checkbox/checkbox.js` + `checkbox-min.js`,
`plugins/fabrik_element/radiobutton/radiobutton.js` + `radiobutton-min.js`
**Symptôme sans la surcharge** dans un groupe repeat, cliquer sur le **libellé** du 2ᵉ groupe
coche l'option du **1ᵉʳ** groupe.

`components/com_fabrik/layouts/fabrik-grid-item.php` rend le label comme **frère** de l'input,
pas comme parent :

```html
<input type="checkbox" id="tbl___elem_1__0_input_0"><label for="tbl___elem_1__0_input_0">…</label>
```

Donc `sub.getParent('label')` renvoie `null`, le `if (label)` court-circuite, l'`id` de l'input du
groupe cloné est renommé mais le `for=` du label reste sur celui du groupe 0.

Remplacer **chaque** `getParent('label')` de `cloned()` et `setName()` par :

```js
var label = sub.getParent('label') || sub.getNext('label');
```

Emplacements : `checkbox.js` → `cloned()` (1). `radiobutton.js` → `cloned()` et `setName()` (2).
Ne pas toucher au `getParent('label')` de `btnGroup()` : il a déjà son propre fallback `getNext()`.

**Ne pas oublier les `-min.js`** : c'est eux qui sont chargés hors debug. Édition à la main
(pas de pipeline Vite dessus), en respectant les noms de variables du minifieur en place — ils
changent à chaque release, d'où le `chk_re` du script de contrôle.

Depuis 4.7.1 l'amont a **adopté** ce fallback dans `cloned()` (les deux éléments), mais **pas**
dans `setName()` de radiobutton. Vérifier les quatre fichiers à chaque fois.

---

## 9. Databasejoin sans `join_conn_id` (#1793)

**Fichier** `plugins/fabrik_element/databasejoin/databasejoin.php` (~ligne 159)
**Origine** `ebaf5f041c`
**Sans elle** un élément databasejoin **sans `join_conn_id`** ne résout plus sa jointure.

L'amont écrit `if ($params->get('join_conn_id') == $connection->get('id') || $element->plugin != 'databasejoin')`.
Remplacer par :

```php
$isSameConnection = $params->get('join_conn_id') == $connection->get('id');
if(!$isSameConnection && $element->plugin == 'databasejoin' && empty($params->get('join_conn_id')))
{
    $isSameConnection = true;
}

if ($isSameConnection || $element->plugin != 'databasejoin')
```

---

## 10. Databasejoin `form-final` — `isset()` et non `!empty()` (#1271)

**Fichier** `plugins/fabrik_element/databasejoin/layouts/fabrik-element-databasejoin-form-final.php` (ligne 6)
**Origine** `91e959f6f6 fix: Display 0 labels in databasejoin elements #1271`
**Sans elle** un contrôle valant littéralement `"0"` n'est plus affiché.

```php
if (isset($d->control)) :
```

L'amont écrit `if (!empty($d->control)) :`.

---

## Changements amont à NE PAS réverter

- **`Helpers/Worker.php`** — nos garde-fous anti-RCE (blocage de `$_POST`/`$_GET`, `preg_replace`
  des mots dangereux, détection de fonctions) ont été retirés par 4.7.1 mais **remplacés par mieux** :
  `forEval` / `forSql` avec détection du contexte de quote via le tokenizer PHP
  (`tokenizeSourceForQuoteContext`, `quoteContextAt`). Les commentaires amont mentionnent un
  « residual RCE report » sur 4.6.9. Remettre les anciens filtres serait redondant et casserait des
  formules calc légitimes. En revanche, revalider les formules calc et les préfiltres après maj :
  les valeurs sont désormais quotées au lieu d'être filtrées.
- **Gardes `!$this->app->isCli()` de `list.php` lignes ~809, ~4573, ~9420** : celles-là viennent
  bien de l'amont 4.7.1, à conserver sans rien faire. Ne pas les confondre avec les nôtres — voir 1c.
- **`Html::validateRequest()`** ajoutée dans les contrôleurs `com_fabrik` : la méthode commence par
  `{ return;` en amont, donc tous ces appels sont des no-op. Rien à faire.

## Points de vigilance connus

- **`plugins/fabrik_element/jdate/jdate.php`** (~ligne 326) : l'amont code en dur
  `$calOpts['weekNumbers'] = true;` et commente le paramètre. Les numéros de semaine s'affichent
  donc **toujours** dans les datepickers, `jdate_show_week_numbers` est inopérant. Contournement
  amont assumé d'un bug J!4.4.0 sur le time picker 24h — laissé tel quel.

## Historique des pertes

| Maj | Surcharges écrasées |
|---|---|
| 4.6.7 (`1ef886291d`) | 8 (checkbox/radio), 9, 10, `getFilterForm()`, + celles restaurées par `c887209fc2` / `197eb2d119` |
| 4.7.1 1ᵉʳ passage (`d58de9d934`) | 1, 2, 4, 5, 6, 7, `setName()` de 8 |
| 4.7.1 2ᵉ passage (`8627d5804a`, via merge) | les mêmes que ci-dessus |

Les gardes CLI 1b et 1c ont survécu aux deux passages de 4.7.1 (uniquement ré-indentées pour 1b),
mais elles restent des surcharges : rien ne garantit que la prochaine version les porte. Les
vérifier systématiquement — c'est le genre de perte qui ne se voit qu'au prochain `tchooz:update`
ou job de migration, donc longtemps après la maj.
