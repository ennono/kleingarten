/**
 * Kleingarten Plugin admin js.
 *
 *  @package Kleingarten Plugin/JS
 */

jQuery(document).ready(function ($) {

    jQuery('#kleingarten_import_meter_readings_form').attr('enctype', 'multipart/form-data');
    jQuery('#kleingarten_import_meter_readings_form').attr('encoding', 'multipart/form-data');

    $(document).on("click", "#kleingarten-add-meter-reading-submission-tokens #kleingarten-add-token-link", function (event) {

        event.preventDefault();

        $.ajax({
            url: kleingarten_admin.ajaxurl, // this is the object instantiated in wp_localize_script function
            type: "post",
            dataType: "json",
            data: {
                action: "kleingarten_add_meter_reading_submission_token", // this is the action in your functions.php that will be triggered
                nonce: kleingarten_admin.nonce,
                meter_id: $("#post_ID").val()
            },
            success: function (data) {

                // Remove no-token-hint:
                $("#kleingarten-no-existing-tokens-hint").remove();

                // Create a new table row for the new token:
                $("#kleingarten-active-tokens tbody").prepend(
                    $("<tr>").prepend(
                        $('<td>').append(data.data.token)
                    ).append(
                        $('<td>').append(kleingarten_admin.trans_active)
                    ).append(
                        $('<td>').append(data.data.expiry_date)
                    ).append(
                        $('<td>').append(
                            $('<label>').attr('style', 'margin-right: 1rem;').attr('for', 'kleingarten_deactivate_tokens').append(
                                $('<input>').attr('type', 'checkbox').attr('name', 'kleingarten_deactivate_tokens[]').attr('value', data.data.token_meta_id)
                            ).append(
                                kleingarten_admin.trans_deactivate
                            )
                        )
                    )
                );

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log(errorThrown);
            }
        });

    });


    $(document).on("click", "#kleingarten-set-task-status", function (event) {

        $.ajax({
            url: kleingarten_admin.ajaxurl, // this is the object instantiated in wp_localize_script function
            type: "post",
            dataType: "json",
            data: {
                action: "kleingarten_set_task_status_token", // this is the action in your functions.php that will be triggered
                //nonce: kleingarten_admin.nonce,
                //task_ID: $("#task_ID").val()
            },
            success: function (data) {

                console.log("Test");

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log(errorThrown);
            }
        });

    });

});
