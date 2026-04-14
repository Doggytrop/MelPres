<p class="text-muted mb-3" style="font-size:13px;">
    Una vez eliminada tu cuenta, todos tus datos serán borrados permanentemente. 
    Asegúrate de guardar cualquier información importante antes de continuar.
</p>

<button type="button" class="btn btn-sm"
        style="color:#c0392b; border:0.5px solid #f5c6c6; border-radius:8px; font-size:13px; padding:8px 20px;"
        data-bs-toggle="modal" data-bs-target="#modalEliminarCuenta">
    Eliminar cuenta
</button>

{{-- Modal confirmación --}}
<div class="modal fade" id="modalEliminarCuenta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border rounded-3" style="border-color:#e8e8e8 !important;">
            <div class="modal-body p-4">
                <h6 class="fw-medium mb-2" style="color:#1a2e1a;">¿Eliminar tu cuenta?</h6>
                <p class="text-muted mb-4" style="font-size:13px;">
                    Esta acción es irreversible. Ingresa tu contraseña para confirmar.
                </p>

                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="mb-4">
                        <label class="d-block mb-1 text-muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.05em;">Contraseña</label>
                        <input type="password" name="password" placeholder="Tu contraseña"
                               class="form-control form-control-sm @error('password', 'userDeletion') is-invalid @enderror">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm"
                                style="background:#f5f5f5; color:#555; border-radius:8px; font-size:13px; padding:8px 20px;"
                                data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-sm"
                                style="background:#c0392b; color:white; border-radius:8px; font-size:13px; padding:8px 20px;">
                            Sí, eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>