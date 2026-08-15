/*
 * HRIS Juru Parkir — shared frontend helpers (spec §30, §55).
 *
 * Every module talks to Laravel through HRIS.api(), so the CSRF header, the
 * { success, message, data } envelope and error surfacing are handled once
 * here instead of being re-implemented per page.
 */
window.HRIS = (function ($) {
    'use strict';

    // Spec §55 — attach the CSRF token to every request jQuery makes.
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    var rupiah = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    });

    var number = new Intl.NumberFormat('id-ID');

    /* ── Notifications ──────────────────────────────────────────────── */

    function toast(message, variant) {
        variant = variant || 'success';

        var icons = {
            success: 'check-circle-fill',
            danger: 'exclamation-octagon-fill',
            warning: 'exclamation-triangle-fill',
            info: 'info-circle-fill'
        };

        var $toast = $(
            '<div class="toast align-items-center text-bg-' + variant + ' border-0" role="alert">' +
            '  <div class="d-flex">' +
            '    <div class="toast-body d-flex align-items-start gap-2">' +
            '      <i class="bi bi-' + (icons[variant] || icons.info) + '"></i>' +
            '      <span class="flex-grow-1"></span>' +
            '    </div>' +
            '    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '  </div>' +
            '</div>'
        );

        // .text() rather than string concatenation: server messages can echo
        // user-supplied names and must never be parsed as HTML.
        $toast.find('span').text(message);
        $('#hrisToasts').append($toast);

        var instance = new bootstrap.Toast($toast[0], { delay: variant === 'danger' ? 6000 : 3500 });
        instance.show();
        $toast.on('hidden.bs.toast', function () { $toast.remove(); });
    }

    /* ── AJAX ───────────────────────────────────────────────────────── */

    /**
     * Thin wrapper over $.ajax that returns a promise resolving to the
     * response `data`, and rejects with a normalised { message, code, errors }.
     */
    function api(options) {
        var settings = $.extend({ type: 'GET', dataType: 'json' }, options);

        if (settings.data instanceof FormData) {
            settings.processData = false;
            settings.contentType = false;
        }

        return $.ajax(settings).then(
            function (response) {
                return response && Object.prototype.hasOwnProperty.call(response, 'data')
                    ? response.data
                    : response;
            },
            function (xhr) {
                var json = xhr.responseJSON || {};

                return $.Deferred().reject({
                    status: xhr.status,
                    message: json.message || defaultMessage(xhr.status),
                    code: json.code || null,
                    errors: json.errors || {},
                    data: json.data || null
                }).promise();
            }
        );
    }

    function defaultMessage(status) {
        if (status === 0) return 'Tidak dapat terhubung ke server. Periksa koneksi Anda.';
        if (status === 401) return 'Sesi Anda telah berakhir. Silakan login kembali.';
        if (status === 403) return 'Anda tidak memiliki akses untuk tindakan ini.';
        if (status === 404) return 'Data tidak ditemukan.';
        if (status === 419) return 'Sesi kedaluwarsa. Muat ulang halaman.';
        return 'Terjadi kesalahan pada server.';
    }

    /** Shows the error, and bounces the user to login when the session died. */
    function handleError(error) {
        toast(error.message, 'danger');

        if (error.status === 401 || error.status === 419) {
            setTimeout(function () { window.location.href = '/login'; }, 1500);
        }
    }

    /* ── Forms ──────────────────────────────────────────────────────── */

    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.server-error').remove();
    }

    /** Paints Laravel's 422 error bag onto the matching form controls. */
    function showErrors($form, errors) {
        clearErrors($form);

        $.each(errors || {}, function (field, messages) {
            var selector = '[name="' + field + '"], [name="' + field + '[]"]';
            var $input = $form.find(selector).first();

            if (!$input.length) return;

            $input.addClass('is-invalid');
            $input.closest('.form-group, .col, .mb-3, .form-floating')
                .append($('<div class="invalid-feedback server-error d-block"></div>').text(messages[0]));
        });
    }

    /** Disables a button and swaps in a spinner for the duration of a request. */
    function busy($button, isBusy, busyLabel) {
        if (isBusy) {
            $button.data('original-html', $button.html());
            $button.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span>' + (busyLabel || 'Memproses…')
            );
        } else {
            $button.prop('disabled', false).html($button.data('original-html'));
        }
    }

    /* ── Formatting ─────────────────────────────────────────────────── */

    function formatRupiah(value) {
        var amount = parseFloat(value);
        return rupiah.format(isNaN(amount) ? 0 : amount);
    }

    function formatNumber(value, decimals) {
        var amount = parseFloat(value);
        if (isNaN(amount)) return '-';
        return decimals ? amount.toFixed(decimals) : number.format(amount);
    }

    function formatDate(value) {
        if (!value) return '-';
        var d = new Date(value.replace(' ', 'T'));
        if (isNaN(d.getTime())) return value;
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function formatTime(value) {
        if (!value) return '-';
        var d = new Date(value.replace(' ', 'T'));
        if (isNaN(d.getTime())) return value;
        return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }

    /** Escapes a value for safe interpolation into an HTML string. */
    function esc(value) {
        if (value === null || value === undefined) return '';
        return $('<div>').text(value).html();
    }

    var STATUS_VARIANTS = {
        PRESENT: 'success',
        LATE: 'warning',
        ABSENT: 'danger',
        INCOMPLETE: 'secondary',
        ACTIVE: 'success',
        INACTIVE: 'secondary',
        RESIGNED: 'dark',
        SCHEDULED: 'primary',
        OFF: 'secondary',
        CANCELLED: 'dark',
        OPEN: 'primary',
        PROCESSED: 'info',
        CLOSED: 'dark',
        DRAFT: 'secondary',
        FINAL: 'success'
    };

    function statusBadge(status) {
        if (!status) return '';
        var variant = STATUS_VARIANTS[status] || 'secondary';
        return '<span class="badge badge-status text-bg-' + variant + '">' + esc(status) + '</span>';
    }

    /* ── Lookups ────────────────────────────────────────────────────── */

    var lookupPromise = null;

    /**
     * Fetches the shared select-option payload once per page load.
     * Pages with several dropdowns share the single response.
     */
    function lookups() {
        if (!lookupPromise) {
            lookupPromise = api({ url: (window.HRIS_URLS && window.HRIS_URLS.lookups) || '/lookups' });
        }

        return lookupPromise;
    }

    /**
     * Fills a <select> from a lookup list, preserving the current value when
     * the option still exists (so a reload does not silently reset a filter).
     */
    function fillSelect($select, items, placeholder) {
        var previous = $select.val();
        var options = [];

        if (placeholder !== null && placeholder !== undefined) {
            options.push($('<option></option>').val('').text(placeholder));
        }

        (items || []).forEach(function (item) {
            options.push($('<option></option>').val(item.id).text(item.label).attr('data-code', item.code || ''));
        });

        $select.empty().append(options);

        if (previous && $select.find('option[value="' + previous + '"]').length) {
            $select.val(previous);
        }

        return $select;
    }

    /* ── Confirmation ───────────────────────────────────────────────── */

    var $confirmModal = null;

    /** Promise-based replacement for window.confirm, styled like the app. */
    function confirmAction(options) {
        var opts = $.extend({
            title: 'Konfirmasi',
            message: 'Lanjutkan tindakan ini?',
            confirmLabel: 'Ya, lanjutkan',
            variant: 'danger'
        }, options);

        if (!$confirmModal) {
            $confirmModal = $(
                '<div class="modal fade" tabindex="-1">' +
                '  <div class="modal-dialog modal-dialog-centered modal-sm">' +
                '    <div class="modal-content">' +
                '      <div class="modal-header"><h5 class="modal-title"></h5>' +
                '        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                '      <div class="modal-body"></div>' +
                '      <div class="modal-footer">' +
                '        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>' +
                '        <button type="button" class="btn js-confirm"></button>' +
                '      </div>' +
                '    </div>' +
                '  </div>' +
                '</div>'
            ).appendTo('body');
        }

        $confirmModal.find('.modal-title').text(opts.title);
        $confirmModal.find('.modal-body').text(opts.message);
        $confirmModal.find('.js-confirm')
            .text(opts.confirmLabel)
            .attr('class', 'btn js-confirm btn-' + opts.variant);

        var deferred = $.Deferred();
        var modal = bootstrap.Modal.getOrCreateInstance($confirmModal[0]);
        var confirmed = false;

        $confirmModal.off('.hrisConfirm');
        $confirmModal.on('click.hrisConfirm', '.js-confirm', function () {
            confirmed = true;
            modal.hide();
        });
        $confirmModal.on('hidden.bs.modal.hrisConfirm', function () {
            if (confirmed) deferred.resolve(); else deferred.reject();
        });

        modal.show();

        return deferred.promise();
    }

    /* ── Table helpers ──────────────────────────────────────────────── */

    function tableMessage($tbody, colspan, message, spinner) {
        var content = spinner
            ? '<span class="spinner-border spinner-border-sm me-2"></span>' + esc(message)
            : esc(message);

        $tbody.html('<tr class="table-empty"><td colspan="' + colspan + '">' + content + '</td></tr>');
    }

    /** Renders Laravel paginator links into a <ul class="pagination">. */
    function renderPagination($container, meta, onPage) {
        $container.empty();

        if (!meta || meta.last_page <= 1) return;

        var pages = [];
        var current = meta.current_page;
        var last = meta.last_page;

        for (var p = 1; p <= last; p++) {
            if (p === 1 || p === last || Math.abs(p - current) <= 2) pages.push(p);
            else if (pages[pages.length - 1] !== '…') pages.push('…');
        }

        var $ul = $('<ul class="pagination pagination-sm mb-0"></ul>');

        pages.forEach(function (page) {
            if (page === '…') {
                $ul.append('<li class="page-item disabled"><span class="page-link">…</span></li>');
                return;
            }

            var $li = $('<li class="page-item"></li>').toggleClass('active', page === current);
            $('<a class="page-link" href="#"></a>').text(page).appendTo($li).on('click', function (e) {
                e.preventDefault();
                if (page !== current) onPage(page);
            });
            $ul.append($li);
        });

        $container.append($ul);
    }

    /* ── DataTables ─────────────────────────────────────────────────── */

    /* Indonesian strings for every server-side table, defined here rather than
       fetched from the DataTables CDN i18n endpoint — one less request, and one
       less thing to break when the network is unavailable. */
    var DT_LANGUAGE = {
        emptyTable: 'Belum ada data.',
        zeroRecords: 'Tidak ada data yang cocok.',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
        infoEmpty: 'Tidak ada data',
        infoFiltered: '',
        lengthMenu: 'Tampilkan _MENU_ baris',
        loadingRecords: 'Memuat…',
        processing: 'Memuat…',
        search: '',
        searchPlaceholder: 'Cari…',
        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
        aria: { orderable: 'Urutkan kolom ini', orderableReverse: 'Balik urutan kolom ini' }
    };

    /* DataTables' Bootstrap 5 integration does not add to the layout class
       names, it replaces them: `row` becomes "row mt-2 justify-content-between"
       and `cell` becomes "d-md-flex …", so dt-layout-row and dt-layout-cell
       never reach the DOM and every rule in app.css that targets them is dead —
       which is why the page-size menu and the row count sat flush against the
       card border. Putting the names back in front of Bootstrap's keeps both
       sets of styles. The other layout keys (tableRow, start, end, full) are
       left alone; the integration already preserves their dt-* names.

       Guarded because app.js also loads on the login page, which has no
       DataTables. */
    if ($.fn.dataTable) {
        $.extend(true, $.fn.dataTable.ext.classes, {
            layout: {
                row: 'dt-layout-row row mt-2 justify-content-between',
                cell: 'dt-layout-cell d-md-flex justify-content-between align-items-center'
            }
        });
    }

    /**
     * Creates a server-side DataTable with this application's conventions
     * already applied: Indonesian strings, the shared error handler, and the
     * page-size menu the backend is willing to serve.
     *
     * @param {string|jQuery} selector  the <table> element
     * @param {object} options          merged over the defaults
     * @param {function} [options.params]  () => extra query parameters
     */
    function dataTable(selector, options) {
        var settings = $.extend(true, {
            serverSide: true,
            processing: true,
            // Filtering is done by each page's own filter bar, which offers more
            // than a single box (status, location, shift, date range), so the
            // built-in search field is turned off rather than duplicated.
            searching: false,
            lengthChange: true,
            // Mirrors Controller::MIN_PER_PAGE/MAX_PER_PAGE — offering a size the
            // server would clamp is how a table ends up skipping rows.
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            language: DT_LANGUAGE,
            layout: {
                topStart: 'pageLength',
                topEnd: null,
                bottomStart: 'info',
                bottomEnd: 'paging'
            }
        }, options || {});

        var params = options && options.params;
        delete settings.params;

        if (settings.ajax && params) {
            var base = settings.ajax.data;

            settings.ajax.data = function (d) {
                if (base) base(d);
                $.extend(d, params() || {});
            };
        }

        var table = $(selector).DataTable(settings);

        // Default error mode is an alert() box; route failures through the
        // toast layer so they read like every other error in the app.
        table.on('error.dt', function (e, dtSettings, techNote, message) {
            toast(message || 'Gagal memuat data.', 'danger');
        });

        return table;
    }

    /**
     * The edit/delete pair every master-data row ends with. HRIS.crud() binds
     * the click handlers by class, so the markup only has to carry the id and
     * the label the delete confirmation quotes back to the user.
     *
     * @param {number} id
     * @param {string} label     e.g. "karyawan JP001"
     * @param {object} [options] { edit: bool, remove: bool }
     */
    function rowActions(id, label, options) {
        var opts = $.extend({ edit: true, remove: true }, options);
        var html = '';

        if (opts.edit) {
            html += '<button class="btn btn-sm btn-outline-secondary js-edit" data-id="' + id +
                '" title="Ubah"><i class="bi bi-pencil"></i></button> ';
        }

        if (opts.remove) {
            html += '<button class="btn btn-sm btn-outline-danger js-delete" data-id="' + id +
                '" data-label="' + esc(label) + '" title="Hapus"><i class="bi bi-trash"></i></button>';
        }

        return html;
    }

    /* ── Theme ──────────────────────────────────────────────────────── */

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);

        $('.theme-icon')
            .toggleClass('bi-moon-stars', theme === 'light')
            .toggleClass('bi-sun', theme === 'dark');

        try { localStorage.setItem('hris-theme', theme); } catch (e) { /* ignore */ }
    }

    /* ── Sidebar ────────────────────────────────────────────────────── */

    /**
     * Which menu groups are folded shut. Stored as the closed set rather than the
     * open one so a newly added group shows up expanded instead of silently hidden.
     */
    function storeClosedGroups() {
        var closed = $('.sidebar-nav .collapse').not('.show').map(function () {
            return this.id;
        }).get();

        try {
            localStorage.setItem('hris-sidebar-groups', JSON.stringify(closed));
        } catch (e) { /* ignore — the menu still works, it just will not persist */ }
    }

    $(function () {
        applyTheme(document.documentElement.getAttribute('data-bs-theme') || 'light');

        $(document).on('click', '.js-theme-toggle', function () {
            var current = document.documentElement.getAttribute('data-bs-theme');
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });

        $(document).on('click', '.js-sidebar-toggle', function () {
            var collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            try {
                localStorage.setItem('hris-sidebar', collapsed ? 'collapsed' : 'expanded');
            } catch (e) { /* ignore */ }
        });

        // Bootstrap has finished the animation by these events, so the .show classes
        // read here are the settled state rather than a mid-transition one.
        $('.sidebar-nav').on('shown.bs.collapse hidden.bs.collapse', '.collapse', storeClosedGroups);
    });

    /** Debounce, so keystroke-driven filters do not fire a request per letter. */
    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(context, args); }, wait || 350);
        };
    }

    return {
        api: api,
        toast: toast,
        handleError: handleError,
        showErrors: showErrors,
        clearErrors: clearErrors,
        busy: busy,
        confirm: confirmAction,
        formatRupiah: formatRupiah,
        formatNumber: formatNumber,
        formatDate: formatDate,
        formatTime: formatTime,
        statusBadge: statusBadge,
        esc: esc,
        lookups: lookups,
        fillSelect: fillSelect,
        tableMessage: tableMessage,
        renderPagination: renderPagination,
        dataTable: dataTable,
        dtLanguage: DT_LANGUAGE,
        rowActions: rowActions,
        debounce: debounce
    };
})(jQuery);
