<div class="panel">
    <h2>Empresa</h2>
    <div class="grid">
        <div class="field">
            <label for="name">Nombre</label>
            <input id="name" name="name" value="{{ old('name', $company->name) }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="slug">Slug</label>
            <input id="slug" name="slug" value="{{ old('slug', $company->slug) }}" required>
            @error('slug') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="email">Correo de contacto</label>
            <input id="email" type="email" name="email" value="{{ old('email', $company->email) }}">
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="phone">Telefono</label>
            <input id="phone" name="phone" value="{{ old('phone', $company->phone) }}">
            @error('phone') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="timezone">Zona horaria</label>
            <input id="timezone" name="timezone" value="{{ old('timezone', $company->timezone) }}" required>
            @error('timezone') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="currency_code">Codigo de moneda</label>
            <input id="currency_code" name="currency_code" value="{{ old('currency_code', $company->currency_code) }}" maxlength="3" required>
            @error('currency_code') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="currency_symbol">Simbolo de moneda</label>
            <input id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $company->currency_symbol) }}" required>
            @error('currency_symbol') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="field">
        <label for="address">Direccion</label>
        <textarea id="address" name="address">{{ old('address', $company->address) }}</textarea>
        @error('address') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="sa-button-group">
    <button class="sa-button sa-button--primary" type="submit">Guardar cambios</button>
    <a class="sa-button sa-button--secondary" href="{{ route('superadmin.companies.index') }}">Cancelar</a>
</div>
