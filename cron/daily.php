<?php

require_once('library/show_authorize_email.php');
SendEmailsForAllUnauthorizedShows();

require_once('library/fetch_adc_online_booking_urls.php');
FetchAdcOnlineBookingUrls();
?>