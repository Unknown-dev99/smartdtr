<div
    x-cloak
    x-show="toast"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="alert-overlay"
    @keydown.escape.window="toast = ''"
>
    <div
        class="alert-dialog"
        @click.outside="toast = ''"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="system-alert-title"
    >
        <div class="alert-icon" :class="toastType === 'success' ? 'alert-icon-success' : 'alert-icon-error'">
            <svg x-show="toastType === 'success'" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <svg x-show="toastType !== 'success'" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>
        </div>

        <h3 id="system-alert-title" class="alert-title" x-text="toastType === 'success' ? 'Success' : 'Action Needed'"></h3>
        <p class="alert-message" x-text="toast"></p>

        <template x-if="toastExtra.length">
            <ul class="alert-list">
                <template x-for="item in toastExtra" :key="item">
                    <li x-text="item"></li>
                </template>
            </ul>
        </template>

        <button type="button" class="btn-primary alert-button" :class="toastType === 'success' ? '' : '!bg-red-600 hover:!bg-red-700'" @click="toast = ''">
            OK
        </button>
    </div>
</div>
