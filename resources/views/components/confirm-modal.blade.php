<div
    id="confirmModal"
    class="admin-modal-overlay hidden"
>

    <div class="admin-modal-panel max-w-md">

        <div class="admin-modal-header">
            <h3 class="text-base font-bold text-admin-primary">
                Confirm Action
            </h3>
        </div>

        <div class="admin-modal-body">
            <p id="confirmText"></p>
        </div>

        <div class="admin-modal-footer">

            <button
                onclick="closeConfirmModal()"
                class="admin-btn-secondary"
            >
                Cancel
            </button>

            <button
                id="confirmYesBtn"
                class="admin-btn-danger"
            >
                Yes, Confirm
            </button>

        </div>

    </div>

</div>
