<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edukasi extends Model
{
    use HasFactory;

    protected $table = 'edukasi';
    protected $primaryKey = 'id_edukasi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_edukasi',
        'judul',
        'kategori',
        'isi_edukasi',
        'link_gambar',
        'link_video',
        'sumber',
        'tag',
        'status_aktif',
        'ditampilkan_beranda',
        'urutan_tampil',
        'dibuat_oleh'
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
        'ditampilkan_beranda' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the URL gambar
     */
    public function getImageUrlAttribute()
    {
        if (!$this->link_gambar) {
            return null;
        }
        return asset('storage/edukasi/' . $this->link_gambar);
    }
}
