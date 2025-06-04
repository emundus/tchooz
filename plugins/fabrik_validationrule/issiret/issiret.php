<?php
/**
 * Is siret  Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.issiret
 * @copyright   Copyright (C) 2005-2017  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin classes
require_once COM_FABRIK_FRONTEND . '/models/validation_rule.php';

/**
 * Is SIRET Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.issiret
 * @since       3.0
 */
class PlgFabrik_ValidationruleIssiret extends PlgFabrik_Validationrule
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'issiret';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		// Could be a drop-down with multi-values
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params = $this->getParams();
		$allow_empty = $params->get('issiret-allow_empty');

		if ($allow_empty == '1' && empty($data))
		{
			return true;
		}
		elseif (empty($data))
		{
			return false;
		}

		$str = preg_replace('/\s+/', '', $data);

		if (empty($str) || preg_match('/^\d{14}$/', $str))
		{
			$sum = 0;

			for ($i = 0; $i < strlen($str); $i++)
			{
				$digit = (int) $str[$i];

				if ($i % 2 === 0)
				{
					$digit *= 2;
					if ($digit > 9)
					{
						$digit -= 9;
					}
				}

				$sum += $digit;
			}

			if ($sum % 10 === 0)
			{
				return true;
			}
			else
			{
				$this->errorMsg = Text::_('PLG_FABRIK_VALIDATIONRULE_ISSIRET_ERROR_MSG');
				return false;
			}
		}
		else
		{
			$this->errorMsg = Text::_('PLG_FABRIK_VALIDATIONRULE_ISSIRET_ERROR_MSG');
			return false;
		}
	}
}
