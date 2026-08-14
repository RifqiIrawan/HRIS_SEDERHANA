/*
 * Laporan Absensi & Laporan Payroll (spec §60 phase 6).
 *
 * Both tables are server-side: the rows on screen are one page, while the
 * footer totals and the CSV export always cover the whole filtered set.
 */
(function ($, HRIS) {
    'use strict';

    /** Appends the current filter values to the export URL. */
    function exportUrl(params) {
        var query = $.param($.extend({}, params));
        return window.HRIS_URLS.export + (query ? '?' + query : '');
    }

    /**
     * Writes the server-computed totals into the footer cells.
     *
     * @param {object} totals
     * @param {string[]} money  keys rendered as rupiah rather than a count
     */
    function renderTotals(totals, money) {
        if (!totals) {
            $('#reportFoot').addClass('d-none');
            return;
        }

        $('[data-total]').each(function () {
            var key = $(this).data('total');
            var value = totals[key] || 0;

            $(this).text(money.indexOf(key) !== -1 ? HRIS.formatRupiah(value) : HRIS.formatNumber(value));
        });

        $('#reportFoot').removeClass('d-none');
    }

    /* ── Laporan Absensi ────────────────────────────────────────────── */

    HRIS.attendanceReport = function () {
        HRIS.lookups()
            .done(function (data) { HRIS.fillSelect($('#locationFilter'), data.locations, 'Semua Lokasi'); })
            .fail(HRIS.handleError);

        function params() {
            return {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                location_id: $('#locationFilter').val() || undefined,
                include_inactive: $('#includeInactive').is(':checked') ? 1 : undefined,
                search: $('#searchInput').val() || undefined
            };
        }

        var table = HRIS.dataTable('#reportTable', {
            ajax: {
                url: window.HRIS_URLS.base,
                dataSrc: function (json) {
                    renderTotals(json.totals, []);
                    return json.data || [];
                }
            },
            params: params,
            language: $.extend({}, HRIS.dtLanguage, {
                emptyTable: 'Tidak ada data pada rentang ini.'
            }),
            columns: [
                { data: 'employee_code', className: 'fw-semibold small', render: HRIS.esc },
                { data: 'employee_name', render: HRIS.esc },
                { data: 'present', orderable: false, className: 'text-center text-tabular' },
                { data: 'late', orderable: false, className: 'text-center text-tabular' },
                { data: 'incomplete', orderable: false, className: 'text-center text-tabular' },
                { data: 'absent', orderable: false, className: 'text-center text-tabular' },
                { data: 'working_days', orderable: false, className: 'text-center text-tabular fw-semibold' },
                {
                    data: 'late_minutes',
                    orderable: false,
                    className: 'text-end text-tabular',
                    render: function (value) { return value + ' mnt'; }
                }
            ]
        });

        $('#startDate, #endDate, #locationFilter, #includeInactive').on('change', function () {
            table.ajax.reload(null, true);
        });

        $('#searchInput').on('input', HRIS.debounce(function () {
            table.ajax.reload(null, true);
        }, 400));

        $('#exportButton').on('click', function () {
            window.location.href = exportUrl(params());
        });
    };

    /* ── Laporan Payroll ────────────────────────────────────────────── */

    HRIS.payrollReport = function () {
        function params() {
            return {
                period_id: $('#periodSelect').val(),
                search: $('#searchInput').val() || undefined
            };
        }

        var table = HRIS.dataTable('#reportTable', {
            // Nothing to fetch until a period exists to report on.
            deferLoading: 0,
            ajax: {
                url: window.HRIS_URLS.base,
                dataSrc: function (json) {
                    $('#periodInfo').text(json.period
                        ? json.period.range + ' · status ' + json.period.status
                        : 'Periode tidak ditemukan.');

                    renderTotals(json.totals, ['gross', 'deduction', 'net']);

                    return json.data || [];
                }
            },
            params: params,
            language: $.extend({}, HRIS.dtLanguage, {
                emptyTable: 'Payroll periode ini belum digenerate.'
            }),
            columns: [
                { data: 'employee_code', className: 'fw-semibold small', render: HRIS.esc },
                { data: 'employee_name', render: HRIS.esc },
                { data: 'present_days', orderable: false, className: 'text-center text-tabular' },
                { data: 'late_days', orderable: false, className: 'text-center text-tabular' },
                { data: 'working_days', className: 'text-center text-tabular fw-semibold' },
                { data: 'daily_rate', orderable: false, className: 'text-end text-tabular small', render: HRIS.formatRupiah },
                { data: 'gross_salary', className: 'text-end text-tabular', render: HRIS.formatRupiah },
                {
                    data: 'total_deduction',
                    className: 'text-end text-tabular',
                    render: function (value) {
                        return value > 0
                            ? '<span class="text-danger">− ' + HRIS.formatRupiah(value) + '</span>'
                            : '−';
                    }
                },
                { data: 'net_salary', className: 'text-end text-tabular fw-semibold', render: HRIS.formatRupiah }
            ]
        });

        HRIS.lookups()
            .done(function (data) {
                var periods = (data.periods || []).map(function (p) {
                    return { id: p.id, code: p.code, label: p.label + ' (' + p.status + ')' };
                });

                HRIS.fillSelect($('#periodSelect'), periods, periods.length ? null : 'Belum ada periode');

                if ($('#periodSelect').val()) table.ajax.reload(null, true);
            })
            .fail(HRIS.handleError);

        $('#periodSelect').on('change', function () { table.ajax.reload(null, true); });

        $('#searchInput').on('input', HRIS.debounce(function () {
            table.ajax.reload(null, true);
        }, 400));

        $('#exportButton').on('click', function () {
            if (!$('#periodSelect').val()) {
                HRIS.toast('Pilih periode payroll terlebih dahulu.', 'warning');
                return;
            }

            window.location.href = exportUrl(params());
        });
    };
})(jQuery, window.HRIS);
