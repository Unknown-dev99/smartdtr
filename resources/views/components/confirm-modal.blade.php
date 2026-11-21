<div
    x-cloak
    x-show="confirmOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="confirm-overlay"
    @keydown.escape.window="cancelConfirm()"
>
    <div
        class="confirm-dialog"
        @click.outside="cancelConfirm()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-title"
    >
        <div class="confirm-icon" :class="confirmType === 'danger' ? 'confirm-icon-danger' : 'confirm-icon-primary'">
            <svg x-show="confirmType === 'danger'" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            <svg x-show="confirmType !== 'danger'" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
        </div>
        <h3 id="confirm-title" class="confirm-title">Please confirm</h3>
        <p class="confirm-message" x-text="confirmMessage"></p>
        <div class="confirm-actions">
            <button type="button" class="btn-secondary" @click="cancelConfirm()">Cancel</button>
            <button
                type="button"
                class="btn-primary"
                :class="confirmType === 'danger' ? '!bg-red-600 hover:!bg-red-700' : ''"
                @click="submitConfirmed()"
                x-text="confirmButton"
            ></button>
        </div>
    </div>
</div>
