
// Setting product data variables
var bubbleDynamicData = automationEngine.bubbleDynamicData;
var productCategories = automationEngine.productCategories;
var templateList = automationEngine.templateList;
var siteUrl = automationEngine.siteUrl;
var AETPActiveStatus = automationEngine.AETPActiveStatus;

document.addEventListener("DOMContentLoaded", function () {

  document.getElementById("new-button").addEventListener("click", function() {
    if (confirm("Create new workflow?")) {
        location.reload(); // Refreshes the page if "Yes" is selected
    }
});

   
  getCronTime();

  /// The list of initial Event blocks (left panel - changes after tab click)
  document.getElementById("blocklist").innerHTML = eventBlocksMain;
  initializeDropdowns();

  flowy(document.getElementById("canvas"), drag, release, snapping);

  document.getElementById("closecard").addEventListener("click", function () {
    // Hide the entire leftcard
    document.getElementById("leftcard2").style.display = "none";
    // Show the opencard
    document.getElementById("opencard").style.display = "block";
  });
 
  document.getElementById("opencard").addEventListener("click", function () {
    // Hide the opencard
    this.style.display = "none";
    // Show the leftcard back
    document.getElementById("leftcard2").style.display = "block";
  });

  // Adding event listeners to the template items to load their workflow in the canvas
  document.body.addEventListener("click", function (event) {

    const item = event.target.closest(".block-template-item");

    if (item) {
      loadWorkflowContent(item);

      // Remove branchDataView element if it exists
      var branchDataView = document.getElementById("branchDataView");
      if (branchDataView) {
        branchDataView.parentNode.removeChild(branchDataView);
      } 
    }
  });

  /// TRENDPILOT NEW FUNCTION***
  document.getElementById("overwrite-template").addEventListener("click", function () {

    var isLastBlockEvent = checkLastBlockElem();

    if (isLastBlockEvent) {
        alert("Must end on either an Action or the 'End or repeat workflow' trigger");
        // This stops the execution of the remaining code in this function
        return;
    }

    // Serialize the master array to a JSON string
    let masterArrayJSONOverwrite = JSON.stringify(buildMasterArray(), null, 2);

    masterArrayJSONOverwrite = sanitizeMasterArrayJSON(masterArrayJSONOverwrite);

    // Get the entire output from flowy
    var currentJsonWorkflow = flowy.output();

    // Stringify the entire output
    var currentJsonWorkflowStringified = JSON.stringify(currentJsonWorkflow);

    // Fetch the workflow name from the DOM element with class 'current-workflow-title'
    var workflowTitleElement = document.querySelector('.current-workflow-title');
    var workflowName = workflowTitleElement ? workflowTitleElement.textContent.replace('Editing: ', '').trim() : '';

    // Call the saveWorkflowTemplate function
    saveWorkflowTemplate(currentJsonWorkflowStringified, masterArrayJSONOverwrite, workflowName);
});
 
  document.getElementById("removeblock").addEventListener("click", function () {
    if (confirm('Clear the canvas? This cannot be undone.')) {
      flowy.deleteBlocks();
    }
  });

  // addEventListenerMulti("touchstart", beginTouch, false, ".block");
  // addEventListener("mousedown", beginTouch, false);
  // addEventListener("mousemove", checkTouch, false);
  addEventListener("mouseup", customDoneTouch, false); 

  document.getElementById("saveProperties").addEventListener("click", saveProperties);

  // Adding the code editor view to the same parent as the diagram view
  diagramView.parentNode.insertBefore(codeEditorView, diagramView.nextSibling);

  // Add click event listener to the left switch
  document.getElementById("leftswitch").addEventListener("click", function () {

    document.getElementById("load-child-states").style.display = "none";

    // Show the diagram view
    diagramView.style.display = "flex";
    leftCard.style.display = "block";
    codeEditorView.style.display = "none";
    document.getElementById("opencard").style.display = "block";
    if (rightcard) {
      rightcard = false;
    }
  });

  // Add click event listener to the right switch
  document.getElementById("rightswitch").addEventListener("click", function () {
    
    // Hide the entire leftcard
    document.getElementById("leftcard2").style.display = "none";
    // Show the opencard
    document.getElementById("opencard").style.display = "block";

    if (rightcard) {
      rightcard = false;
      document.getElementById("properties").classList.remove("expanded");
      setTimeout(function () {
        document.getElementById("propwrap").classList.remove("itson");
      }, 300);
      tempblock.classList.remove("selectedblock");
    }

    handleRightSwitch();
  });

  window.addEventListener("message", function (event) {
    // Check if the message is of the type 'getStateByUniqueIdResponse'
    if (event.data.type && event.data.type === "getStateByUniqueIdResponse") {
      // Extract the data from the message
      var receivedData = event.data.data;

      // Split the received data into parts
      var parts = receivedData.split(" | ");
      var currentStep = Number(parts[1]);
      var parameters = parts[2];
      var status = parts[4];
      var isChild = parts[5];
      // Convert childCount to a number
      var childCount = parseInt(parts[6], 10);

      createMessageLayout(currentStep, parameters, status, isChild, childCount);
    }
  },
    false
  );

  // Add click event listener to the 'publish' button
  document.getElementById("publish").addEventListener("click", function () {

    var isLastBlockEvent = checkLastBlockElem();
    if (isLastBlockEvent) {
      alert("Must end on either an Action or the 'End or repeat workflow' trigger");
      return; // This stops the execution of the remaining code in this function
    }

    // Serialize the master array to a JSON string
    let masterArrayJSONOverwrite = JSON.stringify(buildMasterArray(), null, 2);

    masterArrayJSONOverwrite = sanitizeMasterArrayJSON(masterArrayJSONOverwrite);


    activateWorkflow(masterArrayJSONOverwrite);
  });

  /// TRENDPILOT NEW FUNCTION***
  document.getElementById("save-template").addEventListener("click", function () {

    // Check if the last block is an event
    var isLastBlockEvent = checkLastBlockElem();
    if (isLastBlockEvent) {
        alert("Must end on either an Action or the 'End or repeat workflow' trigger");
        return; // This stops the execution of the remaining code in this function
    }

    var saveAsNew = 1;
    // Show the modal to enter the workflow name
    var modal = document.getElementById("trendpilot_workflowNameModal");
    var span = document.getElementsByClassName("trendpilot_close")[0];
    var saveWorkflowBtn = document.getElementById("trendpilot_saveWorkflowBtn");
    modal.style.display = "block";

    // Close the modal when the user clicks on <span> (x)
    span.onclick = function () {
        modal.style.display = "none";
    }

    // Close the modal when the user clicks anywhere outside of the modal
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Save workflow when the button inside the modal is clicked
    saveWorkflowBtn.onclick = function () {
        var workflowName = document.getElementById("trendpilot_workflowNameInput").value;
        if (!workflowName) {
            alert("Please enter a name for the workflow");
            return;
        }

        // Close the modal
        modal.style.display = "none";

        // Get the entire output
        var currentJsonWorkflow = flowy.output();

        // Stringify the entire output
        var currentJsonWorkflowStringified = JSON.stringify(currentJsonWorkflow);

         // Serialize the master array to a JSON string
        var workflowJSON = JSON.stringify(buildMasterArray(), null, 2);
        workflowJSON = sanitizeMasterArrayJSON(workflowJSON);

        saveWorkflowTemplate(currentJsonWorkflowStringified, workflowJSON, workflowName, saveAsNew);
    };
});

  // event listener for RUN button
  document.getElementById("run-button").addEventListener("click", function () {

    runTemplate(currentLoadedTemplateID);
  });

  // event listener for RESET button
  document
    .getElementById("reset-button")
    .addEventListener("click", function () {

      resetTemplate(currentLoadedTemplateID);
    });

  document.getElementById("load-child-states").addEventListener("click", function () {

    loadChildState();
  });

  // Code below for making sure the activate buton only displays when it can be used.

  //   Check if 'templateList' is empty
  if (templateList.length === 0) {
    // Set the button's style to 'display: none'
    publishButton.style.display = "none";
  }

  // Add input event listener
  searchInput.addEventListener("input", function () {
    var query = searchInput.value.toLowerCase();
    var blocks = blocklist.querySelectorAll(".leftblockelem");

    if (query === "") {
      resetDropdowns(); // Reset dropdowns when search is cleared
    } else {
      // Existing search functionality
      blocks.forEach(function (block) {
        var titleElem = block.querySelector(".blocktitle");
        if (titleElem) {
          var titleText = titleElem.textContent || titleElem.innerText;
          if (titleText.toLowerCase().includes(query)) {
            block.style.display = "block";
          } else {
            block.style.display = "none";
          }
        }
      });
    }
  });

  document.getElementById("close").addEventListener("click", function () {
    if (rightcard) {
      rightcard = false;
      document.getElementById("properties").classList.remove("expanded");
      setTimeout(function () {
        document.getElementById("propwrap").classList.remove("itson");
      }, 300);
      tempblock.classList.remove("selectedblock");
    }
  });

addEventListenerMulti("click", disabledClick, false, ".side");

/**
* Function: disabledClick
* Handles the click event on tabs, toggling active and disabled states and loading content.
*/
function disabledClick() {
  // Remove 'navactive' class from current active tab and add 'navdisabled'
  document.querySelector(".navactive").classList.add("navdisabled");
  document.querySelector(".navactive").classList.remove("navactive");

  // Add 'navactive' to the clicked tab and remove 'navdisabled'
  this.classList.add("navactive");
  this.classList.remove("navdisabled");

  // Load content for the clicked tab
  loadTabContent(this.getAttribute("id"));
};

  /**
 * Function: loadTabContent
 * Loads content based on the provided tabId into a designated HTML element.
 *
 * @param {string} tabId - The ID of the tab whose content is to be loaded.
 */
  function loadTabContent(tabId) {
    let content;
    switch (tabId) {
      case "events-left":
        content = eventBlocksMain; 
        break;
      case "actions":
        content = actionBlocksMain;
        break;
      case "loggers":
        content = constructLoggerDefaultContent();
        break;
      // Add other cases as needed
      default:
        content = ""; // Default case
    }

    document.getElementById("blocklist").innerHTML = content;

    // Check if the current tab is 'loggers' and add event listeners
    if (tabId === 'loggers') {
      addEventListenersToLoggerItems();
    }

    initializeDropdowns(); // Reinitialize dropdown functionality
    flowy.checkAndAddClickListenerWhenSwitchTabs(); // Checking and Adding Event Listener on Block Elements, When Switched Tabs
  }

// Function to load the logger tab content by default on page load
function loadLoggersTabOnPageLoad() {
  loadTabContent2('loggers'); // Directly load the 'loggers' tab content
}

/**
 * Function: loadTabContent2
 * Loads content based on the provided tabId into a designated HTML element.
 *
 * @param {string} tabId - The ID of the tab whose content is to be loaded.
 */
function loadTabContent2(tabId) {
  let content;
  switch (tabId) {
    case "loggers":
      content = constructLoggerDefaultContent();
      break;
    // Add other cases if you have other content types in the future
    default:
      content = ""; // Default case
  }

  document.getElementById("blocklist2").innerHTML = content;

  // Check if the current tab is 'loggers' and add event listeners
  if (tabId === 'loggers') {
    addEventListenersToLoggerItems();
  }

  // Initialize any necessary listeners for blocks after content load
  flowy.checkAndAddClickListenerWhenSwitchTabs(); 
}

// Call the function to load the loggers tab by default when the page loads
window.onload = function() {
  loadLoggersTabOnPageLoad();
};


  /**
  * Function: resetDropdowns
  * Resets dropdowns based on the currently active tab.
  */
  function resetDropdowns() {
    var activeTab = document.querySelector(".navactive").getAttribute("id");
    loadTabContent(activeTab); // Load content for the current active tab
  }

  /**
  * Function: addEventListenersToLoggerItems
  * Adds event listeners for mouseenter and click events to logger items.
  */
  function addEventListenersToLoggerItems() {

    document.querySelectorAll('.block-template-item').forEach(item => {
      item.addEventListener('mouseenter', function () {
        this.classList.add('db-highlight');
      });
      item.addEventListener('mouseleave', function () {
        this.classList.remove('db-highlight');
      });
    });

    document.querySelectorAll('.template-li-delete').forEach(deleteButton => {
      deleteButton.addEventListener('click', function (event) {
        event.stopPropagation(); // Prevent triggering any parent element's events

        var listItem = this.closest('.block-template-item');
        var listItemNum = listItem.querySelector('.listItemNum').value;

        // Show confirmation popup
        var confirmation = confirm('Are you sure? This action cannot be undone.');

        if (confirmation) {
          deleteLocalWorkflow(listItemNum, listItem);
          //deleteExtWorkflow(listItemNum);
          //constructLoggerDefaultContent();
        }
      });
    });
  }

  /*  function drawConnectionLine() {
      var canvas = document.getElementById("canvas");
      var repeatIcon = document.getElementById("repeat-icon");
  
      // Add the icon if it doesn't exist
      if (!repeatIcon) {
        repeatIcon = document.createElement("img");
        repeatIcon.id = "repeat-icon";
        repeatIcon.src = `${assetURL}assets/repeat.svg`; // Set the source of the icon
        canvas.appendChild(repeatIcon);
      }
  
      // Apply the specified styling
      repeatIcon.style.position = "absolute";
      repeatIcon.style.left = "222px";
      repeatIcon.style.top = "64.5px";
      repeatIcon.style.maxWidth = "144px"; // Note: Use maxWidth in camelCase for JavaScript
    }
  
    function removeConnectionLine() {
      var repeatIcon = document.getElementById("repeat-icon");
      if (repeatIcon) {
        repeatIcon.remove();
      }
    }
  
    function removeConnectionLine() {
      var repeatIcon = document.getElementById("repeat-icon");
      if (repeatIcon) {
        repeatIcon.remove();
      }
    }
  */

  repeatCheckbox.addEventListener("click", function () {
    repeatCheckbox.value = repeatCheckbox.checked ? "1" : "0";

  });

  // Attach event listeners to buttons
  Object.keys(tooltips).forEach(buttonId => {
    const button = document.getElementById(buttonId);
    button.addEventListener('mouseover', showTooltip);
    button.addEventListener('mouseout', hideTooltip);
  });
});


