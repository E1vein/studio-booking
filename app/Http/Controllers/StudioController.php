<?php

namespace App\Http\Controllers;

use App\Models\Studio;

class StudioController extends Controller
{
    public function index()
    {
        $studios = Studio::with('photos')->orderBy('name')->get();
        return view('studios.index', compact('studios'));
    }

    public function show(Studio $studio)
    {
        $studio->load(['schedules', 'photos']);
        return view('studios.show', compact('studio'));
    }
}