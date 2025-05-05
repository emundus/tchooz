<?php
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em_filters"
     component="Filters"
     data-module-id="<?= $this->itemId ?>"
     data-menu-id="<?= $this->itemId ?>"
     data-applied-filters='<?= base64_encode(json_encode($this->applied_filters)) ?>'
     data-filters='<?= base64_encode(json_encode($this->filters)) ?>'
     data-quick-search-filters='<?= base64_encode(json_encode($this->quick_search_filters)) ?>'
     data-count-filter-values='<?= $this->count_filter_values ?>'
     data-allow-add-filter='<?= $this->allow_add_filter ?>'
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>