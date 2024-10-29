/**
 * Function: customDoneTouch
 * Handles touch and click events on elements, including selecting blocks and deleting branches.
 *
 * Parameters:
 * @param {Event} event - The event object containing information about the event.
 *
 * Variables:
 * - tempblock: Temporarily stores the selected block element.
 * - rightcard: Boolean indicating if the right card is selected.
 * - selectedBlocks: A list of all elements with the 'selectedblock' class.
 * - actionType: The action type of the selected block.
 * - blockyNameElement: The HTML element containing the block's name.
 * - blockyNameText: The text content of the block's name element.
 * - branchTable: The closest parent element with the ID 'branch-table'.
 * - branchIdInput: The input element with the name 'branch-id' within the 'branch-table'.
 * - branchIdValue: The value of the 'branch-id' input element.
 * - requestPayload: Object containing data to be sent to the server for branch deletion.
 */ 
function customDoneTouch(event) {
    if (event.type === "click") {
        if (event.target.closest(".block")) {
            tempblock = event.target.closest(".block");
            rightcard = true;
            document.getElementById("properties").classList.add("expanded");
            document.getElementById("propwrap").classList.add("itson");
    
            // Remove the 'selectedblock' class from all other blocks
            var selectedBlocks = document.querySelectorAll(".selectedblock");
            selectedBlocks.forEach(function (block) {
                block.classList.remove("selectedblock");
            });
    
            tempblock.classList.add("selectedblock");
    
            // Get the action type from the selected block
            var actionType = tempblock.querySelector(".blockelemtype").value;
            var blockyNameElement = tempblock.querySelector(".blockyname");
    
            // Sanitize the blockyNameText directly before using it
            if (actionProperties[actionType]) {
                updatePropertiesPanel(actionType, DOMPurify.sanitize(blockyNameElement.textContent));
            }
        }
    }
    

    if (event.target.id === "branch-delete") {

        // Find the nearest 'branch-table' element
        var branchTable = event.target.closest("#branch-table");

        // Find the input with name 'branch-id' within the 'branch-table'
        var branchIdInput = branchTable.querySelector('input[name="branch-id"]');

        var branchIdValue = branchIdInput.value;

        const requestPayload = {
            action: 'aetp_delete_branch',
            nonce: automationEngine.nonce,
            branchId: branchIdValue 
        };

        ajaxHandler(
            automationEngine.ajax_url,
            'POST',
            requestPayload,
            handleDeleteBranchSuccess,
            handleDeleteBranchError
        );

        // Prevent further processing if it's a delete button click
        event.preventDefault();
    }
};

/**
 * Function: handleDeleteBranchSuccess
 * Handles the successful deletion of a branch.
 *
 * Parameters:
 * @param {Object} response - The response object from the server.
 *
 * Variables:
 * - response: The response object from the server containing success status and data.
 */
function handleDeleteBranchSuccess(response) {

    var deletedBranch = response.data.state_id;

    if (response.success) {
        // Find all elements with id 'branch-table'
        var branchTables = document.querySelectorAll('#branch-table');

        // Loop through each branch-table element
        branchTables.forEach(function(branchTable) {
            // Find the hidden input field with name 'branch-id'
            var branchIdInput = branchTable.querySelector('input[name="branch-id"]');
            
            // Check if the value of the hidden input matches the deletedBranch value
            if (branchIdInput && branchIdInput.value == deletedBranch) {
                // Remove the branch table from the DOM
                branchTable.remove();
            }
        });
    } else {
        console.error('Failed to delete branch:', response);
    }
}

/**
 * Function: handleDeleteBranchError
 * Handles errors that occur during the branch deletion process.
 *
 * Parameters:
 * @param {string} status - The status of the AJAX request.
 * @param {string} error - The error message from the AJAX request.
 *
 * Variables:
 * - status: The status of the AJAX request.
 * - error: The error message from the AJAX request.
 */
