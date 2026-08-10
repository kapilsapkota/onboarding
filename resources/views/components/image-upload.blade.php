@props([
    'name' => 'image',
    'removeName' => 'remove_image',
    'current' => null,
    'label' => 'Image',
    'isRequired' => false,
    'accept' => 'image/*',
])

<div x-data="imageUpload()" class="space-y-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
        {{ $label }}
    </label>
    {{-- Existing image --}}
    @if($current)
        <div x-data="{show:false}"
             class="mb-3 flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 relative">

            <div class="relative"
                 @mouseenter="show=true"
                 @mouseleave="show=false">

                <img src="{{ Str::startsWith($current,'http') ? $current : Storage::url($current) }}"
                     class="h-14 w-14 object-contain rounded border bg-white p-1 cursor-zoom-in">


                {{-- Large Preview --}}
                <div x-show="show"
                     x-transition
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm pointer-events-none">

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-4 max-w-[90vw] max-h-[90vh]">

                        <img src="{{ Str::startsWith($current,'http') ? $current : Storage::url($current) }}"
                             class="max-w-[80vw] max-h-[80vh] object-contain rounded-lg">

                    </div>

                </div>

            </div>


            <div class="flex-1">

                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Current image
                </p>

                <p class="text-xs text-gray-400">
                    Upload a new file to replace it
                </p>

            </div>


            <label class="text-xs text-red-500 flex items-center gap-1 cursor-pointer">

                <input type="checkbox"
                       name="{{ $removeName }}"
                       value="1"
                       class="rounded text-red-500">

                Remove

            </label>

        </div>
    @endif
    <div x-show="preview"
         class="flex items-center gap-4 p-4 rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20">
        <div class="flex items-center justify-center w-16 h-16 rounded-lg bg-white border border-blue-200 overflow-hidden">
            <img :src="preview" class="w-full h-full object-contain">
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                New image
            </p>
            <p class="text-xs text-gray-500 truncate" x-text="filename"></p>
        </div>
        <button type="button"
                @click="clearImage()"
                class="inline-flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-600">
            <i class="fa-solid fa-xmark"></i>
            Clear
        </button>
    </div>
    <div class="relative flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50/40 dark:hover:bg-blue-900/10 transition">
        <div x-show="!preview">
            <svg class="w-9 h-9 mx-auto text-gray-300 dark:text-gray-600 mb-2"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.5"
                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span class="text-blue-600 dark:text-blue-400 font-semibold">
                    Click to upload
                </span>
                or drag & drop
            </p>
            <p class="text-xs text-gray-400 mt-1">
                PNG, JPG, WebP up to 5MB
            </p>
        </div>
        <input type="file"
               name="{{ $name }}-input"
               accept="{{ $accept }}"
               class="absolute inset-0 opacity-0 cursor-pointer"
               x-ref="input"
               @change="previewImage($event)">
    </div>
    @error($name)
    <p class="text-xs text-red-600">
        {{ $message }}
    </p>
    @enderror
</div>
<script>
    function imageUpload(){
        return {
            preview:null,
            filename:'',
            previewImage(event){
                const file=event.target.files[0];
                if(!file){
                    return;
                }
                this.filename=file.name;
                const reader=new FileReader();
                reader.onload=e=>{
                    this.preview=e.target.result;
                };
                reader.readAsDataURL(file);
            },
            clearImage(){
                this.preview=null;
                this.filename='';
                this.$refs.input.value=null;
            }
        }
    }
</script>
