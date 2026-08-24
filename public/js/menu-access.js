/**
 * Role → menu access, one role at a time (spec §7, §10).
 *
 * The screen used to be a matrix with a column per role, which stopped being
 * readable somewhere around the eighth role: the menu names scrolled out of
 * view and you were ticking boxes without knowing which row you were on. A
 * role picker plus one list of menus stays the same width whatever the role
 * count, and it means a save touches one role instead of the whole mapping —
 * so two administrators editing different roles no longer overwrite each other.
 *
 * The full mapping is already on the page (window.HRIS_MENU_ACCESS), so
 * switching role is instant rather than a round-trip. Only saving talks to the
 * server, and it posts the complete list for that one role: within a role an
 * absent menu is a revocation.
 */
(function ($) {
    'use strict';

    $(function () {
        var $card = $('#menuAccess');

        if (! $card.length) {
            return;
        }

        var $role = $('#accessRole');
        var $boxes = $card.find('.js-menu-access');
        var $count = $('#accessCount');
        var $save = $('#saveMenuAccess');

        var menus = window.HRIS_MENU_ACCESS || [];

        /* ── State ──────────────────────────────────────────────────────── */

        // roleId → { menuId: true }. Built by inverting the mapping the page
        // was rendered with, and edited in place from there.
        var granted = {};
        var dirty = {};

        $role.find('option').each(function () {
            granted[$(this).val()] = {};
        });

        menus.forEach(function (menu) {
            (menu.role_ids || []).forEach(function (roleId) {
                if (granted[roleId]) granted[roleId][menu.id] = true;
            });
        });

        function currentRole() {
            return $role.val();
        }

        function isAdmin() {
            return $role.find('option:selected').data('code') === 'ADMIN';
        }

        function hasUnsaved() {
            for (var id in dirty) {
                if (dirty[id]) return true;
            }
            return false;
        }

        /* ── Rendering ──────────────────────────────────────────────────── */

        /** Paints the checkboxes for whichever role is selected. */
        function render() {
            var role = currentRole();
            var admin = isAdmin();
            var set = granted[role] || {};

            $boxes.each(function () {
                var $box = $(this);
                var locked = String($box.data('locked')) === '1' && admin;

                // A locked menu is one ADMIN cannot lose: unticking it here
                // would only be undone by the server on save, so the box says
                // so instead of pretending the choice exists.
                $box.prop('checked', locked || !! set[$box.data('menu')])
                    .prop('disabled', locked);
            });

            renderCount();
            renderGroupToggles();
        }

        function renderCount() {
            var total = $boxes.length;
            var checked = $boxes.filter(':checked').length;

            $count
                .toggleClass('is-dirty', !! dirty[currentRole()])
                .text(checked + ' dari ' + total + ' menu dipilih' +
                    (dirty[currentRole()] ? ' · belum disimpan' : ''));
        }

        /** Each group's button offers whichever action is still available. */
        function renderGroupToggles() {
            $card.find('.js-group-toggle').each(function () {
                var $button = $(this);
                var $group = groupBoxes($button.data('group'));
                var allOn = $group.length > 0 && $group.filter(':not(:checked)').length === 0;

                $button.text(allOn ? 'Kosongkan' : 'Pilih semua').data('turnOn', ! allOn);
            });
        }

        function groupBoxes(group) {
            return $card.find('.menu-access-item').filter(function () {
                return $(this).data('group') === group;
            }).find('.js-menu-access');
        }

        /* ── Editing ────────────────────────────────────────────────────── */

        $boxes.on('change', function () {
            var $box = $(this);
            var role = currentRole();
            var menu = $box.data('menu');

            if ($box.is(':checked')) {
                granted[role][menu] = true;
            } else {
                delete granted[role][menu];
            }

            dirty[role] = true;
            renderCount();
            renderGroupToggles();
        });

        $card.on('click', '.js-group-toggle', function () {
            var $button = $(this);
            var turnOn = $button.data('turnOn') !== false;

            // Disabled boxes are the locked ADMIN ones; .change() would not
            // fire for them anyway, and their state is not ours to set.
            groupBoxes($button.data('group'))
                .not(':disabled')
                .prop('checked', turnOn)
                .trigger('change');
        });

        /* ── Switching role ─────────────────────────────────────────────── */

        var lastRole = $role.val();

        $role.on('change', function () {
            var target = $role.val();

            if (! dirty[lastRole]) {
                lastRole = target;
                render();
                return;
            }

            // Edits live only in this page's state until they are saved, so
            // leaving a role without saving would drop them silently.
            $role.val(lastRole);

            HRIS.confirm({
                title: 'Perubahan belum disimpan',
                message: 'Akses role ' + roleLabel(lastRole) + ' sudah diubah tapi belum disimpan. ' +
                    'Pindah role dan buang perubahan itu?',
                confirmLabel: 'Ya, buang',
                variant: 'warning'
            }).done(function () {
                resetRole(lastRole);
                lastRole = target;
                $role.val(target);
                render();
            });
        });

        function roleLabel(id) {
            return $role.find('option[value="' + id + '"]').data('code');
        }

        /** Puts a role back to what the page was rendered with. */
        function resetRole(id) {
            granted[id] = {};

            menus.forEach(function (menu) {
                if ((menu.role_ids || []).indexOf(parseInt(id, 10)) !== -1) {
                    granted[id][menu.id] = true;
                }
            });

            delete dirty[id];
        }

        /* ── Saving ─────────────────────────────────────────────────────── */

        $save.on('click', function () {
            var role = currentRole();
            var selected = [];

            // Read the boxes rather than the state object, so the locked ADMIN
            // ones the server is about to re-add are in the payload too and it
            // stays an honest picture of what the screen is showing.
            $boxes.filter(':checked').each(function () {
                selected.push($(this).data('menu'));
            });

            HRIS.busy($save, true, 'Menyimpan…');

            HRIS.api({
                url: window.HRIS_URLS.menuAccess,
                type: 'PUT',
                data: JSON.stringify({ role_id: parseInt(role, 10), menus: selected }),
                contentType: 'application/json'
            }).done(function () {
                delete dirty[role];

                // The saved state is now the baseline a "buang perubahan"
                // should return to.
                menus.forEach(function (menu) {
                    var ids = (menu.role_ids || []).filter(function (id) {
                        return String(id) !== String(role);
                    });

                    if (selected.indexOf(menu.id) !== -1) ids.push(parseInt(role, 10));

                    menu.role_ids = ids;
                });

                HRIS.toast('Akses menu ' + roleLabel(role) + ' berhasil disimpan.');
                renderCount();

                // The sidebar is rendered server-side from this mapping, so it
                // only catches up on the next page load — and only the current
                // user's own role can have changed what it shows.
                if (String(role) === String(window.HRIS_MENU_ACCESS_SELF)) {
                    setTimeout(function () { window.location.reload(); }, 900);
                }
            }).fail(HRIS.handleError).always(function () {
                HRIS.busy($save, false);
            });
        });

        // A reload mid-edit would drop everything that is only in page state.
        $(window).on('beforeunload', function (event) {
            if (! hasUnsaved()) return undefined;

            event.preventDefault();
            return '';
        });

        render();
    });
})(jQuery);
