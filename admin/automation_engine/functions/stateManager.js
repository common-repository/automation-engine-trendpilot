/**
 * Function: getStateById
 * Fetches the state of a template by its ID through an AJAX request.
 *
 * Parameters:
 * @param {number} templateID - The ID of the template.
 *
 * Variables:
 * - requestPayload: Object containing data to be sent to the server for fetching the state.
 */
function getStateById(templateID) { 

    const requestPayload = {
        action: 'aetp_get_state_by_id',
        templateID: templateID,
        nonce: automationEngine.nonce
    }

    ajaxHandler(
        automationEngine.ajax_url,
        'POST',
        requestPayload,
        handleGetStateByIdSuccess,
        handleGetStateByIdError
    );
} 

/**
 * Function: handleGetStateByIdSuccess
 * Handles the successful response of fetching the state by template ID.
 *
 * Parameters:
 * @param {Object} response - The response object from the server.
 *
 * Variables:
 * - response: The response object from the server containing success status and data.
 */
function handleGetStateByIdSuccess(response) {
    
    if (response.success) {
        if (response?.data?.data?.id === undefined) {
            clearStateData();
        } else {
            handleStateData(
                Number(response.data.data.current_step),
                response.data.data.parameters,
                response.data.data.status,
                response.data.data.is_child,
                parseInt(response.data.data.child_count, 10)
            );
        }
    } else {
        console.error("Error from the server in second AJAX call:");
    }
}

/**
 * Function: handleGetStateByIdError
 * Handles errors that occur during the fetching of the state by template ID.
 *
 * Parameters:
 * @param {string} error - The error message from the AJAX request.
 *
 * Variables:
 * - error: The error message from the AJAX request.
 */
function handleGetStateByIdError(error) {
    console.error("Error sending request to the server in second AJAX call:", error);
}

/**
 * Function: handleStateData
 * Processes and displays the state data, including updating the UI and highlighting the current step.
 *
 * Parameters:
 * @param {number} currentStep - The current step in the workflow.
 * @param {string} parameters - The parameters associated with the state.
 * @param {string} status - The status of the current step.
 * @param {boolean} isChild - Indicates if the current state is a child state.
 * @param {number} childCount - The number of child branches.
 *
 * Variables:
 * - htmlTable: HTML string representing the state data.
 * - dataDashboardElement: The HTML element displaying the data dashboard.
 * - canvas: The HTML element containing the workflow blocks.
 * - blockElems: A list of all elements with the 'blockelem' class.
 * - currentStepBlock: The block element corresponding to the current step.
 */
