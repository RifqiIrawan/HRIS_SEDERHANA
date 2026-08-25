/*
 * Shared list + modal-form behaviour for the master-data screens.
 *
 * Employee, Location, Shift, Assignment, User and Role all follow the same
 * pattern from spec §30 (filter → AJAX GET → render → modal POST/PUT/DELETE),
 * so that pattern lives here once and each module only supplies the parts that
 * genuinely differ: how a row renders, and how the form is filled for editing.
 */
(function ($, ParkOps) {
    'use strict';

    /**
     * @param {object} config
     * @param {string} config.baseUrl        collection URL, e.g. "/employees"
     * @param {object[]} config.columns      DataTables column definitions
     * @param {function} [config.fill]       ($form, item) => void, for editing
     * @param {object}   [config.defaults]   field values applied on create
     * @param {string[]} [config.filters]    selectors whose value narrows the list
     * @param {object}   [config.labels]     { create, edit }
     * @param {function} [config.onLoaded]   (json) => void, per draw
     * @param {function} [config.beforeSubmit] ($form) => bool|void, cancel on false
     * @param {function} [config.onSaved]    (isCreate) => void, after a save succeeds
     * @param {object}   [config.table]      extra DataTables options
     */
    ParkOps.crud = function (config) {
        var $table = $(config.tableSelector || '#dataTable');
        var $modal = $(config.modalSelector || '#dataModal');
        var $form = $(config.formSelector || '#dataForm');
        var $saveButton = $modal.find('.js-save');
        var labels = $.extend({ create: 'Tambah Data', edit: 'Ubah Data' }, config.labels);

        var modal = $modal.length ? bootstrap.Modal.getOrCreateInstance($modal[0]) : null;
        var editingId = null;

        /* ── Listing ────────────────────────────────────────────────── */

        /** The module's own filter bar, as query parameters. */
        function filterParams() {
            var params = {};

            (config.filters || []).forEach(function (selector) {
                var $input = $(selector);
                if (!$input.length) return;

                var name = $input.data('param') || $input.attr('name');
                var value = $input.is(':checkbox') ? ($input.is(':checked') ? 1 : '') : $input.val();

                if (value !== '' && value !== null && value !== undefined) params[name] = value;
            });

            return $.extend(params, config.extraParams ? config.extraParams() : {});
        }

        // DataTables owns paging, ordering and the request lifecycle from here:
        // it serialises draw/start/length/order itself, and discards a response
        // whose `draw` is stale, which is what the hand-rolled abort used to do.
        var table = ParkOps.dataTable($table, $.extend(true, {
            ajax: {
                url: config.baseUrl,
                dataSrc: function (json) {
                    if (config.onLoaded) config.onLoaded(json);
                    return json.data || [];
                }
            },
            columns: config.columns,
            language: $.extend({}, ParkOps.dtLanguage, {
                emptyTable: config.emptyMessage || 'Belum ada data.'
            }),
            params: filterParams
        }, config.table || {}));

        /**
         * Reloads the current page. `resetPaging` is only ever true after a
         * create, where the new row may belong on page one.
         */
        function load(resetPaging) {
            table.ajax.reload(null, resetPaging === true);
        }

        /* ── Modal form ─────────────────────────────────────────────── */

        function openCreate() {
            editingId = null;
            $form[0].reset();
            ParkOps.clearErrors($form);
            $modal.find('.modal-title').text(labels.create);

            $.each(config.defaults || {}, function (name, value) {
                var $field = $form.find('[name="' + name + '"]');
                if ($field.is(':checkbox')) $field.prop('checked', !!value); else $field.val(value);
            });

            if (config.onCreate) config.onCreate($form);
            if (modal) modal.show();
        }

        function openEdit(id) {
            ParkOps.api({ url: config.baseUrl + '/' + id })
                .done(function (item) {
                    editingId = id;
                    $form[0].reset();
                    ParkOps.clearErrors($form);
                    $modal.find('.modal-title').text(labels.edit);

                    // Anything whose name matches a key in the payload is filled
                    // automatically; fill() only handles the exceptions.
                    $.each(item, function (name, value) {
                        var $field = $form.find('[name="' + name + '"]');
                        if (!$field.length) return;
                        if ($field.is(':checkbox')) $field.prop('checked', !!value);
                        else $field.val(value);
                    });

                    if (config.fill) config.fill($form, item);
                    if (modal) modal.show();
                })
                .fail(ParkOps.handleError);
        }

        function submit(event) {
            event.preventDefault();

            if (config.beforeSubmit && config.beforeSubmit($form) === false) return;

            ParkOps.clearErrors($form);
            ParkOps.busy($saveButton, true, 'Menyimpan…');

            var data = $form.serializeArray();

            // serializeArray() omits unchecked boxes entirely; the server needs
            // an explicit 0 so "false" is distinguishable from "not sent".
            $form.find('input[type="checkbox"]').each(function () {
                var name = $(this).attr('name');
                if (!name) return;
                if (!this.checked) data.push({ name: name, value: 0 });
            });

            if (editingId) data.push({ name: '_method', value: 'PUT' });

            ParkOps.api({
                url: editingId ? config.baseUrl + '/' + editingId : config.baseUrl,
                type: 'POST',
                data: $.param(data)
            })
                .done(function () {
                    var wasCreate = !editingId;

                    if (modal) modal.hide();
                    ParkOps.toast(wasCreate ? 'Data berhasil disimpan.' : 'Data berhasil diperbarui.');
                    // An edit stays where it is; a new row may sort onto page one.
                    load(wasCreate);

                    if (config.onSaved) config.onSaved(wasCreate);
                })
                .fail(function (error) {
                    ParkOps.showErrors($form, error.errors);
                    ParkOps.toast(error.message, 'danger');
                })
                .always(function () {
                    ParkOps.busy($saveButton, false);
                });
        }

        function remove(id, label) {
            ParkOps.confirm({
                title: 'Hapus data',
                message: 'Hapus ' + (label || 'data ini') + '? Tindakan ini tidak dapat dibatalkan.',
                confirmLabel: 'Ya, hapus'
            }).done(function () {
                ParkOps.api({ url: config.baseUrl + '/' + id, type: 'POST', data: { _method: 'DELETE' } })
                    .done(function () {
                        ParkOps.toast('Data berhasil dihapus.');
                        load();
                    })
                    .fail(ParkOps.handleError);
            });
        }

        /* ── Wiring ─────────────────────────────────────────────────── */

        $(document).on('click', '.js-create', openCreate);
        $table.on('click', '.js-edit', function () { openEdit($(this).data('id')); });
        $table.on('click', '.js-delete', function () { remove($(this).data('id'), $(this).data('label')); });
        $form.on('submit', submit);

        (config.filters || []).forEach(function (selector) {
            var $input = $(selector);
            if (!$input.length) return;

            var event = $input.is('input[type="text"], input[type="search"]') ? 'input' : 'change';
            // A narrowed result set may be shorter than the page you were on,
            // so every filter change returns to page one.
            var handler = function () { load(true); };

            $input.on(event, event === 'input' ? ParkOps.debounce(handler, 400) : handler);
        });

        return {
            reload: load,
            table: table,
            openCreate: openCreate,
            openEdit: openEdit
        };
    };
})(jQuery, window.ParkOps);
