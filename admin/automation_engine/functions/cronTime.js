/**
 * Function: getCronTime
 * Retrieves the cron job time data via AJAX
 */
function getCronTime() {

    const requestPayload = {
        action: 'aetp_get_cron_time',
        nonce: automationEngine.nonce
    };

    ajaxHandler(
        automationEngine.ajax_url,
        'POST',
        requestPayload,
        handleGetCronTimeSuccess,
        handleGetCronTimeError
    );
}

/**
 * Function: handleGetCronTimeSuccess
 * Handles the successful response from the server when retrieving cron job time data.
 *
 * @param {Object} response - The response object containing cron job time data.
 */
function handleGetCronTimeSuccess(response) {

    // Find the <div id="cronswitch"> element and update the <strong> tags
    var cronSwitchElement = jQuery('#cronswitch');
    cronSwitchElement.find('strong').text(response.data.cron_job_time_data);
}

/**
 * Function: handleGetCronTimeError
 * Handles errors that occur during the request to retrieve cron job time data.
 *
 * @param {Object} error - The error object returned from the AJAX request.
 * @param {string|null} message - Optional error message to log.
 */
function handleGetCronTimeError(error, message = null) {
    if (message) {
        console.error("Failed to get cron job time: ", error);
    } else {
        console.error("Error sending request to the server:", error);
    }
}