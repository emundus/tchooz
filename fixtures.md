# Fixtures

La commande `tchooz:fixtures` remplit une base de développement avec des données réalistes, sans passer par l'interface. Elle s'exécute dans le conteneur web :

```shell
docker exec -it [web_service_name] php cli/joomla.php tchooz:fixtures [options]
```

> À réserver aux bases locales ou de recette : la commande écrit directement en base (utilisateurs, dossiers, workflows, droits).

## Vue d'ensemble

| Commande | Rôle |
|---|---|
| `--list` | Liste les fixtures (`--entity`) et les scénarios (`--scenario`) disponibles |
| `--scenario=sampledata` | Programmes, campagnes, candidats, dossiers, pièces jointes, tags, organisations, contacts |
| `--scenario=evaluations` | Étapes d'évaluation, évaluateurs et évaluations sur les dossiers existants |
| `--entity=<nom> --count=<n>` | Une ligne validée par son repository. Aucune fixture d'entité n'est déclarée pour l'instant |

Ordre conseillé sur une base vierge : `sampledata`, puis `evaluations`.

Les scénarios sont **idempotents ou cumulatifs** : relancer complète ce qui manque au lieu de dupliquer (détails par scénario ci-dessous). En fin d'exécution, un tableau récapitule ce qui a été créé.

Toutes les données de test sont reconnaissables :
- utilisateurs en `@sample.emundus.fr` ;
- programmes procéduraux en `sample-program-<n>`, libellés `[TEST] …` ;
- workflow, étapes, formulaire et groupe d'évaluation libellés `[TEST] …`.

---

## Scénario `sampledata`

Génère le jeu de données de base : un catalogue de programmes, des candidats et leurs dossiers.

```shell
php cli/joomla.php tchooz:fixtures --scenario=sampledata --users=5000 --with-uploads --fast
```

| Option | Défaut | Description |
|---|---|---|
| `--users` | `100` | Nombre de candidats à créer (minimum 1) |
| `--files-per-user` | `1` | Nombre maximum de dossiers par candidat (tiré entre 1 et N) |
| `--programs` | `0` | Nombre total de programmes procéduraux à atteindre (`0` = programmes vitrines uniquement) |
| `--campaigns` | `0` | Nombre total de campagnes procédurales à répartir sur les programmes (`0` = campagnes vitrines uniquement) |
| `--organizations` | `0` | Nombre total d'organisations à atteindre |
| `--contacts` | `0` | Nombre total de contacts à atteindre, rattachés aux organisations |
| `--batch` | `500` | Nombre de candidats par lot |
| `--fast` | non | Désactive les événements Joomla : beaucoup plus rapide, mais les subscribers (workflow, statuts, automatisations…) ne se déclenchent pas. Chaque lot est transactionnel |
| `--with-uploads` | non | Joint un PDF d'exemple aux dossiers créés (type de pièce : paramètre `sampledata_attachment_id`, `12` par défaut) |

Ce qui est créé :
- **Programmes vitrines** (déclarés dans `ProgramFixtures`) avec leurs campagnes, rattachés au groupe « tous droits » (`all_rights_group`). Idempotent par code programme.
- **Programmes / campagnes / organisations / contacts procéduraux** : la valeur passée est un *total à atteindre*. Relancer avec la même valeur ne crée rien.
- **Candidats** (profil 1001) et **dossiers** répartis sur les campagnes publiées. Certaines campagnes sont plus remplies que d'autres, et les premiers statuts (brouillon, envoyé…) sont surreprésentés. Ces deux étapes sont **cumulatives** : chaque exécution ajoute `--users` candidats.
- **Tags** : 0 à 2 tags aléatoires par dossier.

---

## Scénario `evaluations`

Ajoute de quoi tester tout ce qui touche à l'évaluation : filtres « Évaluateurs » / « État d'évaluation », listes d'évaluations, exports.

```shell
php cli/joomla.php tchooz:fixtures --scenario=evaluations --evaluators=5 --coverage=60 --max-per-file=3
```

