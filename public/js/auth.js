/* Login screen (spec §47). Posts over AJAX so the error text can be shown
   inline without a full page round-trip. */
jQuery(function ($) {
    'use strict';

    var $form = $('#loginForm');
    var $button = $('#loginButton');
    var $alert = $('#loginAlert');

    $('#togglePassword').on('click', function () {
        var $input = $('#password');
        var isHidden = $input.attr('type') === 'password';

        $input.attr('type', isHidden ? 'text' : 'password');
        $(this).find('i').attr('class', isHidden ? 'bi bi-eye-slash' : 'bi bi-eye');
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        $alert.addClass('d-none').text('');
        ParkOps.clearErrors($form);
        ParkOps.busy($button, true, 'Memeriksa…');

        ParkOps.api({
            url: window.PARKOPS_ROUTES.login,
            type: 'POST',
            data: {
                email: $('#email').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked') ? 1 : 0
            }
        })
            .done(function (data) {
                // Keep the button disabled through the redirect so a second
                // submit cannot fire while the browser is navigating.
                window.location.href = data.redirect;
            })
            .fail(function (error) {
                ParkOps.busy($button, false);
                $alert.removeClass('d-none').text(error.message);
                ParkOps.showErrors($form, error.errors);
                $('#password').val('').trigger('focus');
            });
    });
});
