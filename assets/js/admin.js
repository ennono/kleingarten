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

        var me = this;  // Store a copy of "this" to use it later
        $.ajax({
            url: kleingarten_admin.ajaxurl, // this is the object instantiated in wp_localize_script function
            type: "post",
            dataType: "json",
            data: {
                action: "kleingarten_set_task_status_token", // this is the action in your functions.php that will be triggered
                nonce: kleingarten_admin.nonce,
                task_ID: me.dataset.task_id,
                new_status: me.dataset.status
            },
            success: function (data) {

                //console.log(data);

                // Move task to new status list:
                $( ".kleingarten-task-id-" + data.data.task_ID_updated ).appendTo( $( ".kleingarten-status-slug-" + data.data.new_status.slug ) );

                // Remove new status from "Move To" list:
                $( '.kleingarten-status-list-item-' + data.data.task_ID_updated + '-' + data.data.new_status.slug ).remove();

                // Add old status to "Move To" List:
                $( '.kleingarten-status-list-' + data.data.task_ID_updated ).append( $( '<li class="kleingarten-tasks-kanban-list-item-status-list-item kleingarten-status-list-item-' + data.data.task_ID_updated + '-' + data.data.status_to_add_to_list.slug + '"> <a id="kleingarten-set-task-status" data-task_id="' + data.data.task_ID_updated + '" data-status="' + data.data.status_to_add_to_list.slug + '" href="#">' + data.data.status_to_add_to_list.name + '</a></li>' ));

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log(errorThrown);
            }
        });

    });

});

function addRow() {
    const tbody = document.querySelector('#zaehlerTabelle tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><input type="text" name="typ[]" placeholder="z. B. Wasser" required></td>
      <td><input type="text" name="einheit[]" placeholder="z. B. m³" required></td>
      <td><input type="number" name="preis[]" step="0.01" placeholder="z. B. 1.75" required></td>
      <td><button type="button" onclick="removeRow(this)">Entfernen</button></td>
    `;
    tbody.appendChild(row);
}

function removeRow(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.rows.length > 1) {
        row.remove();
    } else {
        alert("Mindestens ein Zähler muss vorhanden sein.");
    }
}

document.getElementById('zaehlerForm').addEventListener('submit', function (e) {
    e.preventDefault();
    alert('Formular wurde abgeschickt!');
    // Hier kann man Daten weiterverarbeiten oder senden.
});