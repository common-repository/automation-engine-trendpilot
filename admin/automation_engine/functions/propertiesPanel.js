/**
 * Updates the properties panel based on the selected action type and name element.
 * 
 * @param {string} actionType - The type of action to update properties for.
 * @param {string} nameElement - The name of the element to update the properties heading.
 */
function updatePropertiesPanel(actionType, nameElement) {
    var properties = actionProperties[actionType];

    // Clear the existing properties
    var propertiesPanel = document.getElementById("proplist");
    propertiesPanel.innerHTML = "";

    // Retrieve the properties heading and set new text
    var propertiesHeading = document.getElementById("dataprop");
    var heading = DOMPurify.sanitize(nameElement); // Sanitize nameElement
    propertiesHeading.textContent = heading; // Update the text content

    // Get the block element using the class 'selectedblock'
    var blockElement = document.querySelector(".blockelem.selectedblock");

    // Add the new properties
    for (var property in properties) {
        if (
            property !== "id" &&
            property !== "name" &&
            property !== "infoTemplate"
        ) {
            
            // Create label element
            var labelElement = document.createElement("p");
            labelElement.className = "inputlabel";

            // Check if property is 'product_id'
            if (property === "product_id") {
                labelElement.textContent = "Select product:";
                // Create dropdown for product names
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Populate dropdown with product names from bubbleDynamicData
                bubbleDynamicData.forEach(function (product) {
                    var option = document.createElement("option");
                    option.value = DOMPurify.sanitize(product.id); // Sanitize product.id
                    option.textContent = DOMPurify.sanitize(product.name); // Sanitize product.name
                    dropdown.appendChild(option);
                });

                // Add change event listener to the dropdown
                dropdown.addEventListener("change", saveProperties);

                // Fetch the value from the 'hidden-properties' element
                var hiddenValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                )?.value;

                // If a hidden value is found, set the dropdown's selected value
                if (hiddenValue) {
                    hiddenValue = DOMPurify.sanitize(hiddenValue); // Sanitize hiddenValue
                    for (var option of dropdown.options) {
                        if (option.value == hiddenValue) {
                            option.selected = true;
                            break;
                        }
                    } 
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            }
            else if (property === "product_filters") {
                labelElement.textContent = "Filters:";
                
                // Define the updateFilters function
                function updateFilters() {
                    var selectedFilters = {};
                    checkboxGroup.querySelectorAll(".productFilterInput").forEach(function (input) {
                        if (input.name === "show_in_category" || input.name === "show_with_tag") {
                            return; // Skip 'show_in_category' and 'show_with_tag' inputs
                        }
                        if (input.type === "checkbox") {
                            selectedFilters[input.name] = input.checked ? "1" : "0";
                        } else {
                            selectedFilters[input.name] = DOMPurify.sanitize(input.value); // Sanitize input.value
                        }
                    });
                    properties[property] = JSON.stringify(selectedFilters);
                }
            
                // Create a div to group the inputs and add a class
                var checkboxGroup = document.createElement("div");
                checkboxGroup.className = "radioGroupClass"; // Add class to the checkbox group
                
                // Create a container for checkboxes to keep them inline
                var checkboxesContainer = document.createElement("div");
                checkboxesContainer.className = "checkboxesContainer";
                
                // Create 'on sale' checkbox
                var onSale = createCheckbox("on_sale", "On Sale", updateFilters);
                
                // Create 'featured' checkbox
                var featured = createCheckbox("featured", "Featured", updateFilters);
            
                // Append checkboxes to the checkboxes container
                checkboxesContainer.appendChild(onSale);
                checkboxesContainer.appendChild(featured);
            
                // Create a checkbox with dependency on the 'in category' dropdown
                var inCategoryCheckboxWithDependency = createCheckboxWithDependency("show_in_category", "In Category", function() {
                    var dependentElement = createCategoryDropdown("in_category", "In Category", productCategories, updateFilters);
                    // Fetch the existing value for in_category
                    if (selectedFilters && selectedFilters.in_category) {
                        dependentElement.querySelector(`select[name="in_category"]`).value = DOMPurify.sanitize(selectedFilters.in_category);
                    }
                    return dependentElement;
                }, updateFilters);
            
                // Create a checkbox with dependency on the 'with tag' text input
                var withTagCheckboxWithDependency = createCheckboxWithDependency("show_with_tag", "With Tag", function() {
                    var dependentElement = createTextInput("with_tag", "With Tag", updateFilters);
                    // Fetch the existing value for with_tag
                    if (selectedFilters && selectedFilters.with_tag) {
                        // Sanitize the with_tag value using DOMPurify
                        var sanitizedWithTag = DOMPurify.sanitize(selectedFilters.with_tag);
                        
                        // Assign the sanitized value back to the input element
                        dependentElement.querySelector(`input[name="with_tag"]`).value = sanitizedWithTag;
                    }
                    
                    return dependentElement;
                }, updateFilters);
                
                // Create 'sortby' dropdown
                var sortby = createDropdown("sortby", "Select:", ["Most Recent", "Most Viewed" , "Oldest", "Least Viewed", "Most Purchased", "Highest Revenue", "Random"], updateFilters);
            
                // Append containers to the group
                checkboxGroup.appendChild(checkboxesContainer);
                checkboxGroup.appendChild(inCategoryCheckboxWithDependency);
                checkboxGroup.appendChild(withTagCheckboxWithDependency);
                checkboxGroup.appendChild(sortby);
            
                // Append label and checkbox group to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(checkboxGroup);
            
                // Fetch the existing value from the block's hidden-properties, if available
                var hiddenPropertyValue = blockElement.querySelector(`.hidden-properties [name="${property}"]`);
                if (hiddenPropertyValue) {
                    var selectedFilters = JSON.parse(hiddenPropertyValue.value);
                    checkboxGroup.querySelectorAll(".productFilterInput").forEach(function(input) {
                        if (input.name in selectedFilters) {
                            if (input.type === "checkbox") {
                                input.checked = selectedFilters[input.name] === "1";
                                input.value = input.checked ? "1" : "0";
                                if (input.name === "show_in_category" && input.checked) {
                                    var dependentElement = createCategoryDropdown("in_category", "In Category", productCategories, updateFilters);
                                    inCategoryCheckboxWithDependency.appendChild(dependentElement);
                                    dependentElement.querySelector(`select[name="in_category"]`).value = DOMPurify.sanitize(selectedFilters.in_category);
                                }
                                if (input.name === "show_with_tag" && input.checked) {
                                    var dependentElement = createTextInput("with_tag", "With Tag", updateFilters);
                                    withTagCheckboxWithDependency.appendChild(dependentElement);
                                    
                                    var sanitizedWithTag = DOMPurify.sanitize(selectedFilters.with_tag);
                            
                                    // Assign the sanitized value back to the input element
                                    dependentElement.querySelector(`input[name="with_tag"]`).value = sanitizedWithTag;
                                }
                            } else {
                                input.value = DOMPurify.sanitize(selectedFilters[input.name]);
                            }
                        }
                    });
                }
            
                // Initialize the filters update
                updateFilters();
            }
            
            else if (property === "datapoint") {
                labelElement.textContent = "Select Datapoint:";
                // Create dropdown for datapoints
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Populate dropdown with datapoints
                datapoints.forEach(function (datapoint) {
                    var option = document.createElement("option");
                    option.value = DOMPurify.sanitize(datapoint.value);
                    option.textContent = DOMPurify.sanitize(datapoint.name);
                    dropdown.appendChild(option);
                });

                // Add change event listener to the dropdown
                dropdown.addEventListener("change", saveProperties);

                // Fetch the value from the 'hidden-properties' element
                var hiddenValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                )?.value;

                // If a hidden value is found, set the dropdown's selected value
                if (hiddenValue) {
                    hiddenValue = DOMPurify.sanitize(hiddenValue);
                    for (var option of dropdown.options) {
                        if (option.value == hiddenValue) {
                            option.selected = true;
                            break;
                        }
                    }
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            } else if (property === "cat_id") {
                labelElement.textContent = "Select Category:";
                // Create dropdown for product names
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Populate dropdown with product categories
                productCategories.forEach(function (category) {
                    var option = document.createElement("option");
                    option.value = DOMPurify.sanitize(category.id); // Sanitize category.id
                    option.textContent = DOMPurify.sanitize(category.name); // Sanitize category.name
                    dropdown.appendChild(option);
                });

                // Add change event listener to the dropdown
                dropdown.addEventListener("change", saveProperties);

                // Fetch the value from the 'hidden-properties' element
                var hiddenValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                )?.value;

                // If a hidden value is found, set the dropdown's selected value
                if (hiddenValue) {
                    hiddenValue = DOMPurify.sanitize(hiddenValue);
                    for (var option of dropdown.options) {
                        if (option.value == hiddenValue) {
                            option.selected = true;
                            break;
                        }
                    }
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);

                });
            } else if (property === "days") {
                labelElement.textContent = "Days value:";
                // Create input field as before
                var inputField = document.createElement("input");
                inputField.name = property;
                inputField.placeholder = property;

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    inputField.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and input to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(inputField);

                // Update properties object on input change
                inputField.addEventListener("input", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);

                });
            } else if (property === "enable_disable") {
                labelElement.textContent = "Select:";

                // Create a select dropdown element
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Create and append the options
                var enableOption = document.createElement("option");
                enableOption.value = "Enable";
                enableOption.textContent = "Enable";

                var disableOption = document.createElement("option");
                disableOption.value = "Disable";
                disableOption.textContent = "Disable";

                dropdown.appendChild(enableOption);
                dropdown.appendChild(disableOption);

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    dropdown.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);

                });
            } else if (property === "product_status") {
                labelElement.textContent = "Select Status:";

                // Create a select dropdown element
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Define WooCommerce product statuses
                const statuses = ["publish", "draft", "pending", "private", "trash"];

                // Create and append the options
                statuses.forEach(function (status) {
                    var option = document.createElement("option");
                    option.value = status;
                    option.textContent = status;
                    dropdown.appendChild(option);
                });

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    dropdown.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);

                });
            } else if (property === "message") {
                labelElement.textContent = "Message";

                // Create textarea for multi-line input
                var textareaField = document.createElement("textarea");
                textareaField.name = property;
                textareaField.placeholder = property;
                textareaField.rows = 4; // Optional: Sets the number of visible text lines

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue && property === "message") {
                    // Replace escaped newlines with actual newlines for display in textarea
                    // Only for 'message' property
                    textareaField.value = hiddenPropertyValue.value.replace(/\\n/g, "\n");
                } else if (hiddenPropertyValue) {
                    // For other properties, use the value as is
                    textareaField.value = hiddenPropertyValue.value;
                }

                // Append label and textarea to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(textareaField);

                // Create and append the formatted text block
                var textBlock = document.createElement("div");
                textBlock.className = 'property-description'; // Set the class name

                // Main heading
                var heading = document.createElement("p");
                heading.textContent = "Merge Tags:";
                textBlock.appendChild(heading);

                // Subheading 1
                var subHeading1 = document.createElement("p");
                subHeading1.innerHTML = "<strong>Product from previous step:</strong> &lt;product_from_previous_step&gt;";
                textBlock.appendChild(subHeading1);

                // Subheading 2
                var subHeading2 = document.createElement("p");
                subHeading2.innerHTML = "<strong>Category from previous step:</strong> &lt;category_from_previous_step&gt;";
                textBlock.appendChild(subHeading2);

                propertiesPanel.appendChild(textBlock);

                // Update properties object on textarea change
                textareaField.addEventListener("input", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            } else if (property === "start_date") {
                labelElement.textContent = "Start Date:";

                // Create date input field
                var dateInputField = document.createElement("input");
                dateInputField.type = "date"; // Date input type
                dateInputField.name = property;

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    dateInputField.valueAsDate = new Date(DOMPurify.sanitize(hiddenPropertyValue.value)); // Sanitize value
                }

                // Append label and input to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dateInputField);

                // Update properties object on input change
                dateInputField.addEventListener("input", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);

                });
            } else if (property === "add_remove") {
                labelElement.textContent = "Add/remove:";

                // Create a select dropdown element
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Define options for add/remove
                const addRemoveOptions = ["add", "remove"];

                // Create and append the options
                addRemoveOptions.forEach(function (optionValue) {
                    var option = document.createElement("option");
                    option.value = optionValue;
                    option.textContent = optionValue;
                    dropdown.appendChild(option);
                });

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    dropdown.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            } else if (property === "product_tag") {
                labelElement.textContent = "Product Tag:";

                // Create input field as before
                var inputField = document.createElement("input");
                inputField.name = property;
                inputField.placeholder = "Enter Product Tag";

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    inputField.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and input to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(inputField);

                // Update properties object on input change
                inputField.addEventListener("input", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            }
            else if (property === "product_badge") {
                var custom_product_badge = blockElement.querySelector(`.hidden-properties [name="custom_product_badge"]`);
                var product_badge = blockElement.querySelector(`.hidden-properties [name="product_badge"]`);

                labelElement.textContent = "Product Badge:";
                propertiesPanel.appendChild(labelElement);

                // Create a select dropdown element
                var dropdown = document.createElement("select");
                dropdown.name = property;

                ["none", "sale", "new", "popular", "custom"].forEach((opt, index) => {
                    const option = new Option(opt, opt);
                    if (opt === 'custom' && custom_product_badge && product_badge && custom_product_badge.value === product_badge.value) {
                        option.selected = true; // Set selected attribute for 'custom' option
                    }
                    dropdown.add(option);
                });

                propertiesPanel.appendChild(dropdown);


                // Retrieve and set the initial value if it exists
                const hiddenProperty = blockElement.querySelector(`.hidden-properties [name="${property}"]`);
                if (hiddenProperty && (custom_product_badge.value !== product_badge.value)) {
                    dropdown.value = DOMPurify.sanitize(hiddenProperty.value);
                }

                // Create and append custom badge text input
                var customInput = document.createElement("input");
                customInput.type = "text";
                customInput.name = "custom_product_badge"; // Unique name for the input
                customInput.style.display = "none";
                customInput.style.marginTop = "10px";
                customInput.placeholder = "Custom badge text";

                if (custom_product_badge) {
                    customInput.value = DOMPurify.sanitize(custom_product_badge.value);
                }

                propertiesPanel.appendChild(customInput);

                // Event listener to show/hide custom input
                dropdown.addEventListener("change", function () {
                    const isCustom = dropdown.value === "custom";
                    customInput.style.display = isCustom ? "block" : "none";
                    properties[property] = dropdown.value;
                });
                // Immediately set visibility of customInput on page load or when dropdown is initialized
                const isCustomSelected = dropdown.value === "custom";
                customInput.style.display = isCustomSelected ? "block" : "none";

            }else if (property === "display_id") {
                labelElement.textContent = "Display ID (Find in 'Product Displays'):";
                var inputField = document.createElement("input");
                inputField.name = property;
                inputField.placeholder = property;

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    inputField.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and input to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(inputField);

                // Update properties object on input change
                inputField.addEventListener("input", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            }
            
            else if (property === "perc_amount") {
                labelElement.textContent = "Amount:";
                
                // Create container for dropdown and input
                var container = document.createElement("div");
            
                // Create dropdown for selecting the type
                var dropdown = document.createElement("select");
                dropdown.name = "amount_type";
            
                var specificOption = document.createElement("option");
                specificOption.value = "specific";
                specificOption.textContent = "Specific";
                dropdown.appendChild(specificOption);
            
                var percentageOption = document.createElement("option");
                percentageOption.value = "percentage";
                percentageOption.textContent = "Percentage +/-";
                dropdown.appendChild(percentageOption);
            
                // Append dropdown to container
                container.appendChild(dropdown);
            
                // Create a container for the input field
                var inputContainer = document.createElement("div");
            
                // Create specific input field
                var specificInput = document.createElement("input");
                specificInput.type = "number";
                specificInput.name = property;
                specificInput.placeholder = "Value";
                specificInput.style.maxWidth = "100px"; // Set max-width
            
                // Create percentage input field wrapper
                var percentageWrapper = document.createElement("div");
                percentageWrapper.style.display = "flex";
                percentageWrapper.style.alignItems = "center";
            
                var percentageInput = document.createElement("input");
                percentageInput.type = "number";
                percentageInput.name = property;
                percentageInput.placeholder = "+/-";
                percentageInput.style.flex = "1";
                percentageInput.style.maxWidth = "100px"; // Set max-width
            
                var percentageSymbol = document.createElement("span");
                percentageSymbol.textContent = "%";
                percentageSymbol.style.marginLeft = "5px";
            
                // Append percentage input and symbol to wrapper
                percentageWrapper.appendChild(percentageInput);
                percentageWrapper.appendChild(percentageSymbol);
            
                // Create percentage explanation text
                var percentageText = document.createElement("div");
                percentageText.textContent = "Choose % increase/decrease from previous X days. Use negative % for decrease";
                percentageText.style.fontSize = "small";
                percentageText.style.color = "#666";
                percentageText.style.display = "none"; // Hide by default
            
                // Create specific explanation text
                var specificText = document.createElement("div");
                specificText.textContent = "Use 0 for none";
                specificText.style.fontSize = "small";
                specificText.style.color = "#666";
                specificText.style.display = "none"; // Hide by default
            
                // Set the initial value for the dropdown and inputs from block's hidden-properties
                var hiddenAmountType = blockElement.querySelector('.hidden-properties [name="amount_type"]');
                var hiddenPropertyValue = blockElement.querySelector('.hidden-properties [name="perc_amount"]');
                
                if (hiddenAmountType) {
                    dropdown.value = DOMPurify.sanitize(hiddenAmountType.value);
                } else {
                    dropdown.value = "specific"; // Default to specific if no value is set
                }
                
                if (hiddenPropertyValue) {
                    var sanitizedValue = DOMPurify.sanitize(hiddenPropertyValue.value);
                    specificInput.value = sanitizedValue.replace(' times', '');
                    percentageInput.value = sanitizedValue.replace('%', '');
                }
            
                // Append initial input to input container
                if (dropdown.value === "specific") {
                    inputContainer.appendChild(specificInput);
                    specificText.style.display = "block"; // Show specific text
                } else if (dropdown.value === "percentage") {
                    inputContainer.appendChild(percentageWrapper);
                    percentageText.style.display = "block"; // Show percentage text
                }
            
                // Append elements to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(container);
                propertiesPanel.appendChild(inputContainer);
                propertiesPanel.appendChild(percentageText);
                propertiesPanel.appendChild(specificText);
            
                // Update properties object on input change
                specificInput.addEventListener("input", function (event) {
                    if (dropdown.value === "specific") {
                        properties[property] = DOMPurify.sanitize(event.target.value);

                    }
                });
            
                percentageInput.addEventListener("input", function (event) {
                    if (dropdown.value === "percentage") {
                        properties[property] = DOMPurify.sanitize(event.target.value) + '%';
                    }
                });
            
                // Toggle inputs and text based on dropdown selection
                dropdown.addEventListener("change", function () {
                    inputContainer.innerHTML = ""; // Clear input container
                    if (dropdown.value === "specific") {
                        inputContainer.appendChild(specificInput); // Add specific input to DOM
                        specificText.style.display = "block"; // Show specific text
                        percentageText.style.display = "none"; // Hide percentage text
                    } else if (dropdown.value === "percentage") {
                        inputContainer.appendChild(percentageWrapper); // Add percentage input to DOM
                        specificText.style.display = "none"; // Hide specific text
                        percentageText.style.display = "block"; // Show percentage text
                    }
                });
            }else if (property === "above_below") {
                labelElement.textContent = "Above/below:";

                // Create a select dropdown element
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Define options for above/below
                const aboveBelowOptions = ["above", "below"];

                // Create and append the options
                aboveBelowOptions.forEach(function (optionValue) {
                    var option = document.createElement("option");
                    option.value = optionValue;
                    option.textContent = optionValue;
                    dropdown.appendChild(option);
                });

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    dropdown.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            }else if (property === "show_hide") {
                labelElement.textContent = "Show/Hide:";

                // Create a select dropdown element
                var dropdown = document.createElement("select");
                dropdown.name = property;

                // Define options for show/hide
                const showHideOptions = ["show", "hide"];

                // Create and append the options
                showHideOptions.forEach(function (optionValue) {
                    var option = document.createElement("option");
                    option.value = optionValue;
                    option.textContent = optionValue;
                    dropdown.appendChild(option);
                });

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    dropdown.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and dropdown to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(dropdown);

                // Update properties object on dropdown change
                dropdown.addEventListener("change", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            }else if (property === "coupon_id") {
                labelElement.textContent = "Coupon Code:";
                var inputField = document.createElement("input");
                inputField.name = property;
                inputField.placeholder = "code";

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    inputField.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and input to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(inputField);

                // Update properties object on input change
                inputField.addEventListener("input", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);
                });
            }
            
            else {
                labelElement.textContent = DOMPurify.sanitize(property); // Sanitize property
                // Create input field as before
                var inputField = document.createElement("input");
                inputField.name = property;
                inputField.placeholder = property;

                // Set the initial value from block's hidden-properties
                var hiddenPropertyValue = blockElement.querySelector(
                    `.hidden-properties [name="${property}"]`
                );
                if (hiddenPropertyValue) {
                    inputField.value = DOMPurify.sanitize(hiddenPropertyValue.value);
                }

                // Append label and input to properties panel
                propertiesPanel.appendChild(labelElement);
                propertiesPanel.appendChild(inputField);

                // Update properties object on input change
                inputField.addEventListener("input", function (event) {
                    properties[property] = DOMPurify.sanitize(event.target.value);

                });
            }
        }
    }
}


