<div class="d-flex justify-content-between">
    @if(auth()->user()->hasRole('Bidang'))
    <a href="{{ route('transaksi.exportPdf', $id) }}" class="btn btn-sm btn-danger rounded mx-1">
        <i class="bi bi-filetype-pdf"></i>
    </a>
    <a href="{{ route('transaksi.edit', $id) }}" class="btn btn-sm btn-warning rounded mx-1">
        <i class="bi bi-pencil-square"></i>
    </a>
    @elseif(auth()->user()->hasRole('Bendahara'))
    <a href="{{ route('transaksi.exportPdf', $id) }}" class="btn btn-sm btn-danger rounded mx-1">
        <i class="bi bi-filetype-pdf"></i>
    </a>
    @endif
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $(".delete-btn").click(function() {
            var transaksiId = $(this).data("id"); // Ambil ID transaksi
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
                    $("#delete-form-" + transaksiId).submit(); // Submit form
                }
            });
        });
    });
</script>
