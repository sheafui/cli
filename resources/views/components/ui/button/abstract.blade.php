
@props([
    'href' => null,
    'as' => null,
])


@php
    $type = match(true) {
        $as === 'div' && !$href => 'div',
        $as === 'a' || $href => 'a', 
        default => 'button'
    };
@endphp

@switch($type)
    @case('div')
        <div {{ $attributes }}>
            {{ $slot }}
        </div>
        @break
        
    @case('a')
        <a href="{{ $href }}" {{ $attributes }}>
            {{ $slot }}
        </a>
        @break
        
    @default
        <button {{ $attributes->merge(['type' => $type]) }}>
            {{ $slot }}
        </button>
@endswitch