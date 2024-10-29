// js/tooltip-icons.js
document.addEventListener('DOMContentLoaded', function () {
    var tooltipIcons = document.querySelectorAll('.tooltip-icon');

    tooltipIcons.forEach(function (icon) {
        icon.addEventListener('mouseover', function () {
            this.querySelector('.tp-tooltip-image').style.display = 'block';
        });

        icon.addEventListener('mouseout', function () {
            this.querySelector('.tp-tooltip-image').style.display = 'none';
        });
    });
});
