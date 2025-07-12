<?php

namespace App\Http\Controllers;

use App\Models\Edukasi;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EdukasiController extends Controller
{
    /**
     * Konfigurasi upload gambar
     */
    private $imageConfig = [
        'max_size' => 1024, // 1MB dalam kilobytes
        'allowed_types' => ['jpg', 'jpeg', 'png'],
        'max_dimension' => 800,
        'quality' => 85,
        'output_format' => 'jpg',
    ];

    /**
     * Upload dan simpan gambar edukasi
     */
    private function handleImageUpload(Request $request)
    {
        if (!$request->hasFile('link_gambar')) {
            return null;
        }

        // Validasi file
        $request->validate([
            'link_gambar' => 'required|file|mimes:jpg,jpeg,png|max:' . $this->imageConfig['max_size'],
        ]);

        $image = $request->file('link_gambar');

        // Generate nama file unik
        $fileName = 'edukasi-' . time() . '-' . Str::random(8) . '.' . $this->imageConfig['output_format'];

        // Buat instance Image Intervention
        $img = Image::make($image);

        // Resize dengan mempertahankan aspect ratio
        $img->resize($this->imageConfig['max_dimension'], $this->imageConfig['max_dimension'], function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Kompresi gambar
        $img->encode($this->imageConfig['output_format'], $this->imageConfig['quality']);

        // Simpan gambar
        Storage::disk('public')->put('edukasi/' . $fileName, $img->stream());

        return $fileName;
    }

    /**
     * Simpan data edukasi baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string',
            'isi_edukasi' => 'required|string',
            'link_gambar' => 'nullable|file|mimes:jpg,jpeg,png|max:' . $this->imageConfig['max_size'],
        ]);

        try {
            $fileName = $this->handleImageUpload($request);

            $edukasi = Edukasi::create([
                'judul' => $request->judul,
                'kategori' => $request->kategori,
                'isi_edukasi' => $request->isi_edukasi,
                'link_gambar' => $fileName,
                'sumber' => $request->sumber,
                'tag' => $request->tag,
                'status_aktif' => $request->has('status_aktif'),
                'ditampilkan_beranda' => $request->has('ditampilkan_beranda'),
                'urutan_tampil' => $request->urutan_tampil,
            ]);

            return redirect()->back()->with('success', 'Artikel edukasi berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan artikel: ' . $e->getMessage());
        }
    }

    /**
     * Update data edukasi
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string',
            'isi_edukasi' => 'required|string',
            'link_gambar' => 'nullable|file|mimes:jpg,jpeg,png|max:' . $this->imageConfig['max_size'],
        ]);

        try {
            $edukasi = Edukasi::findOrFail($id);

            // Handle upload gambar baru jika ada
            if ($request->hasFile('link_gambar')) {
                // Hapus gambar lama jika ada
                if ($edukasi->link_gambar) {
                    Storage::disk('public')->delete('edukasi/' . $edukasi->link_gambar);
                }

                $fileName = $this->handleImageUpload($request);
                $edukasi->link_gambar = $fileName;
            }

            $edukasi->update([
                'judul' => $request->judul,
                'kategori' => $request->kategori,
                'isi_edukasi' => $request->isi_edukasi,
                'sumber' => $request->sumber,
                'tag' => $request->tag,
                'status_aktif' => $request->has('status_aktif'),
                'ditampilkan_beranda' => $request->has('ditampilkan_beranda'),
                'urutan_tampil' => $request->urutan_tampil,
            ]);

            return redirect()->back()->with('success', 'Artikel edukasi berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui artikel: ' . $e->getMessage());
        }
    }

    /**
     * Hapus data edukasi
     */
    public function destroy($id)
    {
        try {
            $edukasi = Edukasi::findOrFail($id);

            // Hapus gambar jika ada
            if ($edukasi->link_gambar) {
                Storage::disk('public')->delete('edukasi/' . $edukasi->link_gambar);
            }

            $edukasi->delete();

            return redirect()->back()->with('success', 'Artikel edukasi berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus artikel: ' . $e->getMessage());
        }
    }
}
