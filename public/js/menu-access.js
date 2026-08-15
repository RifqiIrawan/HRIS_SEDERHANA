/**
 * Role → menu access matrix (spec §7, §10).
 *
 * The whole grid is posted on every save: the server treats an absent pair as a
 * revocation, so sending only the ticked boxes would leave access the
 * administrator had just cleared still in place.
 */
(function ($) {
    'use strict';

    $(function () {
        var $table = $('#menuAccessTable');

        if (! $table.length) {
            return;
        }

        $('#saveMenuAccess').on('click', function () {
            var $button = $(this).prop('disabled', true);
            var access = {};

            // Seed every row first, so a menu with nothing ticked still posts an
            // empty list and gets cleared instead of being skipped entirely.
            $table.find('tbody tr[data-menu]').each(function () {
                access[$(this).data('menu')] = [];
            });

            $table.find('.js-menu-access').each(function () {
                var $box = $(this);

                // Disabled boxes are the locked ADMIN cells. The server re-adds
                // them regardless, but sending them keeps the payload an honest
                // picture of what the screen is showing.
                if ($box.is(':checked') || $box.is(':disabled')) {
                    access[$box.data('menu')].push($box.data('role'));
                }
            });

            HRIS.api({
                url: window.HRIS_URLS.menuAccess,
                type: 'PUT',
                data: JSON.stringify({ access: access }),
                contentType: 'application/json'
            }).done(function () {
                HRIS.toast('Akses menu berhasil disimpan.');

                // The sidebar is rendered server-side from this mapping, so the
                // navigation only catches up on the next page load.
                setTimeout(function () { window.location.reload(); }, 900);
            }).fail(HRIS.handleError).always(function () {
                $button.prop('disabled', false);
            });
        });
    });
})(jQuery);
