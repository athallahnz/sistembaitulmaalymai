@csrf
<div class="mb-3">
    <label class="mb-2" for="name">Nama Bidang</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $bidang->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="mb-2" for="description">Deskripsi</label>
    <textarea name="description" class="form-control">{{ old('description', $bidang->description ?? '') }}</textarea>
</div>
<button type="submit" class="btn btn-primary">Simpan</button>
<a href="{{ route('add_bidangs.index') }}" class="btn btn-secondary">Kembali</a>
