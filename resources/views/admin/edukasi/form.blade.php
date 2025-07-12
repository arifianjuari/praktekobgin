@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ isset($edukasi) ? 'Edit' : 'Tambah' }} Artikel Edukasi</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ isset($edukasi) ? route('admin.edukasi.update', $edukasi->id_edukasi) : route('admin.edukasi.store') }}" 
                          method="POST" 
                          enctype="multipart/form-data">
                        @csrf
                        @if(isset($edukasi))
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('judul') is-invalid @enderror" 
                                   id="judul" 
                                   name="judul" 
                                   value="{{ old('judul', $edukasi->judul ?? '') }}" 
                                   required>
                            @error('judul')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select @error('kategori') is-invalid @enderror" 
                                    id="kategori" 
                                    name="kategori" 
                                    required>
                                <option value="">Pilih Kategori</option>
                                @foreach($kategori_list as $kategori)
                                    <option value="{{ $kategori }}" 
                                            {{ old('kategori', $edukasi->kategori ?? '') == $kategori ? 'selected' : '' }}>
                                        {{ ucfirst($kategori) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="link_gambar" class="form-label">Gambar</label>
                            <input type="file" 
                                   class="form-control @error('link_gambar') is-invalid @enderror" 
                                   id="link_gambar" 
                                   name="link_gambar" 
                                   accept="image/jpeg,image/png">
                            <small class="text-muted">Format: JPG, PNG. Maksimal 1MB. Akan diresize ke maksimal 800x800px</small>
                            @error('link_gambar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if(isset($edukasi) && $edukasi->link_gambar)
                                <div class="mt-2">
                                    <img src="{{ $edukasi->image_url }}" 
                                         alt="Preview" 
                                         class="img-thumbnail" 
                                         style="max-height: 200px">
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="isi_edukasi" class="form-label">Konten <span class="text-danger">*</span></label>
                            <textarea class="form-control summernote @error('isi_edukasi') is-invalid @enderror" 
                                      id="isi_edukasi" 
                                      name="isi_edukasi" 
                                      required>{{ old('isi_edukasi', $edukasi->isi_edukasi ?? '') }}</textarea>
                            @error('isi_edukasi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="sumber" class="form-label">Sumber</label>
                            <textarea class="form-control @error('sumber') is-invalid @enderror" 
                                      id="sumber" 
                                      name="sumber" 
                                      rows="3">{{ old('sumber', $edukasi->sumber ?? '') }}</textarea>
                            @error('sumber')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="tag" class="form-label">Tag</label>
                            <input type="text" 
                                   class="form-control @error('tag') is-invalid @enderror" 
                                   id="tag" 
                                   name="tag" 
                                   value="{{ old('tag', $edukasi->tag ?? '') }}" 
                                   placeholder="Contoh: kehamilan, kesehatan, tips">
                            @error('tag')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="status_aktif" 
                                       name="status_aktif" 
                                       {{ old('status_aktif', $edukasi->status_aktif ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status_aktif">Status Aktif</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="ditampilkan_beranda" 
                                       name="ditampilkan_beranda" 
                                       {{ old('ditampilkan_beranda', $edukasi->ditampilkan_beranda ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="ditampilkan_beranda">Tampilkan di Beranda</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="urutan_tampil" class="form-label">Urutan Tampil</label>
                            <input type="number" 
                                   class="form-control @error('urutan_tampil') is-invalid @enderror" 
                                   id="urutan_tampil" 
                                   name="urutan_tampil" 
                                   value="{{ old('urutan_tampil', $edukasi->urutan_tampil ?? '') }}" 
                                   min="1">
                            <small class="text-muted">Urutan tampil di beranda (opsional)</small>
                            @error('urutan_tampil')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.edukasi.index') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                {{ isset($edukasi) ? 'Simpan Perubahan' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
$(document).ready(function() {
    $('.summernote').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });

    // Preview gambar yang akan diupload
    $('#link_gambar').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = $('<img>').attr({
                    'src': e.target.result,
                    'alt': 'Preview',
                    'class': 'img-thumbnail mt-2',
                    'style': 'max-height: 200px'
                });
                $('#link_gambar').next('.img-thumbnail').remove();
                $('#link_gambar').after(img);
            }
            reader.readAsDataURL(file);
        }
    });
});
</script>
@endpush 