| Option | Défaut | Description |
|---|---|---|
| `--evaluators` | `5` | Nombre total d'évaluateurs à atteindre dans le groupe de test |
| `--coverage` | `60` | Pourcentage (0–100) des dossiers éligibles évalués, par étape |
| `--max-per-file` | `3` | Nombre maximum d'évaluateurs distincts par dossier sur l'étape multiple |

Prérequis : les programmes du scénario `sampledata` doivent exister (sinon le scénario s'arrête), ainsi que le profil évaluateur `6`.

Ce qui est créé :
- **Formulaire d'évaluation** `[TEST] Évaluation`, partagé par toutes les étapes de test. En plus des colonnes système (`ccid`, `fnum`, `step_id`, `evaluator`…), il contient trois critères :
  - `Note (sur 20)` (champ numérique) ;
  - `Commentaire` (zone de texte) ;
  - `Nationalité` (databasejoin sur `data_nationality`, liste déroulante) : c'est le critère à utiliser pour tester le décompte des valeurs de filtre, qui n'existe pas pour les champs texte ou numériques.
- **Deux étapes par workflow** couvrant les programmes de test :
  - `[TEST] Évaluation simple` : un seul évaluateur par dossier ;
  - `[TEST] Évaluation multiple` : plusieurs évaluateurs possibles.

  Les étapes sont *ajoutées* au workflow auquel le programme appartient déjà. Les autres étapes et l'association programme → workflow ne sont pas modifiées. Les programmes sans workflow sont regroupés dans un nouveau workflow `[TEST] Workflow évaluations`. Statuts d'entrée : tous sauf le brouillon (`0`).
- **Groupe** `[TEST] Évaluateurs`, rattaché aux programmes de test, avec ces droits :
  - lecture sur les dossiers (action 1) ;
  - création, lecture et modification sur l'action des étapes d'évaluation.
- **Évaluateurs** (profil 6) membres de ce groupe.
- **Évaluations** sur les dossiers publiés dont le statut est un statut d'entrée de l'étape :
  - étape simple : un évaluateur aléatoire ;
  - étape multiple : 1 à `--max-per-file` évaluateurs distincts.

  Les critères sont remplis : note entière de 0 à 20, commentaire tiré d'une liste, nationalité tirée parmi 8 nationalités choisies au hasard à chaque exécution (les compteurs se concentrent ainsi sur quelques valeurs au lieu de s'étaler à 1 sur les 200 du référentiel), et les dates d'évaluation sont étalées sur les 60 derniers jours. On obtient ainsi des dossiers évalués par X et Y, par X seul, ou par personne.

Relancer le scénario :
- réutilise le formulaire, les étapes et le groupe ;
- ajoute au formulaire les critères déclarés qui lui manquent (comparés par type de plugin) ;
- remplit les critères vides des évaluations existantes, par exemple un critère ajouté après un premier lancement ;
- crée seulement les évaluateurs manquants ;
- n'ajoute des évaluations qu'aux dossiers qui n'en ont encore aucune pour l'étape.

Le tableau final distingue les critères créés (`criteria`), les nouvelles évaluations (`evaluations`) et les évaluations existantes complétées (`evaluations filled`).

Pour augmenter la densité, relancer avec un `--coverage` plus élevé.

---

## Ajouter une fixture ou un scénario

Le code se trouve dans `components/com_emundus/classes/Fixtures/` ; la commande (`plugins/console/tchooz_cli/.../TchoozFixturesCommand.php`) ne fait que choisir et afficher.

- **Scénario** (gros volume, plusieurs entités) : implémenter `ScenarioInterface`.
  - `getScenarioName()` : nom passé à `--scenario` ;
  - `getOptions()` : options CLI, déclarées en `ScenarioOption` ;
  - `generate()` : renvoie les statistiques affichées en fin de commande.
- **Fixture d'entité** (une ligne validée par son repository) : implémenter `FixtureInterface`.
- Déclarer la classe dans `FixtureRegistry` (`SCENARIOS` ou `FIXTURES`). La commande enregistre automatiquement les options déclarées par chaque scénario.
