jQuery(document).ready(function() {
    let $all_checkbox = $('table input[name=select_all]');
    let $checkboxes = $('table input[name^=agencies]');

    if ($checkboxes.filter(':checked').length === $checkboxes.length) {
        $all_checkbox.prop('checked', true);
    }

    $all_checkbox.click(function () {
        $('table input[name^=agencies]').prop('checked', $(this).prop('checked'));
    });

    $checkboxes.click(function () {
        if (!$(this).prop('checked')) {
            $('table input[name=select_all]').prop('checked', false);
        }

        if ($checkboxes.filter(':checked').length === $checkboxes.length) {
            $all_checkbox.prop('checked', true);
        }
    });
});
