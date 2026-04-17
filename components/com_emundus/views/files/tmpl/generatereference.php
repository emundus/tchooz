<?php
/**
 * @package
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

$data = LayoutFactory::prepareVueData();

?>

<div id="em-generete-reference"
     component="Reference/GenerateReference"
     data="<?php echo htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>
