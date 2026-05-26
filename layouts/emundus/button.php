<?php
/**
 * Layout: emundus.button
 *
 * Transposition du composant Vue `components/com_emundus/src/components/Atoms/Button.vue`
 * en layout Joomla. Mêmes variantes, largeurs, positions d'icône et types HTML.
 *
 * Si `href` est fourni, le layout rend un `<a>` au lieu d'un `<button>` (bouton-lien
 * stylé identiquement). Sinon, c'est un `<button>` standard.
 *
 * Données attendues dans $displayData :
 *  - variant       string  primary|secondary|link|cancel|disabled|dashed (défaut: primary)
 *  - type          string  button|submit (défaut: button) — ignoré si href fourni
 *  - width         string  fit|full (défaut: fit)
 *  - iconPosition  string  left|right (défaut: left)
 *  - disabled      bool    (défaut: false)
 *  - icon          string  Nom Material Symbols (défaut: '')
 *  - text          string  Contenu textuel ou HTML brut — parité avec le <slot> Vue ;
 *                          l'appelant est responsable de l'échappement préalable
 *  - id            string  (optionnel)
 *  - ariaLabel     string  (optionnel — recommandé pour les boutons icon-only)
 *  - onclick       string  (optionnel — JS inline)
 *  - class         string  (optionnel — classes additionnelles)
 *  - attributes    array   (optionnel — paires nom => valeur pour data-*, aria-*, name, value, etc.)
 *  - href          string  (optionnel — bascule le rendu en <a>)
 *  - target        string  (optionnel — _self|_blank|_parent|_top, appliqué uniquement sur <a>)
 *  - rel           string  (optionnel — appliqué sur <a> ; auto-renseigné à
 *                          "noopener noreferrer" si target=_blank et rel non fourni)
 */

use Joomla\CMS\Layout\LayoutHelper;
use Tchooz\Enums\UI\ButtonIconPositionEnum;
use Tchooz\Enums\UI\ButtonTypeEnum;
use Tchooz\Enums\UI\ButtonVariantEnum;
use Tchooz\Enums\UI\ButtonWidthEnum;

defined('_JEXEC') or die;

$data = $displayData ?? [];

$variant      = ButtonVariantEnum::coerce($data['variant'] ?? null, ButtonVariantEnum::PRIMARY);
$type         = ButtonTypeEnum::coerce($data['type'] ?? null, ButtonTypeEnum::BUTTON);
$width        = ButtonWidthEnum::coerce($data['width'] ?? null, ButtonWidthEnum::FIT);
$iconPosition = ButtonIconPositionEnum::coerce($data['iconPosition'] ?? null, ButtonIconPositionEnum::LEFT);

$disabled  = !empty($data['disabled']);
$icon      = (string) ($data['icon'] ?? '');
$text      = (string) ($data['text'] ?? '');
$id        = isset($data['id']) ? (string) $data['id'] : '';
$ariaLabel = isset($data['ariaLabel']) ? (string) $data['ariaLabel'] : '';
$onclick   = isset($data['onclick']) ? (string) $data['onclick'] : '';
$extraCls  = isset($data['class']) ? (string) $data['class'] : '';
$extraAttr = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];

$href   = isset($data['href']) ? (string) $data['href'] : '';
$target = isset($data['target']) ? (string) $data['target'] : '';
$rel    = isset($data['rel']) ? (string) $data['rel'] : '';
$isLink = $href !== '';

if ($isLink && $target === '_blank' && $rel === '')
{
	$rel = 'noopener noreferrer';
}

$classes = trim(
	$variant->cssClass()
	. ' ' . $width->cssClass()
	. ($extraCls !== '' ? ' ' . $extraCls : '')
	. ($isLink && $disabled ? ' em-disabled-button' : '')
);

$tag = $isLink ? 'a' : 'button';
?>
<<?php echo $tag; ?>
	class="<?php echo htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'); ?>"
	<?php if ($isLink): ?>
		<?php if (!$disabled): ?>href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
		<?php if ($target !== ''): ?>target="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
		<?php if ($rel !== ''): ?>rel="<?php echo htmlspecialchars($rel, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
		<?php if ($disabled): ?>aria-disabled="true" tabindex="-1" role="link"<?php endif; ?>
	<?php else: ?>
		type="<?php echo $type->value; ?>"
		<?php if ($disabled): ?>disabled<?php endif; ?>
	<?php endif; ?>
	<?php if ($id !== ''): ?>id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
	<?php if ($ariaLabel !== ''): ?>aria-label="<?php echo htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
	<?php if ($onclick !== ''): ?>onclick="<?php echo htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
	<?php foreach ($extraAttr as $attrName => $attrValue): ?>
		<?php echo htmlspecialchars((string) $attrName, ENT_QUOTES, 'UTF-8'); ?>="<?php echo htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8'); ?>"
	<?php endforeach; ?>
>
	<?php if ($icon !== '' && $iconPosition === ButtonIconPositionEnum::LEFT): ?>
		<?php echo LayoutHelper::render('emundus.icon', ['name' => $icon, 'class' => 'tw-mr-1']); ?>
	<?php endif; ?>
	<?php echo $text; ?>
	<?php if ($icon !== '' && $iconPosition === ButtonIconPositionEnum::RIGHT): ?>
		<?php echo LayoutHelper::render('emundus.icon', ['name' => $icon, 'class' => 'tw-ml-1']); ?>
	<?php endif; ?>
</<?php echo $tag; ?>>
