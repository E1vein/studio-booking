<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudioController extends Controller
{
    private const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public function index()
    {
        $studios = Studio::with('photos')->withCount('bookings')->orderBy('name')->get();
        return view('admin.studios.index', compact('studios'));
    }

    public function create()
    {
        return view('admin.studios.create');
    }

    public function store(Request $request)
    {
        $data      = $this->validated($request);
        $schedules = $this->cleanSchedules($request);   // validasi dulu sebelum menyimpan apa pun

        $studio = Studio::create($data);
        $this->syncSchedules($studio, $schedules);
        $this->storePhotos($studio, $request);

        return redirect()->route('admin.studios.index')->with('success', 'Studio berhasil ditambahkan.');
    }

    public function edit(Studio $studio)
    {
        $studio->load(['schedules', 'photos']);
        return view('admin.studios.edit', compact('studio'));
    }

    public function update(Request $request, Studio $studio)
    {
        $data      = $this->validated($request);
        $schedules = $this->cleanSchedules($request);

        $studio->update($data);
        $this->syncSchedules($studio, $schedules);
        $this->storePhotos($studio, $request);

        return redirect()->route('admin.studios.index')->with('success', 'Studio berhasil diperbarui.');
    }

    public function destroy(Studio $studio)
    {
        $hasUpcoming = $studio->bookings()->active()
            ->whereDate('booking_date', '>=', today())->exists();

        if ($hasUpcoming) {
            return back()->with('error', 'Studio masih punya booking mendatang. Batalkan dulu bookingnya.');
        }

        $studio->photos->each->deleteWithFile();
        $studio->delete();

        return back()->with('success', 'Studio dihapus.');
    }

    // ---------- helper ----------

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'hourly_rate' => ['required', 'integer', 'min:0'],
            'capacity'    => ['required', 'integer', 'min:1'],
            'photos'      => ['nullable', 'array'],
            'photos.*'    => ['image', 'max:4096'],          // maks 4 MB per foto
            'captions.*'  => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function cleanSchedules(Request $request): array
    {
        $rows = [];
        foreach ($request->input('schedules', []) as $day => $row) {
            if (! isset(self::DAYS[$day]) || empty($row['active'])) {
                continue;
            }
            $open  = $row['open_time'] ?? null;
            $close = $row['close_time'] ?? null;

            if (! $open || ! $close || $close <= $open) {
                throw ValidationException::withMessages([
                    'schedules' => 'Jam buka/tutup tidak valid untuk hari ' . self::DAYS[$day] . '.',
                ]);
            }
            $rows[] = ['day_of_week' => (int) $day, 'open_time' => $open, 'close_time' => $close];
        }
        return $rows;
    }

    private function syncSchedules(Studio $studio, array $rows): void
    {
        $studio->schedules()->delete();
        foreach ($rows as $row) {
            $studio->schedules()->create($row);
        }
    }

    private function storePhotos(Studio $studio, Request $request): void
    {
        $order = (int) $studio->photos()->max('sort_order');

        foreach ($request->file('photos', []) as $i => $file) {
            $studio->photos()->create([
                'path'       => $file->store('studios', 'public'),
                'caption'    => $request->input("captions.$i"),
                'sort_order' => ++$order,
            ]);
        }
    }
}