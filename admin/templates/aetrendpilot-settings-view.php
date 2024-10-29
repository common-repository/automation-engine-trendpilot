<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="plugin-set-sect">
	<h1>Cron Settings</h1>
	<p class="description">Active and 'In progress' automations will run once a day via this
		cron.</p>
	<form method="post" style="margin-bottom: 15px">
		<?php wp_nonce_field( 'aetp_update_cron_time_action', 'aetp_update_cron_time_nonce' ); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">Cron Job Time (24hr Format)</th>
					<td>
						<input type="time" name="aetp_cron_job_time"
							value="<?php echo esc_attr( get_option( 'aetp_cron_job_time', '00:00' ) ); ?>" />
						<p class="description">Set the time the cron job will run daily. Default is 00:00.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Current Cron Job Time (24hr Format)</th>
					<td>
						<?php
						// Get the stored cron job time in local time
						$cron_time = get_option( 'aetp_cron_job_time', '00:00' );

						// Convert the stored local time to UTC to find the scheduled cron job time
						list( $hour, $minute ) = explode( ':', $cron_time );
						$timezone_string = get_option( 'timezone_string' );

						if ( ! $timezone_string ) {
							$timezone_string = 'UTC';
						}

						$timezone = new DateTimeZone( $timezone_string );

						$date = new DateTime( "today {$hour}:{$minute}", $timezone );
						$date->setTimezone( new DateTimeZone( 'UTC' ) );
						$utc_time = $date->format( 'H:i' );

						// Retrieve the next scheduled time in UTC and convert it to local time
						$next_scheduled = wp_next_scheduled( 'tpap_daily_workflow_checker' );
						if ( $next_scheduled ) {
							$utc_datetime = new DateTime( "@$next_scheduled" );
							$utc_datetime->setTimezone( $timezone );
							$local_scheduled_time = $utc_datetime->format( 'H:i' );
						} else {
							$local_scheduled_time = 'Not scheduled';
						}
						?>
						<span><?php echo esc_html( $local_scheduled_time ); ?> (Time Zone:
							<?php echo esc_html( $timezone->getName() ); ?>)</span>
						<p class="description">This is the time at which the daily cron job is currently set to run.</p>
					</td>
				</tr>

			</tbody>
		</table>
		<?php submit_button( 'Update Cron Time' ); ?>
	</form>

	<form method="post">
		<?php wp_nonce_field( 'aetp_trigger_cron_action', 'aetp_trigger_cron_nonce' ); ?>
		<table class="form-table">
			<tbody>
				<tr>
					<input style="min-width:210px" type="submit" name="aetp_trigger_daily_cron"
						value="Trigger Daily Cron Job">
				</tr>
				<tr>
					<span style="margin-left: 10px;">Click this button to manually trigger the daily cron job.</span>
				</tr>
			</tbody>
		</table>
	</form>
</div>




<div class="plugin-set-sect" id="data-settings">
	<h1>Data Tools</h1>

	<form method="post" action="options.php#data-settings">
		<?php settings_fields( 'tpae_flush_settings' );
		do_settings_sections( 'tpae_flush_settings' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Flush View Data Period (Days)</th>
				<td>
					<input type="number" name="tpae_page_view_flush_period"
						value="<?php echo esc_attr( get_option( 'tpae_page_view_flush_period', 30 ) ); ?>" />
					<p class="description">Every day we will remove product & category views older than this number of
						days. Default is 30 days. </p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Flush Recommended Data Period (Days)</th>
				<td>
					<input type="number" name="tpae_recommended_data_flush_period"
						value="<?php echo esc_attr( get_option( 'tpae_recommended_data_flush_period', 30 ) ); ?>" />
					<p class="description">Every day we will remove recommended click data older than this number of
						days.
						Default is 30 days. </p>
				</td>
			</tr>
		</table>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Flush Upsell Click Data Period (Days)</th>
				<td>
					<input type="number" name="aetp_click_data_flush_period"
						value="<?php echo esc_attr( get_option( 'aetp_click_data_flush_period', 30 ) ); ?>" />
					<p class="description">Every day we will remove upsell click data older than this number of days.
						Default is 30 days. </p>
				</td>
			</tr>
		</table>

		<!-- Flush buttons -->
		<div style="margin-bottom: 15px">
			<?php wp_nonce_field( 'aetp_flush_page_views_action', 'aetp_flush_page_views_nonce' ); ?>
			<input style="min-width:210px" type="submit" name="aetp_flush_page_views" value="Flush Views Data">
			<span style="margin-left: 10px;">Click this button to manually remove all entries in the Product & Category
				Views
				table.
				Warning: This will reset all product & category view data</span>
		</div>
		<div style="margin-bottom: 15px">
			<?php wp_nonce_field( 'aetp_flush_recommended_data_action', 'aetp_flush_recommended_data_nonce' ); ?>
			<input style="min-width:210px" type="submit" name="aetp_flush_recommended_data"
				value="Flush Recommended Data">
			<span style="margin-left: 10px;">Click this button to manually remove all entries in the recommended data
				table.
			</span>
		</div>

		<!-- Flush buttons -->

		<div style="margin-bottom: 15px">
			<?php wp_nonce_field( 'aetp_flush_click_data_action', 'aetp_flush_click_data_nonce' ); ?>
			<input style="min-width:210px" type="submit" name="aetp_flush_click_data" value="Flush Click Data">
			<span style="margin-left: 10px;">Click this button to manually remove all entries in the Upsell click data
				table.
			</span>
		</div>
		<?php submit_button(); ?>
</div>
</form>

<?php

