
/**
 * Function: loadWorkflowContent()
 * This function handles Api call to load the selected workflow content.
 *
 * Parameters:
 * @param {HTMLElement} item - The HTML element representing the item to be loaded.
 *
 * Variables:
 * - listItemNumValue: The value representing the ID of the selected template item.
 * - currentLoadedTemplateID: A global variable updated with the current template ID.
 * - requestPayload: An object containing the data required for an AJAX request to fetch workflow status.
 */
function loadWorkflowContent(item) {

    if (item.querySelector(".blockelemtype").value == "templateItem") {
        const listItemNumValue = item.querySelector(".listItemNum").value;

        // Update the global variable
        currentLoadedTemplateID = listItemNumValue;
        updateButtonVisibility();

        // show run button initially. if state is completed it will hide later
        document.getElementById("run-button").style.display = "inline-block";

        // Remove the active-template class from all block-template-item elements
        document.querySelectorAll(".block-template-item.active-template").forEach((activeItem) => {
            activeItem.classList.remove("active-template");
        });

        // Add the active-template class to the currently clicked item
        item.classList.add("active-template");

        // Clear blocks
        flowy.deleteBlocks();
        importTemplateJsonFromListItem(listItemNumValue);

        const requestPayload = {
            action: 'aetp_get_workflow_status',
            templateID: currentLoadedTemplateID,
            nonce: automationEngine.nonce // Add the nonce value to the data
        }

        ajaxHandler( 
            automationEngine.ajax_url,
            'POST',
            requestPayload,
            handleLoadWorkflowContentSuccess,
            handleLoadWorkflowContentError
        );

        // Make another call to get the state on loading from left
        getStateById(currentLoadedTemplateID);

    }
}

/**
 * Function: handleLoadWorkflowContentSuccess
 * This function processes the successful response from the AJAX request that fetches the workflow status and make changes in UI.
 *
 * Parameters:
 * @param {Object} response - The response object returned from the server.
 *
 * Variables:
 * - publishElement: The HTML element representing the publish button.
 * - repeatCheckbox: The HTML element representing the repeat checkbox.
 * - hiddenInput: The hidden input element for storing the status.
 * - status: The workflow status retrieved from the response.
 * - checkboxValue: The value indicating whether the workflow is set to repeat.
 */
function handleLoadWorkflowContentSuccess(response) { 
    if (response.success) {
        const publishElement = document.getElementById("publish");
        const repeatCheckbox = document.getElementById("repeat-checkbox");

        if (publishElement && repeatCheckbox) {
            // Create or update the hidden input element
            let hiddenInput = publishElement.querySelector('input[type="hidden"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                publishElement.appendChild(hiddenInput);
            }

            // Sanitize the status and checkboxValue since they come from server response
            const status = DOMPurify.sanitize(response.data.status.trim());
            const checkboxValue = DOMPurify.sanitize(response.data.is_repeat.trim());

            // Update the checkbox value and checked state
            repeatCheckbox.value = checkboxValue;
            repeatCheckbox.checked = checkboxValue === "1";

            // Update the publishElement based on the status
            if (status === "active") {
                publishElement.style.backgroundColor = "red";
                publishElement.childNodes[0].nodeValue = "Deactivate Workflow";
                hiddenInput.value = "active"; // No need to sanitize hardcoded value
            } else if (status === "inactive") {
                publishElement.style.backgroundColor = "#5961F3";
                publishElement.childNodes[0].nodeValue = "Activate Workflow"; 
                hiddenInput.value = "inactive"; // No need to sanitize hardcoded value
            }
        }

        // Make additional call to get state by ID
        getStateById(currentLoadedTemplateID);
 
    } else {
        console.error("Error from the server:", response.data.message);
        console.error("Response details:", response.data);
    }
}


/**
 * Function: handleLoadWorkflowContentError
 * This function handles errors that occur during the AJAX request to load workflow content and log the error.
 *
 * Parameters:
 * @param {Object} error - The error object returned when the AJAX request fails.
 */
function handleLoadWorkflowContentError(error) {
    console.error("Error sending request to the server:", error);
}

