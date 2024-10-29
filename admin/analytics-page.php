<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'GET' ) {
	// Check if the nonce field exists and then validate it
	if ( isset( $_GET['selected_product_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['selected_product_nonce'] ) ), 'selected_product_action' ) ) {
		die( 'Security check failed for selected product' );
	}
	// Check for and validate the nonce for the selected category form
	if ( isset( $_GET['selected_category_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['selected_category_nonce'] ) ), 'selected_category_action' ) ) {
		die( 'Security check failed for selected category' );
	}

	// Similar nonce validation for other forms can be added here
	if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'change-filter-option-nonce' ) ) {
		die( 'Security check failed for update settings' );
	}
}


?>

<div class="tp-header-container">
	<div class="row align-items-center py-2">
		<div class="col-lg-8 col-md-7 d-flex align-items-center">
			<h2>Analytics</h2>
			<!-- Combined Form for Both Checkboxes -->
			<form action="" method="GET" class="d-flex align-items-center ml-3 tp-analytics-filter-form"
				id="filterForm">
				<!-- Generate a nonce and include it as a hidden field -->
				<?php
				$nonce = wp_create_nonce( 'change-filter-option-nonce' );
				echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
				// List of checkbox parameters to be preserved
				$checkboxes = [ 'show_recommended', 'show_upsell' ];
				?>
				<input type="hidden" name="page" value="aetp_analytics">


			</form>
		</div>
		<div class="col-lg-4 col-md-5">
			<form action="options.php" method="post" class="d-flex align-items-center justify-content-lg-end">
				<?php settings_fields( 'aetp_options_group' ); ?>
				<label for="tpae_analytics_period" class="mr-2 mb-0">Select period (days):</label>
				<div class="d-flex align-items-center">
					<input type="number" class="form-control mr-2 tp-period-card-label" id="tpae_analytics_period"
						name="tpae_analytics_period" min="0"
						value="<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ); ?>"
						placeholder="Enter days" required style="width: auto;">
					<button type="submit" class="btn btn-primary tp-period-card-label">Save</button>
				</div>
			</form>
		</div>
	</div>
</div>


<div class="col-lg-8 col-md-7 tp-section-title">
	Views
</div>

<!-- VIEWS ROW Start  -->
<div class="row row-sm upsell-row">

	<!-- CUSTOM CARD: Product Views - START-->
	<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
		<div class="card custom-card overflow-hidden">
			<div class="card-body">
				<div>
					<p><label class="card-label-header font-weight-bold mb-2">Product Views</label></p>
					<p class="text-muted card-sub-title">Product Views over the last
						<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ) ?> days
					</p>
				</div>
				<form method="GET">
					<input type="hidden" name="page" value="aetp_analytics">

					<select name="selected_product" onchange="this.form.submit()">
						<option value="">All Products</option>
						<?php tpae_get_product_view_options(); ?>
					</select>

					<?php
					// Include nonce for the form
					wp_nonce_field( 'selected_product_action', 'selected_product_nonce' );
					?>
				</form>

				<?php
				$productViewsData = tpae_calculate_product_views();

				// Sanitize the labels and datasets here, after fetching the data
				$labels_json = array_map( 'sanitize_text_field', $productViewsData[0] );

				$datasets_json = array_map( function ($dataset) {
					$dataset['label'] = sanitize_text_field( $dataset['label'] );
					$dataset['data'] = array_map( 'intval', $dataset['data'] );  // Ensure data points are integers
					return $dataset;
				}, $productViewsData[1] );

				$total_product_views = (int) $productViewsData[2];
				?>

				<div class="chartjs-wrapper" style="width:100%; overflow-x: auto; height: 100%; min-height:400px">
					<canvas id="productViewChart" class="chartjs-render-monitor"></canvas>
				</div>
				<div>
					<p style="margin-top: 10px;" class="mb-0 text-muted">
						Total product views over the last
						<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ); ?>
						days:
						<b class="text-primary"><?php echo esc_html( $total_product_views ); ?></b>
					</p>
				</div>
			</div>
		</div>
	</div>

	<?php
	//////// 1. enqueue and localise PRODUCT VIEWS data... 
	
	// Enqueue the external JavaScript file
	wp_enqueue_script(
		'product-views-chart',
		plugin_dir_url( __FILE__ ) . 'js/analytics/product-views-chart.js', // Adjust the path if necessary
		array( 'chart-js' ), // Ensure that Chart.js is loaded first if required
		'1.0',
		true // Load in the footer
	);

	// Localize the script with data
	wp_localize_script(
		'product-views-chart',
		'productViewsChartData', // This is the JS object name in the script
		array(
			'labels' => $labels_json,   // Pass labels (already JSON encoded for security)
			'datasets' => $datasets_json  // Pass datasets (already JSON encoded for security)
		)
	); ?>

	<!-- CUSTOM CARD: Product Views - END-->

	<!-- CUSTOM CARD: Category Views - START -->
	<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
		<div class="card custom-card overflow-hidden">
			<div class="card-body">
				<div>
					<p><label class="card-label-header font-weight-bold mb-2">Category Views</label></p>
					<p class="text-muted card-sub-title">Category Views over the last
						<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ); ?> days
					</p>
				</div>
				<form method="GET">
					<input type="hidden" name="page" value="aetp_analytics">

					<select name="selected_category" onchange="this.form.submit()">
						<option value="">All Categories</option>
						<?php tpae_get_cat_view_options(); ?>
					</select>

					<?php
					// Include nonce for the form
					wp_nonce_field( 'selected_category_action', 'selected_category_nonce' );
					?>
				</form>

				<?php
				// Fetch and sanitize the category views data
				$catViewsData = tpae_calculate_category_views();

				// Sanitize the labels and datasets after fetching the data
				$labels_json = array_map( 'sanitize_text_field', $catViewsData[0] );

				$datasets_json = array_map( function ($dataset) {
					$dataset['label'] = sanitize_text_field( $dataset['label'] );
					$dataset['data'] = array_map( 'intval', $dataset['data'] );  // Ensure data points are integers
					return $dataset;
				}, $catViewsData[1] );

				$total_category_views = (int) $catViewsData[2];
				?>

				<div class="chartjs-wrapper" style="width:100%; overflow-x: auto; height: 100%; min-height:400px">
					<canvas id="categoryViewChart" class="chartjs-render-monitor"></canvas>
				</div>
				<div>
					<p style="margin-top: 10px;" class="mb-0 text-muted">Total category views over the last
						<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ); ?> days: <b
							class="text-primary"><?php echo esc_html( $total_category_views ); ?></b>
					</p>
				</div>
			</div>
		</div>
	</div>

	<?php
	// 1. Enqueue the external JavaScript file for the category views chart
	wp_enqueue_script(
		'category-views-chart',
		plugin_dir_url( __FILE__ ) . 'js/analytics/category-views-chart.js', // Adjust the path if necessary
		array( 'chart-js' ), // Ensure that Chart.js is loaded first if required
		'1.0',
		true // Load in the footer
	);

	// 2. Localize the script with sanitized data
	wp_localize_script(
		'category-views-chart',
		'categoryViewsChartData', // This is the JS object name in the script
		array(
			'labels' => $labels_json,   // Pass sanitized labels
			'datasets' => $datasets_json  // Pass sanitized datasets
		)
	);
	?>

	<!-- CUSTOM CARD: Category Views - END -->


	<!-- VIEWS ROW End  -->
