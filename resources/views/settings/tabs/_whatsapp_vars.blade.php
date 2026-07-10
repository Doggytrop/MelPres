<div class="mt-2 d-flex flex-wrap gap-2">
    <span class="text-muted" style="font-size:11px; align-self:center;">Variables:</span>
    @foreach(['{nombre}', '{monto}', '{fecha}', '{saldo}', '{mora}', '{negocio}'] as $var)
        <span class="badge" style="background:#f0faf0; color:#1f6b21; font-size:11px; cursor:pointer; font-family:monospace;"
              onclick="insertVar(this, '{{ $var }}')">{{ $var }}</span>
    @endforeach
</div>
<p class="setting-description mt-1">Haz clic en una variable para insertarla en el mensaje.</p>

<script>
function insertVar(badge, variable) {
    const textarea = badge.closest('.setting-item').querySelector('textarea');
    const start = textarea.selectionStart;
    const end   = textarea.selectionEnd;
    textarea.value = textarea.value.substring(0, start) + variable + textarea.value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + variable.length;
    textarea.focus();
}
</script>