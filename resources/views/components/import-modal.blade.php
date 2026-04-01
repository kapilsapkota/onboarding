<x-modal name="importModal" :show="false" maxWidth="2xl">
    <div class="p-8">
        <h2 class="text-xl font-semibold mb-4">Import Subscribers</h2>

        <!-- Import form -->
        <form id="importForm" action="{{ route('subscribers.import') }}" method="POST" enctype="multipart/form-data" x-show="showImportButton">
            @csrf
            <div class="mb-4">
                <label for="csv_file" class="block text-sm font-medium text-gray-700">Select CSV file to upload:</label>
                <input type="file" name="csv_file" id="csv_file" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="mt-4">
                <button type="submit" @click="showImportButton = false" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Upload</button>
                <button type="button" @click="show = false" class="ml-2 px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</button>
            </div>
        </form>

        <!-- Display imported subscribers -->
        <div class="mt-6" x-show="importedSubscribers.length > 0">
            <h3 class="text-lg font-semibold mb-4">Imported Subscribers</h3>
            <ul>
                <template x-for="subscriber in importedSubscribers" :key="subscriber.id">
                    <li x-text="subscriber.email"></li>
                </template>
            </ul>
        </div>
    </div>
</x-modal>
