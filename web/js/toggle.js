function toggle(button)
{
    var isChecked = true;
    var inputs = document.querySelectorAll('table input[type=checkbox]');

    for (var input of inputs) {
        if (input.checked) {
            isChecked = false;
            break;
        }
    }

    for (var input of inputs) {
        input.checked = isChecked;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var buttons = document.querySelectorAll('.toggle');

    [].forEach.call(buttons, function (button) {
        button.addEventListener('click', toggle);
    });
});
