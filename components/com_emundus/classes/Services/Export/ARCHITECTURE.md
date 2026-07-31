# Architecture — Service d'export (`Tchooz\Services\Export`)

> Document de référence pour l'équipe. Décrit l'architecture **cible** du backend
> d'export et le plan de refactor pour y arriver. À mettre à jour à chaque phase livrée.
>
> Statut : **proposition à valider**, refactor non démarré.

---

## 1. État actuel

```
HTTP  controllers/export.php::export()  (~260 lignes)
        │  parse + sanitize + branches par format → $parameters (array plat)
        ▼
      ActionExport (automation)  ──► ExportRegistry (type → service, auto-discovery + cache)
        │                                   ▼
        │                          ExportInterface : __construct / export() / getType()
        ▼                          ┌────────────────────────────────────────┐
   sync ou Task (async)            │ ExcelService extends Export  (1983 L !)  │
                                   │   ├─ export()      ← version "next" (JSON batch)
                                   │   └─ exportOld()   ← legacy (~1000 L)    │
                                   │ ZipService (792 L) · PdfService (12 Ko)  │
                                   └────────────────────────────────────────┘
   Options : ExportOptions ◄ ExcelOptions / ZipOptions / PdfOptions  (+ fromObject)
   Sortie  : ExportResult (status, progress, filePath)
```

Les fondations sont saines : **interface + registry auto-discovery + value objects d'options
+ résultat typé**. Le problème n'est pas l'ossature, c'est l'**accumulation** à l'intérieur.

### Inventaire des fichiers

| Fichier | Rôle | Taille |
|---|---|---|
| `ExportInterface.php` | Contrat des services (`__construct`, `export()`, `getType()`) | — |
| `ExportRegistry.php` | Auto-découverte des `*/​*Service.php`, clé = `getType()`, cache | — |
| `ExportResult.php` | Résultat typé (status, progress, filePath) | — |
| `ExportOptions.php` | VO de base : format, campaign, exportVersion, elements, lang | — |
| `Export.php` | `getData()` (résolution de valeur) **+** `getXColumns()` (catalogue) | 756 L |
| `Excel/ExcelService.php` | flux « next » **+** `exportOld()` legacy | **1983 L** |
| `Excel/ExcelOptions.php` | VO (+ synthesis) | — |
| `Zip/ZipService.php` | export ZIP | 792 L |
| `Zip/ZipOptions.php` | VO (+ ~15 champs, beaucoup de legacy) | — |
| `Pdf/PdfService.php` · `PdfMerger.php` · `PdfParser.php` · `PdfOptions.php` | export PDF | — |
| `FilenameRenderer.php` | rendu de nom de fichier sûr (tags FNUM/CAMPAIGN…) | — |
| `HeadersEnum.php` | en-têtes système (FNUM, STATUS, …) | 331 L |

---

## 2. Points de douleur (classés par impact)

