<div
    id="confirmModal"
    class="hidden fixed inset-0 z-50
    bg-black/40 backdrop-blur-sm
    flex items-center justify-center"
>

    <div class="bg-white rounded-3xl w-full max-w-md p-6 shadow-2xl">

        <h3 class="text-xl font-bold text-slate-800">
            Confirm Update
        </h3>

        <p
            id="confirmText"
            class="text-slate-500 mt-2"
        >
        </p>

        <div class="flex justify-end gap-3 mt-6">

            <button
                onclick="closeConfirmModal()"
                class="px-5 py-2 rounded-xl border
                hover:bg-slate-100"
            >
                Cancel
            </button>

            <button
                id="confirmYesBtn"
                class="px-5 py-2 rounded-xl
                bg-emerald-600 text-white
                hover:bg-emerald-700 shadow-md"
            >
                Yes, Update
            </button>

        </div>

    </div>

</div>