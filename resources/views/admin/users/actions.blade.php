<div class="text-end">
    @if ($user->trashed())
        <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" class="d-inline">
            @csrf
            @method('PUT')
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </form>
        <form action="{{ route('admin.users.forceDelete', $user->id) }}" method="POST" class="d-inline delete-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-trash"></i>
            </button>
        </form>
    @else
        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil-square"></i>
        </a>

        <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="{{ $user->id }}">
            <i class="bi bi-trash"></i>
        </button>

        <form id="delete-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
            style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>

<!-- Tambahkan library jQuery jika belum ada -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $(".delete-btn").click(function() {
            var userId = $(this).data("id");
            Swal.fire({
                title: "Yakin ingin menonaktifkan pengguna?",
                text: "Data yang dinonaktifkan akan masuk ke tempat sampah, belum sebenarnya terhapus!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, Nonaktifkan!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#delete-form-" + userId).submit();
                }
            });
        });
    });
</script>