</div>


<div class="col-lg-8 col-md-7 tp-section-title">
	Upsells
</div>

<!-- UPSELL ROW Start  -->
<div class="row row-sm upsell-row">

	<!-- CUSTOM CARD: Current Upsell Product Clicks START -->

	<?php
	$upsellData = aetp_get_upsell_data();

	// Check if $upsellData is null or incomplete
	if ( is_array( $upsellData ) && count( $upsellData ) >= 3 ) {
		// Sanitize the fetched data
		$image_url = esc_url( $upsellData[0] );
		$product = $upsellData[1];
		$upsell_clicks = intval( $upsellData[2] );
	} else {
		// Assign default values
		$image_url = '';
		$product = null;
		$upsell_clicks = 0;
	}

	// Get the date the upsell product was last changed
	$aetp_upsell_date_last_change = esc_html( get_option( 'aetp_upsell_date_last_change', gmdate( 'Y-m-d' ) ) );
	?>

	<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
		<div class="card custom-card upsell-card">
			<div class="card-header border-bottom-0 pb-1">
				<label class="card-label-header font-weight-bold mb-2">Upsell 'Add To Cart's</label>
				<p class="text-muted mb-2">Total 'Add To Cart's of the current upsell product.</p>
			</div>
			<div class="card-body pt-0">
				<ul class="list-unstyled">
					<li class="mb-4">
						<div class="row">
							<div class="col-md-4">
								<img src="<?php echo $image_url; ?>" alt="image"
									class="img-fluid rounded custom-card-image-col">
							</div>
							<div class="col-md-6">
								<div class="card-item-title mt-3">
									<label class="font-weight-bold mb-2">Current Product: </label>
									<p class="mb-0 text-muted">
										<b class="text-primary"><?php
										if ( $product ) {
											echo esc_html( $product->get_name() );
										} else {
											echo 'No product selected';
										}
										?></b>
									</p>
								</div>
								<div class="card-item-title mt-3">
									<label class="font-weight-bold mb-2">Add to Carts:</label>
									<p class="mb-0 text-muted">
										<b class="text-primary"><?php echo $upsell_clicks; ?></b>
									</p>
								</div>
								<div class="card-item-title mt-3">
									<label class="font-weight-bold mb-2">Since (date upsell product changed)</label>
									<p class="mb-0 text-muted">
										<b class="text-primary"><?php echo $aetp_upsell_date_last_change; ?></b>
									</p>
								</div>
							</div>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<!-- CUSTOM CARD: Current Upsell Product Clicks END -->


	<!-- CUSTOM CARD: Upsell 'Add To Cart's Chart  START -->
	<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
		<div class="card custom-card overflow-hidden">
			<div class="card-body">
				<div>
					<p><label class="card-label-header font-weight-bold mb-2">Upsell 'Add To Cart's over time</label>
					</p>
					<p class="text-muted card-sub-title">Current upsell product's 'Add To Cart's over last
						<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ); ?> days
					</p>
					<p class="text-muted card-sub-title">Note: Only shows data since the upsell product was last changed
					</p>
				</div>
				<div class="chartjs-wrapper" style="width: 100%; height: 100%; min-height:400px">
					<canvas id="upsellChart" class="chartjs-render-monitor"></canvas>
				</div>
			</div>
		</div>
	</div>

	<?php
	// Fetch the upsell clicks data securely
	$upsellClicks = aetp_get_upsell_clicks();

	// Sanitize the data before localizing it
	$labels_json = array_map( 'sanitize_text_field', $upsellClicks[0] );
	$data_json = array_map( 'intval', $upsellClicks[1] );
	$backgroundColor = sanitize_hex_color( "#6a77c4" );
	$borderColor = sanitize_hex_color( "#6a77c4" );

	// Enqueue the script for the upsell chart
	wp_enqueue_script(
		'upsell-chart',
		plugin_dir_url( __FILE__ ) . 'js/upsell-chart.js', // Adjust path if necessary
		array( 'chart-js' ), // Load after Chart.js
		'1.0',
		true // Load in footer
	);

	// Localize the script with sanitized data
	wp_localize_script(
		'upsell-chart',
		'upsellChartData',
		array(
			'labels' => $labels_json,
			'data' => $data_json,
			'backgroundColor' => $backgroundColor,
			'borderColor' => $borderColor,
		)
	);
	?>
	<!-- CUSTOM CARD: Upsell 'Add To Cart's Chart END -->


	<!-- UPSELL ROW End  -->
