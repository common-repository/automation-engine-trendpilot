<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Get current WordPress user information
$current_user = wp_get_current_user();
$first_name = $current_user->first_name;
$last_name = $current_user->last_name;
$email = $current_user->user_email;

?>

<div class="trendpilot-logo">
	<a href="https://trendpilot.io" target="_blank" rel="noopener noreferrer">
		<img src="<?php echo esc_url( AETRENDPILOT_PLUGIN_URL . 'assets/images/trendpilot-transparent-logo.png' ); ?>"
			alt="Trendpilot Logo">
	</a>
</div>

<body class="form-v2">
	<div class="page-content">
		<div class="form-v2-content">
			<div class="form-left">
				<img src="<?php echo esc_url( AETRENDPILOT_PLUGIN_URL . 'assets/images/form-v2.png' ); ?>" alt="form">
			</div>
			<form class="form-detail" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"
				id="myform">
				<input type="hidden" name="action" value="trendpilot_submit_waitlist">
				<?php wp_nonce_field( 'trendpilot_waitlist_nonce', 'trendpilot_nonce_field' ); ?>

				<h2>Sign up to our waiting list</h2>

				<div class="form-row">
					<label for="first-name">First Name:</label>
					<input type="text" name="first_name" id="first_name" class="input-text"
						value="<?php echo esc_attr( $first_name ); ?>">
				</div>

				<div class="form-row">
					<label for="last-name">Last Name:</label>
					<input type="text" name="last_name" id="last_name" class="input-text"
						value="<?php echo esc_attr( $last_name ); ?>">
				</div>

				<div class="form-row">
					<label for="your_email">Email:</label>
					<input type="email" name="your_email" id="your_email" class="input-text" required
						pattern="[^@]+@[^@]+\.[a-zA-Z]{2,6}" value="<?php echo esc_attr( $email ); ?>">
				</div>

				<div class="form-row-last">
					<input type="submit" name="register" class="register" value="Sign up to the waiting list">
				</div>

				<?php
				// Check for the 'status' parameter, unslash, and validate its value
				if ( isset( $_GET['status'] ) ) {
					$status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
					if ( $status === 'success' ) {
						echo '<p class="success-message">' . esc_html__( 'Form successfully submitted. Thank you!', 'automation-engine-trendpilot' ) . '</p>';
					} elseif ( $status === 'error' ) {
						echo '<p class="error-message">' . esc_html__( 'There was an issue submitting the form. Please try again.', 'automation-engine-trendpilot' ) . '</p>';
					}
				}
				?>
			</form>
		</div>
	</div>
</body>