/**
 * Function: importTemplateJsonFromListItem
 * This function imports the JSON data of a selected template into the workflow editor.
 *
 * Parameters:
 * @param {string} listItemNumValue - The identifier of the selected template item.
 *
 * Variables:
 * - matchedTemplate: The template object from templateList that matches the provided listItemNumValue.
 * - json: The parsed JSON data of the matched template.
 */
function importTemplateJsonFromListItem(listItemNumValue) {

    // Find the template in the templateList that matches the listItemNum
    let matchedTemplate = templateList.find(
        (template) => template.listItem === listItemNumValue
    );

    if (matchedTemplate) {
        try {

            let json = JSON.parse(matchedTemplate.json);

            // Assuming matchedTemplate.json contains the stringified JSON value you provided:
            flowy.import(json);

            updateWorkflowTitle();

        } catch (error) {
            console.error('Error parsing JSON or importing into flowy:', error);
        }
    } else {
        console.warn('No matched template found for listItemNumValue:', listItemNumValue);
    }
}

/**
 * Function: handleSaveTemplateError
 * Handles errors that occur during the AJAX request to save the template representation.
 *
 * Parameters:
 * @param {Object} xhr - The XMLHttpRequest object containing the error details.
 * @param {string} error - The error message or object.
 */
function handleSaveTemplateError(xhr, error) {
    console.error('AJAX Error:'); // Log detailed error
    alert('Error updating template representation: ' + xhr.responseText); // Show error to user
}

/**
 * Function: handleSaveWorkflowResponse
 * Handles the response after saving the workflow, updating the current loaded template ID and template list.
 *
 * Parameters:
 * @param {Object} workflow - The workflow object containing the updated workflow steps.
 * @param {string} name - The name of the workflow.
 * @param {string} unique_id - The unique identifier of the workflow template.
 *
 * Variables:
 * - templateJson: The JSON string representation of the workflow.
 * - repeatCheckbox: The HTML element representing the repeat checkbox.
 * - existingTemplate: The existing template in the templateList with the same unique_id.
 */
function handleSaveWorkflowResponse(workflow, name, unique_id) {

    alert("Workflow saved");

    // Set the current loaded template ID
    currentLoadedTemplateID = unique_id;

    // The second part is the JSON string
    let templateJson = workflow;

    const repeatCheckbox = document.getElementById("repeat-checkbox");

    // Check if the templateList already contains an item with the same unique_id
    let existingTemplate = templateList.find(template => template.listItem === unique_id);

    if (existingTemplate) {
        // Update the existing template
        //existingTemplate.name = name;
        existingTemplate.json = JSON.stringify(templateJson);
    } else {
        // Add the new workflow to the templateList
        templateList.push({
            name: name,
            json: JSON.stringify(templateJson), // Convert the workflow object to a JSON string
            listItem: unique_id,
        });
    }

    // Update the button visibility and workflow title
    updateButtonVisibility();
    updateWorkflowTitle();
}

/**
 * Function: saveWorkflowTemplate
 * Saves the current workflow template by sending it to the server.
 * 
 * Variables:
 * - requestPayload: The payload containing the action, current workflow JSON, workflow name, and security nonce.
 */
function saveWorkflowTemplate(currentJsonWorkflow, workflowJSON, workflowName, saveAsNew = 0 ) {

    // Prepare the JSON data for steps
    const finalWorkflowJSON = `{"steps":${workflowJSON}}`;

    const repeatCheckboxValue = document.getElementById("repeat-checkbox").value;

    // Fetch the publish element and its hidden input
    const publishElement = document.getElementById("publish");
    const hiddenInput = publishElement.querySelector('input[type="hidden"]');
    let statusValue = "inactive"; // Default status to inactive

    // Set status based on hidden input value
    if (hiddenInput && hiddenInput.value === "active") {
        statusValue = "active";
    }
 
    // Prepare the data payload
    const requestPayload = { 
        action: 'aetp_save_template', // Custom action name for the PHP handler
        currentJsonWorkflow: currentJsonWorkflow, // The visual editor's workflow JSON
        workflowName: workflowName, // Name of the workflow
        nonce: automationEngine.nonce, // Add nonce for security

        // Additional data to create entry in trendpilot_automation_engine_workflows
        finalWorkflowJSON: finalWorkflowJSON, // Workflow steps JSON
        status: statusValue, // Workflow status (active/inactive)
        repeat: repeatCheckboxValue, // Whether the workflow is repeating

        //send in unique id
        unique_id: currentLoadedTemplateID,

        // set saveAsNew flag
        saveAsNew: saveAsNew
    };

    // Send AJAX request
    ajaxHandler(
        automationEngine.ajax_url, // URL to send the AJAX request
        'POST', // HTTP method
        requestPayload, // Data payload
        handleSaveTemplateSuccess, // Success handler
        handleSaveTemplateError // Error handler
    );
}

