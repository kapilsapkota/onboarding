<div id="duplicateModal"
     class="hidden fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="duplicate-modal-title"
     role="dialog"
     aria-modal="true">

    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

        {{-- Background overlay --}}
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             aria-hidden="true"
             onclick="closeDuplicateModal()">
        </div>


        {{-- Center modal --}}
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
              aria-hidden="true">
            &#8203;
        </span>


        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">

            {{-- Body --}}
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">

                <div class="sm:flex sm:items-start">

                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">

                        <svg class="h-6 w-6 text-green-600 dark:text-green-400"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M8 16h8M8 12h8m-8-4h8M5 4h10a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>

                        </svg>

                    </div>


                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">

                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                            Duplicate Category
                        </h3>


                        <div class="mt-2">

                            <p class="text-sm text-gray-500 dark:text-gray-400">

                                Are you sure you want to duplicate
                                "<span id="duplicateName"
                                       class="font-semibold text-gray-700 dark:text-gray-200"></span>"?

                            </p>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Footer --}}
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">


                <form id="duplicateForm"
                      method="POST"
                      class="w-full sm:w-auto">

                    @csrf

                    <button type="submit"
                            class="inline-flex items-center justify-center w-full rounded-lg border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">

                        <svg class="w-4 h-4 mr-2"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">

                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M8 16h8M8 12h8m-8-4h8M5 4h10a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>

                        </svg>

                        Yes, Duplicate

                    </button>

                </form>


                <button type="button"
                        onclick="closeDuplicateModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">

                    Cancel

                </button>


            </div>

        </div>

    </div>

</div><script>
    function openDuplicateModal(url,name){

        document.getElementById('duplicateName').innerText = name;

        document.getElementById('duplicateForm').action = url;

        document.getElementById('duplicateModal').classList.remove('hidden');
    }


    function closeDuplicateModal(){

        document.getElementById('duplicateModal').classList.add('hidden');

    }
</script>
