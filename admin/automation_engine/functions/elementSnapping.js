/**
 * Handles the snapping of draggable elements to the canvas, customizing their inner HTML based on their type.
 * 
 * @param {HTMLElement} drag - The element being dragged.
 * @param {boolean} first - Flag indicating if this is the first block being dragged to the canvas.
 */
function snapping(drag, first) {
    var closestDiv = event.target.closest(".blockelem");
    if (closestDiv) {
        var childInput = closestDiv.querySelector("input.blockelemtype");
        if (childInput) {
            const childInputValue = childInput.value;
            isBlockEvent = testValue(childInputValue);
        }
    }

    /// -----------
    var grab = drag.querySelector(".grabme");
    if (grab) grab.parentNode.removeChild(grab);
    var blockin = drag.querySelector(".blockin");
    if (blockin) blockin.parentNode.removeChild(blockin);

    if (!isBlockEvent && isCanvasEmpty()) {
        alert("Triggers only as first block");
        notEventAndCanvas = true;
    } else {
        notEventAndCanvas = false;
    }

    if (!notEventAndCanvas) {  

        // The list of main canvas blocks (after they are dragged into the cnavs this HTML shows)
        if (drag.querySelector(".blockelemtype").value == "run_workflow_now") {

            drag.innerHTML += `<div class='blockyleft'><img src='${assetURL}assets/playblue.svg'><p class='blockyname'>Start Workflow</p></div><div class='blockyright'><img src='${assetURL}assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Start Workflow</div></div><div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "end_workflow") {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/stopblue.svg" />
                <p class="blockyname">End or repeat workflow</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">End or repeat workflow (if repeat checked)</div>
            <div class="hidden-properties"></div>
            `;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_not_viewed"
        ) { 
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/eyeblue.svg" />
                <p class="blockyname">Product X not viewed</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">Product <span class="product-id-display">X</span> not viewed for <span class="sinceDays">X</span> days</div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Product passed to next steps</div>
                    </div>
                </div>
            </div>
            <div class="hidden-properties"></div>
            `;
        } else if (
            drag.querySelector(".blockelemtype").value == "any_product_not_viewed"
        ) {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/eyeblue.svg" />
                <p class="blockyname">Any product not viewed</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo"><span>Any product</span> is not viewed for <span class="sinceDays">X</span> days</div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Product passed to next steps</div>
                    </div>
                </div>
            </div>
            <div class="hidden-properties"><input type="hidden" name="any_event" value="1" /></div>
            `;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_purchased"
        ) {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/eyeblue.svg" />
                <p class="blockyname">Product purchased X times</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">When product <span class="product-id-display">X</span> is purchased <span class="amountDisplay">X times</span> in the last <span class="sinceDays">X</span> days</div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Product passed to next steps</div>
                    </div>
                </div>
            </div>
            <div class="hidden-properties"></div>
            `;
        } else if (
            drag.querySelector(".blockelemtype").value == "any_product_purchased"
        ) {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/eyeblue.svg" />
                <p class="blockyname">Any product purchased X times</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">When <span>Any product</span> is purchased <span class="amountDisplay">X times</span> in the last <span class="sinceDays">X</span> days</div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Product passed to next steps</div>
                    </div>
                </div>
            </div>
            <div class="hidden-properties"><input type="hidden" name="any_event" value="1" /></div>
            `;
        } else if (
            drag.querySelector(".blockelemtype").value == "calculate_category"
        ) {
            drag.innerHTML += `<div class='blockyleft'><img src='${assetURL}assets/databaseorange.svg'><p class='blockyname'>Find Category</p></div><div class='blockyright'><img src='${assetURL}assets/more.svg'></div><div class='blockydiv'></div><div class='blockyinfo'>Find <span class='datapointDisplay'>X</span> over the last <span class='sinceDays'>X</span> days</div></div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Category passed to next steps</div>
                    </div>
                </div>
            </div><div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "put_product_on_sale"
        ) {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/actionblue.svg" />
                <p class="blockyname">Put product on sale</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">Put <span class="product-id-display">X</span> on sale at <span class="percentageDisplay">X</span>% off</div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Product passed to next steps</div>
                    </div>
                </div>
            </div>
            <div class="hidden-properties"></div>
            `;
        } else if (
            drag.querySelector(".blockelemtype").value == "take_product_off_sale"
        ) {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/actionblue.svg" />
                <p class="blockyname">Take product off sale</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">Take product <span class="product-id-display">X</span> off sale</div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Product passed to next steps</div>
                    </div>
                </div>
            </div>
            <div class="hidden-properties"></div>
            `;
        } else if (
            drag.querySelector(".blockelemtype").value == "put_all_products_on_sale"
        ) {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/actionblue.svg" />
                <p class="blockyname">Put all products on sale</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">Put all products on sale at <span class="percentageDisplay">X</span>% off</div>
            <div class="hidden-properties"></div>
            `;
        } else if (drag.querySelector(".blockelemtype").value == "9") {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/logred.svg" />
                <p class="blockyname">Add new log entry</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">Add new <span>success</span> log entry</div>
            <div class="hidden-properties"></div>
            `;
        } else if (drag.querySelector(".blockelemtype").value == "10") {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/logred.svg" />
                <p class="blockyname">Update logs</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">Edit <span>Log Entry 1</span></div>
            <div class="hidden-properties"></div>
            `;
        } else if (drag.querySelector(".blockelemtype").value == "11") {
            drag.innerHTML += `
            <div class="blockyleft">
                <img src="${assetURL}assets/errorred.svg" />
                <p class="blockyname">Prompt an error</p>
            </div>
            <div class="blockyright"><img src="${assetURL}assets/more.svg" /></div>
            <div class="blockydiv"></div>
            <div class="blockyinfo">Trigger <span>Error 1</span></div>
            <div class="hidden-properties"></div>
            `;
        }

        else if (
            drag.querySelector(".blockelemtype").value ==
            "take_all_products_off_sale"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Take all products off sale</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Take all products off sale</div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "put_category_on_sale"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Put category on sale</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Put category <span class="catIdDisplay">X</span> on sale at <span class='percentageDisplay'>X</span>% off</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Category passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "take_category_off_sale"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Take category off sale</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Take category <span class="catIdDisplay">X</span> off sale</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Category passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_not_purchased"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Product not purchased</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When product <span class='product-id-display'>X</span> is not purchased for <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value ==
            "any_product_not_purchased"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Any product not purchased</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When <span>Any product</span> is not purchased for <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'><input type="hidden" name="any_event" value="1"></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "x_purchases_made"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>X purchases made</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When <span class='amountDisplay'>X</span> purchases made in last <span class='sinceDays'>X</span> days</div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "coupon_used") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Coupon used</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Coupon <span class='idDisplay'>X</span> used <span class='amountDisplay'>X</span> times in last <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Coupon code passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "any_coupon_used"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Any coupon used</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Any coupon used <span class='amountDisplay'>X</span> times in last <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Coupon code passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'><input type="hidden" name="any_event" value="1"></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_viewed"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Product viewed</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When product <span class='product-id-display'>X</span> viewed <span class='amountDisplay'>X times</span> in last <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "any_product_viewed"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Any product viewed</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'><span>When any product</span> viewed <span class='amountDisplay'>X times</span> in last <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'><input type="hidden" name="any_event" value="1"></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_cat_viewed"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Product category viewed</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When product category <span class="catIdDisplay">X</span> viewed <span class='amountDisplay'>X</span> times in last <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Category passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "any_product_cat_viewed"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Any product category viewed</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When any product category viewed <span class='amountDisplay'>X</span> times in last <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Category passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "any_rec_product_clicked"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Recommended product clicked</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When any recommended product clicked <span class='amountDisplay'>X</span> times in last <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'><input type="hidden" name="any_event" value="1"></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "upsell_clicked"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Upsell clicked</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Current upsell offer clicked <span class='amountDisplay'>X</span> times in last <span class='sinceDays'>X</span> days</div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_older_than"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Product older than</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When product <span class='product-id-display'>X</span> is older than <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "any_product_older_than"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>Any product older than</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'><span>When any product</span> is older than <span class='sinceDays'>X</span> days</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'><input type="hidden" name="any_event" value="1"></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "wait_x_days") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/timeblue.svg'>
                      <p class='blockyname'>Wait X days</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Wait <span class='sinceDays'>X</span> days</div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "x_new_users") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/eyeblue.svg'>
                      <p class='blockyname'>X new users</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>When <span class='amountDisplay'>X</span> new users in last <span class='sinceDays'>X</span> days</div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "change_default_price"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Change default price</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Change <span class='product-id-display'>X</span>'s default price to <span class='priceDisplay'>X</span></div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "change_upsell_product"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Change upsell product</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Change upsell product to <span class='product-id-display'>X</span></div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "enable_disable_upsell"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Enable/disable upsell</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'><span class='valueDisplay'>Enable/disable</span> upsell</div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_to_recommended"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Add product to recommended</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Add <span class='product-id-display'>X</span> to recommended</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "product_off_recommended"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Remove product from recommended</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Remove <span class='product-id-display'>X</span> from recommended</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value ==
            "display_product_everywhere"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Display product everywhere</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Show <span class='product-id-display'>X</span> on upsell, recommended & feature</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "make_product_featured"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Make product featured</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Make <span class='product-id-display'>X</span> featured</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value ==
            "make_product_not_featured"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Make product not featured</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Make <span class='product-id-display'>X</span> not featured</div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "change_product_status"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Change product status</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Change <span class='product-id-display'>X</span>'s status to <span class='statusDisplay'>X</span></div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (
            drag.querySelector(".blockelemtype").value == "send_admin_alert"
        ) {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Send admin alert</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Send message to <span class='emailDisplay'>email</span></div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "on_set_date") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/timeblue.svg'>
                      <p class='blockyname'>On set date</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>On date <span class='dateDisplay'>X</span></div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "add_remove_tag") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Add/remove product tag</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'><span class='addRemoveDisplay'>Add/remove</span> product tag <span class='ptagDisplay'>X</span> to/from <span class='product-id-display'>X</span></div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "change_topbar_text") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Change top bar text</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Change text in top bar</div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "enable_disable_topbar") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Enable/disable top bar</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'><span class='valueDisplay'>Enable/disable</span> top bar</div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "change_product_badge") {
            drag.innerHTML += `
                  <div class='blockyleft'>
                      <img src='${assetURL}assets/actionblue.svg'>
                      <p class='blockyname'>Change product badge</p>
                  </div>
                  <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
                  <div class='blockydiv'></div>
                  <div class='blockyinfo'>Change <span class='product-id-display'>product X</span>'s badge to <span class='badgeDisplay'>X</span> </div>
                  <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
                  <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "calculate_product") {
            drag.innerHTML += `
            <div class='blockyleft'>
                <img src='${assetURL}assets/databaseorange.svg'>
                <p class='blockyname'>Find Product</p>
                </div>
            <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
            <div class='blockydiv'></div>
            <div class='blockyinfo'>Find Product <span class='productFilterDisplay'>No Filters Selected</span> in last <span class='sinceDays'>X</span> days</div>
            <div class="params-container">
                <div class="blockydiv"></div>
                <div class="data-tooltips">
                    <div class="tooltip-icon-params">
                        parameters
                        <div class="tooltip-text">Product passed to next steps</div>
                    </div>
                </div>
            </div>
            <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "change_product_display") {
            drag.innerHTML += `
            <div class='blockyleft'>
                <img src='${assetURL}assets/actionblue.svg'> 
                <p class='blockyname'>Change Product Display</p>
                </div>
            <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
            <div class='blockydiv'></div>
            <div class='blockyinfo'>Change Product Display to <span class='product-id-display'>product X</span></div>
            <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
            </div>
            <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "product_stock") {
            drag.innerHTML += `
            <div class='blockyleft'>
                <img src='${assetURL}assets/eyeblue.svg'>
                <p class='blockyname'>Product Stock</p>
                </div>
            <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
            <div class='blockydiv'></div>
            <div class='blockyinfo'>When product <span class='product-id-display'>X</span> stock <span class='above-below-display'>Above/below</span> <span class='amountDisplay'>X</span></div></div>
            <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
            <div class='hidden-properties'></div>`;
        } else if (drag.querySelector(".blockelemtype").value == "any_product_stock") {
            drag.innerHTML += `
            <div class='blockyleft'>
                <img src='${assetURL}assets/eyeblue.svg'>
                <p class='blockyname'>Any Product Stock</p>
                </div>
            <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
            <div class='blockydiv'></div>
            <div class='blockyinfo'>When any product stock <span class='above-below-display'>Above/below</span> <span class='amountDisplay'>X</span></div></div>
            <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
            <div class='hidden-properties'></div>`;
        }else if (drag.querySelector(".blockelemtype").value == "show_hide_product") {
            drag.innerHTML += `
            <div class='blockyleft'>
                <img src='${assetURL}assets/eyeblue.svg'>
                <p class='blockyname'>Show/hide Product</p>
                </div>
            <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
            <div class='blockydiv'></div>
            <div class='blockyinfo'><span class='show-hide-display'>Show/hide</span> Product <span class='product-id-display'>X</span> on archives</div></div>
            <div class="params-container">
                    <div class="blockydiv"></div>
                    <div class="data-tooltips">
                        <div class="tooltip-icon-params">
                            parameters
                            <div class="tooltip-text">Product passed to next steps</div>
                        </div>
                    </div>
                  </div>
            <div class='hidden-properties'></div>`;
        }else if (drag.querySelector(".blockelemtype").value == "if_product_viewed") {
            drag.innerHTML += `
            <div class='blockyleft'>
                <img src='${assetURL}assets/eyeblue.svg'>
                <p class='blockyname'>If Product Viewed</p>
                </div>
            <div class='blockyright'><img src='${assetURL}assets/more.svg'></div>
            <div class='blockydiv'></div>
            <div class='blockyinfo'>If product viewed</div></div>
            <div class='hidden-properties'></div>`; 
        }

    }
    openRightSidePanel();
    return true;
}

/**
 * Function: createMessageLayout
 * Creates and displays a layout for the workflow message, including current step, parameters, and status.
 *
 * Parameters:
 * @param {number} currentStep - The current step number in the workflow.
 * @param {string} parameters - The parameters string containing key-value pairs.
 * @param {string} status - The status of the current step (e.g., completed, in-progress).
 * @param {boolean} isChild - Indicates if the step is a child step.
 * @param {number} childCount - The number of child steps.
 *
 * Variables:
 * - htmlTable: A string to hold the HTML content for the layout.
 * - parameterList: An array to hold formatted parameter key-value pairs.
 * - formattedParameters: A string to hold the formatted parameters.
 * - dataDashboardElement: The HTML element where the layout will be inserted.
 * - canvas: The HTML element representing the canvas.
 * - blockElems: A NodeList of block elements in the canvas.
 */
function createMessageLayout(currentStep, parameters, status, isChild, childCount) {
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
        dataDashboardElement.innerHTML = htmlTable;
    } else {
        // Create new dataDashboardElement and append it to codeEditorView
        dataDashboardElement = document.createElement("div");
        dataDashboardElement.id = "data-dashboard";
        dataDashboardElement.style.fontFamily = "Arial, sans-serif";
        dataDashboardElement.innerHTML = DOMPurify.sanitize(htmlTable);
        codeEditorView.appendChild(dataDashboardElement);
    }

    // add some code here toa add some color to the corresponding current step in the canvas.
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
 * Function: handleRightSwitch
 * Handles the transition to the right view by displaying the code editor and updating various elements' visibility.
 *
 * Parameters: None
 *
 * Variables:
 * - existingBranchDataView: The HTML element for the branch data view.
 * - masterArrayJSON: The JSON string representation of the master array.
 * - finalArrayJSON: The final JSON string with added "unique_id" information.
 */
function handleRightSwitch() {
    document.getElementById("load-child-states").style.display = "inline-block";

    // Hide some elements
    diagramView.style.display = "none";
    codeEditorView.style.display = "block";
    leftCard.style.display = "none";
    document.getElementById("opencard").style.display = "none";

    // Check if branchDataView already exists and has content
    var existingBranchDataView = document.getElementById("codeEditorView");
    if (
        existingBranchDataView &&
        existingBranchDataView.innerHTML.trim() !== ""
    ) {
        // If it exists and has content, just display it
        codeEditorView.style.display = "block";
    } else {

        codeEditorView.innerHTML +=
            "<h2>State of workflow below (N/A if not initiated yet):</div>";

        // Modify class lists based on the rightcard variable
        if (rightcard) {
            rightcard = false;
            document.getElementById("properties").classList.remove("expanded");
            setTimeout(function () {
                document.getElementById("propwrap").classList.remove("itson");
            }, 300);
            tempblock.classList.remove("selectedblock");
        }
    }
}

/**
 * Function: constructLoggerDefaultContent
 * Constructs the default HTML content for the logger by iterating through the template list and generating HTML for each template.
 *
 * Variables:
 * - constructedHTML: A string to accumulate the HTML content.
 * - template: An object representing the current template being processed.
 *
 * Returns:
 * - The constructed HTML as a string.
 */
function constructLoggerDefaultContent() { 
    let constructedHTML = "";

    // Construct HTML for each template
    for (let i = 0; i < templateList.length; i++) {
        let template = templateList[i];
        constructedHTML += `<div class="block-template-item">
                          <input type="hidden" name="blockelemtype" class="blockelemtype" value="templateItem">
                          <input type="hidden" name="listItemNum" class="listItemNum" value="${DOMPurify.sanitize(template.listItem)}">
                          <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
                          <div class="blockin">
                              <div class="blockico"><span></span><img src="${assetURL}assets/database.svg"></div>
                              <div class="blocktext-template-item">
                                <span> <p class="blocktitle">${DOMPurify.sanitize(template.name)}</span><span class="template-li-delete">Delete</span></p>
                              </div>
                          </div>
                      </div>`;
    }

    // Return the sanitized HTML
    return constructedHTML;
}