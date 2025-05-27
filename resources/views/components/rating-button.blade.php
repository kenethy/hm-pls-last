@props([
    'serviceId',
    'size' => 'md',
    'variant' => 'primary',
    'showText' => true,
    'disabled' => false
])

@php
    $sizeClasses = [
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-3 py-2 text-sm',
        'lg' => 'px-4 py-2 text-base'
    ];

    $variantClasses = [
        'primary' => 'bg-yellow-500 hover:bg-yellow-600 text-white',
        'secondary' => 'bg-gray-500 hover:bg-gray-600 text-white',
        'outline' => 'border border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-white',
        'ghost' => 'text-yellow-500 hover:bg-yellow-50'
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-md font-medium transition-colors duration-200',
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $variantClasses[$variant] ?? $variantClasses['primary'],
        $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
    ]);
@endphp

<button 
    type="button"
    onclick="openRatingModal({{ $serviceId }})"
    class="{{ $classes }}"
    @if($disabled) disabled @endif
    title="Berikan rating untuk montir"
>
    <svg class="w-4 h-4 {{ $showText ? 'mr-2' : '' }}" fill="currentColor" viewBox="0 0 20 20">
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
    </svg>
    @if($showText)
        <span>Rating Montir</span>
    @endif
</button>
