<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudioPhoto extends Model
{
    protected $fillable = ['studio_id', 'path', 'caption', 'sort_order'];

    public function studio() { return $this->belongsTo(Studio::class); }

    // $photo->url -> URL siap pakai di <img>
    public function getUrlAttribute(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($this->path);
    }

    // Hapus file fisik (kalau bukan URL eksternal) lalu hapus record
    public function deleteWithFile(): void
    {
        if (! Str::startsWith($this->path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($this->path);
        }
        $this->delete();
    }
}