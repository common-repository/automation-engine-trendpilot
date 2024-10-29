/**
 * Tooltips for buttons and functions to show/hide tooltips dynamically.
 *
 * Tooltips are defined as key-value pairs where keys correspond to button IDs and values are tooltip messages.
 * These messages provide guidance or information when interacting with specific buttons in a UI.
 *
 * Tooltips Object Structure:
 * - Each key represents the ID of a button.
 * - Each value is an HTML-formatted string providing detailed information about the button's functionality.
 *
 * Functions:
 * - `showTooltip(event)`: Displays the tooltip for the button triggered by the `mouseover` event.
 *   It fetches the corresponding tooltip message from the `tooltips` object based on the button's ID.
 *   The tooltip is positioned just below the button.
 *
 * - `hideTooltip()`: Hides the tooltip when the mouse moves away from the button (triggered by `mouseout` event).
 *
 * Note:
 * - To use these functions, ensure there is an HTML element with ID 'tooltip' to display tooltip content.
 * - Styling adjustments (e.g., positioning) can be customized in these functions based on UI requirements.
 */

// Tooltip messages for each button
const tooltips = {
    "run-button": "Run workflow now (from current step)",
    "reset-button": "<p>Delete this workflow's current progression</p><p>Includes branches</p>",
    "save-template": "Create new workflow",
    "overwrite-template": "Overwrite workflow",
    "publish": "Activate/Deactivate workflow",
    "repeat-checkbox-container": "<p>Allow workflow to repeat</p><p>Must end on 'End Workflow' trigger or an action</p><p style='background-color:#ffe3e3'>Warning: may cause infinite loop - </p><p style='background-color:#ffe3e3'>only use on workflows containing 'Wait X days'</p>",
    "load-child-states": "<p>For workflows containing 'Any' triggers</p><p>Show all current completed branches</p><p>If 'repeat' is on, completed branches are removed.</p>"
};

// Function to show the tooltip
function showTooltip(event) {
    const tooltip = document.getElementById('tooltip');
    tooltip.style.display = 'block';
    tooltip.innerHTML = tooltips[event.currentTarget.id];
    tooltip.style.left = event.currentTarget.offsetLeft + 'px';
    tooltip.style.top = (event.currentTarget.offsetTop + event.currentTarget.offsetHeight + 5) + 'px'; // 5px below the button
}

// Function to hide the tooltip
function hideTooltip() {
    const tooltip = document.getElementById('tooltip');
    tooltip.style.display = 'none';
}