</div>

<div class="col-lg-8 col-md-7 tp-section-title">
	'Recommended' Section
</div>


<!-- RECOMMENDED ROW Start  -->
<div class="row row-sm recommended-row">


	<!-- CUSTOM CARD: Recommended Chart START  -->

	<?php
	// Fetch and sanitize the recommended data
	$recommendedData = tpae_get_recommended_data();

	// Sanitize the labels and datasets after fetching the data
	$labels_json = array_map( 'sanitize_text_field', $recommendedData[0] );
	$datasets_json = array_map( function ($dataset) {
		$dataset['label'] = sanitize_text_field( $dataset['label'] );
		$dataset['data'] = array_map( 'intval', $dataset['data'] );  // Ensure data points are integers
		return $dataset;
	}, $recommendedData[1] );

	$datasets = array_map( function ($dataset) {
		return [ 
			'label' => sanitize_text_field( $dataset['label'] )
		];
	}, $recommendedData[2] );
	?>

	<div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
		<div class="card custom-card overflow-hidden">
			<div class="card-body">
				<div>
					<p><label class="card-label-header font-weight-bold mb-2">Recommended clicks over time</label></p>
					<p class="text-muted card-sub-title">Over last
						<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ); ?> days
					</p>
				</div>
				<!-- Place this dropdown above or below your chart container -->
				<select id="legendDropdown" onchange="updateDatasetVisibility()">
					<option value="all">Show All</option>
					<!-- Dynamically generate options based on datasets -->
					<?php foreach ( $datasets as $index => $prodName ) : ?>
						<option value="<?php echo esc_attr( $index ); ?>"><?php echo esc_attr( $prodName['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<div class="chartjs-wrapper" style="width: 100%; height: 100%;min-height: 400px;">
					<canvas id="recommendedChart" class="chartjs-render-monitor"></canvas>
				</div>
			</div>
		</div>
	</div>

	<?php
	// Enqueue the external JavaScript file for the recommended chart
	wp_enqueue_script(
		'recommended-chart-js',
		plugin_dir_url( __FILE__ ) . 'js/analytics/recommended-chart.js', // Adjust the path if necessary
		array( 'chart-js' ), // Ensure that Chart.js is loaded first if required
		'1.0',
		true // Load in the footer
	);

	// Localize the script with sanitized data
	wp_localize_script(
		'recommended-chart-js',
		'recommendedChartData', // This is the JS object name in the script
		array(
			'labels' => $labels_json,   // Pass sanitized labels
			'datasets' => $datasets_json  // Pass sanitized datasets
		)
	);
	?>
	<!-- CUSTOM CARD: Recommended Chart END  -->

	<!-- CUSTOM CARD: Top Recommended Start  -->
	<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6">
		<div class="card custom-card recommended-card">
			<div class="card-header border-bottom-0 pb-1">
				<label class="card-label-header font-weight-bold mb-2">Top Recommended Products</label>
				<p class="text-muted mb-2">Most clicked recommended products in the last
					<?php echo esc_attr( get_option( 'tpae_analytics_period' ) ); ?> days
				</p>
			</div>
			<div class="card-body pt-0">
				<ul class="list-unstyled">
					<?php
					$recommended_products = tpae_get_recommended_products();
					foreach ( $recommended_products as $prod ) {
						$product = wc_get_product( $prod->product_id );
						$image_url = wp_get_attachment_url( $product->get_image_id() );
						?>
						<li class="mb-4">
							<div class="row">
								<div class="col-md-1">
									<img src="<?php echo esc_url( $image_url ); ?>"
										alt="<?php echo esc_attr( $product->get_name() ); ?>"
										style="max-width: 100%; height: auto;" class="img-fluid rounded">
								</div>
								<div class="col-md-9">
									<div class="card-item-title mt-3">
										<label
											class="font-weight-bold mb-2"><?php echo esc_html( $product->get_name() ); ?></label>
										<p class="mb-0 text-muted">Clicks: <b
												class="text-primary"><?php echo esc_html( $prod->total_clicks ); ?></b></p>
									</div>
								</div>
							</div>
						</li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
	<!-- CUSTOM CARD: Top Recommended End  -->

	<!-- RECOMMENDED ROW End  -->

</div>