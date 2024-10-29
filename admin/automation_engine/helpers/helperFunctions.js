/**
 * Function: updateButtonVisibility
 * Updates the visibility of the 'overwrite-template' button based on the currentLoadedTemplateID.
 */
function updateButtonVisibility() {
    const button = document.getElementById("overwrite-template");

    if (!currentLoadedTemplateID) {
        // If currentLoadedTemplateID is empty or undefined
        button.style.display = "none";
    } else {
        button.style.display = "inline-block";
    }
}

/**
 * Function: updateWorkflowTitle
 * Updates the workflow title and visibility of the publish button based on the currentLoadedTemplateID.
 */
function updateWorkflowTitle() {
    // Find the matching template in the templateList
    let matchedTemplate = templateList.find(
        (template) => template.listItem === currentLoadedTemplateID
    );

    // If a match is found, update the HTML content
    if (matchedTemplate) {
        let templateName = matchedTemplate.name;

        // Sanitize the templateName using DOMPurify
        let sanitizedTemplateName = DOMPurify.sanitize(templateName);

        document.querySelector(
            ".current-workflow-title"
        ).textContent = `Editing: ${sanitizedTemplateName}`;

        publishButton.style.display = "inline-block";
    }

    openRightSidePanel();
}

/**
 * Function: openRightSidePanel
 * Adds click listeners to each blockelem in the canvas to open the right side panel.
 */
function openRightSidePanel() {
    const canvasBlockElements = document.querySelectorAll(
        "body #canvas > .blockelem"
    );

    canvasBlockElements.forEach((blockElem) => {
        if (!blockElem.hasClickListener) {
            addClickListener(blockElem);
            blockElem.hasClickListener = true;
        }
    });
}

/**
 * Function: addClickListener
 * Adds a click event listener to a given block element.
 *
 * Parameters:
 * @param {HTMLElement} blockElement - The block element to add the listener to.
 */
function addClickListener(blockElement) {
    blockElement.addEventListener("click", (event) => {
        customDoneTouch(event);
    });
}

/**
 * Function: initializeDropdowns
 * Initializes dropdown behavior for all block-head elements.
 */
function initializeDropdowns() {
    var blockHeads = document.querySelectorAll(".block-head");

    blockHeads.forEach(function (head) {
        head.addEventListener("click", function () {
            var parentGroup = this.parentElement;
            var dropArrow = this.querySelector(".droparrow");

            if (parentGroup.classList.contains("active")) {
                parentGroup.classList.remove("active");
                dropArrow.classList.remove("dropdown-selected");
            } else {
                closeAllBlockGroups();
                parentGroup.classList.add("active");
                dropArrow.classList.add("dropdown-selected");
            }
        });
    });
}

/**
 * Function: closeAllBlockGroups
 * Closes all block groups and removes dropdown selection.
 */
function closeAllBlockGroups() {
    var allBlockGroups = document.querySelectorAll(".block-group");
    allBlockGroups.forEach(function (group) {
        group.classList.remove("active");
        var dropArrow = group.querySelector(".droparrow");
        if (dropArrow) {
            dropArrow.classList.remove("dropdown-selected");
        }
    });
}

/**
 * Function: addEventListenerMulti
 * Adds event listeners to multiple elements matching a selector.
 *
 * Parameters:
 * @param {string} type - The event type (e.g., 'click', 'change').
 * @param {EventListener} listener - The event listener function.
 * @param {boolean} capture - Whether to use event capturing.
 * @param {string} selector - The CSS selector to match elements.
 */
function addEventListenerMulti(type, listener, capture, selector) {
    var nodes = document.querySelectorAll(selector);
    for (var i = 0; i < nodes.length; i++) {
        nodes[i].addEventListener(type, listener, capture);
    }
}

/**
 * Function: testValue
 * Tests whether a given value is an action or an event.
 *
 * Parameters:
 * @param {string} value - The value to test.
 * @returns {boolean} - True if the value is in eventStrings, false otherwise.
 */
function testValue(value) {
    if (eventStrings.includes(value)) {
        return true;
    } else {
        // Add more conditions here if needed
        return false;
    }
}

/**
 * Function: isCanvasEmpty
 * Checks if the canvas element is empty (has only an indicator child).
 *
 * @returns {boolean} - True if the canvas is empty, false otherwise.
 */
