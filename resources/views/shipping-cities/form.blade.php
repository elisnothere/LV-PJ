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
        <label for="name" class="form-label">Ciudad</label>
        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $shippingCity->name ?? '') }}">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label for="shipping_cost" class="form-label">Costo de envio</label>
        <input type="number" step="0.01" min="0" id="shipping_cost" name="shipping_cost" class="form-control @error('shipping_cost') is-invalid @enderror" value="{{ old('shipping_cost', isset($shippingCity) ? number_format((float) $shippingCity->shipping_cost, 2, '.', '') : '') }}">
        @error('shipping_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="form-check">
        <input type="checkbox" id="active" name="active" value="1" class="form-check-input" @checked(old('active', $shippingCity->active ?? true))>
        <label for="active" class="form-check-label">Activa</label>
    </div>
</div>
<div class="card-footer">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>
        Guardar
    </button>
    <a href="{{ route('shipping-cities.index') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
</div>
