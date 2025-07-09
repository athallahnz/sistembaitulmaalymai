<div class="d-flex justify-content-end align-items-center gap-1 flex-wrap">
    @if (auth()->user()->hasRole('Bidang'))
        <a href="{{ route('transaksi.exportPdf', $id) }}" class="btn btn-outline-danger btn-sm rounded py-0 px-1"
            title="Cetak PDF">
            <i class="bi bi-filetype-pdf"></i>
        </a>
        <a href="{{ route('transaksi.edit', $id) }}" class="btn btn-warning btn-sm rounded py-0 px-1" title="Edit">
            <i class="bi bi-pencil-square"></i>
        </a>
        <button type="button" class="btn btn-danger btn-sm rounded delete-btn py-0 px-1" data-id="{{ $id }}"
            title="Hapus">
            <i class="bi bi-trash"></i>
        </button>
    @elseif(auth()->user()->hasRole('Bendahara'))
        <button type="button" class="btn btn-outline-danger btn-sm rounded delete-btn py-0 px-1"
            data-id="{{ $id }}" title="Hapus">
            <i class="bi bi-trash"></i>
        </button>
        <a href="{{ route('transaksi.exportPdf', $id) }}" class="btn btn-outline-danger btn-sm rounded py-0 px-1"
            title="Cetak PDF">
            <i class="bi bi-filetype-pdf"></i>
        </a>
    @endif
</div>


<!-- Hidden delete form -->
<form id="delete-form-{{ $id }}" action="{{ route('transaksi.destroy', $id) }}" method="POST"
    style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $(".delete-btn").click(function() {
            var transaksiId = $(this).data("id");
            Swal.fire({
                title: "Apakah Anda yakin?",
                text: "Data ini akan dihapus dan tidak dapat dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Ya, hapus!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#delete-form-" + transaksiId).submit();
                }
            });
        });
    });
</script>
