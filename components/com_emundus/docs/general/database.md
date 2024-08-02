# Base de données
## Ressources
- [Documentation officielle Joomla!](https://manual.joomla.org/docs/next/general-concepts/database/)

## Points d'attention
- Utiliser `->quoteName()` pour les noms de colonnes et de tables
- Utiliser `->quote()` pour les valeurs

## Insertion via un tableau associatif
Plutôt que de faire une requête SQL pour insérer des données, il est possible d'utiliser la méthode `insertObject()` de la classe `DatabaseDriver` pour insérer un objet dans une table. Lorsque vous utilisez cette méthode pas besoin d'indiquer `->quoteName()` ou `->quote()`.
```php
<?php
// Get a db connection.
$db = Factory::getContainer()->get('DatabaseDriver');

$campaign = [
    'label' => 'Campagne 1',
    'description' => 'Description de la campagne 1',
    'start_date' => '2024-08-02 12:00:00',
    'end_date' => '2024-09-02 12:00:00'
];
$campaign (object) $campaign;
$db->insertObject('#__emundus_campaigns', $campaign);
```

## Mise à jour via un tableau associatif
Plutôt que de faire une requête SQL pour mettre à jour des données, il est possible d'utiliser la méthode `updateObject()` de la classe `DatabaseDriver` pour mettre à jour un objet dans une table. Lorsque vous utilisez cette méthode pas besoin d'indiquer `->quoteName()` ou `->quote()`.
```php
<?php
// Get a db connection.
$db = Factory::getContainer()->get('DatabaseDriver');

$campaign = [
    'id' => 1,
    'label' => 'Le nouveau nom de ma campagne'
];
$campaign (object) $campaign;

// The last parameter is the column name to use as where condition
$db->updateObject('#__emundus_campaigns', $campaign, 'id');
```