function isCanvasEmpty() {
    const canvas = document.getElementById("canvas");
    const children = canvas.children;
    // If there's only one child and it has the class 'indicator', the canvas is empty
    if (children.length === 1 && children[0].classList.contains("indicator")) {
        return true;
    }

    return false;
}

/**
 * Function: anyEventCount
 * Counts the number of block elements containing 'any_event' inputs in the canvas.
 *
 * @returns {number} - The count of block elements with 'any_event' inputs.
 */
function anyEventCount() {
    var count = 0;

    // Select all blockelem elements within the canvas
    var blockElems = document.querySelectorAll("#canvas .blockelem");

    blockElems.forEach(function (blockElem) {
        // Check if the blockElem contains an 'any_event' input directly
        var anyEventInput = blockElem.querySelector('input[name="any_event"]');
        if (anyEventInput) {
            count++;
        }
    });

    return count;
}

/**
 * Function: toggleLoadingOverlay
 * Toggles the visibility of the loading overlay based on the 'show' parameter.
 *
 * Parameters:
 * @param {number} show - 1 to show the loading overlay, 0 to hide it.
 */
function toggleLoadingOverlay(show) {
    if (show === 1) {
        // Create and display the loading overlay
        var overlay = document.createElement("div");
        overlay.id = "loadingOverlay";
        overlay.style.position = "fixed";
        overlay.style.top = "0";
        overlay.style.left = "0";
        overlay.style.width = "100%";
        overlay.style.height = "100%";
        overlay.style.backgroundColor = "rgba(0,0,0,0.5)";
        overlay.style.display = "flex";
        overlay.style.justifyContent = "center";
        overlay.style.alignItems = "center";
        overlay.style.zIndex = "9999";
        document.body.style.overflow = "hidden";  // Disable scrolling

        var loader = document.createElement("div");
        loader.className = "loader";  // You can define CSS animations for this class
        overlay.appendChild(loader);

        document.body.appendChild(overlay);
    } else if (show === 0) {
        // Remove the loading overlay
        var overlay = document.getElementById("loadingOverlay");
        if (overlay) {
            overlay.parentNode.removeChild(overlay);
            document.body.style.overflow = "auto";  // Enable scrolling
        }
    }
}

/**
 * Function: checkLastBlockElem
 * Checks if the last block element in the canvas requires further processing.
 *
 * @returns {boolean} - True if further processing is needed, false otherwise.
 */
function checkLastBlockElem() {
    var canvas = document.getElementById("canvas");
    var blockElems = canvas.querySelectorAll(".blockelem");
    if (blockElems.length === 0) {
        return false; // Indicates no further processing is needed
    }
    var lastBlockElem = blockElems[blockElems.length - 1];
    if (lastBlockElem) {
        var childInput = lastBlockElem.querySelector("input.blockelemtype");
        if (childInput) {
            const childInputValue = childInput.value;
            if (childInputValue === "end_workflow") {
                return false; // Indicates no further processing is needed
            } else {
                return testValue(childInputValue);
            }
        }
    }
    return false; // Default return value in case other conditions are not met
}

/**
 * Function: drag
 * Prepares a block element for dragging by adding a 'blockdisabled' class.
 *
 * @param {HTMLElement} block - The block element to prepare for dragging.
 */
function drag(block) {
    block.classList.add("blockdisabled");
    tempblock2 = block;
}

/**
 * Function: release
 * Releases a block element from dragging by removing the 'blockdisabled' class.
 */
function release() {
    if (tempblock2) {
        tempblock2.classList.remove("blockdisabled");
    }
}

/**
 * Function: beginTouch
 * Sets initial touch-related flags and determines if the touch started within a specific area.
 *
 * @param {Event} event - The touch event object.
 */
function beginTouch(event) {
    aclick = true;
    noinfo = false;
    if (event.target.closest(".create-flowy")) {
        noinfo = true;
    }
};

/**
 * Function: checkTouch
 * Checks if a touch event occurred, resetting aclick flag.
 *
 * @param {Event} event - The touch event object.
 */
function checkTouch(event) {
    aclick = false;
};

/**
 * Function: doneTouch
 * Handles the completion of a touch event, performing actions based on the event type and target.
 *
 * @param {Event} event - The touch or mouse event object.
 */
