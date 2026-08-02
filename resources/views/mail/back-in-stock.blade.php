<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Producto disponible nuevamente</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h1 style="font-size: 22px; margin-bottom: 16px;">Tu producto ya esta disponible otra vez</h1>

    <p>El producto <strong>{{ $product->name }}</strong> ya volvio a tener stock.</p>

    <ul>
        <li>Categoria: {{ $product->category?->name ?? 'Sin categoria' }}</li>
        <li>Precio: ${{ number_format($product->price, 2) }}</li>
        <li>Stock actual: {{ $product->stock }}</li>
    </ul>

    <p>
        Puedes verlo aqui:
        <a href="{{ route('catalog.show', $product) }}">{{ route('catalog.show', $product) }}</a>
    </p>
</body>
</html>