document.getElementById('currentWorkflowTitle').addEventListener('click', function() {
  const currentWorkflowTitle = this;
  
  // Create an input field with the current value
  const input = document.createElement('input');
  input.type = 'text';
  input.value = currentWorkflowTitle.textContent.replace('Editing: ', '').trim();
  input.className = 'edit-workflow-name';

  // Replace the text with the input field
  currentWorkflowTitle.replaceWith(input);
  input.focus();

  // When the user presses "Enter" or blurs out of the input, update the value
  input.addEventListener('blur', function() {
      updateWorkflowName(input, currentWorkflowTitle);
  });

  input.addEventListener('keydown', function(event) {
      if (event.key === 'Enter') {
          updateWorkflowName(input, currentWorkflowTitle);
      }
  });
});

function updateWorkflowName(input, currentWorkflowTitle) {
  const newName = input.value.trim();
  
  // Create a new "currentWorkflowTitle" element with the updated name
  const updatedTitle = document.createElement('div');
  updatedTitle.className = 'current-workflow-title';
  updatedTitle.id = 'currentWorkflowTitle';
  updatedTitle.textContent = 'Editing: ' + newName;

  // Replace the input field with the updated title element
  input.replaceWith(updatedTitle);

  // Reattach the click event listener to the updated title element
  updatedTitle.addEventListener('click', function() {
      // Allow editing again when clicked
      updatedTitle.replaceWith(input);
      input.focus();
  });

}
