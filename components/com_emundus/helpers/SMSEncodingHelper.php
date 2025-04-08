<?php

class SMSEncodingHelper
{
	public function isGsm7bitCompatible($text) {
		// Caractères de base GSM 03.38
		$gsm7bitBasic = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

		// Caractères étendus (utilisent 2 caractères dans l'encodage)
		$gsm7bitExtended = "^{}\[~]|€";

		$len = mb_strlen($text, 'UTF-8');
		for ($i = 0; $i < $len; $i++) {
			$char = mb_substr($text, $i, 1, 'UTF-8');
			if (!str_contains($gsm7bitBasic, $char)) {
				if (!str_contains($gsm7bitExtended, $char)) {
					return false;
				}
			}
		}
		return true;
	}

	public function findInvalidGsmChars($text): array
	{
		$invalidChars = [];
		$gsmBasicSet = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
		$gsmExtended = "\f^{}\[~]|€";

		$len = mb_strlen($text, 'UTF-8');
		for ($i = 0; $i < $len; $i++) {
			$char = mb_substr($text, $i, 1, 'UTF-8');
			if (mb_strpos($gsmBasicSet, $char, 0, 'UTF-8') === false &&
				mb_strpos($gsmExtended, $char, 0, 'UTF-8') === false) {
				$invalidChars[] = $char;
			}
		}

		return array_unique($invalidChars);
	}

	public function replaceNonGsmChars($text) {
		// Table de substitution des caractères problématiques
		$substitutionTable = [
			// Caractères étendus GSM avec substituts
			'€' => 'EUR',

			// Caractères non-GSM courants
			'à' => 'a',
			'â' => 'a',
			'ä' => 'a',
			'ç' => 'c',
			'è' => 'e',
			'é' => 'e',
			'ê' => 'e',
			'ë' => 'e',
			'î' => 'i',
			'ï' => 'i',
			'ô' => 'o',
			'ö' => 'o',
			'ù' => 'u',
			'û' => 'u',
			'ü' => 'u',
			'ÿ' => 'y',
			'À' => 'A',
			'Â' => 'A',
			'Ä' => 'A',
			'Ç' => 'C',
			'È' => 'E',
			'É' => 'E',
			'Ê' => 'E',
			'Ë' => 'E',
			'Î' => 'I',
			'Ï' => 'I',
			'Ô' => 'O',
			'Ö' => 'O',
			'Ù' => 'U',
			'Û' => 'U',
			'Ü' => 'U',
			'Ÿ' => 'Y',

			// Ponctuation et symboles spéciaux
			'«' => '"',
			'»' => '"',
			'‘' => "'",
			'’' => "'",
			'“' => '"',
			'”' => '"',
			'–' => '-',
			'—' => '-',
			'…' => '...',

			// Symboles mathématiques
			'×' => '*',
			'÷' => '/',
			'±' => '+/-',

			// Autres symboles
			'©' => '(c)',
			'®' => '(r)',
			'™' => '(tm)',
			'µ' => 'u',
			'°' => 'deg',
			'²' => '2',
			'³' => '3',
			'¼' => '1/4',
			'½' => '1/2',
			'¾' => '3/4'
		];

		// Caractères GSM de base (ne pas les remplacer)
		$gsmBasicSet = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

		$result = '';
		$len = mb_strlen($text, 'UTF-8');

		for ($i = 0; $i < $len; $i++) {
			$char = mb_substr($text, $i, 1, 'UTF-8');

			// Conserver les caractères GSM de base
			if (mb_strpos($gsmBasicSet, $char, 0, 'UTF-8') !== false) {
				$result .= $char;
				continue;
			}

			// Appliquer les substitutions
			if (isset($substitutionTable[$char])) {
				$result .= $substitutionTable[$char];
			} else {
				// Pour les caractères inconnus, supprimer ou remplacer par ?
				$result .= '?';
			}
		}

		return $result;
	}

	public function ensureSmsCompatibility($text) {
		if ($this->isGsm7bitCompatible($text)) {
			return [
				'encoding' => 'GSM-7',
				'message' => $text,
				'length' => $this->calculateGsmLength($text),
				'parts' => $this->calculateSmsParts($text, 'GSM-7')
			];
		} else {
			return [
				'encoding' => 'UCS-2',
				'message' => $text,
				'length' => mb_strlen($text, 'UTF-8'),
				'parts' => $this->calculateSmsParts($text, 'UCS-2')
			];
		}
	}

	public function calculateSmsParts($text, $encoding) {
		$length = ($encoding === 'GSM-7') ? $this->calculateGsmLength($text) : mb_strlen($text, 'UTF-8');
		$maxLength = ($encoding === 'GSM-7') ? 160 : 70;

		return ceil($length / $maxLength);
	}

	public function calculateGsmLength($text) {
		$length = 0;
		$gsmExtended = "\f^{}\[~]|€";

		$len = mb_strlen($text, 'UTF-8');
		for ($i = 0; $i < $len; $i++) {
			$char = mb_substr($text, $i, 1, 'UTF-8');
			if (mb_strpos($gsmExtended, $char, 0, 'UTF-8') !== false) {
				$length += 2; // Les caractères étendus comptent double
			} else {
				$length += 1;
			}
		}

		return $length;
	}
}