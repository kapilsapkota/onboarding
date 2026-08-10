<div class="mb-5">

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-3">

        <div class="flex flex-col lg:flex-row lg:items-center gap-3">

            {{-- Search --}}
            <form
                id="quoteSearchForm"
                method="GET"
                action="{{ route('admin.quotes.index') }}"
                class="flex-1 min-w-0"
            >
                <input
                    type="hidden"
                    name="status"
                    value="{{ request('status') }}"
                >

                <div class="relative">

                    {{-- Search icon --}}
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg
                            class="w-5 h-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                            />
                        </svg>
                    </div>

                    <input
                        id="quoteSearch"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search quotes, clients or email..."
                        autocomplete="off"
                        class="w-full h-11 pl-11 pr-10 rounded-lg
                               border border-gray-200 dark:border-gray-600
                               bg-gray-50 dark:bg-gray-700/60
                               text-sm text-gray-900 dark:text-gray-100
                               placeholder-gray-400 dark:placeholder-gray-500
                               focus:bg-white dark:focus:bg-gray-700
                               focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20
                               transition"
                    >

                    {{-- Loading indicator --}}
                    <div
                        id="quoteSearchLoading"
                        class="hidden absolute inset-y-0 right-3 items-center"
                    >
                        <svg
                            class="w-4 h-4 text-blue-500 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="3"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"
                            />
                        </svg>
                    </div>

                    {{-- Clear search --}}
                    <button
                        id="clearQuoteSearch"
                        type="button"
                        class="hidden absolute inset-y-0 right-3 items-center justify-center
                               w-7 text-gray-400 hover:text-gray-600
                               dark:hover:text-gray-200 transition"
                        aria-label="Clear search"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                </div>
            </form>


            {{-- Divider --}}
            <div class="hidden lg:block w-px h-8 bg-gray-200 dark:bg-gray-700"></div>


            {{-- Status filter --}}
            <div class="flex items-center gap-1 overflow-x-auto pb-0.5">

                @foreach([
                    ''         => 'All',
                    'draft'    => 'Draft',
                    'sent'     => 'Sent',
                    'accepted' => 'Accepted',
                    'rejected' => 'Rejected',
                ] as $val => $label)

                    @php
                        $isActive = request('status', '') === $val;

                        $statusClasses = match ($val) {
                            'draft' => $isActive
                                ? 'bg-gray-700 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',

                            'sent' => $isActive
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-blue-50 dark:text-gray-300 dark:hover:bg-blue-900/20',

                            'accepted' => $isActive
                                ? 'bg-green-600 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-green-50 dark:text-gray-300 dark:hover:bg-green-900/20',

                            'rejected' => $isActive
                                ? 'bg-red-600 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-red-50 dark:text-gray-300 dark:hover:bg-red-900/20',

                            default => $isActive
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                        };
                    @endphp

                    <button
                        type="button"
                        data-status="{{ $val }}"
                        class="quote-status-filter
                               inline-flex items-center gap-1.5
                               whitespace-nowrap
                               px-3 py-2
                               rounded-lg
                               text-sm font-medium
                               transition-all duration-150
                               {{ $statusClasses }}"
                    >

                        {{ $label }}

                        @if($val && isset($statusCounts[$val]))
                            <span
                                class="inline-flex items-center justify-center
                                       min-w-[20px] h-5 px-1.5
                                       rounded-full
                                       text-[11px] font-semibold
                                       {{ $isActive
                                           ? 'bg-white/20 text-white'
                                           : 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-300' }}"
                            >
                                {{ $statusCounts[$val] }}
                            </span>
                        @endif

                    </button>

                @endforeach

            </div>

        </div>

    </div>

</div>


{{-- ==========================================================
     AUTO SEARCH
=========================================================== --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const searchInput = document.getElementById('quoteSearch');
        const searchForm = document.getElementById('quoteSearchForm');
        const clearButton = document.getElementById('clearQuoteSearch');
        const loading = document.getElementById('quoteSearchLoading');

        let searchTimer = null;

        function updateClearButton() {
            if (!searchInput || !clearButton) {
                return;
            }

            if (searchInput.value.trim().length > 0) {
                clearButton.classList.remove('hidden');
                clearButton.classList.add('flex');
            } else {
                clearButton.classList.add('hidden');
                clearButton.classList.remove('flex');
            }
        }

        function performSearch() {

            if (!searchForm) {
                return;
            }

            const searchValue = searchInput.value.trim();

            loading?.classList.remove('hidden');
            loading?.classList.add('flex');

            clearButton?.classList.add('hidden');
            clearButton?.classList.remove('flex');

            const url = new URL(searchForm.action, window.location.origin);

            if (searchValue !== '') {
                url.searchParams.set('search', searchValue);
            }

            const status = searchForm.querySelector('[name="status"]')?.value;

            if (status) {
                url.searchParams.set('status', status);
            }

            // Always start from page 1 when filtering.
            url.searchParams.delete('page');

            window.location.href = url.toString();
        }

        if (searchInput) {

            searchInput.addEventListener('input', function () {

                updateClearButton();

                clearTimeout(searchTimer);

                const value = this.value.trim();

                /*
                 * Don't search immediately for every keystroke.
                 * Wait until the user has stopped typing.
                 */
                searchTimer = setTimeout(function () {

                    /*
                     * Avoid unnecessary reload if the current URL
                     * already contains the same search value.
                     */
                    const currentUrl = new URL(window.location.href);
                    const currentSearch = currentUrl.searchParams.get('search') || '';

                    if (currentSearch === value) {
                        return;
                    }

                    performSearch();

                }, 800);
            });

            searchInput.addEventListener('keydown', function (event) {

                if (event.key === 'Enter') {
                    event.preventDefault();

                    clearTimeout(searchTimer);

                    performSearch();
                }

                if (event.key === 'Escape') {

                    clearTimeout(searchTimer);

                    if (this.value !== '') {
                        this.value = '';

                        updateClearButton();

                        performSearch();
                    }
                }
            });

            updateClearButton();
        }


        {{-- Clear search --}}
        if (clearButton) {

            clearButton.addEventListener('click', function () {

                clearTimeout(searchTimer);

                searchInput.value = '';

                updateClearButton();

                performSearch();
            });
        }


        {{-- Status filters --}}
        document.querySelectorAll('.quote-status-filter').forEach(function (button) {

            button.addEventListener('click', function () {

                const status = this.dataset.status || '';

                const url = new URL(window.location.href);

                if (status) {
                    url.searchParams.set('status', status);
                } else {
                    url.searchParams.delete('status');
                }

                // Keep the current search.
                // Remove pagination because filters should start at page 1.
                url.searchParams.delete('page');

                window.location.href = url.toString();
            });

        });

    });
</script>
