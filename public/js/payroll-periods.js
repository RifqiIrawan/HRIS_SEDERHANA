/* Periode Payroll (spec §37, §44). */
jQuery(function ($) {
    'use strict';

    ParkOps.crud({
        baseUrl: window.PARKOPS_URLS.base,
        filters: ['#statusFilter'],
        labels: { create: 'Tambah Periode Payroll', edit: 'Ubah Periode Payroll' },
        emptyMessage: 'Belum ada periode payroll.',

        onCreate: function ($form) {
            // Lift any lock left behind by editing a closed period.
            $form.find('input').prop('readonly', false);
            $('.js-save').prop('disabled', false);

            // Default to the current calendar month, the usual case.
            var now = new Date();
            var first = new Date(now.getFullYear(), now.getMonth(), 1);
            var last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            var iso = function (d) {
                return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            };

            $form.find('#period_code').val(now.getFullYear() + '-' + pad(now.getMonth() + 1));
            $form.find('#period_name').val(months[now.getMonth()] + ' ' + now.getFullYear());
            $form.find('#start_date').val(iso(first));
            $form.find('#end_date').val(iso(last));
        },

        fill: function ($form, item) {
            // A closed period is frozen (spec §44) — editing it would be refused
            // by the server anyway, so the fields are locked here too.
            $form.find('input').prop('readonly', !item.editable);
            $('.js-save').prop('disabled', !item.editable);
        },

        columns: [
            { data: 'period_code', className: 'fw-semibold', render: ParkOps.esc },
            {
                data: 'period_name',
                render: function (value, type, row) {
                    return ParkOps.esc(value) + (row.closed_at
                        ? '<div class="small text-body-secondary">Ditutup ' + ParkOps.esc(row.closed_at) + '</div>'
                        : '');
                }
            },
            { data: 'start_date', className: 'text-center small', render: ParkOps.formatDate },
            { data: 'end_date', className: 'text-center small', render: ParkOps.formatDate },
            { data: 'payrolls_count', orderable: false, className: 'text-center text-tabular', render: ParkOps.esc },
            { data: 'status', className: 'text-center', render: ParkOps.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    // A closed period is frozen (spec §44): still openable and
                    // renameable, but no longer deletable.
                    return ParkOps.actionGroup(
                        '<a class="btn btn-sm btn-icon btn-icon-accent" title="Buka payroll"' +
                        ' aria-label="Buka payroll" href="' +
                        window.PARKOPS_URLS.payroll + '?period_id=' + row.id +
                        '"><i class="bi bi-cash-stack"></i></a>' +
                        ParkOps.rowActions(row.id, 'periode ' + row.period_name, { remove: !!row.editable })
                    );
                }
            }
        ]
    });
});
