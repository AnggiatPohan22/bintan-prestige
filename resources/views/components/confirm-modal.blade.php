<div
    id="confirmModal"
    class="admin-modal-overlay hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirmModalTitle"
>
    <div class="admin-modal-panel max-w-md">

        <div class="admin-modal-header">
            <div class="flex items-center gap-3">
                <div id="confirmModalIcon"
                     class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <i class="fa-solid fa-trash text-sm"></i>
                </div>
                <h3 id="confirmModalTitle" class="text-base font-bold text-admin-primary">
                    Konfirmasi Hapus
                </h3>
            </div>
        </div>

        <div class="admin-modal-body">
            <p id="confirmText" class="text-sm text-admin-secondary leading-relaxed"></p>
        </div>

        <div class="admin-modal-footer">
            <button
                type="button"
                onclick="closeConfirmModal()"
                class="admin-btn-secondary"
            >
                Batal
            </button>
            <button
                type="button"
                id="confirmYesBtn"
                class="admin-btn-danger"
            >
                <i class="fa-solid fa-trash mr-1.5 text-xs"></i>
                <span id="confirmYesLabel">Hapus</span>
            </button>
        </div>

    </div>
</div>