function handleStateData(currentStep, parameters, status, isChild, childCount) {

    // Construct a HTML table with the data
    var htmlTable = `<div style="background-color: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 10px;">
                        <h2 style="color: #333;">Automation Overview</h2>
                        <div style="display: flex; justify-content: space-between;">
                            <div style="flex: 1; padding: 10px;">
                                <h3 style="color: #555;">Current Step</h3>
                                <p>${currentStep + 1}</p>
                            </div>
                            <div style="flex: 1; padding: 10px;">
                                <h3 style="color: #555;">Status</h3>`;

    // Conditionally adding class based on the status
    if (status === "completed") {
        htmlTable += `<div class="status-completed-button">${status}</div>`;
    } else if (status === "in-progress") {
        htmlTable += `<div class="status-inprogress-button">${status}</div>`;
    } else {
        htmlTable += `<p>${status}</p>`;
    }

    htmlTable += `</div></div></div>`;

    // Only add Parameters section if it's not empty
    if (parameters) {
        if (parameters.trim() !== "") {
            let parameterList = parameters.split(',').map(param => {
                let [key, value] = param.split(':').map(p => p.trim());
                // Skip the parameter if the key is 'wait_x_days_set_for_step'
                if (key === 'wait_x_days_set_for_step') {
                    return '';
                }
                if (key === 'start_date') {
                    key = 'Wait X days start date';
                }
                return `<strong>${key}</strong>: ${value}`;
            }).filter(param => param !== ''); // Remove empty strings from the array

            let formattedParameters = parameterList.join('<br>');
            htmlTable += `
              <div style="background-color: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 10px;">
                  <h3 style="color: #555;">Parameters (stored parameters from previous steps)</h3>
                  <p style="line-height: 30px;">${formattedParameters}</p>
              </div>`;
        }
    } 

    // Conditionally add 'Any' Branches section
    if (childCount > 0) {
        htmlTable += `
              <div style="background-color: #f8f8f8; padding: 20px; border-radius: 8px;">
                  <h3 style="color: #555;">'Any' Trigger Branches</h3>
                  <p>${childCount}</p>
              </div>`;
    }

    var dataDashboardElement = document.getElementById("data-dashboard");

    // Check if dataDashboardElement exists
    if (dataDashboardElement) {
        // Replace the innerHTML of the existing dataDashboardElement
        dataDashboardElement.innerHTML = DOMPurify.sanitize(htmlTable);
    } else {
        // Create new dataDashboardElement and append it to codeEditorView
        dataDashboardElement = document.createElement("div");
        dataDashboardElement.id = "data-dashboard";
        dataDashboardElement.style.fontFamily = "Arial, sans-serif";
        dataDashboardElement.innerHTML = DOMPurify.sanitize(htmlTable);
        var codeEditorView = document.getElementById("codeEditorView");
        codeEditorView.appendChild(dataDashboardElement);
    }

    // add some code here to add some color to the corresponding current step in the canvas.
    // we will use the currentStep variable defined above to select the blockelem[currentStep] and make it green. But only if isChild is 0.

    var canvas = document.getElementById("canvas");
    var blockElems = canvas.querySelectorAll(".blockelem");

    // Remove existing highlight from all blocks
    blockElems.forEach(function (block) {
        block.classList.remove("current-step-completed");
        block.classList.remove("current-step-inprogress");
        block.style.backgroundColor = ""; // Clear any inline background color style
    });

    // Ensure currentStep is a number and within the range of blockElems
    if (
        !isNaN(currentStep) &&
        currentStep >= 0 &&
        currentStep <= blockElems.length
    ) {
        var currentStepBlock = blockElems[currentStep];

        if (status === "completed") {
            currentStepBlock.classList.add("current-step-completed");
            document.getElementById("run-button").style.display = "none";

        } else if (status === "in-progress") {
            currentStepBlock.classList.add("current-step-inprogress");
            document.getElementById("run-button").style.display = "inline-block";
        } else {
            document.getElementById("run-button").style.display = "inline-block";
            currentStepBlock.classList.add("current-step-inprogress");
        }
    }
}

/**
 * Function: clearStateData
 * Clears the workflow overview and resets any colors applied to the blocks in the canvas.
 */
/**
 * Function: clearStateData
 * Clears the workflow overview and resets the codeEditorView to its original state.
 */
function clearStateData() {

    // Reset the codeEditorView to its original state
    var codeEditorView = document.getElementById("codeEditorView");

    if (codeEditorView) {
        // Remove the data-dashboard element if it exists
        var dataDashboardElement = document.getElementById("data-dashboard");
        if (dataDashboardElement) {
            dataDashboardElement.remove();
        }

        // Reset the content of codeEditorView to its original state
        codeEditorView.innerHTML = `<h2>State of workflow below (N/A if not initiated yet):</h2>`;
    }

    // Remove any highlights or status from the blocks in the canvas
    var canvas = document.getElementById("canvas");
    var blockElems = canvas.querySelectorAll(".blockelem");

    blockElems.forEach(function (block) {
        block.classList.remove("current-step-completed");
        block.classList.remove("current-step-inprogress");
        block.style.backgroundColor = ""; // Clear any inline background color style
    });

    // Ensure the run button is displayed
    document.getElementById("run-button").style.display = "inline-block";
}


