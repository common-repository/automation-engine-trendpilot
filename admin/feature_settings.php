<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AETP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_settings_section( 'aetp_badge_settings', null, '__return_null', 'ae_plugin' );

// Enqueue the custom hex color picker script
wp_enqueue_script(
	'hex-color-picker',
	plugin_dir_url( __FILE__ ) . 'js/hex-color-picker.js', // Adjust the path if necessary
	array( 'jquery' ), // Ensure jQuery is loaded
	'1.0',
	true // Load in the footer
);

// Build the URL for the WooCommerce settings products page
$woocommerce_settings_products_url = admin_url( 'admin.php?page=wc-settings&tab=products' );

?>

<div class="plugin-set-sect" id="recommended-settings">
	<h1>
		'Recommended' Section Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="<?php echo esc_url( AETRENDPILOT_PLUGIN_URL . 'assets/images/aetp_recommended_products_graphic.png' ); ?>"
					alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">
			</span>
		</span>
	</h1>
	<p class="description">Use the 'Recommended' section to show products first in all of their respective archive pages
		(Shop,
		categories and search)
	</p>
	<p class="description">We've added a new 'order by' option to the Woocommerce shop page. Choose products to
		highlight here.</p>
	<!-- Separate form for adding a product to the recommended list '-->
	<div class="wrap">
		<form method="post" action="#recommended-settings">
			<!-- Nonce field for Recommended Products -->
			<?php wp_nonce_field( 'aetp_add_to_recommended_nonce_action', 'aetp_add_to_recommended_nonce' ); ?>
			<!-- Checkbox to Enable/Disable Recommended Section -->
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Enable Recommended Section</th>
					<td>
						<input type="hidden" name="tpae_enable_disable_recommended" value="0">
						<input type="checkbox" name="tpae_enable_disable_recommended"
							id="tpae_enable_disable_recommended" value="1" <?php echo checked( 1, get_option( 'tpae_enable_disable_recommended', 0 ) ); ?> />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Manually Add Product To Recommended</th>
					<td>
						<select name="aetp_add_to_recommended_product_id">
							<option value="">Select a product:</option>
							<?php
							$args = array(
								'limit' => -1, // Get all products
								'status' => 'publish', // Only get published products
							);
							$products = wc_get_products( $args );
							foreach ( $products as $product ) {
								echo '<option value="' . esc_attr( $product->get_id() ) . '">' . esc_html( $product->get_name() ) . '</option>';
							}
							?>
						</select>
						<p class="description">Select a Product to add it to the recommended list.</p>
					</td>
				</tr>
				<!-- Settings for Total Recommended Products -->
				<tr valign="top">
					<th scope="row">Total Recommended Products</th>
					<td>
						<input type="number" name="tpae_total_recommended_products"
							value="<?php echo esc_attr( get_option( 'tpae_total_recommended_products', 5 ) ); ?>" />
						<p class="description">Set the max number of recommended products allowed at any time. Default
							is 5. New entries will overwrite the oldest-added</p>
					</td>
				</tr>
				<!-- Admin-selected Sorting Method for Remaining Products -->
				<tr valign="top">
					<th scope="row">Sorting Method for Remaining Products</th>
					<td>
						<select name="tpae_remaining_products_orderby">
							<option value="menu_order" <?php selected( get_option( 'tpae_remaining_products_orderby' ), 'menu_order' ); ?>>Menu Order</option>
							<option value="date" <?php selected( get_option( 'tpae_remaining_products_orderby' ), 'date' ); ?>>Recent</option>
							<option value="title" <?php selected( get_option( 'tpae_remaining_products_orderby' ), 'title' ); ?>>Title</option>
							<option value="popularity" <?php selected( get_option( 'tpae_remaining_products_orderby' ), 'popularity' ); ?>>Popularity</option>
							<option value="rating" <?php selected( get_option( 'tpae_remaining_products_orderby' ), 'rating' ); ?>>Rating</option>
						</select>
						<p class="description">Select how to sort the remaining products when "Recommended" is selected.
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Update' ); ?>
		</form>
	</div>

	<!-- New table for displaying the names of recommended products -->
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Current Recommended Products</th>
			<td>
				<ul>
					<?php
					global $wpdb;
					$recommended_table_name = $wpdb->prefix . 'trendpilot_automation_engine_recommended_products';
					$recommended_products = $wpdb->get_col( "SELECT product_id FROM $recommended_table_name" ); // db call ok
					
					foreach ( $recommended_products as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( $product ) {
							echo '<li>' . esc_html( $product->get_name() ) . ' <button class="remove-product" data-product-id="' . (int) $product_id . '">X</button></li>';

						}
					}
					?>
				</ul>
			</td>
		</tr>
	</table>
</div>

<?php
// Enqueue the remove-recommended-product.js script
wp_enqueue_script(
	'remove-recommended-product-js',
	plugin_dir_url( __FILE__ ) . 'js/remove-recommended-product.js',
	array( 'jquery' ), // Ensure jQuery is loaded first as the script uses it
	'1.0',
	true // Load in footer
);

// Localize the script with necessary data
wp_localize_script(
	'remove-recommended-product-js',
	'trendpilotSettings',
	array(
		'ajaxUrl' => esc_url( admin_url( 'admin-ajax.php' ) ),
		'removeProductNonce' => wp_create_nonce( 'tpae_remove_recommended_product_nonce' ),
	)
);
?>


<?php

// Enqueue the tooltip icons JavaScript file
wp_enqueue_script(
	'tooltip-icons',
	plugin_dir_url( __FILE__ ) . 'js/tooltip-icons.js',
	array(),
	'1.0',
	true
);

?>

