
const assetURL = automationEngineAssets.assetUrl;

//defining variables
var rightcard = false;
var tempblock;
var tempblock2;
let currentJsonWorkflow;
let currentLoadedTemplateID = null;
var notEventAndCanvas;
var aclick = false;
var noinfo = false;

var publishButton = document.getElementById("publish");
publishButton.style.display = "none";

// Select the diagram view
var diagramView = document.getElementById("canvas");
var leftCard = document.getElementById("leftcard");

// Get search input and blocklist elements
var searchInput = document.querySelector(".search-blocks-input");
var blocklist = document.getElementById("blocklist");

// Event listener for the repeat checkbox
var repeatCheckbox = document.getElementById("repeat-checkbox");

// Create a new div for the code editor view
var codeEditorView = document.createElement("div");
codeEditorView.id = "codeEditorView"; // Setting an ID for the new div
codeEditorView.innerHTML = "";
// Adding text to the code editor view
codeEditorView.style.display = "none"; // Initially hiding the code editor view  