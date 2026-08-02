@php
    $alt = $alt ?? 'Producto';
    $imageClass = $imageClass ?? 'img-thumbnail';
    $style = $style ?? null;

    if ($style === null) {
        $size = $size ?? '64px';
        $style = "width: {$size}; height: {$size}; object-fit: cover;";
    }

    $placeholderSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160">
    <rect width="160" height="160" rx="18" fill="#e9ecef"/>
    <path d="M80 33 109 47v39c0 22-16 40-29 47-13-7-29-25-29-47V47l29-14Zm0 8.7L58 52v33.7c0 17.8 12.6 32.8 22 38.7 9.4-5.9 22-20.9 22-38.7V52L80 41.7Zm0 13.5 16 7.8-16 8.6-16-8.6 16-7.8Zm-21 14.2 18 9.7v27.7c-9.2-5.9-18-17.6-18-31.1V69.4Zm24 37.4V79.1l18-9.7v6.3c0 13.5-8.8 25.2-18 31.1Z" fill="#6c757d"/>
</svg>
SVG;

    $placeholderImage = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($placeholderSvg);
    $resolvedImage = filled($imageUrl ?? null) ? $imageUrl : $placeholderImage;
@endphp

<img
    src="{{ $resolvedImage }}"
    alt="{{ $alt }}"
    class="{{ $imageClass }}"
    style="{{ $style }}"
    onerror="this.onerror=null;this.src='{{ $placeholderImage }}';"
>