function doneTouch(event) {

    if (notEventAndCanvas) {
        flowy.deleteBlocks();
    }

    if (event.type === "mouseup" && aclick && !noinfo) {
        if (
            event.target.closest(".block") ||
            !event.target.closest(".block")?.classList.contains("dragging")
        ) {
            tempblock = event.target.closest(".block");
            rightcard = true;
            document.getElementById("properties").classList.add("expanded");
            document.getElementById("propwrap").classList.add("itson");

            // Remove the 'selectedblock' class from all other blocks.
            var selectedBlocks = document.querySelectorAll(".selectedblock");
            selectedBlocks.forEach(function (block) {
                block.classList.remove("selectedblock");
            });

            tempblock.classList.add("selectedblock");

            // Get the action type from the selected block
            var actionType = tempblock.querySelector(".blockelemtype").value;
            var blockyNameElement = tempblock.querySelector(".blockyname");
            var blockyNameText = blockyNameElement.textContent;
            if (actionProperties[actionType]) {
                updatePropertiesPanel(actionType, blockyNameText);
            }
        }
    }

    if (event.target.id === "branch-delete") {

        // Find the nearest 'branch-table' element
        var branchTable = event.target.closest("#branch-table");

        // Find the input with name 'branch-id' within the 'branch-table'
        var branchIdInput = branchTable.querySelector('input[name="branch-id"]');

        var branchIdValue = branchIdInput.value;

        return;
    }
};

// Function to generate checkboxes and their labels
function createCheckbox(name, labelText, updateFilters) {
    var container = document.createElement("span"); // Use span to keep checkboxes inline
    
    var label = document.createElement("label");
    label.textContent = labelText;
    
    var checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.name = name;
    checkbox.value = "0"; // Default value is 0 (unchecked)
    checkbox.className = "productFilterInput"; // Add specific class to the checkbox
    
    checkbox.addEventListener("change", function() {
        checkbox.value = checkbox.checked ? "1" : "0";
        updateFilters();
    });
    
    container.appendChild(checkbox);
    container.appendChild(label);
    return container;
}

// Function to generate text inputs and their labels
function createTextInput(name, labelText, updateFilters) {
    var container = document.createElement("div");
    
    var label = document.createElement("label");
    label.textContent = labelText;
    
    var textInput = document.createElement("input");
    textInput.type = "text";
    textInput.name = name;
    textInput.className = "productFilterInput";
    
    textInput.addEventListener("input", updateFilters);
    
    container.appendChild(label);
    container.appendChild(textInput);
    return container;
} 

// Function to generate dropdowns and their labels
function createDropdown(name, labelText, options, updateFilters) { 
    var container = document.createElement("div");
    
    var label = document.createElement("p");
    label.textContent = labelText;
    label.classList.add("inputlabel");

    var select = document.createElement("select");
    select.name = name;
    select.className = "productFilterInput";
    
    options.forEach(function(optionText) {
        var option = document.createElement("option");
        option.value = optionText.toLowerCase().replace(' ', '_');
        option.textContent = optionText;
        select.appendChild(option);
    });
    
    select.addEventListener("change", updateFilters);
    
    container.appendChild(label);
    container.appendChild(select);
    return container;
}

// Function to generate category dropdown
function createCategoryDropdown(name, labelText, categories, updateFilters) {
    var container = document.createElement("div");
    
    var label = document.createElement("label");
    label.textContent = labelText;
    
    var select = document.createElement("select");
    select.name = name;
    select.className = "productFilterInput";
    
    categories.forEach(function(category) {
        var option = document.createElement("option");
        option.value = category.id; // Assuming each category has an 'id' field
        option.textContent = category.name;
        select.appendChild(option);
    });
    
    select.addEventListener("change", updateFilters);
    
    container.appendChild(select);
    return container;
}

// Function to show/hide an element based on a checkbox
function createCheckboxWithDependency(checkboxName, checkboxLabelText, createDependentElement, updateFilters) {
    var container = document.createElement("div");
    
    var checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.name = checkboxName;
    checkbox.className = "productFilterInput";
    
    var label = document.createElement("label");
    label.textContent = checkboxLabelText;
    
    checkbox.addEventListener("change", function() {
        if (checkbox.checked) {
            var dependentElement = createDependentElement();
            container.appendChild(dependentElement);
        } else {
            var dependentElement = container.querySelector(`[name="${checkboxName.replace('show_', '')}"]`).parentElement;
            container.removeChild(dependentElement);
        }
        updateFilters();
    });
    
    container.appendChild(checkbox);
    container.appendChild(label);
    
    return container;
}