/**
 * Function to save properties of the selected block.
 * 
 * This function retrieves the selected block and the properties panel elements. 
 * It processes each form element (input, select, textarea) within the properties panel, 
 * sanitizes their values, and updates the hidden properties of the selected block. 
 * Additionally, it updates various display elements within the block to reflect the 
 * newly saved properties.
 * 
 * Steps:
 * 1. Retrieves the selected block, properties panel, and hidden properties div.
 * 2. Processes each form element in the properties panel:
 *    a. Escapes merge tags for sanitization.
 *    b. Sanitizes the input values.
 *    c. Unescapes merge tags after sanitization.
 *    d. Creates hidden inputs with sanitized values.
 * 3. Updates display elements within the block to reflect the new property values.
 * 4. Displays an alert to notify that the step has been updated.
 */
function saveProperties() { 
    var block = document.querySelector(".selectedblock");
    if (!block) {
        return;
    }

    var propertiesPanel = document.getElementById("proplist");
    if (!propertiesPanel) {
        return;
    }

    var formElements = propertiesPanel.querySelectorAll("input, select, textarea, .radioGroupClass");

    var hiddenPropertiesDiv = block.querySelector(".hidden-properties");
    if (!hiddenPropertiesDiv) {
        return;
    }

    var productIDDisplay = block.querySelector(".product-id-display");
    var myParamDisplay = block.querySelector(".my-param-display");
    var myParam2Display = block.querySelector(".my-param-2");
    var sinceDaysDisplay = block.querySelector(".sinceDays");
    var amountDisplay = block.querySelector(".amountDisplay");
    var idDisplay = block.querySelector(".idDisplay");
    var catIdDisplay = block.querySelector(".catIdDisplay");
    var datapointDisplay = block.querySelector(".datapointDisplay");
    var percentageDisplay = block.querySelector(".percentageDisplay");
    var priceDisplay = block.querySelector(".priceDisplay");
    var valueDisplay = block.querySelector(".valueDisplay");
    var statusDisplay = block.querySelector(".statusDisplay");
    var emailDisplay = block.querySelector(".emailDisplay");
    var messageDisplay = block.querySelector(".messageDisplay");
    var dateDisplay = block.querySelector(".dateDisplay");
    var ptagDisplay = block.querySelector(".ptagDisplay");
    var addRemoveDisplay = block.querySelector(".addRemoveDisplay");
    var badgeDisplay = block.querySelector(".badgeDisplay");
    var productFilterDisplay = block.querySelector(".productFilterDisplay"); 
    var aboveBelowDisplay = block.querySelector(".above-below-display"); 
    var showHideDisplay = block.querySelector(".show-hide-display"); 
    var paramsContainer = block.querySelector(".params-container"); 

    // First, try to find the input element with the name 'any_event'
    var anyEventInputElement = hiddenPropertiesDiv.querySelector('input[name="any_event"]');

    // Check if the anyEventInputElement exists
    if (anyEventInputElement) {
        // If it exists, set the innerHTML of hiddenPropertiesDiv to the outerHTML of anyEventInputElement
        hiddenPropertiesDiv.innerHTML = anyEventInputElement.outerHTML;
    } else {
        // If the element does not exist, reset the innerHTML to an empty string
        hiddenPropertiesDiv.innerHTML = "";
    }

    var productFiltersHandled = false; // Flag to indicate if product_filters has been handled

    formElements.forEach(function (element) {
        var elementName = element.name;
        var elementValue = element.value;
        
        if (element.classList.contains("radioGroupClass")) {
            // Handle radio group class separately
            var selectedFilters = {};
            element.querySelectorAll(".productFilterInput").forEach(function (input) {
                if (input.type === "checkbox") {
                    selectedFilters[input.name] = DOMPurify.sanitize(input.checked ? "1" : "0"); // Sanitize values here
                } else {
                    selectedFilters[input.name] = DOMPurify.sanitize(input.value); // Sanitize values here
                }
            });
            elementValue = JSON.stringify(selectedFilters);

            // Create a single hidden input for product_filters
            var hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = "product_filters";
            hiddenInput.value = elementValue;
        
            hiddenPropertiesDiv.appendChild(hiddenInput);
        
            // Update the display text for product-filter-display
            var displayText = Object.entries(selectedFilters)
                .filter(([key, value]) => (key === "in_category" || (value !== "0" && value !== "")) && key !== "show_in_category" && key !== "show_with_tag")
                .map(([key, value]) => {
                    if (key === "in_category") {
                        var categorySelect = element.querySelector('select[name="in_category"]');
                        var selectedOption = categorySelect.options[categorySelect.selectedIndex];
                        return value === "" ? "in category: category from previous step" : `in category: ${selectedOption.textContent}`;
                    } else if (key === "sortby") {
                        var sortbySelect = element.querySelector('select[name="sortby"]');
                        var selectedSortOption = sortbySelect.options[sortbySelect.selectedIndex];
                        return `${selectedSortOption.textContent}`;
                    } else if (key === "on_sale" || key === "featured") {
                        return key.replace(/_/g, " ");
                    }
                    return `${key.replace(/_/g, " ")}: ${value}`;
                })
                .join(", ");
        
            // Sanitize the display text using DOMPurify before inserting it into the DOM
            var sanitizedDisplayText = DOMPurify.sanitize(displayText);
        
            productFilterDisplay.textContent = sanitizedDisplayText || "No filters selected";
        
            productFiltersHandled = true; // Set the flag to indicate product_filters has been handled
            return; // Skip further processing for this element
        }
        
        // Skip individual inputs within radioGroupClass if product_filters has already been handled
        if (element.classList.contains("productFilterInput") && productFiltersHandled) {
            return;
        }

        // Function to process merge tags (escape/unescape)
        function processMergeTags(value, escape = true) {
            const mergeTags = ['<product_from_previous_step>', '<category_from_previous_step>'];
            mergeTags.forEach(tag => {
                const originalTag = escape ? tag : tag.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const processedTag = escape ? tag.replace(/</g, '&lt;').replace(/>/g, '&gt;') : tag;
                value = value.replace(new RegExp(originalTag, 'g'), processedTag);
            });
            return value;
        }

        // Escape the merge tags before sanitizing
        elementValue = processMergeTags(elementValue, true);
        // Sanitize the input value
        elementValue = DOMPurify.sanitize(elementValue);
        // Unescape the merge tags after sanitizing
        elementValue = processMergeTags(elementValue, false);

        // Check if 'custom' is selected in the product_badge dropdown
        if (elementName === "product_badge" && element.value === "custom") {
            var customValue = propertiesPanel.querySelector('input[name="custom_product_badge"]').value.trim();
            customValue = DOMPurify.sanitize(customValue);
            if (customValue !== "") {
                elementValue = customValue;
            }
        }

        // Create a hidden input element within the block's HTML
        var hiddenInput = document.createElement("input");
        hiddenInput.type = "hidden";
        hiddenInput.name = elementName;
        hiddenInput.value = elementValue;
        hiddenPropertiesDiv.appendChild(hiddenInput);

        // Update the display ba sed on the element's name
        if (elementName === "product_id") {
            productIDDisplay.textContent = element.tagName.toLowerCase() === "select"
                ? element.options[element.selectedIndex].text
                : elementValue;
        
            if (elementValue == "") {
                paramsContainer.setAttribute("style", "display: none;"); // Make display style permanent
            } else {
                paramsContainer.setAttribute("style", "display: inline;"); // Make display style permanent
            }
        }
        else if (elementName === "my_param") {
            myParamDisplay.textContent = elementValue;
        } else if (elementName === "my_param_2") {
            myParam2Display.textContent = elementValue;
        } else if (elementName === "days") {
            sinceDaysDisplay.textContent = elementValue;
        } else if (elementName === "perc_amount") {
            const amountTypeElement = propertiesPanel.querySelector('select[name="amount_type"]');
            const amountType = amountTypeElement ? amountTypeElement.value : "specific";
            if (amountType === "specific") {
                amountDisplay.textContent = elementValue + " times";
            } else if (amountType === "percentage") {
                const amountValue = parseFloat(elementValue.replace('%', ''));
                amountDisplay.textContent = (amountValue >= 0 ? "+" : "") + amountValue + "%";
            }
        } else if (elementName === "amount") {
            amountDisplay.textContent = elementValue;
        } else if (elementName === "coupon_id") {
            idDisplay.textContent = elementValue;

            if (elementValue == "") {
                paramsContainer.setAttribute("style", "display: none;"); // Make display style permanent
            } else {
                paramsContainer.setAttribute("style", "display: inline;"); // Make display style permanent
            }

        } else if (elementName === "cat_id") {
            catIdDisplay.textContent = element.tagName.toLowerCase() === "select"
                ? element.options[element.selectedIndex].text
                : elementValue;

                if (elementValue == "") {
                    paramsContainer.setAttribute("style", "display: none;"); // Make display style permanent
                } else {
                    paramsContainer.setAttribute("style", "display: inline;"); // Make display style permanent
                } 

        } else if (elementName === "datapoint") {
            datapointDisplay.textContent = elementValue;
        } else if (elementName === "percentage") {
            percentageDisplay.textContent = elementValue;
        } else if (elementName === "price") {
            priceDisplay.textContent = elementValue;
        } else if (elementName === "enable_disable") {
            valueDisplay.textContent = elementValue;
        } else if (elementName === "product_status") {
            statusDisplay.textContent = elementValue;
        } else if (elementName === "email") {
            emailDisplay.textContent = elementValue;
        } else if (elementName === "start_date") {
            var dateParts = elementValue.split("-");
            var dateObject = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
            var day = dateObject.getDate();
            var month = dateObject.toLocaleString("default", { month: "long" });
            var year = dateObject.getFullYear();
            var suffix = ["th", "st", "nd", "rd"][(day % 100 - 20) % 10] || ["th", "st", "nd", "rd"][day] || "th";
            dateDisplay.textContent = `${day}${suffix} ${month} ${year}`;
        } else if (elementName === "product_tag") {
            ptagDisplay.textContent = "'" + elementValue + "'";
        } else if (elementName === "add_remove") {
            addRemoveDisplay.textContent = elementValue;
        } else if (elementName === "product_badge") {
            badgeDisplay.textContent = elementValue;
        } else if (elementName === "above_below") {
            aboveBelowDisplay.textContent = elementValue;
        } else if (elementName === "show_hide") {
            showHideDisplay.textContent = elementValue;
        }
    });

   // alert('Step updated, remember to "Save Current"');

// Select the message element within the 'saveProperties' div
var messageElement = document.getElementById('saveMessage');

// Show the message element after the button click
if (messageElement) {
    messageElement.style.display = "block";
}

// Remove the message after a short delay
setTimeout(function() {
    if (messageElement) {
        messageElement.style.display = "none";
    }
}, 3000);



}