/**
 * Function: loadChildState
 * Loads the child state of the current template by sending an AJAX request to the server.
 *
 * Variables:
 * - branchDataView: The HTML element displaying the branch data.
 * - requestPayload: Object containing data to be sent to the server for loading the child state.
 */
// Ensure this is the correct container element ID
var dataDashboard = document.getElementById("data-dashboard");
var branchDataView = document.getElementById("branchDataView");

function loadChildState() {

    if (!branchDataView) {

        const requestPayload = {
            action: 'aetp_load_child_states',
            templateID: currentLoadedTemplateID,
            nonce: automationEngine.nonce
        };

        ajaxHandler(
            automationEngine.ajax_url,
            'POST',
            requestPayload,
            handleLoadChildStateSuccess,
            handleLoadChildStateError
        );
    }
} 

/**
 * Function: handleLoadChildStateSuccess
 * Handles the successful response of loading the child state.
 *
 * Parameters:
 * @param {Object} response - The response object from the server.
 *
 * Variables:
 * - response: The response object from the server containing success status and data.
 * - tableHead: The HTML string representing the heading and container for branch data.
 * - tableHTML: The HTML string representing the branch data.
 */
function handleLoadChildStateSuccess(response) {

var branchDataView = document.createElement("div");
branchDataView.id = "branchDataView";

var dataDashboard = document.getElementById("data-dashboard");
//var branchDataView = document.getElementById("branchDataView");

    if (response.success && Array.isArray(response.data) && response.data.length > 0) {

        // Prepare the heading and container for branch data
        var tableHead = `<h2>Branches so far for 'Any' trigger:</h2><p>When you use an 'Any' trigger, items that match that step's condition branch off into their own workflow continuations from that point. Below shows the item that initiated the branch, it's current step and status.
        <p>This is so you can keep track of which items caused a branch, and their current step in their new workflow continuation</p>
        <p>If 'Repeat' is turned on, branches that complete will be removed to allow them to repeat</p><p>If a specific branch is removed, branches will be allowed to occur again for that item</p></p>`;
        branchDataView.innerHTML = tableHead;

        // Iterate over the branches and update the HTML structure with API data
        response.data.forEach(function (branch, index) {

            var tableHTML = `<div id="branch-table"><input type="hidden" name="branch-id" value="${branch.id}"><table class="child-state-table" style="border-collapse: collapse; width: 100%;"><tbody>`;

            tableHTML += `<tr style="border: 1px solid black;"><th style="border: 1px solid black; text-align: left; padding: 8px;">Branch ${index + 1}</th><td style="border: 1px solid black; text-align: left; padding: 8px;"></td></tr>`;
            tableHTML += `<tr><th>Current Step</th><td>${branch.current_step + 1}</td></tr>`;
            if (branch.product_name) {
                tableHTML += `<tr><th>Product Name</th><td>${branch.product_name}</td></tr>`;
            }
            if (branch.category_name) {
                tableHTML += `<tr><th>Category Name</th><td>${branch.category_name}</td></tr>`;
            }
            if (branch.coupon_name) {
                tableHTML += `<tr><th>Coupon Name</th><td>${branch.coupon_name}</td></tr>`;
            }
            tableHTML += `<tr><th>Status</th><td>${branch.status}</td></tr>`;
            tableHTML += `</tbody></table><div id="branch-delete" class="a-button">Delete</div><br></div>`;

            // Append the tableHTML to branchDataView
            branchDataView.innerHTML += tableHTML;
        });

        // Append the branchDataView to the dataDashboard
        if (dataDashboard) {
            dataDashboard.appendChild(branchDataView);
        } else {
            console.error("dataDashboard element not found");
        }
    }
}

/**
 * Function: handleLoadChildStateError
 * Handles errors that occur during the loading of the child state.
 *
 * Parameters:
 * @param {string} error - The error message from the AJAX request.
 *
 * Variables:
 * - error: The error message from the AJAX request.
 */
function handleLoadChildStateError(error) {
    console.log("-> Error loading child state");
}