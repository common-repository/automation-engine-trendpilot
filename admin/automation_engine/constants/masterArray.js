/**
 * Function: buildMasterArray
 * Constructs a master array of step structures based on blocks within a canvas.
 * Each block type corresponds to a specific structure defined in `stepStructures`.
 * Handles type casting and property assignment based on hidden input values.
 *
 * @returns {Array} masterArray - An array where each index represents a block ID
 *                               and contains a structured object based on block type.
 */
function buildMasterArray() { 
    const stepStructures = {
        run_workflow_now: {
            type: "event",
            event: {
                name: "run_workflow_now",
                parameters: {},
            },
        },
        end_workflow: {
            type: "event",
            event: {
                name: "end_workflow",
                parameters: {},
            },
        },
        product_added_to_cart: {
            type: "event",
            event: {
                name: "product_added_to_cart",
                parameters: {
                    product_id: 0, // Placeholder for integer value
                },
            },
        },
        product_not_viewed: {
            type: "event",
            event: {
                name: "product_not_viewed",
                parameters: {
                    product_id: 0,
                    days: 0,
                },
            },
        },
        any_product_not_viewed: {
            type: "event",
            event: {
                name: "any_product_not_viewed",
                parameters: {
                    // product_id: null, // don't need a product id here for 'Any product' - triggering function does not require a product ID.
                    days: 0,
                },
            },
        },
        product_purchased: {
            type: "event",
            event: {
                name: "product_purchased",
                parameters: {
                    product_id: 0,
                    //amount: 0,
                    amount_type: "",
                    perc_amount: 0,
                    days: 0,
                },
            },
        },
        any_product_purchased: {
            type: "event",
            event: {
                name: "any_product_purchased",
                parameters: {
                    amount_type: "",
                    perc_amount: 0,
                    days: 0,
                },
            },
        },
        13: {
            type: "action",
            action: {
                name: "product_added_to_cart2",
                parameters: {
                    my_param: 0,
                    my_param_2: "",
                },
            },
        },
        product_not_purchased: {
            type: "event",
            event: {
                name: "product_not_purchased",
                parameters: {
                    product_id: 0,
                    days: 0,
                },
            },
        },
        any_product_not_purchased: {
            type: "event",
            event: {
                name: "any_product_not_purchased",
                parameters: {
                    days: 0,
                },
            },
        },
        x_purchases_made: {
            type: "event",
            event: {
                name: "x_purchases_made",
                parameters: {
                    amount: 0,
                    days: 0,
                },
            },
        },
        coupon_used: {
            type: "event",
            event: {
                name: "coupon_used",
                parameters: {
                    coupon_id: "",
                    amount: 0,
                    days: 0,
                },
            },
        },
        any_coupon_used: {
            type: "event",
            event: {
                name: "any_coupon_used",
                parameters: {
                    amount: 0,
                    days: 0,
                },
            },
        },
        product_viewed: {
            type: "event",
            event: {
                name: "product_viewed",
                parameters: {
                    product_id: 0,
                    amount_type: "",
                    perc_amount: 0,
                    days: 0,
                },
            },
        },
        any_product_viewed: {
            type: "event",
            event: {
                name: "any_product_viewed",
                parameters: {
                    amount_type: "",
                    perc_amount: 0,
                    days: 0,
                },
            },
        },
        product_cat_viewed: {
            type: "event",
            event: {
                name: "product_cat_viewed",
                parameters: {
                    cat_id: 0,
                    amount_type: "",
                    perc_amount: 0,
                    days: 0,
                },
            },
        },
        any_product_cat_viewed: {
            type: "event",
            event: {
                name: "any_product_cat_viewed",
                parameters: {
                    amount_type: "",
                    perc_amount: 0,
                    days: 0,
                },
            },
        },
        any_rec_product_clicked: {
            type: "event",
            event: {
                name: "any_rec_product_clicked",
                parameters: {
                    amount: 0,
                    days: 0,
                },
            },
        },
        upsell_clicked: {
            type: "event",
            event: {
                name: "upsell_clicked",
                parameters: {
                    amount: 0,
                    days: 0,
                },
            },
        },
        product_older_than: {
            type: "event",
            event: {
                name: "product_older_than",
                parameters: {
                    product_id: 0,
                    days: 0,
                },
            },
        },
        any_product_older_than: {
            type: "event",
            event: {
                name: "any_product_older_than",
                parameters: {
                    days: 0,
                },
            },
        },
        wait_x_days: {
            type: "event",
            event: {
                name: "wait_x_days",
                parameters: {
                    days: 0,
                },
            },
        },
        x_new_users: {
            type: "event",
            event: {
                name: "x_new_users",
                parameters: {
                    amount: 0,
                    days: 0,
                },
            },
        },
        every_x_days: {
            type: "event",
            event: {
                name: "every_x_days",
                parameters: {
                    days: 0,
                    start_date: new Date(),
                },
            },
        },
        calculate_category: {
            type: "event",
            event: {
                name: "calculate_category",
                parameters: {
                    datapoint: "",
                    days: 0,
                },
            },
        },
        put_product_on_sale: {
            type: "action",
            action: {
                name: "put_product_on_sale",
                parameters: {
                    product_id: 0,
                    percentage: 0,
                },
            },
        },
        take_product_off_sale: {
            type: "action",
            action: {
                name: "take_product_off_sale",
                parameters: {
                    product_id: 0,
                },
            },
        },
        put_all_products_on_sale: {
            type: "action",
            action: {
                name: "put_all_products_on_sale",
                parameters: {
                    percentage: 0,
                },
            },
        },
        take_all_products_off_sale: {
            type: "action",
            action: {
                name: "take_all_products_off_sale",
                parameters: {},
            },
        },
        put_category_on_sale: {
            type: "action",
            action: {
                name: "put_category_on_sale",
                parameters: {
                    cat_id: 0,
                    percentage: 0,
                },
            },
        },
        take_category_off_sale: {
            type: "action",
            action: {
                name: "take_category_off_sale",
                parameters: {
                    cat_id: 0,
                },
            },
        },
        change_default_price: {
            type: "action",
            action: {
                name: "change_default_price",
                parameters: {
                    product_id: 0,
                    price: 0.1,
                },
            },
        },
        change_upsell_product: {
            type: "action",
            action: {
                name: "change_upsell_product",
                parameters: {
                    product_id: 0,
                },
            },
        },
        enable_disable_upsell: {
            type: "action",
            action: {
                name: "enable_disable_upsell",
                parameters: {
                    enable_disable: "",
                },
            },
        },
        product_to_recommended: {
            type: "action",
            action: {
                name: "product_to_recommended",
                parameters: {
                    product_id: 0,
                },
            },
        },
        product_off_recommended: {
            type: "action",
            action: {
                name: "product_off_recommended",
                parameters: {
                    product_id: 0,
                },
            },
        },
        display_product_everywhere: {
            type: "action",
            action: {
                name: "display_product_everywhere",
                parameters: {
                    product_id: 0,
                },
            },
        },
        make_product_featured: {
            type: "action",
            action: {
                name: "make_product_featured",
                parameters: {
                    product_id: 0,
                },
            },
        },
        make_product_not_featured: {
            type: "action",
            action: {
                name: "make_product_not_featured",
                parameters: {
                    product_id: 0,
                },
            },
        },
        change_product_status: {
            type: "action",
            action: {
                name: "change_product_status",
                parameters: {
                    product_id: 0,
                    product_status: "",
                },
            },
        },
        send_admin_alert: {
            type: "action",
            action: {
                name: "send_admin_alert",
                parameters: {
                    email: "",
                    message: "",
                },
            },
        },
        on_set_date: {
            type: "event",
            event: {
                name: "on_set_date",
                parameters: {
                    start_date: new Date(), // we are creating a date format here, so that the format is correctly picked up by the parameter panel.
                },
            },
        },
        add_remove_tag: {
            type: "action",
            action: {
                name: "add_remove_tag",
                parameters: {
                    add_remove: "",
                    product_tag: "",
                    product_id: 0,
                },
            },
        },
        change_topbar_text: {
            type: "action",
            action: {
                name: "change_topbar_text",
                parameters: {
                    message: "",
                },
            },
        },
        enable_disable_topbar: {
            type: "action",
            action: {
                name: "enable_disable_topbar",
                parameters: {
                    enable_disable: "",
                },
            },
        },
        change_product_badge: {
            type: "action",
            action: {
                name: "change_product_badge",
                parameters: {
                    product_id: 0,
                    product_badge: "",
                },
            },
        },
        calculate_product: {
            type: "event",
            event: {
                name: "calculate_product",
                parameters: {
                    product_filters: "",
                    days: 0,
                },
            },
        },
        change_product_display: {
            type: "action",
            action: {
                name: "change_product_display",
                parameters: {
                    display_id: "",
                    product_id: 0,
                },
            },
        },
        product_stock: {
            type: "event",
            event: {
                name: "product_stock",
                parameters: {
                    product_id: 0,
                    above_below: "",
                    amount: 0,
                },
            },
        },
        any_product_stock: {
            type: "event",
            event: {
                name: "any_product_stock",
                parameters: {
                    above_below: "",
                    amount: 0,
                },
            },
        },
        show_hide_product: {
            type: "action",
            action: {
                name: "show_hide_product",
                parameters: {
                    product_id: 0,
                    show_hide: "",
                },
            },
        },
        if_product_viewed: {
            type: "if_condition",
            if_condition: {
                name: "if_product_viewed",
                parameters: {
                  
                },
            },
        },
        // Add more block type structures as needed
    };

    const masterArray = [];

    // Get all blocks inside the canvas
    const blocks = document.getElementById("canvas").querySelectorAll(".block");

    blocks.forEach((block) => {
        // Extract hidden inputs
        const blockType = block.querySelector(
            'input[name="blockelemtype"]'
        ).value;
        const blockId = block.querySelector('input[name="blockid"]').value;

        const hiddenPropertiesDiv = block.querySelector(".hidden-properties");
        const hiddenProperties = {};

        if (hiddenPropertiesDiv) {
            hiddenPropertiesDiv.querySelectorAll('input[type="hidden"]').forEach((input) => {
                const name = input.getAttribute("name");
                let value = input.value; // Get the value first
            
                if (name === "product_filters") {
                    try {
                        // Decode the HTML-encoded JSON string
                        const decodedValue = value.replace(/&quot;/g, '"'); 
                        value = JSON.parse(decodedValue);
                    } catch (e) {
                        console.error("Error parsing product_filters:", e);
                    }
                } else {
                    // Temporarily replace placeholders with unique tokens
                    value = value.replace(/<product_from_previous_step>/g, '###PRODUCT_PLACEHOLDER###')
                                 .replace(/<category_from_previous_step>/g, '###CATEGORY_PLACEHOLDER###');
        
                    // Sanitize the value
                    value = DOMPurify.sanitize(value);
        
                    // Replace tokens back to original placeholders
                    value = value.replace(/###PRODUCT_PLACEHOLDER###/g, '<product_from_previous_step>')
                                 .replace(/###CATEGORY_PLACEHOLDER###/g, '<category_from_previous_step>');
                }
            
                hiddenProperties[name] = value; // Store sanitized or parsed value
            });            
            
        }

        // Get the template structure for the block type
        const stepStructure = JSON.parse(
            JSON.stringify(stepStructures[blockType])
        );

        // Determine the key to use (either 'event' or 'action')
        const key = stepStructure.type === "event" ? "event" : "action";

        // Fill in the properties with type casting
        for (const property in hiddenProperties) {
            if (stepStructure[key].parameters.hasOwnProperty(property)) {
                let inputValue = hiddenProperties[property]; // Declare inputValue with let

                // If the property is 'message', replace line breaks
                if (property === 'message' && typeof inputValue === 'string') {
                    inputValue = inputValue.replace(/(\r\n|\n|\r)/gm, "\\n");
                }

                const originalValue = stepStructure[key].parameters[property];

                // Cast the value to the same type as the original value
                if (typeof originalValue === "number") {
                    stepStructure[key].parameters[property] =
                        originalValue % 1 === 0
                            ? parseInt(inputValue, 10)
                            : parseFloat(inputValue);
                } else if (originalValue instanceof Date) {
                    stepStructure[key].parameters[property] = new Date(inputValue);
                } else {
                    stepStructure[key].parameters[property] = inputValue;
                }
            }
        }


        // Add the processed step to the master array
        masterArray[blockId] = stepStructure;
    });

    return masterArray;
}

/**
 * Function: sanitizeMasterArrayJSON
 * Parses a JSON string into an array, filters out null values, and converts it back to a JSON string.
 *
 * @param {string} masterArrayJSON - A JSON string representing an array.
 * @returns {string} - A sanitized JSON string with null values filtered out.
 */
function sanitizeMasterArrayJSON(masterArrayJSON) {
    // Parse the JSON string to an array
    const masterArray = JSON.parse(masterArrayJSON);

    // Filter out null values
    const sanitizedArray = masterArray.filter(item => item !== null);

    // Convert the array back to a JSON string
    return JSON.stringify(sanitizedArray, null, 2);
}