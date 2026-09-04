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

            <x-customer.logs :logs="$container->logs" />
        </main>
    </div>
</x-customer.layout>
