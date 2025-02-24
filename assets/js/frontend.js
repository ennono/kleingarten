/**
 * Kleingarten Plugin js.
 *
 *  @package Kleingarten Plugin/JS
 */

jQuery(document).ready(function ($) {

    // Do not reload page on like link clicks:
    $('#kleingartenlike').on('click', function (event) {
        event.preventDefault();
    });
    $('#kleingarten-likes-counter-show-all').on('click', function (event) {
        event.preventDefault();
    });

    // Instead do some AJAX stuff on like link click:
    $(document).on("click", "#kleingartenlike", function () {

        $.ajax({
            url: kleingarten_frontend.ajaxurl, // this is the object instantiated in wp_localize_script function
            type: 'POST',
            data: {
                action: "kleingarten_like_post", // this is the action in your functions.php that will be triggered
                nonce: kleingarten_frontend.nonce
            },
            success: function (data) {

                console.log(data.data.label);								// Print "liked" or "Disliked" in console
                console.log(data);

                // Set link text to "liked" oder "Disliked"
                $("#kleingartenlike").text(data.data.label);

                // Set default link text after a few seconds
                setTimeout(function () {
                    // Your jQuery action here
                    $("#kleingartenlike").text(data.data.default_label);
                }, 2000); // Delay in milliseconds

                // Update counter:
                $("#kleingarten-likes-counter-value").text(data.data.counter);

                // Update list of likes:
                if ($("#kleingarten-list-of-likes").length) {
                    $("#kleingarten-list-of-likes").empty();
                    $("#kleingarten-list-of-likes").append(data.data.list_of_likes)
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log(errorThrown);
            }
        });


    });

    // Instead do some AJAX stuff on show all link click:
    $(document).on("click", "#kleingarten-likes-counter > #kleingarten-likes-counter-show-all", function () {

        $.ajax({
            url: kleingarten_frontend.ajaxurl, // this is the object instantiated in wp_localize_script function
            type: 'POST',
            data: {
                action: "kleingarten_show_all_likes", // this is the action in your functions.php that will be triggered
                nonce: kleingarten_frontend.nonce
            },
            success: function (data) {

                console.log(data);

                if (!$('#kleingarten-list-of-likes').is(':visible')) {
                    $("#kleingarten-list-of-likes").show();
                    $("#kleingarten-likes-counter-show-all").text(data.data.show_all_hide_label);
                } else {
                    $("#kleingarten-list-of-likes").hide();
                    $("#kleingarten-likes-counter-show-all").text(data.data.show_all_show_label);
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log(errorThrown);
            }
        });
    });

});