function handleDeleteBranchError(status, error) {
    console.error('Delete branch AJAX Error:', status, error);
}

/**
 * Function: runTemplate
 * Initiates the running of a template by sending an AJAX request to the server.
 *
 * Parameters:
 * @param {number} currentLoadedTemplateID - The ID of the currently loaded template.
 *
 * Variables:
 * - requestPayload: Object containing data to be sent to the server for running the template.
 */
function runTemplate(currentLoadedTemplateID) { 
    toggleLoadingOverlay(1);  // Show loading overlay

    const requestPayload = {
        action: 'aetp_run_button_click',
        templateID: currentLoadedTemplateID,
        nonce: automationEngine.nonce 
    };

    ajaxHandler(
        automationEngine.ajax_url,
        'POST',
        requestPayload,
        handleRunTemplateSuccess,
        handleRunTemplateError
    );
}
 
/**
 * Function: handleRunTemplateSuccess
 * Handles the successful execution of a template run.
 *
 * Parameters:
 * @param {Object} response - The response object from the server.
 *
 * Variables:
 * - response: The response object from the server containing success status and data.
 */
function handleRunTemplateSuccess(response) {
    toggleLoadingOverlay(0);  // Hide loading overlay

    if (response.success) {
        getStateById(currentLoadedTemplateID);

        // Check if `response.data` exists and has a `message` property
        const message = response.data?.message || "Workflow successfully run.";

        alert(message);
    } else {
        const errorMessage = response.data?.message || "An error occurred while running the workflow.";
    }
}


/**
 * Function: handleRunTemplateError
 * Handles errors that occur during the template run process.
 *
 * Parameters:
 * @param {string} error - The error message from the AJAX request.
 *
 * Variables:
 * - error: The error message from the AJAX request.
 */
function handleRunTemplateError(error) {
    toggleLoadingOverlay(0);  // Hide loading overlay
    console.error("Error sending request to the server in first AJAX call:", error);
}
  
/**
 * Function: resetTemplate
 * Resets the current template by sending an AJAX request to the server.
 *
 * Parameters:
 * @param {number} currentLoadedTemplateID - The ID of the currently loaded template.
 *
 * Variables:
 * - requestPayload: Object containing data to be sent to the server for resetting the template.
 */
function resetTemplate(currentLoadedTemplateID) {

    const requestPayload = {
        action: 'aetp_reset_button_clicked',
        templateID: currentLoadedTemplateID,
        nonce: automationEngine.nonce
    };

    ajaxHandler(
        automationEngine.ajax_url,
        'POST',
        requestPayload,
        handleResetTemplateSuccess,
        handleResetTemplateError
    );
}

/**
 * Function: handleResetTemplateSuccess
 * Handles the successful execution of a template reset.
 *
 * Parameters:
 * @param {Object} response - The response object from the server.
 *
 * Variables:
 * - response: The response object from the server containing success status and data.
 */
function handleResetTemplateSuccess(response) {
    if (response.success) {
        alert('Success: ' + response.data.message);

        getStateById(currentLoadedTemplateID);

        document.getElementById("run-button").style.display = "inline-block";

    } else {
        console.error('Error: ' + response.data.message);
        alert('Error: ' + response.data.message);
    }
}

/**
 * Function: handleResetTemplateError
 * Handles errors that occur during the template reset process.
 *
 * Parameters:
 * @param {string} xhr - The XMLHttpRequest object containing information about the request.
 * @param {string} status - The status of the AJAX request.
 * @param {string} error - The error message from the AJAX request.
 *
 * Variables:
 * - xhr: The XMLHttpRequest object containing information about the request.
 * - status: The status of the AJAX request.
 * - error: The error message from the AJAX request.
 */
function handleResetTemplateError(xhr, status, error) {
    console.error('Error clicking reset button:', status, error);
    alert('Error clicking reset button: ' + status + ' ' + error);
}