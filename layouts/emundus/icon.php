<?php
/**
 * Layout: emundus.icon
 *
 * Rend une icône Material Symbols Outlined.
 *
 * Par défaut l'icône est décorative (`aria-hidden="true"`). Si l'icône porte
 * du sens (ex. bouton icon-only), passer `ariaLabel` ; le layout bascule alors
 * sur `role="img"` + `aria-label` et retire `aria-hidden`.
 *
 * Données attendues dans $displayData :
 *  - name       string  Nom Material Symbols (ex. "arrow_back", "home"). Obligatoire.
 *  - class      string  (optionnel) Classes additionnelles (ex. "tw-mr-1").
 *  - ariaLabel  string  (optionnel) Étiquette accessible si l'icône est porteuse de sens.
 *  - id         string  (optionnel)
 *  - attributes array   (optionnel) paires nom => valeur (data-*, title, etc.).
 */

defined('_JEXEC') or die;

$data = $displayData ?? [];

$name      = (string) ($data['name'] ?? '');
$extraCls  = isset($data['class']) ? trim((string) $data['class']) : '';
$ariaLabel = isset($data['ariaLabel']) ? (string) $data['ariaLabel'] : '';
$id        = isset($data['id']) ? (string) $data['id'] : '';
$extraAttr = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];

if ($name === '')
{
	return;
}

$classes = 'material-symbols-outlined' . ($extraCls !== '' ? ' ' . $extraCls : '');
?>
<span
	class="<?php echo htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'); ?>"
	<?php if ($id !== ''): ?>id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
	<?php if ($ariaLabel !== ''): ?>
		role="img" aria-label="<?php echo htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8'); ?>"
	<?php else: ?>
		aria-hidden="true"
	<?php endif; ?>
	<?php foreach ($extraAttr as $attrName => $attrValue): ?>
		<?php echo htmlspecialchars((string) $attrName, ENT_QUOTES, 'UTF-8'); ?>="<?php echo htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8'); ?>"
	<?php endforeach; ?>
><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span>
