<?php

defined( 'ABSPATH' ) || exit;

$flowy_assets = AETRENDPILOT_PLUGIN_URL . "admin/automation_engine/assets";

?>

<body>
	<div id="navigation">
		<div id="leftside">
			<div id="details">
				<div id="new-button">Create New</div>
				<div class="current-workflow-title" id="currentWorkflowTitle"></div>
			</div>
		</div>
		<div id="centerswitch">
			<div id="leftswitch">Diagram view</div>
			<div id="rightswitch">Automation Stats</div>
			<div id="cronswitch">Next Run: <strong>Cron time not set</strong></div>
		</div>
		<div id="buttonsright">

			<div id="repeat-checkbox-container">
				<input type="checkbox" id="repeat-checkbox" value="0">
				<label for="repeat-checkbox">Repeat?</label>
			</div>
			<div id='load-child-states' style="display:none">Show Branches</div>

			<div id="tour-overlay"
				style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0, 0, 0, 0.6); z-index: 999;">
			</div>

			<div id="tour-message" style="display:none;">
				<span id="tour-text">This is the run button</span>
				<div id="tour-next">Next</div>
			</div>

			<div id="tooltip" style="display: none;"></div>
			<div id="run-button">Run Now</div>
			<div id="reset-button">Reset</div>
			<div id="save-template">Save As New</div>
			<div id="overwrite-template" style="display:none">Save Current</div>
			<div id="publish">Activate Workflow</div>

			<!-- <div id="removeblock">Clear Canvas</div> -->
		</div>
	</div>

	<div id="opencard" style="display: none;">
		<img src="<?php echo esc_url( $flowy_assets . "/closeleft.svg" ); ?>">
	</div>
	<div id="leftcard2">
		<div id="closecard">
			<img src="<?php echo esc_url( $flowy_assets . "/closeleft.svg" ); ?>">
		</div>
		<p id="header"> </p>

		<div id="loggers" class="autom-tab side2">Automations</div>

		<div id="blocklist2">
			<div class="blockelem create-flowy noselect">
				<input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
				<div class="grabme">
					<img src="<?php echo esc_url( $flowy_assets . "/grabme.svg" ); ?>">
				</div>
				<div class="blockin">
					<div class="blockico">
						<span></span>
						<img src="<?php echo esc_url( $flowy_assets . "/eye.svg" ); ?>">
					</div>
					<div class="blocktext">
						<p class="blocktitle"></p>
						<p class="blockdesc"></p>
					</div>
				</div>
			</div>



		</div>
	</div>
	<div id="propwrap">
		<div id="properties">
			<div id="close">
				<img src="<?php echo esc_url( $flowy_assets . "/close.svg" ); ?>">
			</div>
			<p id="header2">Properties</p>
			<div id="propswitch">
				<div id="dataprop">Data</div>
			</div>
			<div id="proplist">
				<p class="inputlabel">Select database</p>
				<div class="dropme">Database 1 <img src="assets/dropdown.svg"></div>
				<p class="inputlabel">Check properties</p>
				<div class="dropme">All<img src="<?php echo esc_url( $flowy_assets . "/dropdown.svg" ); ?>">
				</div>
				<div class="checkus"><img src="<?php echo esc_url( $flowy_assets . "/checkon.svg" ); ?>">
					<p>Log on successful performance</p>
				</div>
				<div class="checkus"><img src="<?php echo esc_url( $flowy_assets . "/checkoff.svg" ); ?>">
					<p>Give priority to this block</p>
				</div>
			</div>

			<button id="saveProperties">Update</button>
			<div id="saveMessage" style="display: none; color: green;margin-top: -10px;margin-bottom: 20px;">
				Canvas updated. Remember to 'Save Current'.
			</div>
			<button id="removeblock">Clear Canvas</button>
		</div>
	</div>
	<div id="canvas">
	</div>
</body>

<div id="trendpilot_workflowNameModal" class="trendpilot_modal" style="display:none;">
	<div class="trendpilot_modal-content">
		<span class="trendpilot_close">&times;</span>
		<p>Enter Workflow Name</p>
		<input type="text" id="trendpilot_workflowNameInput" placeholder="Enter workflow name">
		<button id="trendpilot_saveWorkflowBtn">Save Workflow</button>
	</div>
</div>

<!-- Modal Structure -->
<div id="blockModal" class="modal" style="display: none;">
	<div class="modal-content">
		<span class="close">&times;</span>
		<div id="leftcard">
			<p id="header"> </p>
			<div id="search-ae">
				<img src="<?php echo esc_url( $flowy_assets . "/search.svg" ); ?>">
				<input class="search-blocks-input" type="text" placeholder="Search">
			</div>
			<div id="subnav">
				<div id="events-left" class="navactive side navitem">Triggers</div>
				<div id="actions" class="navdisabled side navitem">Actions</div>
			</div>
		</div>
		<div id="blocklist">
			<div class="blockelem create-flowy noselect">
				<input type="hidden" name='blockelemtype' class="blockelemtype" value="1">
				<div class="grabme">
					<img src="<?php echo esc_url( $flowy_assets . "/grabme.svg" ); ?>">
				</div>
				<div class="blockin">
					<div class="blockico">
						<span></span>
						<img src="<?php echo esc_url( $flowy_assets . "/eye.svg" ); ?>">
					</div>
					<div class="blocktext">
						<p class="blocktitle"></p>
						<p class="blockdesc"></p>
					</div>
				</div>
			</div>
			<div class="blockelem create-flowy noselect">
				<input type="hidden" name='blockelemtype' class="blockelemtype" value="2">
				<div class="grabme">
					<img src="<?php echo esc_url( $flowy_assets . "/grabme.svg" ); ?>">
				</div>
				<div class="blockin">
					<div class="blockico">
						<span></span>
						<img src="<?php echo esc_url( $flowy_assets . "/action.svg" ); ?>">
					</div>
					<div class="blocktext">
						<p class="blocktitle"></p>
						<p class="blockdesc"></p>
					</div>
				</div>
			</div>
			<div class="blockelem create-flowy noselect">
				<input type="hidden" name='blockelemtype' class="blockelemtype" value="3">
				<div class="grabme">
					<img src="<?php echo esc_url( $flowy_assets . "/grabme.svg" ); ?>">
				</div>
				<div class="blockin">
					<div class="blockico">
						<span></span>
						<img src="<?php echo esc_url( $flowy_assets . "/time.svg" ); ?>">
					</div>
					<div class="blocktext">
						<p class="blocktitle">Time has passed</p>
						<p class="blockdesc">Triggers after a specified amount of time</p>
					</div>
				</div>
			</div>
			<div class="blockelem create-flowy noselect">
				<input type="hidden" name='blockelemtype' class="blockelemtype" value="4">
				<div class="grabme">
					<img src="<?php echo esc_url( $flowy_assets . "/closeleft.svg" ); ?>">
				</div>
				<div class="blockin">
					<div class="blockico">
						<span></span>
						<img src="<?php echo esc_url( $flowy_assets . "/error.svg" ); ?>">
					</div>
					<div class="blocktext">
						<p class="blocktitle">Error prompt</p>
						<p class="blockdesc">Triggers when a specified error happens</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>