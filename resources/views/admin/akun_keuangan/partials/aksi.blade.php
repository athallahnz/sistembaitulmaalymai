<button type="button" class="btn btn-info btn-sm me-2 btn-detail-akun" data-id="{{ $row->id }}" data-bs-toggle="modal"
    data-bs-target="#akunDetailModal" title="Detail">
    <i class="bi bi-eye"></i>
</button>

<button type="button" class="btn btn-warning btn-sm me-2 btn-edit-akun" data-id="{{ $row->id }}"
    data-bs-toggle="modal" data-bs-target="#akunEditModal" title="Edit">
    <i class="bi bi-pencil-square"></i>
</button>

<form id="delete-form-{{ $row->id }}" onsubmit="return confirmDelete(event, {{ $row->id }})"
    action="{{ route('admin.akun_keuangan.destroy', $row->id) }}" method="POST" style="display:inline-block;">
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
        <i class="bi bi-trash"></i>
    </button>
</form>
