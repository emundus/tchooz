<?php
/**
 * Déclarations utilisées uniquement par l'analyse PHPStan des fragments de code eMundus.
 *
 * Ces fonctions représentent des valeurs remplacées à l'exécution. Sans elles, PHPStan voit
 * des chaînes littérales et juge tout test dessus toujours vrai ou toujours faux.
 */

/**
 * Représente une balise Fabrik du type {table___element}.
 * Fabrik y injecte une valeur à l'exécution : elle est donc inconnue au moment de l'analyse.
 *
 * @param   string  $name  Nom de la balise, sans les accolades
 *
 * @return string
 */
function fabrikPlaceholder(string $name): string
{
	return $name;
}

/**
 * Représente un tag eMundus du type [FNUM].
 * Le tag est remplacé par sa valeur à l'exécution : elle est donc inconnue au moment de l'analyse.
 *
 * @param   string  $name  Nom du tag, sans les crochets
 *
 * @return string
 */
function emundusTag(string $name): string
{
	return $name;
}
