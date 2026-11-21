<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\StudioPhoto;

class StudioPhotoController extends Controller
{
    public function destroy(Studio $studio, StudioPhoto $photo)
    {
        abort_unless($photo->studio_id === $studio->id, 404);

        $photo->deleteWithFile();

        return back()->with('success', 'Foto dihapus.');
    }
}