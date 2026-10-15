@props([
    'title',
    'subtitle' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h2 class="text-title-md2 font-bold text-gray-800 dark:text-white/90 flex items-center gap-2.5">
            @if($icon)
                <i class="{{ $icon }} text-brand-500"></i>
            @endif
            <span>{{ $title }}</span>
        </h2>
        @if($subtitle)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            {{ $actions }}
        </div>
    @elseif(!empty(trim($slot)))
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
