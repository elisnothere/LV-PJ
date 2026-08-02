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
            <label for="category_id">Categoria existente</label>
            @php
                $selectedCategoryId = old('category_id', $product->category_id ?? '');
            @endphp
            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                <option value="">Seleccione una categoria</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-secondary">Seleccione una categoria existente o escriba una nueva abajo.</small>
        </div>

        <div class="form-group col-12 mb-3">
            <label for="new_category_name">Nueva categoria</label>
            <input type="text" class="form-control @error('new_category_name') is-invalid @enderror" id="new_category_name" name="new_category_name" value="{{ old('new_category_name') }}" placeholder="Ej.: Electronica premium">
            @error('new_category_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
            <label for="image_files">Subir imagenes</label>
            <input type="file" class="form-control @error('image_files') is-invalid @enderror @error('image_files.*') is-invalid @enderror" id="image_files" name="image_files[]" accept="image/*" multiple>
            @error('image_files') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @foreach ($errors->get('image_files.*') as $fileErrors)
                @foreach ($fileErrors as $message)
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @endforeach
            @endforeach
            <small class="text-secondary">Puede seleccionar varias imagenes JPG, JPEG o PNG. Maximo 10 MB por archivo.</small>
        </div>

        <div class="form-group col-12 mb-3">
            <label for="image_urls">Imagenes por URL</label>
            <textarea class="form-control @error('image_urls') is-invalid @enderror" id="image_urls" name="image_urls" rows="3" placeholder="https://ejemplo.com/imagen-1.jpg&#10;https://ejemplo.com/imagen-2.jpg">{{ old('image_urls') }}</textarea>
            @error('image_urls') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="text-secondary">Ingrese una URL por linea.</small>
        </div>

        @if (isset($product) && $product->images->isNotEmpty())
            @php
                $selectedPrimaryImageId = old('primary_image_id', $product->primaryImage?->id);
                $deletedImageIds = old('delete_image_ids', []);
            @endphp
            <div class="form-group col-12 mb-3">
                <label class="d-block">Imagenes actuales</label>
                <div class="row g-3">
                    @foreach ($product->images as $image)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="border rounded p-2 h-100">
                                <img src="{{ $image->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded border mb-2" style="width: 100%; height: 140px; object-fit: cover;">
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="primary_image_id" id="primary_image_{{ $image->id }}" value="{{ $image->id }}" @checked((string) $selectedPrimaryImageId === (string) $image->id)>
                                    <label class="form-check-label" for="primary_image_{{ $image->id }}">Principal</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="delete_image_ids[]" id="delete_image_{{ $image->id }}" value="{{ $image->id }}" @checked(in_array($image->id, $deletedImageIds))>
                                    <label class="form-check-label text-danger" for="delete_image_{{ $image->id }}">Eliminar</label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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