<!-- Upsell Settings HTML -->
<div class="plugin-set-sect" id="upsell-settings">
	<h1>
		Upsell Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="<?php echo esc_url( AETRENDPILOT_PLUGIN_URL . 'assets/images/Upsell Popup.png' ); ?>"
					alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">

			</span> 
		</span>
	</h1>
	<p class="description">Popup shows upon Add to Cart on product page.</p>
	<form method="post" action="options.php#upsell-settings">
		<?php settings_fields( 'aetp_upsell_settings' );
		do_settings_sections( 'aetp_upsell_settings' ); ?>
		<table class="form-table">
			<!-- New setting for enabling Upsell Page -->
			<tr valign="top">
				<th scope="row">Enable Upsell Popup</th>
				<td>
					<input type="checkbox" name="aetp_enable_upsell_page" <?php echo get_option( 'aetp_enable_upsell_page' ) ? 'checked' : ''; ?> />
					<p class="description">Enable or disable the Upsell Popup feature.</p>
				</td>
			</tr>
			<!-- New setting for defining Upsell Product ID -->
			<tr valign="top">
				<th scope="row">Upsell Product</th>
				<td>
					<select name="aetp_upsell_product_id">
						<?php
						$current_upsell_id = (int) get_option( 'aetp_upsell_product_id', '' );
						$args = array(
							'limit' => -1, // Get all products
							'status' => 'publish', // Only get published products
						);
						$products = wc_get_products( $args );

						foreach ( $products as $product ) {
							$selected = ( $product->get_id() == $current_upsell_id ) ? ' selected' : '';
							echo '<option value="' . esc_attr( $product->get_id() ) . '"' . esc_attr( $selected ) . '>' . esc_html( $product->get_name() ) . '</option>';
						}
						?>
					</select>
					<p class="description">Select a Product for the Upsell Page.</p>
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>
</div>

</form>

<div class="plugin-set-sect" id="topbar-settings">
	<h1>
		Top Bar Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="<?php echo esc_url( AETRENDPILOT_PLUGIN_URL . 'assets/images/Top Bar.png' ); ?>" alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">
			</span>
		</span>
	</h1>
	<form method="post" action="options.php#topbar-settings">
		<?php
		settings_fields( 'aetp_topbar_settings' );
		do_settings_sections( 'aetp_topbar_settings' );
		$topBarText = get_option( 'aetp_top_bar_message', 'Default message' );
		$topBarColor = get_option( 'aetp_top_bar_background_color', '#012C6D' );
		$topBarTextColor = get_option( 'aetp_top_bar_text_color', '#FFFFFF' );
		$topBarActive = get_option( 'aetp_top_bar_active', 0 );
		?>
		<table class="form-table">
			<tr valign="top"> 
				<th scope="row">Activate Top Bar</th>
				<td>
					<input type="checkbox" name="aetp_top_bar_active" value="1" <?php checked( 1, $topBarActive, true ); ?> />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Top Bar Text</th>
				<td><input type="text" name="aetp_top_bar_message" value="<?php echo esc_attr( $topBarText ); ?>"
						style="width: 500px;" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Top Bar Background Color</th>
				<td><input type="text" id="aetp_top_bar_background_color" name="aetp_top_bar_background_color"
						class="hex-color-picker" value="<?php echo esc_attr( $topBarColor ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Top Bar Text Color</th>
				<td><input type="color" name="aetp_top_bar_text_color" class="hex-color-picker"
						value="<?php echo esc_attr( $topBarTextColor ); ?>" /></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

<?php

?>

<div class="plugin-set-sect" id="badge-settings">
	<h1>
		Product Badge Settings
		<!-- Tooltip Icon -->
		<span class="tooltip-icon" style="position: relative; display: inline-block;">
			<i class="dashicons dashicons-info-outline"></i>
			<span class="tp-tooltip-image" style="display: none; position: absolute; top: -50px; left: 20px;">
				<img src="<?php echo esc_url( AETRENDPILOT_PLUGIN_URL . 'assets/images/Product Badges.png' ); ?>"
					alt="Top Bar Icon"
					style="width: 600px; height: 502px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);">
			</span>
		</span>
	</h1>
	<p class="description">Set badges on Edit product pages</p>
	<form method="post" action="options.php#badge-settings">
		<?php
		settings_fields( 'aetp_badge_settings' );
		do_settings_sections( 'aetp_badge_settings' );
		$badgeFontSize = get_option( 'aetp_badge_font_size', '14px' );
		$badgeColor = get_option( 'aetp_badge_color', '#FFA500' );
		$badgeBorderRadius = get_option( 'aetp_badge_border_radius', '100px' );
		?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">Enable Product Badges</th>
				<td><input type="checkbox" name="aetp_enable_badges" value="1" <?php checked( 1, get_option( 'aetp_enable_badges', 1 ) ); ?> /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Badge Font Size</th>
				<td><input type="text" name="aetp_badge_font_size" value="<?php echo esc_attr( $badgeFontSize ); ?>"
						style="width: 100px;" /></td>
			</tr>

			<tr valign="top">
				<th scope="row">Badge Color</th>
				<td><input type="text" id="aetp_badge_color" name="aetp_badge_color" class="hex-color-picker"
						value="<?php echo esc_attr( $badgeColor ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row">Badge Border Radius</th>
				<td><input type="text" name="aetp_badge_border_radius"
						value="<?php echo esc_attr( $badgeBorderRadius ); ?>" style="width: 100px;" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Badge Font Color</th>
				<td>
					<input type="text" id="aetp_badge_font_color" name="aetp_badge_font_color" class="hex-color-picker"
						value="<?php echo esc_attr( get_option( 'aetp_badge_font_color', '#FFFFFF' ) ); ?>" />
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
 
<?php