<!-- Displaying Session Message -->
@if (session('success') || session('error'))
<div id="sessionMessage" class="py-2">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div id="successMessage" class="max-w-7xl mx-auto sm:px-6 lg:px-8 bg-green-100 border  border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="hideMessage('successMessage')">
            <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <title>Close</title>
                <path fill-rule="evenodd" d="M14.293 5.293a1 1 0 010 1.414L11.414 10l2.879 2.879a1 1 0 01-1.414 1.414L10 11.414l-2.879 2.879a1 1 0 01-1.414-1.414L8.586 10 5.707 7.121a1 1 0 011.414-1.414L10 8.586l2.879-2.879a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </span>
            </div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $key => $error)
                                <li>{{ $key. ' '. $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="hideMessage('errorMessage')">
            <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <title>Close</title>
                <path fill-rule="evenodd" d="M14.293 5.293a1 1 0 010 1.414L11.414 10l2.879 2.879a1 1 0 01-1.414 1.414L10 11.414l-2.879 2.879a1 1 0 01-1.414-1.414L8.586 10 5.707 7.121a1 1 0 011.414-1.414L10 8.586l2.879-2.879a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </span>
        </div>
        @endif
    </div>
</div>
@endif

<script>
    function hideMessage(messageId) {
        var message = document.getElementById(messageId);
        if (message) {
            setTimeout(function() {
                message.style.display = 'none';
            }, 10000); // 5000 milliseconds = 5 seconds
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        hideMessage('sessionMessage');
        hideMessage('successMessage');
        hideMessage('errorMessage');
    });

</script>
