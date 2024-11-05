<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

?>

<?php if ($display_dashboard) : ?>

	<?php
	Text::script('COM_EMUNDUS_DASHBOARD_CAMPAIGN_PUBLISHED');
	Text::script('COM_EMUNDUS_DASHBOARD_CAMPAIGN_FROM');
	Text::script('COM_EMUNDUS_DASHBOARD_CAMPAIGN_TO');
	Text::script('COM_EMUNDUS_DASHBOARD_NO_CAMPAIGN');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES');
	Text::script('COM_EMUNDUS_DASHBOARD_FILE_NUMBER');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES_BY_STATUS');
	Text::script('COM_EMUNDUS_DASHBOARD_STATUS');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES_BY_STATUS_NUMBER');
	Text::script('COM_EMUNDUS_DASHBOARD_USERS_BY_DAY');
	Text::script('COM_EMUNDUS_DASHBOARD_USERS_NUMBER');
	Text::script('COM_EMUNDUS_DASHBOARD_USERS_REGISTER');
	Text::script('COM_EMUNDUS_DASHBOARD_USERS_DAYS');
	Text::script('COM_EMUNDUS_DASHBOARD_USERS_TOTAL');
	Text::script('COM_EMUNDUS_DASHBOARD_USERS');
	Text::script('COM_EMUNDUS_DASHBOARD_FAQ_QUESTION');
	Text::script('COM_EMUNDUS_DASHBOARD_FAQ_REDIRECT');
	Text::script('COM_EMUNDUS_DASHBOARD_SELECT_FILTER');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES_BY_STATUS');
	/* SCIENCES PO */
	Text::script('COM_EMUNDUS_DASHBOARD_KEY_FIGURES_TITLE');
	Text::script('COM_EMUNDUS_DASHBOARD_INCOMPLETE_FILES');
	Text::script('COM_EMUNDUS_DASHBOARD_REGISTERED_FILES');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES_BY_STATUS_AND_DATE');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES_BY_STATUS_AND_SESSION');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES_BY_COURSES');
	Text::script('COM_EMUNDUS_DASHBOARD_ALL_PROGRAMMES');
	Text::script('COM_EMUNDUS_DASHBOARD_FILTER_BY_PROGRAMMES');
	Text::script('COM_EMUNDUS_DASHBOARD_FILES_BY_NATIONALITIES');
	Text::script('COM_EMUNDUS_DASHBOARD_UNIVERSITY');
	Text::script('COM_EMUNDUS_DASHBOARD_PRECOLLEGE');
	Text::script('COM_EMUNDUS_DASHBOARD_1ST_SESSION');
	Text::script('COM_EMUNDUS_DASHBOARD_2ND_SESSION');
	Text::script('COM_EMUNDUS_DASHBOARD_JUNE_SESSION');
	Text::script('COM_EMUNDUS_DASHBOARD_JULY_SESSION');

	Text::script('COM_EMUNDUS_DASHBOARD_OK');

	Text::script('COM_EMUNDUS_DASHBOARD_AREA');
	Text::script('COM_EMUNDUS_DASHBOARD_EMPTY_LABEL');
	Text::script('COM_EMUNDUS_DASHBOARD_HELLO');
	Text::script('COM_EMUNDUS_DASHBOARD_WELCOME');

	Text::script('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER');
	Text::script('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER_DESC');
	Text::script('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER_CONFIRM');
	Text::script('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER_CANCEL');


	?>
    <div id="em-dashboard-vue"
         programmeFilter="<?= $programme_filter ?>"
         displayDescription="<?= $display_description ?>"
         displayShapes="<?= $display_shapes ?>"
         displayTchoozy="<?= $display_tchoozy ?>"
         name="<?= $name ?>"
         language="<?= $language ?>"
         displayName="<?= $display_name ?>"
         profile_name="<?= $profile_details->label ?>"
         profile_description="<?= $profile_details->description ?>"
    ></div>

    <script src="media/mod_emundus_dashboard_vue/app.js"></script>

    <!-- Only for messenger widget -->
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                var close = document.querySelectorAll("a.closeMessenger");
                if(close.length > 0){
                    close.forEach(function(element) {
                        element.addEventListener("click", function() {
                            var fnum = this.getAttribute("data-fnum");
                            const swalWithEmundusButtons = Swal.mixin({
                                customClass: {
                                    confirmButton: "em-swal-confirm-button",
                                    cancelButton: "em-swal-cancel-button",
                                    title: 'em-swal-title',
                                    header: 'em-flex-column',
                                    text: 'em-text-color'
                                },
                                buttonsStyling: false
                            });
                            swalWithEmundusButtons.fire({
                                title: Joomla.JText._('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER'),
                                html: '<p class="em-text-align-center">'+Joomla.JText._('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER_DESC')+'</p>',
                                icon: "info",
                                type: "warning",
                                reverseButtons: true,
                                showCancelButton: true,
                                confirmButtonText: Joomla.JText._('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER_CONFIRM'),
                                cancelButtonText: Joomla.JText._('COM_EMUNDUS_DASHBOARD_CLOSE_MESSENGER_CANCEL')
                            }).then((result) => {
                                if (result.value) {
                                    // Envoie les données via une requête POST
                                    var formData = new FormData();
                                    formData.append("fnum", fnum);
                                    fetch("index.php?option=com_emundus&controller=messenger&task=closeMessenger", {
                                        method: "POST",
                                        body: formData
                                    })
                                        .then(response => response.text())
                                        .then(data => {
                                            window.location.href = "/";
                                        })
                                        .catch(error => console.error("Erreur:", error));
                                }
                            });
                        });
                    });
                }
            },3000);
        });
    </script>

<?php endif; ?>