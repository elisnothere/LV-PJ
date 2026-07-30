@csrf

<div class="card-body">
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revise los datos ingresados.</strong>
        </div>
    @endif

    <div class="row">
        <div class="form-group col-12 col-md-6 mb-3">
            <label for="name">Nombre</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name ?? '') }}">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-6 mb-3">
            <label for="email">Correo</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email ?? '') }}">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-6 mb-3">
            <label for="password">Contrasena</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="{{ isset($user) ? 'Dejar vacio para mantener' : 'Ingrese la contrasena' }}">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group col-12 col-md-6 mb-3">
            <label for="password_confirmation">Confirmar contrasena</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
        </div>

        <div class="form-group col-12 col-md-6 mb-3">
            <label for="role">Rol</label>
            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                @foreach (\App\Models\User::ROLES as $role)
                    <option value="{{ $role }}" @selected(old('role', $user->role ?? 'cliente') === $role)>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-check col-12 ms-2">
            <input type="checkbox" class="form-check-input" id="active" name="active" value="1" @checked(old('active', $user->active ?? true))>
            <label for="active" class="form-check-label">Usuario activo</label>
        </div>
    </div>
</div>

<div class="card-footer">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>
        Guardar
    </button>
    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
</div>
