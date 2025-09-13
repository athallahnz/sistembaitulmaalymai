<a href="{{ route('admin.akun_keuangan.edit', $row->id) }}" class="btn btn-warning btn-sm me-2">
    <i class="bi bi-pencil-square"></i>
</a>

<form id="delete-form-{{ $row->id }}" onsubmit="return confirmDelete(event, {{ $row->id }})"
    action="{{ route('admin.akun_keuangan.destroy', $row->id) }}" method="POST" style="display:inline-block;">
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-danger btn-sm">
        <i class="bi bi-trash"></i>
    </button>
</form>
