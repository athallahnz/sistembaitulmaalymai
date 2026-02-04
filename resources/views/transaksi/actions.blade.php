<div class="d-flex justify-content-end align-items-center gap-1 flex-wrap">
    <a href="{{ route('transaksi.exportPdf', $id) }}" class="btn btn-outline-danger btn-sm rounded py-0 px-1"
        title="Cetak PDF">
        <i class="bi bi-filetype-pdf"></i>
    </a>

    {{-- Tombol Edit pakai modal --}}
    <button type="button" class="btn btn-warning btn-sm rounded py-0 px-1 btn-edit-transaksi"
        data-id="{{ $id }}" title="Edit">
        <i class="bi bi-pencil-square"></i>
    </button>

    {{-- Tombol Hapus --}}
    <button type="button" class="btn btn-danger btn-sm rounded py-0 px-1 delete-btn" data-id="{{ $id }}"
        title="Hapus">
        <i class="bi bi-trash"></i>
    </button>
</div>

{{-- Hidden delete form --}}
<form id="delete-form-{{ $id }}" action="{{ route('transaksi.destroy', $id) }}" method="POST"
    style="display:none;">
    @csrf
    @method('DELETE')
</form>
