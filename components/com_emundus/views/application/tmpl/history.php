<?php
use Joomla\CMS\Language\Text;

$default_tab = 'history';
$jinput = JFactory::getApplication()->input;
$input_tab = $jinput->getString('tab', '');
if (!in_array($input_tab, $this->tabs)) {
    $input_tab = $default_tab;
}

$icons = [
    'history' => 'history',
    'forms' => 'content_paste',
    'attachments' => 'description',
    'comments' => 'comment'
];

?>

<div class="tw-flex tw-items-center tw-border-b-1 tw-border-neutral-300">
    <?php foreach ($this->tabs as $key => $tab) : ?>
        <div class="tw-py-4 tw-px-5 tw-border-b tw-flex tw-gap-2 <?php if ($key == 0) : ?>tw-border-main-500<?php else : ?>tw-border-neutral-400<?php endif; ?> tw-cursor-pointer"
             id="tab_<?php echo $tab; ?>"
             onclick="selectTab('<?php echo $tab; ?>')">
            <span class="material-symbols-outlined -tw-mb-2 tw-text-neutral-900">
                <?php echo $icons[$tab] ?? 'help'; ?>
            </span>
            <span class="em-font-size-14"><?php echo Text::_('COM_EMUNDUS_APPLICATION_HISTORY_TAB_' . strtoupper($tab)); ?></span>
        </div>
    <?php endforeach; ?>
</div>

<div id="history">
</div>

<div id="application" class="tw-mt-6">
</div>

<div id="attachments" class="tw-mt-6">
</div>

<div id="comments" class="tw-mt-6">
</div>
<script>
    //domready
    document.addEventListener("DOMContentLoaded", function (event) {
        const tab = '<?= $input_tab; ?>';

        // If tab is history, load history data, tab is already selected but data is not loaded
        if (tab === 'history') {
            displayHistory();
        } else {
            selectTab(tab);
        }
    });

    function emptyElements(elements = ['application', 'attachments', 'history', 'comments']) {
        elements.forEach((elementId) => {
            const foundElement = document.getElementById(elementId);

            if (foundElement) {
                foundElement.innerHTML = '';
            }
        });
    }

    function displayHistory() {
        toggleLoader();
        fetch('/index.php?option=com_emundus&view=application&layout=logs&format=raw&fnum=<?php echo $this->fnum ?>&ccid=<?php echo $this->ccid ?>', {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.text();
            }
        }).then((res) => {
            emptyElements();
            document.getElementById('history').innerHTML = res;

            toggleLoader();
        });
    }

    function displayApplication() {
        toggleLoader();
        fetch('/index.php?option=com_emundus&view=application&layout=form&format=raw&fnum=<?php echo $this->fnum ?>&ccid=<?php echo $this->ccid ?>', {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.text();
            }
        }).then((res) => {
            emptyElements();
            document.getElementById('application').innerHTML = res;
            toggleLoader();
        });
    }

    function displayAttachments() {
        toggleLoader();
        fetch('/index.php?option=com_emundus&view=application&layout=attachment&format=raw&fnum=<?php echo $this->fnum ?>&ccid=<?php echo $this->ccid ?>', {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.text();
            }
        }).then((res) => {
            emptyElements();

            // Use jQuery is required to load javascript present in the html
            jQuery('#attachments').append(res);
            toggleLoader();
        });
    }

    function displayComments() {
        toggleLoader();

        fetch('/index.php?option=com_emundus&view=application&layout=comment&format=raw&fnum=<?php echo $this->fnum ?>&ccid=<?php echo $this->ccid ?>', {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.text();
            }
        }).then((res) => {
            emptyElements();
            document.getElementById('comments').innerHTML = res;

            // Use jQuery is required to load javascript present in the html
            jQuery('#comments').append(res);
            toggleLoader();
        });
    }

    function selectTab(tab) {
        let selected_tab = document.getElementById('tab_' + tab);
        let old_tab = document.getElementsByClassName('tw-border-main-500');

        if (selected_tab && selected_tab.classList.contains('tw-border-main-500')) {
            return;
        }

        if (old_tab && old_tab.length > 0) {
            old_tab[0].classList.add('tw-border-neutral-400');
            old_tab[0].classList.remove('tw-border-main-500');
        }

        if (selected_tab) {
            selected_tab.classList.remove('tw-border-neutral-400');
            selected_tab.classList.add('tw-border-main-500');
        }

        switch (tab) {
            case 'forms':
                displayApplication();
                break;
            case 'history':
                displayHistory();
                break;
            case 'attachments':
                displayAttachments();
                break;
            case 'comments':
                displayComments();
                break;
        }
    }

    function toggleLoader() {
        let loader = document.querySelector('.em-page-loader');

        if (loader.classList.contains('hidden') || loader.style.display === 'none') {
            loader.classList.remove('hidden');
            loader.style.display = 'block';
        } else {
            loader.style.removeProperty('display');
            loader.classList.add('hidden');
        }
    }
</script>