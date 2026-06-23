<?php
/**
 * @package
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

defined('_JEXEC') or die('Restricted Access');

use Tchooz\Factories\LayoutFactory;

$data = LayoutFactory::prepareVueData();
$data['fnum'] = $this->fnum;



?>

<div id="em-update-associated-organizations"
     component="Contacts/UpdateAssociatedOrganizations"
     data="<?php echo htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?= $data['hash'] . uniqid() ?>"></script>
