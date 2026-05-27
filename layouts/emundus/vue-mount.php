<?php
/**
 * Layout: emundus.vue-mount
 *
 * Rend un point de montage pour une application Vue chargée par
 * `media/com_emundus_vue/app_emundus.js`. Centralise le `<div id="…" component="…" data="…">`
 * et le `<script type="module">` afin que les ~40 vues Joomla qui montent une SPA
 * partagent la même source de vérité (path du bundle, stratégie de hash, escaping).
 *
 * Données attendues dans $displayData :
 *  - component     string  Nom du composant Vue (ex. "Campaigns", "Formbuilder"). Obligatoire.
 *  - data          array   (optionnel) Sera JSON-encodé dans l'attribut `data`.
 *                          Si la clé `hash` est présente et qu'aucun `hash` explicite n'est
 *                          fourni, elle sert au cache-busting du script.
 *  - hash          string  (optionnel) Cache-buster appliqué en query string sur le bundle.
 *  - id            string  (optionnel, défaut "em-component-vue") ID du conteneur DOM.
 *  - bundle        string  (optionnel, défaut "media/com_emundus_vue/app_emundus.js")
 *                          Chemin relatif du bundle Vue.
 *  - attributes    array   (optionnel) Attributs supplémentaires sur le `<div>` (ex.
 *                          ['prid' => 12, 'shortLang' => 'fr']). Valeurs castées en string
 *                          et échappées.
 *  - extraScripts  array   (optionnel) Liste de scripts à inclure AVANT le bundle Vue,
 *                          chaque entrée étant une string (URL) ou un tableau
 *                          ['src' => '…', 'module' => bool, 'hash' => string|null].
 */

defined('_JEXEC') or die;

$displayData = $displayData ?? [];

$component = (string) ($displayData['component'] ?? '');
if ($component === '')
{
    return;
}

$data         = is_array($displayData['data'] ?? null) ? $displayData['data'] : [];
$id           = (string) ($displayData['id'] ?? 'em-component-vue');
$bundle       = (string) ($displayData['bundle'] ?? 'media/com_emundus_vue/app_emundus.js');
$extraAttr    = is_array($displayData['attributes'] ?? null) ? $displayData['attributes'] : [];
$extraScripts = is_array($displayData['extraScripts'] ?? null) ? $displayData['extraScripts'] : [];

$hash = (string) ($displayData['hash'] ?? ($data['hash'] ?? ''));

$dataJson  = htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
$bundleSrc = $bundle . ($hash !== '' ? '?' . rawurlencode($hash) : '');
?>
<div
    id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
    component="<?php echo htmlspecialchars($component, ENT_QUOTES, 'UTF-8'); ?>"
    data="<?php echo $dataJson; ?>"
<?php foreach ($extraAttr

               as $attrName => $attrValue): ?>
<?php echo htmlspecialchars((string) $attrName, ENT_QUOTES, 'UTF-8'); ?>="<?php echo htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8'); ?>"
<?php endforeach; ?>
></div>

<?php foreach ($extraScripts as $script): ?>
    <?php
    if (is_string($script))
    {
        $scriptSrc    = $script;
        $scriptModule = false;
        $scriptHash   = $hash;
    }
    else
    {
        $scriptSrc    = (string) ($script['src'] ?? '');
        $scriptModule = !empty($script['module']);
        $scriptHash   = isset($script['hash']) ? (string) $script['hash'] : $hash;
    }
    if ($scriptSrc === '')
    {
        continue;
    }
    $scriptFullSrc = $scriptSrc . ($scriptHash !== '' ? '?' . rawurlencode($scriptHash) : '');
    ?>
    <script <?php if ($scriptModule): ?>type="module"<?php endif; ?>
            src="<?php echo htmlspecialchars($scriptFullSrc, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>

<script type="module" src="<?php echo htmlspecialchars($bundleSrc, ENT_QUOTES, 'UTF-8'); ?>"></script>