/**
 * Function: handleSaveTemplateSuccess
 * Handles the successful response from saving the workflow template.
 *
 * Parameters:
 * @param {Object} response - The response object returned by the server.
 *
 * Variables:
 * - responseData: The nested data property from the response object.
 */
function handleSaveTemplateSuccess(response) {

    // Access the nested data property correctly
    var responseData = response.data.data;

    // Call the existing handler
    handleSaveWorkflowResponse(
        responseData.workflow,
        responseData.name,
        responseData.unique_id,
    );

    // Update the current workflow title in the DOM
    var currentWorkflowTitleElement = document.querySelector('.current-workflow-title');
    if (currentWorkflowTitleElement) {
        currentWorkflowTitleElement.textContent = 'Editing: ' + responseData.name;
    }

    // Update the relevant templateList item by matching the listItem with responseData.unique_id
    var templateFound = false;
    templateList.forEach(function(template) {
        if (template.listItem === responseData.unique_id) {
            template.name = responseData.name; // Update the templateList item's name
            templateFound = true;
        }
    });

    // If no matching template in the templateList, add the new template
    if (!templateFound) {
        templateList.push({
            listItem: responseData.unique_id,
            name: responseData.name,
            json: JSON.stringify(responseData.workflow)
        });
    }

    // Re-render the entire block list using constructLoggerDefaultContent()
    document.getElementById('blocklist2').innerHTML = constructLoggerDefaultContent();
}


/**
 * Function: handleSaveTemplateError
 * Handles errors that occur during the AJAX request to save the workflow template.
 *
 * Parameters:
 * @param {Object} xhr - The XMLHttpRequest object containing detailed error information.
 * @param {string} status - The status of the AJAX request.
 * @param {string} error - The error message.
 *
 * Variables: None
 */
function handleSaveTemplateError(xhr, status, error) {
    console.error('AJAX Error:', xhr.responseText); // Log detailed error
    alert('Error saving template: ' + xhr.responseText); // Show error to user
}

/**
 * Function: activateWorkflow
 * Activates or deactivates a workflow by sending an AJAX request with the current workflow data and status.
 *
 * Parameters:
 * @param {string} masterArrayJSONOverwrite - The JSON string representation of the master array to overwrite.
 *
 * Variables:
 * - finalArrayJSONOverwrite: The final JSON string with the workflow steps.
 * - repeatCheckboxValue: The value of the repeat checkbox element.
 * - publishElement: The HTML element representing the 'publish' button.
 * - hiddenInput: The hidden input field within the 'publish' button element.
 * - statusValue: The status of the workflow (active or inactive).
 * - data: The payload for the AJAX request, containing action, template ID, status, JSON, repeat value, and nonce.
 */
function activateWorkflow(masterArrayJSONOverwrite) {

    // Get reference to the 'publish' button element
    var publishElement = document.getElementById("publish");
    // Search for a hidden input field within the 'publish' button element
    var hiddenInput = publishElement.querySelector('input[type="hidden"]');

    var statusValue = ""; // Initialize a variable to hold the status value

    // Check if the hidden input exists and read its value
    if (hiddenInput) {
        if (hiddenInput.value === "active") {
            statusValue = "inactive";
        } else if (hiddenInput.value === "inactive") {
            statusValue = "active";
        }
    }

    // Ensure statusValue is set
    if (!statusValue) {
        statusValue = "active";
    }

    // Prepare the data for the AJAX request
    let data = {
        action: 'aetp_activate_workflow', // This specifies the action to be taken 
        templateID: currentLoadedTemplateID,
        status: statusValue,
        nonce: automationEngine.nonce // Add the nonce value to the data
    };

    ajaxHandler(
        automationEngine.ajax_url,
        'POST',
        data,
        handleActivateWorkflowSuccess,
        handleActivateWorkflowError
    );

}

