<div class="card">
    <div class="icon bi {{ $icon }}"></div>
    <h5>{{ $nama }}</h5>
    <div class="value {{ $saldo >= 0 ? 'positive' : 'negative' }}">
        Rp <span class="hidden-value" style="display: none;">
            {{ number_format($saldo, 0, ',', '.') }}
        </span>
        <span class="masked-value">***</span>
        <i class="bi bi-eye toggle-eye" style="cursor: pointer; margin-left: 10px;" onclick="toggleVisibility(this)"></i>
    </div>
    <div class="description">{{ $keterangan }}</div>
</div>
