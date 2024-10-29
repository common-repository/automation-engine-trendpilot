<?php

namespace AETrendpilot;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class TrendpilotProductDisplay {

	public function registerHooks() {
		add_action( 'init', array( $this, 'registerCustomPostType' ) );
		add_filter( 'manage_edit-tp_product_display_columns', array( $this, 'setCustomProductDisplayColumns' ) );
		add_action( 'manage_tp_product_display_posts_custom_column', array( $this, 'customProductDisplayColumn' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
		add_action( 'save_post', array( $this, 'saveMetaBoxes' ) );
		add_action( 'do_meta_boxes', array( $this, 'removeDefaultEditor' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
		add_shortcode( 'tp_product_display', array( $this, 'renderProductDisplayShortcode' ) );


	}

	public function registerCustomPostType() {

		$args = array(
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => false, // Do not show in admin menu directly
			'supports' => array( 'title' ), // Removed 'editor' to remove post_content
			'label' => 'Product Displays',
			'has_archive' => true,
		);
		register_post_type( 'tp_product_display', $args );
	}

	public function setCustomProductDisplayColumns( $columns ) {

		$new_columns = array(
			'cb' => $columns['cb'], // Checkbox for bulk actions
			'title' => $columns['title'], // Title of the post
			'product_id' => 'Product', // Custom column for Product
			'display_type' => 'Display Type', // Custom column for Display Type
			'display_id' => 'Display ID', // Custom column for Display ID
			'shortcode' => 'Shortcode', // Custom column for Shortcode
			'date' => $columns['date'] // Date column
		);
		return $new_columns;
	}

	public function customProductDisplayColumn( $column, $post_id ) {

		switch ( $column ) {
			case 'display_id':
				echo esc_html( $post_id );
				break;
			case 'shortcode':
				$shortcode = '[tp_product_display id="' . $post_id . '"]';
				echo '<input type="text" value="' . esc_attr( $shortcode ) . '" readonly="readonly" onclick="copyShortcode(this)" />';
				echo '<span class="shortcode-copied-message" style="display:none; color: green; margin-left: 10px;">Shortcode copied!</span>';
				break;
			case 'product_id':
				$product_id = get_post_meta( $post_id, 'product_id', true );
				$product = wc_get_product( $product_id );
				echo esc_html( $product ? $product->get_title() : 'No Product' );
				break;
			case 'display_type':
				$display_type = get_post_meta( $post_id, 'display_type', true );
				$display_type_label = $this->getDisplayTypeLabel( $display_type );
				echo esc_html( $display_type_label );
				break;
		}
	}

	public function addMetaBoxes() {

		// Adding Product meta box first
		add_meta_box(
			'product_display_product_id_meta_box',  // ID of the meta box
			'Product',                             // Title of the meta box
			array( $this, 'displayProductIDMetaBox' ), // Callback function to display the meta box content
			'tp_product_display',                     // Post type
			'normal',                               // Context (normal, side, etc.)
			'high'                                  // Priority
		);
		// Adding Display Type meta box second
		add_meta_box(
			'product_display_display_type_meta_box', // ID of the meta box
			'Display Type',                          // Title of the meta box
			array( $this, 'displayDisplayTypeMetaBox' ), // Callback function to display the meta box content
			'tp_product_display',                      // Post type
			'normal',                                // Context (normal, side, etc.)
			'high'                                   // Priority
		);
		// Adding HTML meta box last
		add_meta_box(
			'product_display_html_meta_box',        // ID of the meta box
			'HTML',                                 // Title of the meta box
			array( $this, 'displayHTMLMetaBox' ),     // Callback function to display the meta box content
			'tp_product_display',                     // Post type
			'normal',                               // Context (normal, side, etc.)
			'high'                                  // Priority
		);
		// Adding Shortcode meta box
		add_meta_box(
			'product_display_shortcode_meta_box',   // ID of the meta box
			'Shortcode',                            // Title of the meta box
			array( $this, 'displayShortcodeMetaBox' ), // Callback function to display the meta box content
			'tp_product_display',                     // Post type
			'side',                                 // Context (side, normal, etc.)
			'high'                                  // Priority
		);

		// Adding Design Attributes meta box
		add_meta_box(
			'product_display_design_attributes_meta_box',
			'Design Attributes',
			array( $this, 'displayDesignAttributesMetaBox' ),
			'tp_product_display',
			'normal',
			'high'
		);

		// Adding Shortcode Preview meta box
		add_meta_box(
			'product_display_shortcode_preview_meta_box', // ID of the meta box
			'Product Display Preview', // Title of the meta box
			array( $this, 'displayShortcodePreviewMetaBox' ), // Callback function to display the meta box content
			'tp_product_display', // Post type
			'normal', // Context (normal, side, etc.)
			'high' // Priority
		);

	}

	//temporary method to allow style tags. replace with above method when done.
	public function saveMetaBoxes( $post_id ) {
		// Verify the nonce for the product ID and display type meta boxes.
		if ( ! isset( $_POST['meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meta_box_nonce'] ) ), 'product_display_meta_box_nonce' ) ) {
			return;
		}

		// Verify the nonce for the design attributes meta box.
		if ( ! isset( $_POST['design_attributes_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['design_attributes_meta_box_nonce'] ) ), 'product_display_design_attributes_meta_box_nonce' ) ) {
			return;
		}

		// Check for autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitize and save the product ID.
		if ( isset( $_POST['product_id'] ) ) {
			update_post_meta( $post_id, 'product_id', sanitize_text_field( $_POST['product_id'] ) );
		}

		// Sanitize and save the display type.
		if ( isset( $_POST['display_type'] ) ) {
			update_post_meta( $post_id, 'display_type', sanitize_text_field( $_POST['display_type'] ) );
		}

		// Sanitize and save the HTML content.
		if ( isset( $_POST['html'] ) ) {
			$allowed_tags = array(
				'div' => array( 'class' => array(), 'style' => array() ),
				'img' => array( 'class' => array(), 'src' => array(), 'alt' => array(), 'style' => array() ),
				'h1' => array( 'class' => array(), 'style' => array() ),
				'p' => array( 'class' => array(), 'style' => array() ),
				'a' => array( 'class' => array(), 'href' => array(), 'style' => array() ),
				'button' => array( 'class' => array(), 'style' => array() ),
				'style' => array(),
			);
			update_post_meta( $post_id, 'html', wp_kses( $_POST['html'], $allowed_tags ) );
		}

		// Sanitize and save other meta fields.
		if ( isset( $_POST['design_heading_1'] ) ) {
			update_post_meta( $post_id, 'design_heading_1', sanitize_text_field( $_POST['design_heading_1'] ) );
		}

		if ( isset( $_POST['background_image_1'] ) ) {
			update_post_meta( $post_id, 'background_image_1', intval( $_POST['background_image_1'] ) );
		}

		if ( isset( $_POST['theme_color'] ) ) {
			update_post_meta( $post_id, 'theme_color', sanitize_hex_color( $_POST['theme_color'] ) );
		}

		if ( isset( $_POST['cta_color'] ) ) {
			update_post_meta( $post_id, 'cta_color', sanitize_hex_color( $_POST['cta_color'] ) );
		}

		if ( isset( $_POST['text_color'] ) ) {
			update_post_meta( $post_id, 'text_color', sanitize_hex_color( $_POST['text_color'] ) );
		}

		if ( isset( $_POST['cta_text_color'] ) ) {
			update_post_meta( $post_id, 'cta_text_color', sanitize_hex_color( $_POST['cta_text_color'] ) );
		}

		if ( isset( $_POST['cta_text'] ) ) {
			update_post_meta( $post_id, 'cta_text', sanitize_text_field( $_POST['cta_text'] ) );
		}

		if ( isset( $_POST['show_badge'] ) ) {
			update_post_meta( $post_id, 'show_badge', sanitize_text_field( $_POST['show_badge'] ) );
		} else {
			update_post_meta( $post_id, 'show_badge', '0' );
		}
	}


	public function displayDesignAttributesMetaBox( $post ) {
		$design_heading = get_post_meta( $post->ID, 'design_heading_1', true );
		$background_image = get_post_meta( $post->ID, 'background_image_1', true );
		$theme_color = get_post_meta( $post->ID, 'theme_color', true );
		$cta_color = get_post_meta( $post->ID, 'cta_color', true );
		$text_color = get_post_meta( $post->ID, 'text_color', true );
		$cta_text_color = get_post_meta( $post->ID, 'cta_text_color', true );
		$cta_text = get_post_meta( $post->ID, 'cta_text', true );
		$show_badge = get_post_meta( $post->ID, 'show_badge', true );

		wp_nonce_field( 'product_display_design_attributes_meta_box_nonce', 'design_attributes_meta_box_nonce' );

		?>
		<p>
			<label for="design_heading_1">Design Heading:</label>
			<input type="text" name="design_heading_1" id="design_heading_1"
				value="<?php echo esc_attr( $design_heading ); ?>" />
		</p>
		<p>
			<label for="cta_text">CTA Text:</label>
			<input type="text" name="cta_text" id="cta_text" value="<?php echo esc_attr( $cta_text ); ?>" />
		</p>
		<p>
			<label for="background_image_1">Background Image:</label>
			<input type="hidden" name="background_image_1" id="background_image_1"
				value="<?php echo esc_attr( $background_image ); ?>" />
			<button type="button" class="button" id="upload_background_image_button">Upload Image</button>
			<span id="background_image_url" style="font-weight: bold; margin-left: 10px;">
				<?php if ( $background_image ) : ?>
					<?php echo esc_url( wp_get_attachment_url( $background_image ) ); ?>
				<?php endif; ?>
			</span>
		</p>
		<p>
			<label for="theme_color">Theme Color:</label>
			<input type="text" name="theme_color" id="theme_color" value="<?php echo esc_attr( $theme_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="cta_color">CTA Button Color:</label>
			<input type="text" name="cta_color" id="cta_color" value="<?php echo esc_attr( $cta_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="text_color">Text Color:</label>
			<input type="text" name="text_color" id="text_color" value="<?php echo esc_attr( $text_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="cta_text_color">CTA Text Color:</label>
			<input type="text" name="cta_text_color" id="cta_text_color" value="<?php echo esc_attr( $cta_text_color ); ?>"
				class="color-picker" />
		</p>
		<p>
			<label for="show_badge">Show Product Badge:</label>
			<input type="checkbox" name="show_badge" id="show_badge" value="1" <?php checked( $show_badge, '1' ); ?> />
		</p>
		<?php
	}


	public function displayShortcodeMetaBox( $post ) {
		$shortcode = '[tp_product_display id="' . (int) $post->ID . '"]';
		?>
		<p>
			<input type="text" value="<?php echo esc_attr( $shortcode ); ?>" readonly="readonly" class="shortcode-copy-input" />
			<span class="shortcode-copied-message" style="display:none; color: green; margin-left: 10px;">Shortcode
				copied!</span>
		</p>
		<?php

	}

	public function displayProductIDMetaBox( $post ) {
		$product_id = get_post_meta( $post->ID, 'product_id', true );
		wp_nonce_field( 'product_display_meta_box_nonce', 'meta_box_nonce' );
		?>
		<p>
			<label for="product_id">Select product:</label>
			<input type="hidden" name="product_id" id="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
			<input type="text" id="product_search" value="<?php echo esc_attr( $this->getProductTitle( $product_id ) ); ?>" />
		<p>Choose product to show in this display</p>
		</p>

		<?php
	}

	public function displayDisplayTypeMetaBox( $post ) {
		$display_type = get_post_meta( $post->ID, 'display_type', true );
		wp_nonce_field( 'product_display_meta_box_nonce', 'meta_box_nonce' );
		?>
		<p>
			<label for="display_type">Display Type:</label>
			<select name="display_type" id="display_type">
				<option value="full_width_design_1" <?php selected( $display_type, 'full_width_design_1' ); ?>>Full-width Design
					1</option>
				<option value="full_width_design_2" <?php selected( $display_type, 'full_width_design_2' ); ?>>Full-width Design
					2</option>
				<option value="full_width_design_3" <?php selected( $display_type, 'full_width_design_3' ); ?>>Full-width Design
					3</option>
				<option value="custom" <?php selected( $display_type, 'custom' ); ?>>Custom</option>
			</select>
		</p>

		<?php
	}

	public function getProductTitle( $product_id ) {
		$product = wc_get_product( $product_id );
		return $product ? $product->get_title() : '';
	}

	public function getProductsList() {
		$products = wc_get_products( array( 'limit' => -1 ) );
		$product_list = array();

		foreach ( $products as $product ) {
			$product_list[] = array(
				'label' => sanitize_text_field( $product->get_title() ), // Sanitizing the product title
				'value' => sanitize_text_field( $product->get_title() ), // Sanitizing the product title
				'id' => intval( $product->get_id() ) // Ensuring the ID is an integer
			);
		}

		return $product_list;
	}


	public function getDisplayTypeLabel( $display_type ) {
		$labels = array(
			'full_width_design_1' => 'Full-width Design 1',
			'full_width_design_2' => 'Full-width Design 2',
			'cta_banner_left' => 'CTA Banner (left)',
			'cta_banner_right' => 'CTA Banner (right)',
			'custom' => 'Custom'
		);
		return isset( $labels[ $display_type ] ) ? $labels[ $display_type ] : $display_type;
	}

	public function displayHTMLMetaBox( $post ) {
		$html = get_post_meta( $post->ID, 'html', true );
		wp_nonce_field( 'product_display_meta_box_nonce', 'meta_box_nonce' );
		?>
		<p>
			<label for="html">HTML:</label>
			<textarea name="html" id="codemirrorhtml" rows="10"
				style="width:100%;"><?php echo esc_textarea( $html ); ?></textarea>
		</p>
		<p>Use the following class names to bring in specific elements:</p>
		<ul>
			<li><strong>.trendpilot-product-image:</strong> Displays the product image. Example:
				<code>&lt;img src="image_url" class="trendpilot-product-image" /&gt;</code>
			</li>
			<li><strong>.trendpilot-product-title:</strong> Displays the product title. Example:
				<code>&lt;div class="trendpilot-product-title"&gt;Product Title&lt;/div&gt;</code>
			</li>
			<li><strong>.trendpilot-product-description:</strong> Displays the product description. Example:
				<code>&lt;div class="trendpilot-product-description"&gt;Product Description&lt;/div&gt;</code>
			</li>
			<li><strong>.trendpilot-cta-link:</strong> Displays the Call to Action link. Example:
				<code>&lt;a href="link_url" class="trendpilot-cta-link"&gt;Call to Action&lt;/a&gt;</code>
			</li>
			<li><strong>.trendpilot-design-heading:</strong> Displays the design heading. Example:
				<code>&lt;div class="trendpilot-design-heading"&gt;Design Heading&lt;/div&gt;</code>
			</li>
			<li><strong>.trendpilot-design-background-img:</strong> Displays the background image. Example:
				<code>&lt;img src="background_image_url" class="trendpilot-design-background-img" /&gt;</code>
			</li>
		</ul>

		<?php
	}

	public function removeDefaultEditor() {
		remove_post_type_support( 'tp_product_display', 'editor' );
	}

	public function enqueueScripts() {
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_style( 'jquery-ui-css', esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/css/jquery-ui.css' ) );

		// Initialize CodeMirror using wp_enqueue_code_editor
		$settings = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

		if ( false !== $settings ) {
			// Enqueue the initialization script
			wp_enqueue_script(
				'codemirror-init-script',
				esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/js/codemirror.js' ),
				array( 'jquery' ),
				'1.8.0',
				true
			);

			// Localize the settings for use in JavaScript
			wp_localize_script( 'codemirror-init-script', 'codemirrorSettings', $settings );
		}

		// Enqueue the Spectrum JS
		wp_enqueue_script(
			'spectrum-js',
			esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/js/spectrum.js' ),
			array( 'jquery' ),
			'1.8.0',
			true
		);

		// Enqueue the Spectrum CSS
		wp_enqueue_style(
			'spectrum-css',
			esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/css/spectrum.css' ),
			array(),
			'1.8.0'
		);

		wp_enqueue_style(
			'hidePublishBoxElements',
			esc_url( AETRENDPILOT_PLUGIN_URL . 'admin/css/hidePublishBoxElements.css' )
		);

		wp_enqueue_script(
			'product-display-scripts',
			esc_url( plugin_dir_url( __FILE__ ) . '../admin/js/product-display-scripts.js' ),
			array( 'jquery', 'spectrum-js', 'wp-mediaelement' ),
			'1.0',
			true
		);

		// Initialize the isCustomDisplay variable as false
		$is_custom_display = false;

		// Enqueue and localize product data for autocomplete and shortcode preview
		$products_list = $this->getProductsList();
		$shortcode = '[tp_product_display id="' . (int) get_the_ID() . '"]';  // Example shortcode localization

		wp_localize_script(
			'product-display-scripts',
			'productDisplaySettings',
			array(
				'products' => $products_list,
				'shortcode' => do_shortcode( $shortcode ), // Localizing the shortcode for preview
				'isCustomDisplay' => $is_custom_display,  // Pass the initial state of isCustomDisplay
				'customDisplayData' => array(), // Empty by default, will be populated later
			)
		);
	}

	public function renderProductDisplayShortcode( $atts ) {
		// Sanitize and validate the shortcode attributes
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'tp_product_display' );
		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		// Retrieve and validate product-related data
		$product_id = intval( get_post_meta( $post_id, 'product_id', true ) );
		if ( ! $product_id ) {
			return '';
		}

		$display_type = sanitize_text_field( get_post_meta( $post_id, 'display_type', true ) );

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		// Retrieve and sanitize product-related details
		$product_image_url = esc_url( wp_get_attachment_url( $product->get_image_id() ) );
		$product_title = esc_html( html_entity_decode( $product->get_title() ) );
		$product_description = wp_kses_post( $product->get_description() );
		$product_url = esc_url( get_permalink( $product_id ) );
		$design_heading = esc_html( get_post_meta( $post_id, 'design_heading_1', true ) ?: 'Trending...' );
		$background_image_url = esc_url( wp_get_attachment_url( get_post_meta( $post_id, 'background_image_1', true ) ) );
		$theme_color = sanitize_hex_color( get_post_meta( $post_id, 'theme_color', true ) ?: '' );
		$theme_color_2 = sanitize_hex_color( get_post_meta( $post_id, 'theme_color_2', true ) ?: '' );
		$cta_color = sanitize_hex_color( get_post_meta( $post_id, 'cta_color', true ) ?: '' );
		$text_color = sanitize_hex_color( get_post_meta( $post_id, 'text_color', true ) ?: '' );
		$cta_text_color = sanitize_hex_color( get_post_meta( $post_id, 'cta_text_color', true ) ?: '' );
		$cta_text = esc_html( get_post_meta( $post_id, 'cta_text', true ) ?: 'Shop Now' );

		// Safely encode the product data as a JSON object
		$product_data = json_encode( array(
			'image_url' => $product_image_url,
			'title' => $product_title,
			'description' => $product_description,
			'design_heading' => $design_heading,
			'background_image' => $background_image_url,
			'theme_color' => $theme_color,
			'theme_color_2' => $theme_color_2,
			'cta_color' => $cta_color,
			'text_color' => $text_color,
			'cta_text_color' => $cta_text_color,
			'cta_text' => $cta_text
		), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		// Select and sanitize HTML template based on display type
		if ( $display_type === 'custom' ) {
			$html_template = wp_kses_post( get_post_meta( $post_id, 'html', true ) );
		} elseif ( $display_type === 'full_width_design_1' ) {
			// Enqueue the stylesheet for design 1
			wp_enqueue_style( 'full-width-design-1-css', plugin_dir_url( __FILE__ ) . '../public/css/product-displays/full-width-design-1.css', array(), '1.0.0', 'all' );

			ob_start();
			include plugin_dir_path( __FILE__ ) . '../public/views/product-displays/full-width-design-1.php';
			$html_template = ob_get_clean();
			$html_template = '<div class="trendpilot-pd-full-width-1">' . $html_template . '</div>';
		} elseif ( $display_type === 'full_width_design_2' ) {
			// Enqueue the stylesheet for design 2
			wp_enqueue_style( 'full-width-design-2-css', plugin_dir_url( __FILE__ ) . '../public/css/product-displays/full-width-design-2.css', array(), '1.0.0', 'all' );

			ob_start();
			include plugin_dir_path( __FILE__ ) . '../public/views/product-displays/full-width-design-2.php';
			$html_template = ob_get_clean();
			$html_template = '<div class="trendpilot-pd-full-width-2">' . $html_template . '</div>';
		} elseif ( $display_type === 'full_width_design_3' ) {
			// Enqueue the stylesheet for design 3
			wp_enqueue_style( 'full-width-design-3-css', plugin_dir_url( __FILE__ ) . '../public/css/product-displays/full-width-design-3.css', array(), '1.0.0', 'all' );

			ob_start();
			include plugin_dir_path( __FILE__ ) . '../public/views/product-displays/full-width-design-3.php';
			$html_template = ob_get_clean();
			$html_template = '<div class="trendpilot-pd-full-width-3">' . $html_template . '</div>';
		} else {
			return 'no display_type';
		}

		$html = $html_template;


		// Enqueue the custom JS script and localize the product data
		wp_enqueue_script( 'product-display-custom-display', plugin_dir_url( __FILE__ ) . '../admin/js/product-display-custom-shortcode-script.js', array( 'jquery' ), NULL, true );


		// Localize product data for the custom display script
		wp_localize_script( 'product-display-custom-display', 'productDisplayData', array(
			'image_url' => $product_image_url,
			'title' => $product_title,
			'description' => $product_description,
			'design_heading' => $design_heading,
			'background_image' => $background_image_url,
			'theme_color' => $theme_color,
			'theme_color_2' => $theme_color_2,
			'cta_color' => $cta_color,
			'text_color' => $text_color,
			'cta_text_color' => $cta_text_color,
			'cta_text' => $cta_text,
			'product_url' => $product_url,
		) );

		return $html;
	}


	public function displayShortcodePreviewMetaBox( $post ) {
		// Prepare the shortcode with the post ID
		$post_id = $post->ID;

		if ( ! $post_id ) {
			return '';
		}

		// Use an iframe to render the shortcode preview for other display types
		$preview_url = esc_url( AETRENDPILOT_PLUGIN_URL . '/admin/shortcode-preview.php?post_id=' . $post_id );
		?>
		<div>
			<iframe id="" src="<?php echo esc_url( $preview_url ); ?>" style="width:100%; height:600px; border:none;"></iframe>
		</div>
		<?php

	}

}