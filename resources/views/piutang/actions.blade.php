<a href="{{ route('piutangs.edit', $piutang->id) }}" class="btn btn-warning btn-sm">
    <i class="bi bi-pencil-square"></i>
</a>

<button type="button" class="btn btn-danger btn-sm delete-btn" data-id="{{ $piutang->id }}">
    <i class="bi bi-trash"></i>
</button>

<form id="delete-form-{{ $piutang->id }}" action="{{ route('piutangs.destroy', $piutang->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
