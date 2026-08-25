{{--
    Modal wrapper for the create/edit form driven by ParkOps.crud(). The title is
    set at open time, so it is intentionally blank here.
--}}
@props([
    'id' => 'dataModal',
    'formId' => 'dataForm',
    'size' => 'modal-lg',
    'saveLabel' => 'Simpan',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog {{ $size }} modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="{{ $formId }}" novalidate autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">{{ $slot }}</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary js-save">{{ $saveLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
