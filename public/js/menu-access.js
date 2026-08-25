/**
 * Role → menu access, one role at a time (spec §7, §10).
 *
 * The screen used to be a matrix with a column per role, which stopped being
 * readable somewhere around the eighth role: the menu names scrolled out of
 * view and you were ticking boxes without knowing which row you were on. A
 * role picker plus one grouped list stays the same width whatever the role
 * count, and it means a save touches one role instead of the whole mapping —
 * so two administrators editing different roles no longer overwrite each other.
 *
 * A row is no longer one bit. Each menu carries a switch per verb its routes
 * actually answer, and each switch is a stored grant the middleware reads, so
 * "may open Karyawan" and "may delete a karyawan" are finally separable. The
 * row's own checkbox is the master for its switches, not a third state.
 *
 * The whole mapping is already on the page (window.PARKOPS_MENU_ACCESS), so
 * switching role, copying, searching and folding are all instant. Only saving
 * talks to the server, and it posts the complete list for that one role:
 * within a role an absent menu is a revocation.
 */
(function ($) {
    'use strict';

    $(function () {
        var $card = $('#menuAccess');

        if (! $card.length) {
            return;
        }

        var $role = $('#accessRole');
        var $copy = $('#accessCopy');
        var $boxes = $card.find('.js-menu-access');
        var $items = $card.find('.access-item');
        var $groups = $card.find('.access-group');
        var $summary = $('#accessCount');
        var $badges = $('#accessRoleBadges');
        var $label = $('#accessRoleLabel');
        var $modal = $('#accessModal');
        var $save = $('#saveMenuAccess');
        var $search = $('#accessSearch');
        var $checkAll = $('#accessCheckAll');
        var $empty = $('#accessEmpty');

        // Flattened out of the grouped payload the view renders from, so the
        // page and the script never disagree about which menus exist.
        var menus = [];

        (window.PARKOPS_MENU_ACCESS || []).forEach(function (group) {
            (group.menus || []).forEach(function (menu) { menus.push(menu); });
        });

        /* ── State ──────────────────────────────────────────────────────── */

        // roleId → menuId → { action: true }. Rebuilt from menu.grants, which
        // the server already expanded from "NULL means everything" — the rule
        // lives in MenuAccessService and is not re-implemented here.
        var granted = {};
        var dirty = {};

        function currentRole() {
            return String($role.val());
        }

        function selectedOption() {
            return $role.find('option:selected');
        }

        function isAdmin() {
            return selectedOption().data('code') === 'ADMIN';
        }

        function roleLabel(id) {
            return $role.find('option[value="' + id + '"]').data('code');
        }

        function hasUnsaved() {
            for (var id in dirty) {
                if (dirty[id]) return true;
            }
            return false;
        }

        /** Puts a role back to what the page was last rendered or saved with. */
        function resetRole(id) {
            granted[id] = {};

            menus.forEach(function (menu) {
                var actions = (menu.grants || {})[id];

                if (! actions || ! actions.length) {
                    return;
                }

                var held = {};

                actions.forEach(function (action) { held[action] = true; });
                granted[id][menu.id] = held;
            });

            delete dirty[id];
        }

        $role.find('option').each(function () {
            resetRole(String($(this).val()));
        });

        /* ── Rendering ──────────────────────────────────────────────────── */

        /**
         * The row's checkbox is derived, never stored: a menu is held exactly
         * when at least one of its verbs is. Keeping it as its own flag would
         * allow "granted, but nothing granted" — a sidebar entry that answers
         * 403 at the door.
         */
        function syncRow($item) {
            var $switches = $item.find('.js-action');
            var on = $switches.filter(':checked').length > 0;
            var locked = $item.hasClass('is-locked');

            $item.find('.js-menu-access').prop('checked', on).prop('disabled', locked);
            $item.toggleClass('is-off', ! on);

            // Switches on an unheld menu still show their position — the row is
            // off, not blank — but cannot be moved until the menu is back.
            $switches.prop('disabled', locked || ! on);
        }

        /** Turns a whole row on or off, switches and state together. */
        function setRow($item, on) {
            var role = currentRole();
            var id = $item.data('menu');
            var held = {};

            $item.find('.js-action').each(function () {
                var $switch = $(this);

                $switch.prop('checked', on);
                if (on) held[$switch.data('action')] = true;
            });

            if (on && Object.keys(held).length) {
                granted[role][id] = held;
            } else {
                delete granted[role][id];
            }

            syncRow($item);
        }

        /** Paints every switch on the page for whichever role is selected. */
        function render() {
            var set = granted[currentRole()] || {};
            var admin = isAdmin();

            $items.each(function () {
                var $item = $(this);
                var $box = $item.find('.js-menu-access');
                var held = set[$item.data('menu')] || {};

                // A locked menu is one ADMIN cannot lose: switching it off here
                // would only be undone by the server on save, so the row says
                // so rather than pretending the choice exists.
                var locked = String($box.data('locked')) === '1' && admin;

                $item.toggleClass('is-locked', locked);

                $item.find('.js-action').each(function () {
                    var $switch = $(this);

                    $switch.prop('checked', locked || !! held[$switch.data('action')]);
                });

                syncRow($item);
            });

            renderRoleBadges();
            renderCounts();
        }

        function renderRoleBadges() {
            var $option = selectedOption();
            var html = '';

            // The picker is hidden inside the dialog, so the title is the only
            // thing saying which role these switches belong to.
            $label.text($option.data('code') || '');

            if (String($option.data('system')) === '1') {
                html += '<span class="badge badge-soft">System Role</span>';
            }

            if ($option.text().indexOf('(nonaktif)') !== -1) {
                html += '<span class="badge text-bg-secondary">Nonaktif</span>';
            }

            $badges.html(html);
        }

        /** The summary line, and each group's own tally and tri-state box. */
        function renderCounts() {
            var checked = $boxes.filter(':checked').length;
            var total = $boxes.length;
            var permissions = $card.find('.js-action:checked').length;
            var unsaved = !! dirty[currentRole()];

            $summary
                .toggleClass('is-dirty', unsaved)
                .html(
                    '<strong>' + checked + '</strong> dari ' + total + ' menu · ' +
                    '<strong>' + permissions + '</strong> izin' +
                    (unsaved ? ' <span class="access-unsaved">belum disimpan</span>' : '')
                );

            $groups.each(function () {
                var $group = $(this);
                var $groupBoxes = $group.find('.js-menu-access');
                var on = $groupBoxes.filter(':checked').length;

                $group.find('.access-group-count').text(on + '/' + $groupBoxes.length);

                // Indeterminate rather than a half-truth: an unticked box on a
                // group where three of five are on would read as "none".
                $group.find('.js-group-all')
                    .prop('checked', on > 0 && on === $groupBoxes.length)
                    .prop('indeterminate', on > 0 && on < $groupBoxes.length);
            });

            var all = $boxes.length > 0 && checked === $boxes.length;

            $checkAll
                .prop('checked', all)
                .prop('indeterminate', checked > 0 && ! all);
        }

        /* ── Editing ────────────────────────────────────────────────────── */

        $card.on('change', '.js-action', function () {
            var $switch = $(this);
            var role = currentRole();
            var id = $switch.data('menu');
            var action = $switch.data('action');

            granted[role][id] = granted[role][id] || {};

            if ($switch.is(':checked')) {
                granted[role][id][action] = true;
            } else {
                delete granted[role][id][action];
            }

            // No verbs left is no grant. The row drops out of the payload
            // rather than being saved as an empty one.
            if (! Object.keys(granted[role][id]).length) {
                delete granted[role][id];
            }

            dirty[role] = true;
            syncRow($switch.closest('.access-item'));
            renderCounts();
        });

        /**
         * The row checkbox is a master switch, and deliberately hands over
         * every verb at once — including delete. That used to be the hidden
         * part of ticking a menu; now the switches beside it visibly flip, so
         * what is being granted is on screen rather than in a tooltip.
         */
        $card.on('change', '.js-menu-access', function () {
            setRow($(this).closest('.access-item'), this.checked);

            dirty[currentRole()] = true;
            renderCounts();
        });

        /**
         * Bulk toggles only ever touch what is currently visible: after a
         * search, "pilih semua" that quietly ticked the rows scrolled out of
         * sight would be the opposite of helpful.
         */
        function bulkSet($target, on) {
            $target.filter(':visible').not('.is-locked').each(function () {
                setRow($(this), on);
            });

            dirty[currentRole()] = true;
            renderCounts();
        }

        $card.on('change', '.js-group-all', function () {
            var slug = $(this).data('group');

            bulkSet($card.find('.access-group[data-group="' + slug + '"] .access-item'), this.checked);
        });

        $checkAll.on('change', function () {
            bulkSet($items, this.checked);
        });

        /* ── Copying another role ───────────────────────────────────────── */

        /**
         * Loads the source role's switches into the form and stops there.
         * Nothing is written until Simpan, so a wrong pick costs a second pick
         * rather than an undo — but it does overwrite what is on screen, which
         * is worth one question first.
         */
        $copy.on('change', function () {
            var source = String($copy.val() || '');
            var target = currentRole();

            // Reset immediately: the select is a verb, not a stored setting,
            // and leaving it showing a role would read as one.
            $copy.val('');

            if (! source || source === target) {
                return;
            }

            ParkOps.confirm({
                title: 'Salin hak akses',
                message: 'Ganti seluruh hak akses role ' + roleLabel(target) + ' dengan milik ' +
                    roleLabel(source) + '? Perubahan baru tersimpan setelah Simpan Akses.',
                confirmLabel: 'Ya, salin',
                variant: 'warning'
            }).done(function () {
                var from = granted[source] || {};

                granted[target] = {};

                for (var id in from) {
                    if (Object.prototype.hasOwnProperty.call(from, id)) {
                        granted[target][id] = $.extend({}, from[id]);
                    }
                }

                dirty[target] = true;
                render();

                ParkOps.toast('Hak akses ' + roleLabel(source) + ' disalin. Belum disimpan.');
            });
        });

        /* ── Folding ────────────────────────────────────────────────────── */

        $card.on('click', '.js-group-collapse', function () {
            var $button = $(this);
            var open = $button.attr('aria-expanded') === 'true';

            $button.attr('aria-expanded', open ? 'false' : 'true')
                .closest('.access-group')
                .toggleClass('is-collapsed', open);
        });

        function foldAll(collapsed) {
            $groups.toggleClass('is-collapsed', collapsed);
            $card.find('.js-group-collapse').attr('aria-expanded', collapsed ? 'false' : 'true');
        }

        $('#accessExpand').on('click', function () { foldAll(false); });
        $('#accessCollapse').on('click', function () { foldAll(true); });

        /* ── Search ─────────────────────────────────────────────────────── */

        /**
         * Filters the list in place. A group with no surviving row hides
         * entirely rather than leaving a heading over nothing, and a search
         * opens the groups it matched — a hit inside a folded group the user
         * cannot see is the same as no hit at all.
         */
        function applySearch() {
            var term = $.trim(($search.val() || '').toLowerCase());
            var matches = 0;

            $items.each(function () {
                var $item = $(this);
                var hit = ! term || String($item.data('search')).indexOf(term) !== -1;

                $item.toggleClass('d-none', ! hit);
                if (hit) matches++;
            });

            $groups.each(function () {
                var $group = $(this);
                var visible = $group.find('.access-item').not('.d-none').length;

                $group.toggleClass('d-none', visible === 0);

                if (term) {
                    $group.removeClass('is-collapsed');
                    $group.find('.js-group-collapse').attr('aria-expanded', 'true');
                }
            });

            $empty.toggleClass('d-none', matches > 0);
        }

        $search.on('input', ParkOps.debounce(applySearch, 150));

        /* ── Switching role ─────────────────────────────────────────────── */

        var lastRole = currentRole();

        $role.on('change', function () {
            var target = currentRole();

            if (! dirty[lastRole] || target === lastRole) {
                lastRole = target;
                render();
                return;
            }

            // Edits live only in this page's state until they are saved, so
            // leaving a role without saving would drop them silently.
            $role.val(lastRole);

            ParkOps.confirm({
                title: 'Perubahan belum disimpan',
                message: 'Hak akses role ' + roleLabel(lastRole) + ' sudah diubah tapi belum disimpan. ' +
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

        /* ── Saving ─────────────────────────────────────────────────────── */

        $save.on('click', function () {
            var role = currentRole();
            var payload = [];

            // Read the switches rather than the state object, so the locked
            // ADMIN rows the server is about to re-add ride along and the
            // payload stays an honest picture of what the screen is showing.
            $items.each(function () {
                var $item = $(this);
                var actions = [];

                $item.find('.js-action:checked').each(function () {
                    actions.push($(this).data('action'));
                });

                if (actions.length) {
                    payload.push({ id: $item.data('menu'), actions: actions });
                }
            });

            ParkOps.busy($save, true, 'Menyimpan…');
            $card.addClass('is-saving');

            ParkOps.api({
                url: window.PARKOPS_URLS.menuAccess,
                type: 'PUT',
                data: JSON.stringify({ role_id: parseInt(role, 10), menus: payload }),
                contentType: 'application/json'
            }).done(function () {
                delete dirty[role];

                // What was just stored becomes the baseline a later "buang
                // perubahan" returns to.
                menus.forEach(function (menu) {
                    var saved = null;

                    for (var i = 0; i < payload.length; i++) {
                        if (payload[i].id === menu.id) {
                            saved = payload[i];
                            break;
                        }
                    }

                    menu.grants = menu.grants || {};

                    if (saved) {
                        menu.grants[role] = saved.actions.slice();
                    } else {
                        delete menu.grants[role];
                    }
                });

                ParkOps.toast('Hak akses ' + roleLabel(role) + ' berhasil disimpan.');
                renderCounts();

                // The sidebar is rendered server-side from this mapping, so it
                // only catches up on the next page load — and only the current
                // user's own role can have changed what it shows.
                if (role === String(window.PARKOPS_MENU_ACCESS_SELF)) {
                    setTimeout(function () { window.location.reload(); }, 900);
                } else if ($modal.length) {
                    // Saved and nothing left to warn about, so the dialog has
                    // done its job. Skipped on a self-save, where the reload
                    // above is about to replace the page anyway.
                    bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
                }
            }).fail(ParkOps.handleError).always(function () {
                ParkOps.busy($save, false);
                $card.removeClass('is-saving');
            });
        });

        /*
         * Dismissing the dialog discards whatever is only in page state, the
         * same as switching role did. Bootstrap offers no way to cancel a
         * close, so the dialog goes, the question is asked over the empty
         * page, and the dialog comes back if the answer was no — the
         * alternative is trapping someone inside a dialog they asked to shut.
         *
         * Asked on `hidden` rather than `hide` so the confirm never opens on
         * top of a dialog that is still on its way out: two backdrops stack,
         * and the one underneath is the one being removed.
         */
        $modal.on('hidden.bs.modal', function () {
            var role = currentRole();

            if (! dirty[role]) return;

            ParkOps.confirm({
                title: 'Perubahan belum disimpan',
                message: 'Hak akses role ' + roleLabel(role) + ' sudah diubah tapi belum disimpan. ' +
                    'Tutup dan buang perubahan itu?',
                confirmLabel: 'Ya, buang',
                variant: 'warning'
            }).done(function () {
                resetRole(role);
                render();
            }).fail(function () {
                bootstrap.Modal.getOrCreateInstance($modal[0]).show();
            });
        });

        // A reload mid-edit would drop everything that only exists in page state.
        $(window).on('beforeunload', function (event) {
            if (! hasUnsaved()) return undefined;

            event.preventDefault();
            return '';
        });

        render();
    });
})(jQuery);
