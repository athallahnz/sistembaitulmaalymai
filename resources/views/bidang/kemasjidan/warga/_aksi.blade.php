{{-- Aksi untuk satu baris datatables --}}

{{-- Tombol Edit --}}
<button type="button" class="btn btn-sm btn-primary btn-edit-warga me-1" data-update-url="{{ $updateUrl }}"
    data-nama="{{ $w->nama }}" data-rt="{{ $w->rt }}" data-no="{{ $w->no }}"
    data-hp="{{ $w->hp }}" data-alamat="{{ $w->alamat }}" data-warga-id="{{ $w->warga_id }}"
    data-is-kepala="{{ $isKepala }}">
    <i class="bi bi-pencil"></i>
</button>


{{-- Tombol Meninggal (hanya kepala keluarga) --}}
@if ($isKepala)
    <button type="button" class="btn btn-sm btn-secondary btn-warga-meninggal me-1" data-bs-toggle="tooltip"
        title="Tandai Meninggal" data-id="{{ $w->id }}" data-nama="{{ $w->nama }}">
        <i class="bi bi-person-x"></i>
    </button>
@endif

{{-- Tombol Hapus --}}
<form action="{{ $deleteUrl }}" method="POST" class="d-inline form-delete-warga">
    @csrf
    @method('DELETE')

    <button type="button" class="btn btn-sm btn-danger btn-delete-warga" data-bs-toggle="tooltip" title="Hapus Warga">
        <i class="bi bi-trash"></i>
    </button>
</form>
