@php
    $checked = fn (?bool $value): string => $value ? 'Sudah' : 'Belum';
@endphp

<section class="export-progress" aria-label="Tracking Progress IMPORT">
    <div class="section-title">
        <p class="eyebrow">Tracking Progress IMPORT</p>
        <h2>Shipment Details</h2>
    </div>

    <article class="process-block">
        <p class="process-kicker">OUTPUT — Process 1</p>
        <h3>Document Received</h3>
        <dl class="facts-grid">
            <div>
                <dt>Importir Name (customer)</dt>
                <dd>{{ $billOfLading->importerDisplayName() }}</dd>
            </div>
            <div>
                <dt>Checking Document</dt>
                <dd>{{ $checked($billOfLading->document_checked) }}</dd>
            </div>
            <div>
                <dt>No. BL</dt>
                <dd>{{ $billOfLading->bl_number }}</dd>
            </div>
            @if ($billOfLading->shipping_schedule)
                <div class="fact-wide">
                    <dt>Container Shipping Schedule</dt>
                    <dd>{{ $billOfLading->shipping_schedule }}</dd>
                </div>
            @endif
        </dl>
    </article>

    <article class="process-block">
        <p class="process-kicker">INPUT — Process 2</p>
        <h3>Draft PIB</h3>
        <dl class="facts-grid">
            <div>
                <dt>Shipping Line</dt>
                <dd>{{ $billOfLading->carrier_name ?: '-' }}</dd>
            </div>
            <div>
                <dt>Checking draft PIB to Importir</dt>
                <dd>{{ $checked($billOfLading->draft_pib_checked) }}</dd>
            </div>
            <div>
                <dt>Vessel Name</dt>
                <dd>{{ $billOfLading->vessel_name ?: '-' }}</dd>
            </div>
            <div>
                <dt>Konfirmasi customer</dt>
                <dd>{{ $checked($billOfLading->customer_confirmed) }}</dd>
            </div>
            <div>
                <dt>Final Sending PIB to Custom</dt>
                <dd>{{ $checked($billOfLading->pib_sent_to_customs) }}</dd>
            </div>
            <div>
                <dt>Voyage</dt>
                <dd>{{ $billOfLading->voyage_number ?: '-' }}</dd>
            </div>
            <div>
                <dt>Status billing</dt>
                <dd>{{ $billOfLading->billing_issued ? 'Sudah terbit' : 'Belum terbit' }}</dd>
            </div>
            <div>
                <dt>Process payment THC</dt>
                <dd>{{ $checked($billOfLading->thc_paid) }}</dd>
            </div>
            <div>
                <dt>Port of Loading</dt>
                <dd>{{ $billOfLading->port_of_loading ?: '-' }}</dd>
            </div>
            <div>
                <dt>Waiting release DO</dt>
                <dd>{{ $checked($billOfLading->waiting_do_release) }}</dd>
            </div>
            <div>
                <dt>Departure Date</dt>
                <dd>{{ $billOfLading->departure_date?->locale('id')->translatedFormat('j M Y') ?: '-' }}</dd>
            </div>
            <div>
                <dt>DO Release</dt>
                <dd>
                    {{ $checked($billOfLading->do_released) }}
                    @if ($billOfLading->do_release_date)
                        · {{ $billOfLading->do_release_date->locale('id')->translatedFormat('j M Y') }}
                    @endif
                </dd>
            </div>
            <div>
                <dt>Port of Discharging</dt>
                <dd>{{ $billOfLading->port_of_discharge ?: '-' }}</dd>
            </div>
            <div>
                <dt>Payment Billing</dt>
                <dd>{{ $checked($billOfLading->billing_paid) }}</dd>
            </div>
            <div>
                <dt>Arrival Time / ETA</dt>
                <dd>{{ $billOfLading->eta_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Response Billing</dt>
                <dd>{{ $billOfLading->customsResponseLabel() }}</dd>
            </div>
            <div>
                <dt>Nomor Aju</dt>
                <dd>{{ $billOfLading->aju_number ?: '-' }}</dd>
            </div>
            <div>
                <dt>Description of Goods</dt>
                <dd>{{ $billOfLading->goods_description ?: '-' }}</dd>
            </div>
            <div>
                <dt>HS Code</dt>
                <dd>{{ $billOfLading->hs_code ?: '-' }}</dd>
            </div>
            <div>
                <dt>Packages</dt>
                <dd>{{ $billOfLading->package_count ?: '-' }}</dd>
            </div>
            <div>
                <dt>Terminal Name</dt>
                <dd>{{ $billOfLading->terminal_name ?: '-' }}</dd>
            </div>
            <div>
                <dt>Date of Loading</dt>
                <dd>{{ $billOfLading->loading_date?->locale('id')->translatedFormat('j M Y') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Loading Destination</dt>
                <dd>{{ $billOfLading->loading_destination ?: '-' }}</dd>
            </div>
            @if ($billOfLading->shipping_schedule)
                <div class="fact-wide">
                    <dt>Container Shipping Schedule</dt>
                    <dd>{{ $billOfLading->shipping_schedule }}</dd>
                </div>
            @endif
        </dl>

        @if ($billOfLading->isSpjmResponse())
            <div class="spjm-extra">
                <p class="process-kicker">Tambahan step SPJM</p>
                <dl class="facts-grid">
                    <div>
                        <dt>Upload All Document</dt>
                        <dd>
                            @forelse ($billOfLading->importDocumentUrls() as $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">Dokumen</a>@if (! $loop->last), @endif
                            @empty
                                Belum
                            @endforelse
                        </dd>
                    </div>
                    <div>
                        <dt>Waiting Process Bahandle</dt>
                        <dd>{{ $checked($billOfLading->waiting_bahandle) }}</dd>
                    </div>
                    <div>
                        <dt>Payment Bahandle</dt>
                        <dd>{{ $checked($billOfLading->bahandle_paid) }}</dd>
                    </div>
                    <div>
                        <dt>Container Inspection</dt>
                        <dd>{{ $checked($billOfLading->container_inspected) }}</dd>
                    </div>
                    <div>
                        <dt>Waiting SPJM to SPPB</dt>
                        <dd>{{ $checked($billOfLading->waiting_spjm_to_sppb) }}</dd>
                    </div>
                </dl>
            </div>
        @endif

        @forelse ($billOfLading->containers as $container)
            <div class="container-progress-card">
                <div class="container-progress-head">
                    <a
                        href="{{ route('customer.containers.show', [$billOfLading, $container]) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ $container->container_number }}</a>
                    <span>Size {{ $container->container_type ?: '-' }}</span>
                </div>
                <dl class="facts-grid">
                    <div>
                        <dt>Gross Weight</dt>
                        <dd>{{ $container->gross_weight_kg ? number_format((float) $container->gross_weight_kg, 3).' kg' : '-' }}</dd>
                    </div>
                    <div>
                        <dt>CBM / Measurement</dt>
                        <dd>{{ $container->measurement_cbm ? number_format((float) $container->measurement_cbm, 3) : '-' }}</dd>
                    </div>
                </dl>
            </div>
        @empty
            <div class="empty-state"><strong>Belum ada kontainer tercatat</strong></div>
        @endforelse
    </article>

    <article class="process-block">
        <p class="process-kicker">FINAL — Process 3</p>
        <h3>Gate Out from Inbound Terminal for Delivery to Consignee</h3>
        <dl class="facts-grid">
            <div>
                <dt>Container On The Way Factory</dt>
                <dd>{{ $billOfLading->on_the_way_factory_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Container Arrived in Factory</dt>
                <dd>{{ $billOfLading->arrived_at_factory_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Empty Container Returned</dt>
                <dd>{{ $checked($billOfLading->empty_container_returned) }}</dd>
            </div>
            @if ($billOfLading->shipping_schedule)
                <div class="fact-wide">
                    <dt>Container Shipping Schedule</dt>
                    <dd>{{ $billOfLading->shipping_schedule }}</dd>
                </div>
            @endif
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
                        <dt>Gate out CY</dt>
                        <dd>{{ $container->gate_out_cy_at?->locale('id')->translatedFormat('j M Y H:i') ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Driver Name</dt>
                        <dd>{{ $container->driver_name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>No. License</dt>
                        <dd>{{ $container->license_number ?: '-' }}</dd>
                    </div>
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
                        <dt>Loading in Factory</dt>
                        <dd>{{ $container->factoryLoadingProgressLabel() }}</dd>
                    </div>
                    <div>
                        <dt>Return Empty Cont in Depot</dt>
                        <dd>
                            {{ $container->empty_return_depot ?: '-' }}
                            @if ($container->empty_return_at)
                                · {{ $container->empty_return_at->locale('id')->translatedFormat('j M Y') }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        @endforeach
    </article>
</section>
