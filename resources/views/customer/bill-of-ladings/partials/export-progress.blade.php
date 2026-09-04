<section class="export-progress" aria-label="Tracking Progress EXPORT">
    <div class="section-title">
        <p class="eyebrow">Tracking Progress EXPORT</p>
        <h2>Shipment Details</h2>
    </div>

    <article class="process-block">
        <p class="process-kicker">OUTPUT — Process 1</p>
        <h3>Document Received</h3>
        <dl class="facts-grid">
            <div>
                <dt>Exporter Name (customer)</dt>
                <dd>{{ $billOfLading->exporterDisplayName() }}</dd>
            </div>
            <div>
                <dt>Checking Booking Order</dt>
                <dd>{{ $billOfLading->booking_order_checked ? 'Sudah dicek' : 'Belum' }}</dd>
            </div>
            <div>
                <dt>No. DO</dt>
                <dd>{{ $billOfLading->do_number ?: '-' }}</dd>
            </div>
            <div>
                <dt>Shipping Line</dt>
                <dd>{{ $billOfLading->carrier_name ?: '-' }}</dd>
            </div>
            <div>
                <dt>Closing time at Depo</dt>
                <dd>{{ $billOfLading->depot_closing_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Closing time at CY (pelabuhan)</dt>
                <dd>{{ $billOfLading->cy_closing_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Vessel Name</dt>
                <dd>{{ $billOfLading->vessel_name ?: '-' }}</dd>
            </div>
            <div>
                <dt>Voyage</dt>
                <dd>{{ $billOfLading->voyage_number ?: '-' }}</dd>
            </div>
            <div>
                <dt>Size Container</dt>
                <dd>{{ $billOfLading->container_size ?: '-' }}</dd>
            </div>
            <div>
                <dt>Port of Discharging</dt>
                <dd>{{ $billOfLading->port_of_discharge ?: '-' }}</dd>
            </div>
        </dl>
    </article>

    <article class="process-block">
        <p class="process-kicker">INPUT — Process 2</p>
        <h3>Pick Up Empty Cont at Depot</h3>
        <dl class="facts-grid">
            <div>
                <dt>Pick Up Depot</dt>
                <dd>{{ $billOfLading->pickup_depot ?: '-' }}</dd>
            </div>
            <div>
                <dt>Date of Stuffing</dt>
                <dd>{{ $billOfLading->stuffing_date?->locale('id')->translatedFormat('j M Y') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Stuffing Destination</dt>
                <dd>{{ $billOfLading->stuffing_destination ?: '-' }}</dd>
            </div>
        </dl>

        @forelse ($billOfLading->containers as $container)
            <div class="container-progress-card">
                <div class="container-progress-head">
                    <a
                        href="{{ route('customer.containers.show', [$billOfLading, $container]) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ $container->container_number }}</a>
                    <span>No. Seal {{ $container->seal_number ?: '-' }}</span>
                </div>
                <dl class="facts-grid">
                    <div>
                        <dt>Driver Name</dt>
                        <dd>{{ $container->driver_name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>No. License</dt>
                        <dd>{{ $container->license_number ?: '-' }}</dd>
                    </div>
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
            </div>
        @empty
            <div class="empty-state"><strong>Belum ada kontainer tercatat</strong></div>
        @endforelse
    </article>

    <article class="process-block">
        <p class="process-kicker">FINAL — Process 3</p>
        <h3>Factory, PEB/NPE, and Gate In CY</h3>
        <dl class="facts-grid">
            <div>
                <dt>Container On The Way Factory</dt>
                <dd>{{ $billOfLading->on_the_way_factory_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Checking PEB and NPE</dt>
                <dd>{{ $billOfLading->peb_npe_checked ? 'Sudah dicek' : 'Belum' }}</dd>
            </div>
            <div>
                <dt>Process Gate In CY</dt>
                <dd>{{ $billOfLading->gate_in_cy_processed ? 'Selesai' : 'Belum' }}</dd>
            </div>
            <div class="fact-wide">
                <dt>Final Checking Details Shipment</dt>
                <dd>{{ $billOfLading->final_checking_notes ?: '-' }}</dd>
            </div>
        </dl>

        @foreach ($billOfLading->containers as $container)
            <div class="container-progress-card">
                <div class="container-progress-head">
                    <a
                        href="{{ route('customer.containers.show', [$billOfLading, $container]) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ $container->container_number }}</a>
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
                    <div>
                        <dt>Progress Stuffing at Factory</dt>
                        <dd>{{ $container->stuffingProgressLabel() }}</dd>
                    </div>
                    <div>
                        <dt>Gate in CY — Port of Loading</dt>
                        <dd>{{ $container->gate_in_pol ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Gate in CY Date</dt>
                        <dd>{{ $container->gate_in_cy_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Amount of VGM</dt>
                        <dd>{{ $container->vgm_kg ? number_format((float) $container->vgm_kg, 3).' kg' : '-' }}</dd>
                    </div>
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
            </div>
        @endforeach
    </article>
</section>
