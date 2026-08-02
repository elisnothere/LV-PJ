@csrf

@if ($errors->any())
    <div class="card-body pb-0">
        <div class="alert alert-danger mb-0">
            <strong>Revise los datos ingresados.</strong>
        </div>
    </div>
@endif

<div class="card-body">
    <div class="mb-3">
        <label for="primary_address" class="form-label">Direccion principal</label>
        <input type="text" id="primary_address" name="primary_address" class="form-control @error('primary_address') is-invalid @enderror" value="{{ old('primary_address', $address->primary_address ?? '') }}">
        @error('primary_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label for="secondary_address" class="form-label">Direccion secundaria</label>
        <input type="text" id="secondary_address" name="secondary_address" class="form-control @error('secondary_address') is-invalid @enderror" value="{{ old('secondary_address', $address->secondary_address ?? '') }}" placeholder="Departamento, referencia, bloque, etc.">
        @error('secondary_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label for="shipping_city_id" class="form-label">Ciudad</label>
        <select id="shipping_city_id" name="shipping_city_id" class="form-select @error('shipping_city_id') is-invalid @enderror">
            <option value="">Seleccione una ciudad</option>
            @foreach ($shippingCities as $shippingCity)
                <option value="{{ $shippingCity->id }}" @selected(old('shipping_city_id', $address->shipping_city_id ?? null) == $shippingCity->id)>
                    {{ $shippingCity->name }} - ${{ number_format($shippingCity->shipping_cost, 2) }}
                </option>
            @endforeach
        </select>
        @error('shipping_city_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
<div class="card-footer">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>
        Guardar
    </button>
    <a href="{{ route('addresses.index') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
</div>
