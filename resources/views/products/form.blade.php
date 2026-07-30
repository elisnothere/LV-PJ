@csrf

<div class="card-body">
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revise los datos ingresados.</strong>
        </div>
    @endif

    <div class="row">
        <div class="form-group col-12 col-md-6 mb-3">
            <label for="name">Producto</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name ?? '') }}">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-6 mb-3">
            <label for="category">Categoria</label>
            <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $product->category ?? '') }}">
            @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-4 mb-3">
            <label for="price">Precio</label>
            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price ?? '') }}">
            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-4 mb-3">
            <label for="stock">Stock</label>
            <input type="number" min="0" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', $product->stock ?? 0) }}">
            @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-4 mb-3">
            <label for="image_url">Imagen URL</label>
            <input type="text" class="form-control @error('image_url') is-invalid @enderror" id="image_url" name="image_url" value="{{ old('image_url', $product->image_url ?? '') }}">
            @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-4 mb-3">
            <label for="image_file">Subir imagen</label>
            <input type="file" class="form-control @error('image_file') is-invalid @enderror" id="image_file" name="image_file" accept="image/*">
            @error('image_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 mb-3">
            <label for="description">Descripcion</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $product->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-check col-12 ms-2">
            <input type="checkbox" class="form-check-input" id="active" name="active" value="1" @checked(old('active', $product->active ?? true))>
            <label for="active" class="form-check-label">Producto activo</label>
        </div>
    </div>
</div>

<div class="card-footer">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>
        Guardar
    </button>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
</div>
