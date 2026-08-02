@php
    $size = $size ?? '64px';
    $iconClass = $iconClass ?? 'bi-box-seam';
    $wrapperClass = $wrapperClass ?? 'img-thumbnail';
    $alt = $alt ?? 'Producto';
@endphp

@if (! empty($imageUrl))
    <img src="{{ $imageUrl }}" alt="{{ $alt }}" class="{{ $wrapperClass }}" style="width: {{ $size }}; height: {{ $size }}; object-fit: cover;">
@else
    <div class="bg-body-secondary d-flex align-items-center justify-content-center rounded text-secondary {{ $wrapperClass === 'img-thumbnail' ? 'img-thumbnail' : '' }}" style="width: {{ $size }}; height: {{ $size }};">
        <i class="bi {{ $iconClass }}"></i>
    </div>
@endif
