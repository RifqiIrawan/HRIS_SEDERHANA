/* Master Karyawan (spec §11). */
jQuery(function ($) {
    'use strict';

    HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput', '#statusFilter'],
        labels: { create: 'Tambah Karyawan', edit: 'Ubah Karyawan' },
        defaults: {
            employment_status: 'PERCOBAAN',
            employment_type: 'DAILY',
            status: 'ACTIVE',
            daily_rate: 0
        },
        emptyMessage: 'Belum ada data karyawan.',

        columns: [
            {
                data: 'employee_code',
                className: 'fw-semibold',
                render: HRIS.esc
            },
            { data: 'full_name', render: HRIS.esc },
            {
                data: 'nik',
                className: 'small text-body-secondary',
                render: function (value) { return HRIS.esc(value || '−'); }
            },
            {
                data: 'phone',
                className: 'small',
                render: function (value) { return HRIS.esc(value || '−'); }
            },
            {
                data: 'employment_type',
                render: function (value) {
                    return '<span class="badge text-bg-light border">' + HRIS.esc(value) + '</span>';
                }
            },
            {
                data: 'join_date',
                className: 'small',
                render: function (value) { return value ? HRIS.formatDate(value) : '−'; }
            },
            {
                data: 'daily_rate',
                className: 'text-end text-tabular',
                render: HRIS.formatRupiah
            },
            {
                data: 'status',
                className: 'text-center',
                render: HRIS.statusBadge
            },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    return HRIS.rowActions(row.id, 'karyawan ' + row.employee_code);
                }
            }
        ]
    });
});
