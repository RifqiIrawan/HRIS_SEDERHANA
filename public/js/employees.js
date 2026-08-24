/* Master Karyawan (spec §11). */
jQuery(function ($) {
    'use strict';

    // Status kepegawaian, tipe dan status karyawan are master data now
    // (Master Data → Status Kepegawaian / Tipe Kepegawaian / Status Karyawan),
    // so the three dropdowns are filled from /lookups instead of the markup.
    var refs = { employment_statuses: [], employment_types: [], employee_statuses: [] };

    /** Reference options are selected by code — that is what an employee row stores. */
    function fillRef($select, items, placeholder) {
        return HRIS.fillSelect($select, items, placeholder, { valueKey: 'code' });
    }

    /** code → name, for rendering a list cell without a second request. */
    function labelFor(list, code) {
        for (var i = 0; i < list.length; i++) {
            if (list[i].code === code) return list[i].label;
        }
        return code;
    }

    function firstCode(list) {
        return list.length ? list[0].code : '';
    }

    /**
     * Keeps a value the master has since deactivated selectable while editing
     * that employee: /lookups only returns ACTIVE rows, so without this the
     * field would silently fall back to the first option and the save would
     * quietly change the record.
     */
    function ensureOption($select, code) {
        if (!code) return;

        if (!$select.find('option').filter(function () { return this.value === code; }).length) {
            $select.append($('<option></option>').val(code).text(code + ' (nonaktif)'));
        }

        $select.val(code);
    }

    HRIS.lookups()
        .done(function (data) {
            refs = {
                employment_statuses: data.employment_statuses || [],
                employment_types: data.employment_types || [],
                employee_statuses: data.employee_statuses || []
            };

            fillRef($('#statusFilter'), refs.employee_statuses, 'Semua Status');
            fillRef($('#employment_status'), refs.employment_statuses, null);
            fillRef($('#employment_type'), refs.employment_types, null);
            fillRef($('#status'), refs.employee_statuses, null);

            // The table may already have drawn with raw codes in those columns.
            if (crud) crud.table.rows().invalidate().draw(false);
        })
        .fail(HRIS.handleError);

    var crud = HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput', '#statusFilter'],
        labels: { create: 'Tambah Karyawan', edit: 'Ubah Karyawan' },
        defaults: { daily_rate: 0 },
        emptyMessage: 'Belum ada data karyawan.',

        // Defaults are whatever each master lists first, not a value baked in
        // here — the point of the masters is that the list can change.
        onCreate: function ($form) {
            $form.find('#employment_status').val(firstCode(refs.employment_statuses));
            $form.find('#employment_type').val(firstCode(refs.employment_types));
            $form.find('#status').val(firstCode(refs.employee_statuses));
        },

        fill: function ($form, item) {
            ensureOption($form.find('#employment_status'), item.employment_status);
            ensureOption($form.find('#employment_type'), item.employment_type);
            ensureOption($form.find('#status'), item.status);
        },

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
                    return '<span class="badge text-bg-light border">' +
                        HRIS.esc(labelFor(refs.employment_types, value)) + '</span>';
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