/**
 * Function: handleActivateWorkflowSuccess
 * Handles the successful response from the server after activating or deactivating the workflow.
 *
 * Parameters:
 * @param {Object} response - The response object returned by the server.
 *
 * Variables:
 * - updatedStatus: The status of the workflow returned by the server (active or inactive).
 */
function handleActivateWorkflowSuccess(response) {

    const publishElement = document.getElementById("publish");
    // Search for a hidden input field within the 'publish' button element
    const hiddenInput = publishElement.querySelector('input[type="hidden"]');

    if (response.success) {

        // Update the CSS and style of the publish element based on the status
        const updatedStatus = response.data.status;
        if (updatedStatus === "active") {

            publishElement.style.backgroundColor = "red";
            publishElement.childNodes[0].nodeValue = "Deactivate Workflow";

            if (!hiddenInput) {

                const newHiddenInput = document.createElement("input");
                newHiddenInput.type = "hidden";
                newHiddenInput.value = "active";
                publishElement.appendChild(newHiddenInput);

            } else {
                hiddenInput.value = "active";
            }

        } else if (updatedStatus === "inactive") {

            publishElement.style.backgroundColor = "#5961F3";
            publishElement.childNodes[0].nodeValue = "Activate Workflow";

            if (!hiddenInput) {
                const newHiddenInput = document.createElement("input");

                newHiddenInput.type = "hidden";
                newHiddenInput.value = "inactive";
                publishElement.appendChild(newHiddenInput);
            } else {
                hiddenInput.value = "inactive";
            }

        }
    } else {
        console.error("Error from the server: ", response.data.message);
        console.error("Response details: ", response.data);
    }
}

/**
 * Function: handleActivateWorkflowError
 * Handles errors that occur during the AJAX request to activate or deactivate the workflow.
 *
 * Parameters:
 * @param {Object} error - The error object containing detailed error information.
 */
function handleActivateWorkflowError(error) {
    console.error("Error sending status update to the server: ", error);
}

/**
 * Function: deleteLocalWorkflow
 * Initiates the deletion of a local workflow by sending an AJAX request with the specified list item number.
 *
 * Parameters:
 * @param {number} listItemNum - The number identifying the list item to delete.
 * @param {HTMLElement} listItem - The HTML element representing the list item to delete.
 *
 * Variables:
 * - localListItem: A global variable to hold the reference to the list item being deleted.
 * - requestPayload: The payload for the AJAX request, containing action, list item number, and nonce.
 */
var localListItem;

function deleteLocalWorkflow(listItemNum, listItem) {
    localListItem = listItem;
    // Proceed with the AJAX call if the user confirms
    const requestPayload = {
        action: 'aetp_delete_local_workflow',
        listItemNum: listItemNum,
        nonce: automationEngine.nonce
    };

    ajaxHandler(
        automationEngine.ajax_url,
        'POST',
        requestPayload,
        handleDeleteLocalWorkflowSuccess,
        handleDeleteLocalWorkflowError
    );
}

/** 
 * Function: handleDeleteLocalWorkflowSuccess
 * Handles the successful response from the server after attempting to delete a local workflow.
 *
 * Parameters:
 * @param {Object} response - The response object returned by the server.
 *
 * Variables: None
 */
function handleDeleteLocalWorkflowSuccess(response) {

    // Optionally, remove the list item from the DOM if the response is successful
    if (response.success) {
        localListItem.remove();
        flowy.deleteBlocks();

        // Remove the relevant item from the templateList array
        const listItemNum = localListItem.querySelector('.listItemNum').value;

        templateList = templateList.filter(template => template.listItem !== listItemNum);

    }
}


/**
 * Function: handleDeleteLocalWorkflowError
 * Handles errors that occur during the AJAX request to delete a local workflow.
 *
 * Parameters:
 * @param {Object} jqXHR - The jQuery XMLHttpRequest object containing detailed error information.
 * @param {string} textStatus - The status of the AJAX request.
 * @param {string} errorThrown - The error message.
 *
 * Variables: None
 */
function handleDeleteLocalWorkflowError(jqXHR, textStatus, errorThrown) {
    // Handle error response
    console.error('Error deleting workflow', textStatus, errorThrown);
}