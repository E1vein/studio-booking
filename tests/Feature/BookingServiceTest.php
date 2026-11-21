<?php

namespace Tests\Feature;

use App\Models\Studio;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;
    private Studio $studio;
    private User $user;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BookingService::class);
        $this->user    = User::factory()->create();
        $this->date    = now()->addDay()->toDateString();

        $this->studio = Studio::create([
            'name' => 'Studio Test', 'hourly_rate' => 150000, 'capacity' => 4,
        ]);

        foreach (range(0, 6) as $day) {
            $this->studio->schedules()->create([
                'day_of_week' => $day, 'open_time' => '09:00', 'close_time' => '21:00',
            ]);
        }
    }

    public function test_booking_berhasil_dan_harga_dihitung_backend(): void
    {
        $booking = $this->service->create($this->user->id, $this->studio, $this->date, '14:00', '16:00');

        $this->assertEquals(300000, $booking->total_price);
        $this->assertEquals('pending', $booking->status);
    }

    public function test_tidak_bisa_double_booking_slot_sama(): void
    {
        $this->service->create($this->user->id, $this->studio, $this->date, '14:00', '16:00');

        $this->expectException(ValidationException::class);
        $this->service->create($this->user->id, $this->studio, $this->date, '14:00', '16:00');
    }

    public function test_tidak_bisa_booking_tumpang_tindih_sebagian(): void
    {
        $this->service->create($this->user->id, $this->studio, $this->date, '14:00', '16:00');

        $this->expectException(ValidationException::class);
        $this->service->create($this->user->id, $this->studio, $this->date, '15:00', '17:00');
    }

    public function test_booking_menempel_tidak_dianggap_bentrok(): void
    {
        $this->service->create($this->user->id, $this->studio, $this->date, '14:00', '16:00');
        $booking = $this->service->create($this->user->id, $this->studio, $this->date, '16:00', '18:00');

        $this->assertNotNull($booking->id);
    }

    public function test_booking_cancelled_tidak_memblokir_slot(): void
    {
        $b = $this->service->create($this->user->id, $this->studio, $this->date, '14:00', '16:00');
        $b->update(['status' => 'cancelled']);

        $baru = $this->service->create($this->user->id, $this->studio, $this->date, '14:00', '16:00');
        $this->assertNotNull($baru->id);
    }

    public function test_tolak_di_luar_jam_operasional(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->create($this->user->id, $this->studio, $this->date, '20:00', '22:00');
    }

    public function test_tolak_jam_selesai_sebelum_jam_mulai(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->create($this->user->id, $this->studio, $this->date, '16:00', '14:00');
    }

    public function test_tolak_booking_di_masa_lalu(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->create($this->user->id, $this->studio, now()->subDay()->toDateString(), '10:00', '12:00');
    }
}