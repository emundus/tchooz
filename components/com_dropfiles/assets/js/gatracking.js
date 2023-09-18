
jQuery(document).ready(function($) {
    dropfilesTrackDownload();
});

function dropfilesSendTrackingEventThenDownload(Action, Label, Url) {
    "use strict";
    var rtn = false;
    if (typeof (_gaq) !== "undefined") {
        _gaq.push(['_trackEvent', 'Dropfiles', Action, Label]);
        rtn = true;
    }

    if (typeof (ga) !== "undefined") {
        try {
            var trackers = window.ga.getAll();
            // Send event to all trackers
            trackers.forEach(function(tracker) {
                var trackerName = tracker.get('name');
                if (trackerName) {
                    ga(trackerName + '.send', 'event', 'Dropfiles', Action, Label);
                }
            });
        } catch (error) {
            console.log(error);
        }
        rtn = true;
    }

    if (typeof (gtag) !== "undefined") {
        gtag('event', Action, {
            'event_category' : 'Dropfiles',
            'event_label' : Label
        });
        rtn = true;
    }

    return rtn;
}

function dropfilesTrackDownload() {
    if(ga_download_tracking === "1") {
        jQuery(document).on('click', 'a.downloadlink', function(e) {
            var href = jQuery(this).attr('href');
            var extLink = href.replace(/^https?\:\/\//i, '');

            dropfilesSendTrackingEventThenDownload('Download', extLink, href);
        })

            //run below code when open preview on new tab
            .on('click', 'a.openlink', function(e) {
                var href = jQuery(this).attr('href');
                var extLink = href.replace(/^https?\:\/\//i, '');

                dropfilesSendTrackingEventThenDownload('Preview', extLink, href);
            });
    }
}