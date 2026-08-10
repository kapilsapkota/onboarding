<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 w-full">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Quotes
            </h2>

            <a href="{{ route('admin.quotes.create') }}"
               class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Quote
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-full mx-auto sm:px-6 lg:px-8">

        {{-- Flash message --}}
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        @include('admin.quotes.partials.filters')

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Quote #</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Client</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Items</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 text-right">Total (inc. GST)</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($quotes as $quote)
                    @php
                        $sendQuoteData = $quote->sendModalData();
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                        data-quote='@json($sendQuoteData)'
                        onclick="openQuoteSendModalFromRow(this)"
                        >
                        <td  class="px-4 py-3 font-mono font-medium text-blue-600 dark:text-blue-400" onclick="event.stopPropagation()">
                            <a href="{{ route('admin.quotes.show', $quote) }}" class="hover:underline">
                                {{ $quote->quote_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $quote->client_name }}</div>
                            @if($quote->email)
                                <div class="text-xs text-gray-400">{{ $quote->email }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $quote->items_count }} item{{ $quote->items_count !== 1 ? 's' : '' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                            ${{ number_format($quote->total, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @php $badge = $quote->status_badge; @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                            {{ $quote->created_at->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3" onclick="event.stopPropagation()">
                            <div class="flex items-center gap-2 justify-end">

                                {{-- View --}}
                                <a href="{{ route('admin.quotes.show', $quote) }}"
                                   class="text-gray-400 hover:text-blue-500 transition"
                                   title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @if($quote->sharepoint_file_url)
                                    <a
                                        href="{{ $quote->sharepoint_file_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-gray-400 hover:text-blue-600 transition"
                                        title="Open SharePoint PDF"
                                        onclick="event.stopPropagation()"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a2 2 0 00-.293-.707l-5.414-5.414A2 2 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 13h6M9 17h4"
                                            />
                                        </svg>
                                    </a>
                                @endif

                                {{-- PDF --}}
                                <a href="{{ route('admin.quotes.pdf', $quote) }}"
                                   target="_blank"
                                   class="text-gray-400 hover:text-red-500 transition"
                                   title="View PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A2 2 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </a>

                                {{-- Edit - only available while quote is editable --}}
                                @if($quote->isEditable())
                                    <a href="{{ route('admin.quotes.edit', $quote) }}"
                                       class="text-gray-400 hover:text-yellow-500 transition"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                @else
                                    {{-- Locked indicator --}}
                                    <span class="text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                          title="{{ $quote->status === 'accepted' ? 'Quote accepted and locked' : 'Quote sent and locked' }}">
                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-7a2 2 0 00-2-2h-1V7a5 5 0 00-10 0v3H6a2 2 0 00-2 2v7a2 2 0 002 2zm4-11V7a2 2 0 114 0v3h-4z"/>
                </svg>
            </span>
                                @endif

                                {{-- Duplicate --}}
                                <form method="POST"
                                      action="{{ route('admin.quotes.duplicate', $quote) }}"
                                      class="inline">
                                    @csrf

                                    <button type="submit"
                                            class="text-gray-400 hover:text-green-500 transition"
                                            title="Duplicate">
                                        <svg class="w-4 h-4"
                                             fill="none"
                                             stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </form>

                                {{-- Delete - only available while quote is editable --}}
                                @if($quote->isEditable())
                                    <form method="POST"
                                          action="{{ route('admin.quotes.destroy', $quote) }}"
                                          class="inline"
                                          onsubmit="return confirm('Delete quote {{ $quote->quote_number }}?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="text-gray-400 hover:text-red-500 transition"
                                                title="Delete">
                                            <svg class="w-4 h-4"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    {{-- Locked delete indicator --}}
                                    <span class="text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                          title="Sent and accepted quotes cannot be deleted">
                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 01-1 1v3M4 7h16"/>
                </svg>
            </span>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            No quotes found.
                            <a href="{{ route('admin.quotes.create') }}" class="text-blue-500 hover:underline">Create your first quote</a>.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            @if($quotes->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    {{ $quotes->links() }}
                </div>
            @endif
            @include('admin.quotes.partials.send-modal')
            <script>
                function openQuoteSendModalFromRow(row) {
                    if (!row) {
                        return;
                    }

                    const quoteJson = row.dataset.quote;

                    if (!quoteJson) {
                        console.error('Quote data is missing from row.');
                        return;
                    }

                    try {
                        const quote = JSON.parse(quoteJson);

                        openSendQuoteModal(quote);

                    } catch (error) {
                        console.error('Unable to parse quote data:', error);
                    }
                }
            </script>
        </div>


    </div>
</x-app-layout>
