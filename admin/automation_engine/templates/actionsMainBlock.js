const proActionBlocksAvailable = typeof actionBlocksMainPro !== 'undefined' && AETPActiveStatus;

const actionBlocksMain = `
    <!-- Block 1 -->
    <div class="block-group">
        <div class="block-head">
            <div class="droparrow"><img src="${assetURL}assets/dropdown-arrow.svg"></div>
            <p class="block-parent-title">Product</p>
        </div>

        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="add_remove_tag">

                <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>

                <div class="blockin">
                    <div class="blockico"><span></span><img src="${assetURL}assets/action.svg"></div>
                    <div class="blocktext">
                        <p class="blocktitle">Add/remove product tag</p>
                        <p class="blockdesc">Add/remove tag from product</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="put_product_on_sale">

                <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>

                <div class="blockin">
                    <div class="blockico"><span></span><img src="${assetURL}assets/action.svg"></div>
                    <div class="blocktext">
                        <p class="blocktitle">Put product on sale</p>
                        <p class="blockdesc">Put specific product on sale</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="take_product_off_sale">

                <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>

                <div class="blockin">
                    <div class="blockico"><span></span><img src="${assetURL}assets/action.svg"></div>
                    <div class="blocktext">
                        <p class="blocktitle">Take product off sale</p>
                        <p class="blockdesc">Take specific product off sale</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="change_default_price">

                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>

                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Change default price</p>
                        <p class="blockdesc">Change specific product's default price</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="show_hide_product">
                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>

                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Show/hide product from shop</p>
                        <p class="blockdesc">Show/hide on archive pages</p>
                    </div>
                </div>
            </div>
        </div>


      <div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="product_to_recommended">
        <div class="grabme">
            <img src="${assetURL}assets/grabme.svg">
        </div>
        <div class="blockin">
            <div class="blockico">
                <span></span>
                <img src="${assetURL}assets/action.svg">
            </div>
            <div class="blocktext">
                <p class="blocktitle">Add product to recommended</p>
                <p class="blockdesc">Show product at the top of the store</p>
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

       <div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="product_off_recommended">
        <div class="grabme">
            <img src="${assetURL}assets/grabme.svg">
        </div>
        <div class="blockin">
            <div class="blockico">
                <span></span>
                <img src="${assetURL}assets/action.svg">
            </div>
            <div class="blocktext">
                <p class="blocktitle">Remove product from recommended</p>
                <p class="blockdesc">Remove product from top of store</p>
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
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="make_product_featured">

                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>

                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Make product featured</p>
                        <p class="blockdesc">Make specific product featured</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="make_product_not_featured">

                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>

                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Make product not featured</p>
                        <p class="blockdesc">Make specific product not featured</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="change_product_status">

                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>

                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Change product status</p>
                        <p class="blockdesc">Change specific product's status</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="change_product_display">

                <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>

                <div class="blockin">
                    <div class="blockico"><span></span><img src="${assetURL}assets/action.svg"></div>
                    <div class="blocktext">
                        <p class="blocktitle">Change Product Display</p>
                        <p class="blockdesc">Change product used in Product Display</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="change_product_badge">
        <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>
        <div class="blockin">
            <div class="blockico"><span></span><img src="${assetURL}assets/action.svg"></div>
            <div class="blocktext">
                <p class="blocktitle">Change product badge</p>
                <p class="blockdesc">Add or remove product badge</p>
            </div>
            <!-- Tooltip icon -->
            <div class="tooltip-icon">
                <img src="${assetURL}assets/info-icon.png" alt="info">
                <div class="tooltip-image">
                    <img src="${assetURL}assets/product_badges.png" alt="tooltip">
                </div>
                
            </div>
        </div>
    </div>
</div>
<div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="display_product_everywhere">

                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>
                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Display product everywhere</p>
                        <p class="blockdesc">Add to upsell & recommended</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
    <!-- Block 2 --> 

    <div class="block-group">
        <div class="block-head">
            <div class="droparrow"><img src="${assetURL}assets/dropdown-arrow.svg"></div>
            <p class="block-parent-title">Category</p>
        </div>

        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="put_category_on_sale">
                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>

                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Put category on sale</p>
                        <p class="blockdesc">Put specific category on sale</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="take_category_off_sale">
                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>
                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Take category off sale</p>
                        <p class="blockdesc">Take specific category off sale</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Block 3 -->
    <div class="block-group">
        <div class="block-head">
            <div class="droparrow"><img src="${assetURL}assets/dropdown-arrow.svg"></div>
            <p class="block-parent-title">Store</p>
        </div>
       
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="put_all_products_on_sale">

                <div class="grabme"><img src="${assetURL}assets/grabme.svg"></div>

                <div class="blockin">
                    <div class="blockico"><span></span><img src="${assetURL}assets/action.svg"></div>
                    <div class="blocktext">
                        <p class="blocktitle">Put all products on sale</p>
                        <p class="blockdesc">Put all products on sale</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="take_all_products_off_sale">

                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>

                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Take all products off sale</p>
                        <p class="blockdesc">Take all products off sale</p>
                    </div>
                </div>
            </div>
        </div>
 <div class="leftblockelem">
            <div class="blockelem create-flowy noselect">
                <input type="hidden" name="blockelemtype" class="blockelemtype" value="send_admin_alert">
                <div class="grabme">
                    <img src="${assetURL}assets/grabme.svg">
                </div>
                <div class="blockin">
                    <div class="blockico">
                        <span></span>
                        <img src="${assetURL}assets/action.svg">
                    </div>
                    <div class="blocktext">
                        <p class="blocktitle">Send admin alert</p>
                        <p class="blockdesc">Send custom message to admin</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="leftblockelem">
        <div class="blockelem create-flowy noselect">
            <input type="hidden" name="blockelemtype" class="blockelemtype" value="change_upsell_product">
            <div class="grabme">
                <img src="${assetURL}assets/grabme.svg">
            </div>
            <div class="blockin">
                <div class="blockico">
                    <span></span>
                    <img src="${assetURL}assets/action.svg">
                </div>
                <div class="blocktext">
                    <p class="blocktitle">Change upsell product</p>
                    <p class="blockdesc">Change upsell product</p>
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
    <div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="enable_disable_upsell">
        <div class="grabme">
            <img src="${assetURL}assets/grabme.svg">
        </div>
        <div class="blockin">
            <div class="blockico">
                <span></span>
                <img src="${assetURL}assets/action.svg">
            </div>
            <div class="blocktext">
                <p class="blocktitle">Enable/disable upsell</p>
                <p class="blockdesc">Enable/disable upsell</p>
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
<div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="change_topbar_text">
        <div class="grabme">
            <img src="${assetURL}assets/grabme.svg">
        </div>
        <div class="blockin">
            <div class="blockico">
                <span></span>
                <img src="${assetURL}assets/action.svg">
            </div>
            <div class="blocktext">
                <p class="blocktitle">Change top bar text</p>
                <p class="blockdesc">Change text in top bar</p>
            </div>
            <!-- Tooltip icon -->
            <div class="tooltip-icon">
                <img src="${assetURL}assets/info-icon.png" alt="info">
                <div class="tooltip-image">
                    <img src="${assetURL}assets/top_bar.png" alt="tooltip">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="leftblockelem">
    <div class="blockelem create-flowy noselect">
        <input type="hidden" name="blockelemtype" class="blockelemtype" value="enable_disable_topbar">
        <div class="grabme">
            <img src="${assetURL}assets/grabme.svg">
        </div>
        <div class="blockin">
            <div class="blockico">
                <span></span>
                <img src="${assetURL}assets/action.svg">
            </div>
            <div class="blocktext">
                <p class="blocktitle">Enable/disable top bar</p>
                <p class="blockdesc">Enable/disable top bar</p>
            </div>
            <!-- Tooltip icon -->
            <div class="tooltip-icon">
                <img src="${assetURL}assets/info-icon.png" alt="info">
                <div class="tooltip-image">
                    <img src="${assetURL}assets/top_bar.png" alt="tooltip">
                </div>
            </div>
        </div>
    </div>
</div>

`;