const proEventBlocksAvailable = typeof eventBlocksMainPro !== 'undefined' && AETPActiveStatus;

const eventBlocksMain = `

<div class="defaultblock leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="calculate_product">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/database.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">Find Product</p>
                <p class="blockdesc">Find a product to use in next steps</p>
            </div>
        </div>
    </div>
</div> 

<div class="defaultblock leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="calculate_category">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/database.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">Find Category</p>
                <p class="blockdesc">Find a Category to use in next steps</p>
            </div>
        </div>
    </div>
</div>

<!-- wait_x_days -->
<div class="defaultblock leftblockelem">
    <div class=" blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="wait_x_days">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/time.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">Wait X Days</p>
                <p class="blockdesc">Wait for X days</p>
            </div>
        </div>
    </div>
</div>
<!-- on_set_date -->
<div class="defaultblock leftblockelem">
    <div class=" blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="on_set_date">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/time.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">On set date</p>
                <p class="blockdesc">Trigger event on a set date</p>
            </div>
        </div>
    </div>
</div>
<div class="defaultblock leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="end_workflow">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/stop.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">End or repeat workflow</p>
                <p class="blockdesc">Use to end or allow repeat of the workflow</p>
            </div>
        </div>
    </div>
</div> 

<!-- parent dropdowns start here -->

<div class="block-group">

    <div class="block-head">
        <div class="droparrow"><img src="${assetURL}assets/dropdown-arrow.svg"></div>
        <p class="block-parent-title">Product WHEN Conditions</p>
    </div>

 <!-- Child Block: Product Stock Above/Below -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="product_stock">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Product Stock Above/Below</p>
                    <p class="blockdesc">Specific product stock above/below</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Child Block: Any Product Stock Above/Below -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="any_product_stock">
            <input type="hidden" name="any_event" class="any_event" value="1">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Any Product Stock Above/Below</p>
                    <p class="blockdesc">Any product stock above/below</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Child Block: Product purchased X times -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="product_purchased">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Product purchased X times</p>
                    <p class="blockdesc">Specific product purchased X times</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Child Block: Any product purchased X times -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="any_product_purchased">
            <input type="hidden" name="any_event" class="any_event" value="1">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Any product purchased X times</p>
                    <p class="blockdesc">Any product purchased X times</p>
                </div>
            </div>
        </div>
    </div>
    <!-- product_viewed -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="product_viewed">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Product Viewed</p>
                    <p class="blockdesc">Specific product viewed X times</p>
                </div>
            </div>
        </div>
    </div>
    <!-- any_product_viewed -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="any_product_viewed">
            <input type="hidden" name="any_event" class="any_event" value="1">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Any Product Viewed</p>
                    <p class="blockdesc">Any product viewed X times</p>
                </div>
            </div>
        </div>
    </div>
    <!-- product_cat_viewed -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="product_cat_viewed">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Product Category Viewed</p>
                    <p class="blockdesc">Specific product category viewed X times</p>
                </div>
            </div>
        </div>
    </div>
    <!-- any_product_cat_viewed -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="any_product_cat_viewed">
            <input type="hidden" name="any_event" class="any_event" value="1">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Any Product Category Viewed</p>
                    <p class="blockdesc">Any product category viewed X times</p>
                </div>
            </div>
        </div>
    </div>
    <!-- product_older_than -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="product_older_than">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Product older than</p>
                    <p class="blockdesc">Specific Product is older than X days</p>
                </div>
            </div>
        </div>
    </div>
    <!-- any_product_older_than -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="any_product_older_than">
            <input type="hidden" name="any_event" class="any_event" value="1">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Any product older than</p>
                    <p class="blockdesc">Any product is older than X days</p>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="block-group">

    <div class="block-head">
        <div class="droparrow"><img src="${assetURL}assets/dropdown-arrow.svg"></div>
        <p class="block-parent-title">Store WHEN Conditions</p>
    </div>

        <!-- Child Block: X Purchases Made -->
    <div class="leftblockelem">
        <div class="blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="x_purchases_made">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">X Purchases Made</p>
                    <p class="blockdesc">X overall store purchases made in X days</p>
                </div>
                
            </div>
        </div>
    </div>

    
    
    <!-- any_rec_product_clicked -->
<div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="any_rec_product_clicked">
        <input type="hidden" name="any_event" class="any_event" value="1">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">Recommended Product Clicked</p>
                <p class="blockdesc">Any recommended product clicked X times</p>
            </div>
            <!-- Tooltip icon -->
            <div class="tooltip-icon">
                <img src="${assetURL}assets/info-icon.png" alt="info">
                <div class="tooltip-image">
                    <img src="${assetURL}assets/recommended_products.png" alt="tooltip">
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- x_new_users -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="x_new_users">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">X New Users</p>
                    <p class="blockdesc">X new users in last X days</p>
                </div>
            </div>
        </div>
    </div>

<!-- coupon_used -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="coupon_used">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Coupon Used</p>
                    <p class="blockdesc">Specific Coupon used X times</p>
                </div>
            </div>
        </div>
    </div>

    <!-- any_coupon_used -->
    <div class="leftblockelem">
        <div class=" blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="any_coupon_used">
            <input type="hidden" name="any_event" class="any_event" value="1">
            <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
            <div class="blockin">
                <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
                <div class="blocktext">
                    <p class="blocktitle">Any coupon used</p>
                    <p class="blockdesc">Any coupon used X times</p>
                </div>
            </div>
        </div>
    </div>

    <!-- upsell_clicked -->
  <div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="upsell_clicked">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/eye.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">Upsell clicked</p>
                <p class="blockdesc">Current upsell offer clicked X times</p>
            </div>
            <!-- Tooltip icon -->
            <div class="tooltip-icon">
                <img src="${assetURL}assets/info-icon.png" alt="info">
                <div class="tooltip-image">
                    <img src="${assetURL}assets/upsell_popup.png" alt="tooltip">
                </div>
            </div>
        </div>
    </div>
</div>

</div>
`;  