| # | Problème | Règle projet |
|---|---|---|
| **1** | `ExcelService` = **1983 lignes** mélangeant le flux « next » (JSON batch) ET `exportOld()` (~1000 L legacy, contrat propre `tmp_file/elts/objs/opts/method`). | R1 (nom ≠ corps), R4 (taille) |
| **2** | **Deux contrats d'entrée** cohabitent (`version=default` vs `next`). Le contrôleur a une grosse branche `if xlsx && default`. Dans le service, `$this->oldOptions` (array brut) double `$this->options` (typé) : les VO existent mais le legacy les contourne. | R3 (deux sources de vérité) |
| **3** | `Export` mélange **deux responsabilités** : `getData()` (valeur par fichier) ET les `getXColumns()` statiques (catalogue, qui sert aussi l'endpoint `elements()`). `ExcelService extends Export` juste pour `getData()`. | R1, responsabilité unique |
| **4** | Contrôleur `export()` fait du **travail de service** : shaping par format, `preg_replace` sur filename, `unlink()` du tmp. | R4 (orchestrer, pas faire) |
| **5** | `ZipOptions::fromObject` truffé d'**alias legacy** (`form_ids`/`formids`, `attach_ids`/`attachids`, `legacy_header_options`/`options`). | R3 (drift) |
| **6** | `catch (\Exception $e) { throw $e; }` dans `ExcelService::export()` — rethrow inutile. | bruit |

---

## 3. Architecture cible

Un **seul chemin de dispatch**, des **value objects purs** alimentés par une **frontière
d'entrée typée**, des services réduits à leur format.

```
┌─ FRONTIÈRE HTTP ─────────────────────────────────────────────┐
│ EmundusControllerExport::export()      orchestration (~40 L)  │
│   1. access  2. délègue  3. répond (EmundusResponse)          │
└──────────────────────────┬───────────────────────────────────┘
                           │ construit
                           ▼
   ExportRequestFactory::fromInput($input)        ◄ NOUVEAU
   parse + sanitize + validate → *Options typé (VO pur)
                           │
                           ▼
   ActionExport ──► ExportRegistry (type → service, inchangé)
                           │  chemin de dispatch UNIQUE (sync + task async)
                           ▼
   ExportInterface
     ├─ ExcelService        flux "next" uniquement      (~600 L)
     ├─ LegacyExcelService  ex-exportOld() isolé         ◄ NOUVEAU (conservé)
     ├─ ZipService
     └─ PdfService
        composent (plus d'héritage `extends Export`) :
          ElementValueResolver   ◄ ex-Export::getData()        NOUVEAU
          ExportColumnCatalog    ◄ ex-Export::getXColumns()    NOUVEAU
                           │     (source unique, sert aussi l'endpoint elements())
                           ▼
                      ExportResult (status, progress, filePath)
```

### Décisions actées

- **Legacy `exportOld()` → `Excel/LegacyExcelService`** : extrait **tel quel, sans suppression**
  (le flux `version=default` est peut-être encore déclenché par d'anciennes vues / l'API ;
  on isole par sécurité plutôt que de supprimer). `ExcelService` retombe à ~600 L et perd
  les `require_once` de modèles legacy dans `registerClasses()`. Comportement identique.
- Le choix de version (`default` → `LegacyExcelService`, `next` → `ExcelService`) se résout
  **dans le registry / `ExportRequestFactory`**, plus dans un `if` au cœur du service.
- Le chemin `contrôleur → ActionExport → ExportRegistry` reste l'**unique** point de dispatch.

### Responsabilités après refactor

| Classe | Responsabilité unique |
|---|---|
| `EmundusControllerExport::export()` | Vérifier l'accès, déléguer, formater la réponse. Rien d'autre. |
| `ExportRequestFactory` (nouveau) | `$input` → VO `*Options` typé (parse, sanitize, validate). Seul endroit qui connaît le format HTTP. |
| `ExportRegistry` | Résoudre `type` → service. Inchangé. |
| `ActionExport` | Orchestrer l'exécution (sync / task async), persister `ExportEntity`. |
| `*Service implements ExportInterface` | Produire le fichier pour **un** format. |
| `ElementValueResolver` (nouveau) | Résoudre la valeur d'un élément pour une liste de fichiers (ex-`getData()`). |
| `ExportColumnCatalog` (nouveau) | Fournir le catalogue de colonnes disponibles (ex-`getXColumns()`). Source unique, consommée aussi par l'endpoint `elements()`. |
| `*Options extends ExportOptions` | VO **pur** : porte la config d'un export, sans parsing legacy. |
| `ExportResult` | Résultat typé. Inchangé. |

---

## 4. Sections de contenu (onglets) & résumé

Les « onglets » de la modale (Corps / Synthèse / En-tête / Documents) n'existent pas
en tant qu'onglets côté backend : ce sont des **sections de contenu**, c.-à-d. des
collections nommées de références d'éléments portées par le VO `*Options`.

### 4.1 État actuel — sections dispersées et dupliquées

| Onglet (UI) | Champ backend | Porté par |
|---|---|---|
| Corps | `elements` | `ExportOptions` (base) |
| Synthèse | `synthesis` | `ExcelOptions` **et** `ZipOptions` (dupliqué ⚠️ R3) |
| En-tête | `headers` | `ZipOptions` |
| Documents | `attachments` | `ZipOptions` |

`synthesis` est redéclaré dans deux sous-types → drift garanti. Et chaque nouvelle
section impose un nouveau champ ad-hoc dans le bon sous-type.

### 4.2 Cible — une `ExportSection` unique, déclarative

On classe le contenu par **section** dans une collection unique indexée — symétrique
au store front indexé par `section`.

```php
enum ExportSection: string {
    case BODY        = 'body';        // corps
    case SYNTHESIS   = 'synthesis';
    case HEADER      = 'header';
    case ATTACHMENTS = 'attachments';
}

class ExportOptions {
    /** @var array<string, string[]>  section->value => [elementId|HeadersEnum, ...] */
    private array $sections = [];

    public function getSection(ExportSection $s): array { return $this->sections[$s->value] ?? []; }

    // Chaque format déclare les sections qu'il sait rendre — déclaratif, pas d'if éparpillé.
    public static function supportedSections(): array { return [ExportSection::BODY, ExportSection::SYNTHESIS]; }
}
```

- **Source unique** pour `synthesis` (et le reste) : fin de la duplication Excel/Zip.
- `ExcelService::supportedSections()` = `[BODY, SYNTHESIS]` ; `ZipService` / `PdfService`
  = `[BODY, SYNTHESIS, HEADER, ATTACHMENTS]`. Le front peut lire ces sections pour piloter
  ses étapes par format (symétrie front/back).
- Les contraintes (max 5 en-tête, max 10 synthèse) deviennent des métadonnées de section,
  déclarées une fois.

### 4.3 Contenu d'une section — trois rôles séparés

La section dit **quoi**, le service dit **comment**. Une même valeur résolue se rend
différemment selon la section et le format.

| Question | Qui répond |
|---|---|
| Quels éléments sont *disponibles* (arbre de gauche, endpoint `elements()`) | `ExportColumnCatalog` |
| Quelle est la *valeur* d'un élément pour chaque dossier | `ElementValueResolver` (ex-`getData()`) |
| Comment cette valeur est *rendue* selon la section | le `*Service` lui-même |

| Section | Excel | Zip / Pdf |
|---|---|---|
| `BODY` | une colonne | corps du PDF |
| `SYNTHESIS` | colonnes prépendues (`phase: 'synthesis'` puis `'elements'` dans `ExcelService::export()`) | page de couverture par dossier |
| `HEADER` | — | en-tête de page du PDF |
| `ATTACHMENTS` | — | fichiers embarqués (ou concaténés au PDF) |

Chaque service itère sur **ses** sections supportées et applique sa stratégie de rendu.
Ajouter un format ne touche jamais la définition des sections.

### 4.4 L'onglet « Résumé » / le « report »

Trois choses distinctes derrière le mot :

1. **Étape « Résumé » de l'UI** → **aucun objet backend**. Simple projection read-only des
   `*Options` déjà construites (format + sections + options). Le front a tout en mémoire :
   l'étape ne déclenche aucun appel serveur.
2. **« Nombre total de lignes générées »** → seule donnée du résumé qui exige le backend
   (le nb de lignes ≠ nb de dossiers : groupes répétés / choix multiples démultiplient les
   lignes). C'est un calcul de **preview**, à isoler **hors** du pipeline d'export :
   méthode dédiée (`ExportColumnCatalog` ou un `ExportPreview`) appelée par un endpoint
   `export/preview`. **Jamais** dans `export()`. *Réservé, non implémenté* (cf. décision
   « pas d'options pour le moment »).
3. **« Report » au sens fichier généré** (le XLSX titré *« eMundus Report »*, la page de
   synthèse du PDF) → ce n'est pas un onglet, c'est le **résultat de rendu** de la section
   `SYNTHESIS`, géré par chaque `*Service`. Aucun concept transversal nécessaire.

> En une phrase : **les onglets sont des sections de données (VO, source unique), le rendu
> est par service, et le résumé est une projection front — sauf le comptage de lignes, qui
> est un preview backend déféré.**

---

## 5. Plan d'implémentation (phasé, chaque phase testable)

| Phase | Contenu | Risque |
|---|---|---|
| **1 ✅ fait** | Isoler `exportOld()` → `Excel/LegacyExcelService`. `ExcelService` : 1983 → 816 L, `registerClasses()` allégé (modèles legacy retirés). Aucun changement de comportement. Cf. notes ci-dessous. | mini |
| **2** | Split `Export` → `ElementValueResolver` + `ExportColumnCatalog`, branchés par **composition** dans les services et l'endpoint `elements()`. Supprimer `extends Export`. | moyen |
| **3** | `ExportRequestFactory` : centraliser le parsing, amincir le contrôleur `export()` à ~40 L, purger les alias legacy des `*Options`. | moyen |
| **4** | Uniformiser les sections : `ExportSection` + collection unique dans `ExportOptions`, `supportedSections()` par service, suppression du `synthesis` dupliqué (cf. §4.2). | moyen |
| **5** | Nettoyage : rethrow inutile, `oldOptions` mort, docblocks legacy. | mini |
| **Tests** | `tests/Unit/.../Services/Export/` — `ExportRequestFactory` (input→VO) et `ElementValueResolver` en **unitaire pur** ; services en intégration. | — |

### Notes Phase 1 (livré)

- `LegacyExcelService` extrait **verbatim** d'`ExcelService` ; il `extends Export implements ExportInterface`,
  marqué `@deprecated`. `getType()` renvoie **`'excel_legacy'`** (type distinct → pas de collision
  registry avec `ExcelService` = `'excel'`).
- **Dispatch** : `ExcelService::export()` délègue tôt à `LegacyExcelService` quand
  `export_version === 'default'` (avant `registerClasses()`, donc plus aucun `require_once` de modèle
  legacy dans `ExcelService`). Le contrôleur, `ActionExport` et le registry sont **inchangés**.
- **`convertToXlsx()`** rendue `public static` sur `ExcelService` et réutilisée par `LegacyExcelService`
  (pas de duplication ; la méthode n'utilisait pas `$this`).
- Couvert par `ExcelServiceTest::testDefaultExport()` (chemin legacy délégué) et `testExport()` (next).
  À exécuter dans Docker.
- ⚠️ Le registry met `export_services` en cache : le nouveau type `'excel_legacy'` n'y apparaîtra
  qu'après vidage du cache. Sans impact en Phase 1 (la délégation est directe, pas via le registry).

---

## 6. Points d'extension (préparés, non implémentés)

Cette architecture rend les évolutions futures additives, sans toucher l'orchestrateur :

| Besoin | Geste | Impact |
|---|---|---|
| **Nouveau format** d'export | `Foo/FooService implements ExportInterface` + `Foo/FooOptions extends ExportOptions` | Registry auto-découvre. **Zéro** changement contrôleur. |
| **Nouvelle option** (ex. donnée pivot, nom des groupes en cellule, langue) | 1 champ **avec défaut** (R9) dans le `*Options` concerné + lecture dans le service + 1 mapping dans `ExportRequestFactory` | Frontière + VO ; le reste intact. |
| **Nouvelle section de contenu** | 1 cas dans `ExportSection` + l'ajouter au `supportedSections()` des formats concernés + sa stratégie de rendu dans ces services | Enum + services concernés ; la classification reste unique. |

> Les options de la nouvelle maquette d'export (pivot, nom des groupes en cellule, nombre
> de lignes…) viendront se brancher ici : `ExportRequestFactory` + les `*Options` sont les
> points d'extension dormants prévus à cet effet.

---

## Références

- Conventions backend : `.claude/skills/tchooz-developer/` (R1–R5, anti-patterns).
- Frontend de l'export : `components/com_emundus/src/views/Exports/Exports.vue`.
- Contrôleur : `components/com_emundus/controllers/export.php`.
- Automation : `classes/Entities/Automation/Actions/ActionExport.php`.
