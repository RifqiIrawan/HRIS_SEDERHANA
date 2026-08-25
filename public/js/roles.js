/* Master Role (spec §10). */
jQuery(function ($) {
    'use strict';

    var crud = ParkOps.crud({
        baseUrl: window.PARKOPS_URLS.base,
        filters: ['#searchInput'],
        labels: { create: 'Tambah Role', edit: 'Ubah Role' },
        defaults: { status: 'ACTIVE' },
        emptyMessage: 'Belum ada role.',

        fill: function ($form, item) {
            // The three built-in codes are referenced by the authorisation
            // checks, so their code field is locked (the server enforces this
            // too — this only avoids a pointless round-trip).
            $form.find('#role_code').prop('readonly', !! item.is_system);
            $form.find('.js-system-role-note').toggleClass('d-none', ! item.is_system);
        },

        onCreate: function ($form) {
            $form.find('#role_code').prop('readonly', false);
            $form.find('.js-system-role-note').addClass('d-none');
        },

        columns: [
            {
                data: 'role_code',
                className: 'fw-semibold text-nowrap',
                render: ParkOps.esc
            },
            {
                data: 'role_name',
                render: function (value, type, row) {
                    // The badge carries what the lock icon used to only hint
                    // at, and says it in words rather than in an icon nobody
                    // hovers long enough to read.
                    return ParkOps.esc(value) + (row.is_system
                        ? ' <span class="badge badge-soft ms-1">System Role</span>'
                        : '');
                }
            },
            {
                data: 'users_count',
                orderable: false,
                className: 'text-center text-tabular',
                render: function (value) {
                    return value
                        ? ParkOps.esc(value)
                        : '<span class="text-body-secondary">0</span>';
                }
            },
            { data: 'status', className: 'text-center', render: ParkOps.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    // A built-in role is referenced by the authorisation checks,
                    // so it can be renamed but never deleted. The entry stays in
                    // the menu, disabled, rather than disappearing — an action
                    // that is missing on some rows reads as a rendering bug.
                    // Hak akses is what this screen is for, so it gets a
                    // button of its own rather than a second click inside a
                    // menu; the two that edit the role itself stay in the menu.
                    return ParkOps.actionGroup(
                        '<button type="button" class="btn btn-sm btn-icon js-manage-access"' +
                        ' data-role="' + row.id + '" data-code="' + ParkOps.esc(row.role_code) + '"' +
                        ' title="Atur hak akses" aria-label="Atur hak akses ' + ParkOps.esc(row.role_code) + '">' +
                        '<i class="bi bi-shield-check"></i></button>' +
                        ParkOps.rowMenu([
                        {
                            label: 'Ubah role',
                            icon: 'pencil',
                            className: 'js-edit',
                            data: { id: row.id }
                        },
                        '-',
                        {
                            label: row.is_system ? 'Role sistem — tidak bisa dihapus' : 'Hapus role',
                            icon: 'trash',
                            className: 'js-delete',
                            danger: ! row.is_system,
                            disabled: !! row.is_system,
                            data: { id: row.id, label: 'role ' + row.role_code }
                        }
                    ]));
                }
            }
        ]
    });

    /**
     * Opens the access dialog on the role of the row that was clicked.
     *
     * The hidden #accessRole select is still what menu-access.js reads the
     * current role from, so setting it and firing change is what loads that
     * role's switches — the dialog only has to be shown afterwards.
     */
    $('#dataTable').on('click', '.js-manage-access', function () {
        var $modal = $('#accessModal');

        if (! $modal.length) return;

        $('#accessRole').val($(this).data('role')).trigger('change');

        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    // The user count on each row is a live figure, so a role saved from the
    // access panel should not leave a stale one behind it.
    window.PARKOPS_ROLES_RELOAD = function () { crud.reload(); };
});
