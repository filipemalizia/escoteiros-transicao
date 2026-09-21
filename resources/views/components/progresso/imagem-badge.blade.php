@props(['url' => null, 'colorida' => false, 'alt' => '', 'size' => 'h-16 w-16', 'percentual' => null])

<div {{ $attributes->class(['relative flex shrink-0 items-center justify-center', $size]) }}>
    @if ($url)
        @if ($percentual !== null)
            <img
                src="{{ $url }}"
                alt="{{ $alt }}"
                class="absolute inset-0 h-full w-full object-contain grayscale opacity-40"
            />
            <img
                src="{{ $url }}"
                alt=""
                class="absolute inset-0 h-full w-full object-contain"
                style="clip-path: inset({{ 100 - max(0, min(100, $percentual * 100)) }}% 0 0 0)"
            />
        @else
            <img
                src="{{ $url }}"
                alt="{{ $alt }}"
                class="h-full w-full object-contain {{ $colorida ? '' : 'grayscale opacity-40' }}"
            />
        @endif
    @else
        <div class="flex h-full w-full items-center justify-center rounded-xl bg-gray-100 text-gray-300 dark:bg-white/5 dark:text-gray-600">
            <x-filament::icon icon="heroicon-o-photo" class="h-6 w-6" />
        </div>
    @endif
</div>
