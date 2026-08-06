@props([
    'src' => null,
    'alt' => 'Image',
    'background' => '#ffffff',
    'wrapperClass' => '',
    'thumbClass' => 'w-14 h-14',
    'thumbImageClass' => 'w-full h-full object-contain rounded border bg-white p-1',
    'previewClass' => 'w-[28rem] h-[28rem]',
    'previewImageClass' => 'w-full h-full object-contain',
    'previewBoxClass' => 'bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-3',
    'overlayClass' => 'bg-black/50 backdrop-blur-sm',
])
@if($src)
    <div x-data="{show:false}"
         class="relative {{ $wrapperClass }}">

        <div @mouseenter="show=true"
             @mouseleave="show=false"
             class="cursor-zoom-in overflow-hidden">

            <img src="{{ $src }}"
                 alt="{{ $alt }}"
                 class="{{ $thumbClass }} {{ $thumbImageClass }}">

        </div>


        <div x-show="show"
             x-transition
             class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none {{ $overlayClass }}">

            <div class="{{ $previewBoxClass }}">

                <div class="{{ $previewClass }} rounded-xl flex items-center justify-center overflow-hidden"
                     style="background-color: {{ $background }}">

                    <img src="{{ $src }}"
                         alt="{{ $alt }}"
                         class="{{ $previewImageClass }}"
                         loading="lazy"
                    >

                </div>

            </div>

        </div>

    </div>
@endif
