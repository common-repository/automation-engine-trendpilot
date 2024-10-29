/**
 * Function: ajaxHandler
 * Handles all AJAX requests asynchronously using jQuery's AJAX function. It sends a request
 * to a specified URL with provided data, and executes appropriate callbacks based on the
 * success or failure of the request.
 *
 * @param {string} ajaxUrl - The URL to which the AJAX request is sent.
 * @param {string} requestType - The HTTP method for the AJAX request (e.g., 'GET', 'POST').
 * @param {Object} requestData - The data to be sent with the AJAX request.
 * @param {Function} successCallback - The callback function to be executed upon successful AJAX request.
 *                                     It receives the response object as an argument.
 * @param {Function} errorCallback - The callback function to be executed if the AJAX request encounters an error.
 *                                    It receives the error object as an argument.
 *
 * @returns {Promise<void>} A promise that resolves after the AJAX request completes.
 */
async function ajaxHandler(ajaxUrl, requestType, requestData, successCallback, errorCallback) {
    try {
        // Use jQuery's AJAX function to send the request asynchronously
        jQuery.ajax({
            url: ajaxUrl,         // URL for the AJAX request
            type: requestType,    // HTTP method (e.g., 'GET', 'POST')
            data: requestData,    // Data to send with the request
            success: function (response) {
                // If the AJAX request is successful (status 200) and response.success is true
                if (response.success) {
                    successCallback(response);  // Call the success callback with the response object
                } else {
                    successCallback(response);  // Call the success callback with the response object even if not successful
                }
            },
            error: function (error) {
                errorCallback(error);  // Call the error callback with the error object
            }
        });

    } catch (error) {
        console.error('Error in AJAX request:', error);
        throw error;  // Re-throw the error for further handling if needed
    }
}