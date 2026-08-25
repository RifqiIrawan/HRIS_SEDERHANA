/*
 * Laporan Absensi & Laporan Payroll (spec §60 phase 6).
 *
 * Both tables are server-side: the rows on screen are one page, while the
 * footer totals and the CSV export always cover the whole filtered set.
 */
(function ($, ParkOps) {
    'use strict';

    /** Appends the current filter values to the export URL. */
    function exportUrl(params) {
        var query = $.param($.extend({}, params));
        return window.PARKOPS_URLS.export + (query ? '?' + query : '');
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

            $(this).text(money.indexOf(key) !== -1 ? ParkOps.formatRupiah(value) : ParkOps.formatNumber(value));
        });

        $('#reportFoot').removeClass('d-none');
    }

    /* ── Laporan Absensi ────────────────────────────────────────────── */

    ParkOps.attendanceReport = function () {
        ParkOps.lookups()
            .done(function (data) { ParkOps.fillSelect($('#locationFilter'), data.locations, 'Semua Lokasi'); })
            .fail(ParkOps.handleError);

        function params() {
            return {
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                location_id: $('#locationFilter').val() || undefined,
                include_inactive: $('#includeInactive').is(':checked') ? 1 : undefined,
                search: $('#searchInput').val() || undefined
            };
        }

        var table = ParkOps.dataTable('#reportTable', {
            ajax: {
                url: window.PARKOPS_URLS.base,
                dataSrc: function (json) {
                    renderTotals(json.totals, []);
                    return json.data || [];
                }
            },
            params: params,
            language: $.extend({}, ParkOps.dtLanguage, {
                emptyTable: 'Tidak ada data pada rentang ini.'
            }),
            columns: [
                { data: 'employee_code', className: 'fw-semibold small', render: ParkOps.esc },
                { data: 'employee_name', render: ParkOps.esc },
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

        $('#searchInput').on('input', ParkOps.debounce(function () {
            table.ajax.reload(null, true);
        }, 400));

        $('#exportButton').on('click', function () {
            window.location.href = exportUrl(params());
        });
    };

    /* ── Laporan Payroll ────────────────────────────────────────────── */

    ParkOps.payrollReport = function () {
        function params() {
            return {
                period_id: $('#periodSelect').val(),
                search: $('#searchInput').val() || undefined
            };
        }

        var table = ParkOps.dataTable('#reportTable', {
            // Nothing to fetch until a period exists to report on.
            deferLoading: 0,
            ajax: {
                url: window.PARKOPS_URLS.base,
                dataSrc: function (json) {
                    $('#periodInfo').text(json.period
                        ? json.period.range + ' · status ' + json.period.status
                        : 'Periode tidak ditemukan.');

                    renderTotals(json.totals, ['gross', 'deduction', 'net']);

                    return json.data || [];
                }
            },
            params: params,
            language: $.extend({}, ParkOps.dtLanguage, {
                emptyTable: 'Payroll periode ini belum digenerate.'
            }),
            columns: [
                { data: 'employee_code', className: 'fw-semibold small', render: ParkOps.esc },
                { data: 'employee_name', render: ParkOps.esc },
                { data: 'present_days', orderable: false, className: 'text-center text-tabular' },
                { data: 'late_days', orderable: false, className: 'text-center text-tabular' },
                { data: 'working_days', className: 'text-center text-tabular fw-semibold' },
                { data: 'daily_rate', orderable: false, className: 'text-end text-tabular small', render: ParkOps.formatRupiah },
                { data: 'gross_salary', className: 'text-end text-tabular', render: ParkOps.formatRupiah },
                {
                    data: 'total_deduction',
                    className: 'text-end text-tabular',
                    render: function (value) {
                        return value > 0
                            ? '<span class="text-danger">− ' + ParkOps.formatRupiah(value) + '</span>'
                            : '−';
                    }
                },
                { data: 'net_salary', className: 'text-end text-tabular fw-semibold', render: ParkOps.formatRupiah }
            ]
        });

        ParkOps.lookups()
            .done(function (data) {
                var periods = (data.periods || []).map(function (p) {
                    return { id: p.id, code: p.code, label: p.label + ' (' + p.status + ')' };
                });

                ParkOps.fillSelect($('#periodSelect'), periods, periods.length ? null : 'Belum ada periode');

                if ($('#periodSelect').val()) table.ajax.reload(null, true);
            })
            .fail(ParkOps.handleError);

        $('#periodSelect').on('change', function () { table.ajax.reload(null, true); });

        $('#searchInput').on('input', ParkOps.debounce(function () {
            table.ajax.reload(null, true);
        }, 400));

        $('#exportButton').on('click', function () {
            if (!$('#periodSelect').val()) {
                ParkOps.toast('Pilih periode payroll terlebih dahulu.', 'warning');
                return;
            }

            window.location.href = exportUrl(params());
        });
    };
})(jQuery, window.ParkOps);
