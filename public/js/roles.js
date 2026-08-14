/* Master Role (spec §10). */
jQuery(function ($) {
    'use strict';

    HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput'],
        labels: { create: 'Tambah Role', edit: 'Ubah Role' },
        defaults: { status: 'ACTIVE' },
        emptyMessage: 'Belum ada role.',

        fill: function ($form, item) {
            // The three built-in codes are referenced by the authorisation
            // checks, so their code field is locked (the server enforces this
            // too — this only avoids a pointless round-trip).
            $form.find('#role_code').prop('readonly', !!item.is_system);
        },

        onCreate: function ($form) {
            $form.find('#role_code').prop('readonly', false);
        },

        columns: [
            {
                data: 'role_code',
                className: 'fw-semibold',
                render: function (value, type, row) {
                    return HRIS.esc(value) + (row.is_system
                        ? ' <i class="bi bi-lock-fill text-body-secondary small" title="Role sistem"></i>'
                        : '');
                }
            },
            { data: 'role_name', render: HRIS.esc },
            { data: 'users_count', orderable: false, className: 'text-center text-tabular', render: HRIS.esc },
            { data: 'status', className: 'text-center', render: HRIS.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    // A built-in role is referenced by the authorisation checks,
                    // so it can be renamed but never deleted.
                    return HRIS.rowActions(row.id, 'role ' + row.role_code, { remove: !row.is_system });
                }
            }
        ]
    });
});
