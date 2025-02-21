<a href="{{ route('hutangs.edit', $hutang->id) }}" class="btn btn-warning">
    <i class="bi bi-pencil-square"></i>
</a>

<button type="button" class="btn btn-danger delete-btn" data-id="{{ $hutang->id }}">
    <i class="bi bi-trash"></i>
</button>

<form id="delete-form-{{ $hutang->id }}" action="{{ route('hutangs.destroy', $hutang->id) }}" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
