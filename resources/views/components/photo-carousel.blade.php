@props(['photos', 'height' => 'h-44', 'interval' => 3500])

@php
    $slides = $photos->map(fn ($p) => ['url' => $p->url, 'caption' => $p->caption])->values();
@endphp

@if($slides->isEmpty())
    <div class="{{ $height }} bg-gray-200 flex items-center justify-center text-gray-400">
        Belum ada foto
    </div>
@else
    <div
        x-data="{
            slides: @js($slides),
            i: 0,
            timer: null,
            next() { this.i = (this.i + 1) % this.slides.length },
            prev() { this.i = (this.i - 1 + this.slides.length) % this.slides.length },
            start() { if (this.slides.length > 1) this.timer = setInterval(() => this.next(), {{ $interval }}) },
            stop() { clearInterval(this.timer) },
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        class="relative {{ $height }} overflow-hidden bg-gray-900 group"
    >
        {{-- Slides --}}
        <template x-for="(s, idx) in slides" :key="idx">
            <img
                x-show="i === idx"
                x-transition.opacity.duration.500ms
                :src="s.url"
                :alt="s.caption ?? 'Foto studio'"
                loading="lazy"
                class="absolute inset-0 w-full h-full object-cover"
            >
        </template>

        {{-- Caption fasilitas --}}
        <div x-show="slides[i].caption" x-transition.opacity
             class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent
                    text-white text-xs px-3 pt-6 pb-2">
            <span x-text="slides[i].caption"></span>
        </div>

        {{-- Panah (hanya jika foto > 1) --}}
        <template x-if="slides.length > 1">
            <div>
                <button type="button" @click.prevent="prev()"
                    class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40
                           text-white opacity-0 group-hover:opacity-100 transition">‹</button>
                <button type="button" @click.prevent="next()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40
                           text-white opacity-0 group-hover:opacity-100 transition">›</button>

                {{-- Dots --}}
                <div class="absolute top-2 inset-x-0 flex justify-center gap-1.5">
                    <template x-for="(s, idx) in slides" :key="idx">
                        <button type="button" @click.prevent="i = idx"
                            :class="i === idx ? 'bg-white' : 'bg-white/50'"
                            class="w-2 h-2 rounded-full"></button>
                    </template>
                </div>
            </div>
        </template>
    </div>
@endif