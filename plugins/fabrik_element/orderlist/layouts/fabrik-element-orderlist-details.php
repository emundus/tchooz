<?php

$orderedLabels = [];

$values = !empty($displayData->value) ? explode(',', $displayData->value) : $displayData->values;

foreach ($values as $value)
{
	$index = array_search($value, $displayData->values);
	if ($index !== false)
	{
		$orderedLabels[] = $displayData->labels[$index];
	}
	else
	{
		$orderedLabels[] = $value;
	}
}

?>

<ol class="fabrik-orderlist-details">
	<?php foreach ($orderedLabels as $label): ?>
		<li><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></li>
	<?php endforeach; ?>
</ol>

<input type="hidden" id="<?= $displayData->id; ?>" name="<?= $displayData->name; ?>" value="<?= htmlspecialchars($displayData->value, ENT_QUOTES, 'UTF-8'); ?>">
