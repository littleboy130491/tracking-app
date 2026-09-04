<x-customer.layout :title="$container->container_number">
    <a class="back-link" href="{{ route('customer.bill-of-ladings.show', $billOfLading) }}">&larr; {{ $billOfLading->bl_number }}</a>

    <section class="bl-summary" aria-label="Ringkasan kontainer">
        <div class="section-title">
            <p class="eyebrow">Kontainer</p>
            <h1>{{ $container->container_number }}</h1>
        </div>

        <dl class="summary-facts">
            <div>
                <dt>Nomor kontainer</dt>
                <dd>{{ $container->container_number }}</dd>
            </div>
            <div>
                <dt>Nomor BL</dt>
                <dd>
                    <a href="{{ route('customer.bill-of-ladings.show', $billOfLading) }}">{{ $billOfLading->bl_number }}</a>
                </dd>
            </div>
            <div>
                <dt>Perusahaan</dt>
                <dd>{{ $billOfLading->company?->name ?: '-' }}</dd>
            </div>
            <div>
                <dt>Segel</dt>
                <dd>{{ $container->seal_number ?: '-' }}</dd>
            </div>
            <div>
                <dt>Jenis</dt>
                <dd>{{ $container->container_type ?: '-' }}</dd>
            </div>
        </dl>
    </section>

    <div class="tracking-layout">
        <main class="tracking-main">
            <section class="detail-section">
                <div class="section-title">
                    <p class="eyebrow">Kargo</p>
                    <h2>Detail kontainer</h2>
                </div>

                <dl class="facts-grid">
                    <div><dt>Kemasan</dt><dd>{{ $container->package_count ?: '-' }}</dd></div>
                    <div><dt>Berat</dt><dd>{{ $container->gross_weight_kg ? number_format((float) $container->gross_weight_kg, 3).' kg' : '-' }}</dd></div>
                    <div><dt>CBM</dt><dd>{{ $container->measurement_cbm ? number_format((float) $container->measurement_cbm, 3) : '-' }}</dd></div>
                </dl>
            </section>

            @if ($billOfLading->isExport())
                <section class="detail-section">
                    <div class="section-title">
                        <p class="eyebrow">INPUT — Process 2</p>
                        <h2>Dokumentasi kontainer</h2>
                    </div>
                    <dl class="facts-grid">
                        <div><dt>Driver Name</dt><dd>{{ $container->driver_name ?: '-' }}</dd></div>
                        <div><dt>No. License</dt><dd>{{ $container->license_number ?: '-' }}</dd></div>
                    </dl>
                    <div class="photo-grid">
                        @foreach ($container->documentationPhotos() as $label => $url)
                            <figure>
                                <figcaption>{{ $label }}</figcaption>
                                @if ($url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ $url }}" alt="{{ $label }} {{ $container->container_number }}">
                                    </a>
                                @else
                                    <span class="photo-empty">Belum ada foto</span>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </section>

                <section class="detail-section">
                    <div class="section-title">
                        <p class="eyebrow">FINAL — Process 3</p>
                        <h2>Stuffing, gate in, VGM</h2>
                    </div>
                    <dl class="facts-grid">
                        <div>
                            <dt>Tracking Position Driver</dt>
                            <dd>
                                @if ($container->driver_tracking_url)
                                    <a href="{{ $container->driver_tracking_url }}" target="_blank" rel="noopener noreferrer">Buka tautan</a>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div><dt>Progress Stuffing at Factory</dt><dd>{{ $container->stuffingProgressLabel() }}</dd></div>
                        <div><dt>Gate in CY — Port of Loading</dt><dd>{{ $container->gate_in_pol ?: '-' }}</dd></div>
                        <div><dt>Gate in CY Date</dt><dd>{{ $container->gate_in_cy_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd></div>
                        <div><dt>Amount of VGM</dt><dd>{{ $container->vgm_kg ? number_format((float) $container->vgm_kg, 3).' kg' : '-' }}</dd></div>
                        <div>
                            <dt>Final Checking</dt>
                            <dd>
                                {{ $container->final_checked ? 'Sudah dicek' : 'Belum' }}
                                @if ($container->final_checked_at)
                                    · {{ $container->final_checked_at->locale('id')->translatedFormat('j M Y') }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>
            @endif

            <x-customer.logs :logs="$container->logs" />
        </main>
    </div>
</x-customer.layout>
