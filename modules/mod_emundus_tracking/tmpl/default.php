<?php

use Emundus\Module\Tracking\Site\Helper\TrackingHelper;
use Joomla\CMS\Factory;

$google_tag_manager_iframe = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . $params->get('gtm_id','') . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';

$dataForEvent = [
	'email_address' => '',
	'phone_number' => '',
	'first_name' => '',
	'last_name' => '',
	'address_city' => '',
	'address_region' => '',
	'address_postal_code' => '',
	'address_country' => ''
];

$user_id = Factory::getApplication()->getIdentity()->id;
$db = Factory::getContainer()->get('DatabaseDriver');
$query = $db->createQuery();

if (!empty($user_id)) {
	$fnum = '';
	$dataForEvent = TrackingHelper::getEventDetailsFromUserId($user_id);
	$fnum_details = [];
	$dataForSubmitEvent = [];

	$emundus_user = Factory::getApplication()->getSession()->get('emundusUser');
	if (!empty($emundus_user->fnum)) {
		$fnum = $emundus_user->fnum;
		$dataForSubmitEvent = array_merge($dataForEvent, TrackingHelper::getEventDetailsFromFnum($fnum));
	}


    // check files and see if there are some that are not notified as paid
	$untracked_files = TrackingHelper::getUntrackedPaidFiles($user_id);

    if (!empty($untracked_files)) {
        ?>
            <script>document.body.insertAdjacentHTML('afterbegin', '<?php echo $google_tag_manager_iframe; ?>');</script>
		<?php
        foreach ($untracked_files as $file) {
			$order_price = TrackingHelper::getOrderPrice($file->order_id);
            $dataForPaymentEvt = array_merge($dataForEvent, TrackingHelper::getEventDetailsFromFnum($file->fnum));
			$dataForPaymentEvt['EventValue'] = $order_price;
			TrackingHelper::setFileAsTracked($file->fnum);
			?>
            <script>
                if (window.dataLayer) {
                    let event = {
                        'event': 'Candidature_Validee'
                    }
                    Object.assign(event, <?php echo json_encode($dataForPaymentEvt); ?>);

                    dataLayer.push(event);
                }
            </script>
			<?php
        }

        return;
    }
}
?>

<script>
    document.body.insertAdjacentHTML('afterbegin', '<?php echo $google_tag_manager_iframe; ?>');

    if (window.dataLayer) {
        let pageViewEvtData = {'event': 'page_view'};
        pageViewEvtData = Object.assign(pageViewEvtData, <?php echo json_encode($dataForEvent); ?>);
        dataLayer.push(pageViewEvtData);

        const applicantForm = document.querySelector('.applicant-form');

        if (applicantForm) {
            var submittedDataEvtData = {
                'event': 'Candidature_Deposee'
            };
            submittedDataEvtData = Object.assign(submittedDataEvtData, <?php echo json_encode($dataForSubmitEvent); ?>);

            document.addEventListener('click', function (e) {
                let confirmElt = document.getElementById('jos_emundus_declaration___confirm');

                if (confirmElt && e.target.classList.contains('submit')) {
                    dataLayer.push(submittedDataEvtData);
                }
            });

            document.addEventListener('submit', function (e) {
                let confirmElt = document.getElementById('jos_emundus_declaration___confirm');

                if (confirmElt) {
                    console.log('submit');
                    dataLayer.push(submittedDataEvtData);
                }
            });
        }
    }
</script>