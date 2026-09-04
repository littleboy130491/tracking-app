<x-customer.layout title="{{ $billOfLading->bl_number }}">
    @php
        $pod = $billOfLading->port_of_discharge ?: '-';
        $cbm = $billOfLading->measurement_cbm;
        $latestNote = $billOfLading->customer_note;
        $hasRelatedParties = filled($billOfLading->shipper_name)
            || filled($billOfLading->consignee_name)
            || filled($billOfLading->notify_party_name);
        $hasDestinationDetails = filled($billOfLading->port_of_discharge)
            || filled($billOfLading->place_of_delivery)
            || filled($billOfLading->consignee_address)
            || filled($billOfLading->destination_agent_name);
    @endphp

    <a class="back-link" href="{{ route('customer.dashboard') }}">&larr; Pengiriman</a>

    <section
        class="bl-summary {{ $laneClass }}"
        data-shipment-type="{{ $billOfLading->shipment_type }}"
        aria-label="Ringkasan bill of lading"
    >
        <div class="section-title">
            <p class="eyebrow">Bill of lading</p>
            <h1>Ringkasan</h1>
        </div>

        <dl class="summary-facts">
            <div>
                <dt>Nomor BL</dt>
                <dd>{{ $billOfLading->bl_number }}</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>
                    <span class="type-tag type-{{ $billOfLading->shipment_type }}">{{ $billOfLading->shipmentTypeLabel() }}</span>
                </dd>
            </div>
            <div>
                <dt>Tracking URL</dt>
                <dd>
                    @if ($billOfLading->gps_tracking_url)
                        <a
                            href="{{ $billOfLading->gps_tracking_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Buka tautan
                        </a>
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div>
                <dt>Nomor Aju</dt>
                <dd>{{ $billOfLading->aju_number ?: '-' }}</dd>
            </div>
            <div>
                <dt>Jenis Kontainer</dt>
                <dd>{{ $billOfLading->shippingMethodLabel() }}</dd>
            </div>
        </dl>
    </section>

    @if ($latestNote)
        <aside class="latest-update">
            <span>Pembaruan terbaru</span>
            <p>{{ $latestNote }}</p>
            <time datetime="{{ $billOfLading->updated_at->toIso8601String() }}">{{ $billOfLading->updated_at->locale('id')->translatedFormat('j M Y H:i') }}</time>
        </aside>
    @endif

    <div class="tracking-layout">
        <main class="tracking-main">
            @foreach ($timelineTracks as $track)
                <section class="track-section">
                    <div class="section-title">
                        <p class="eyebrow">Pelacakan</p>
                        <h2>{{ $track['title'] }}</h2>
                    </div>

                    <ol class="step-list {{ $laneClass }}">
                        @foreach ($track['nodes'] as $index => $node)
                            <li class="step-item state-{{ $node['state'] }}">
                                <div class="step-rail" aria-hidden="true">
                                    <span class="step-dot">
                                        @if ($node['state'] === 'completed')
                                            &#10003;
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </span>
                                </div>
                                <div class="step-body">
                                    <strong>{{ $node['label'] }}</strong>
                                    <span>
                                        @if ($node['state'] === 'completed')
                                            Selesai
                                        @elseif ($node['state'] === 'in_progress')
                                            Sedang diproses
                                        @else
                                            Mendatang
                                        @endif
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endforeach

            <section class="detail-section">
                <div class="section-title">
                    <p class="eyebrow">Kargo</p>
                    <h2>Detail pengiriman</h2>
                </div>

                <dl class="facts-grid">
                    <div><dt>Perusahaan</dt><dd>{{ $billOfLading->company?->name ?: '-' }}</dd></div>
                    <div><dt>Carrier</dt><dd>{{ $billOfLading->carrier_name ?: '-' }}</dd></div>
                    <div>
                        <dt>Kapal / voyage</dt>
                        <dd>{{ $billOfLading->vessel_name ?: '-' }}{{ $billOfLading->voyage_number ? ' / '.$billOfLading->voyage_number : '' }}</dd>
                    </div>
                    <div><dt>Shipped on board</dt><dd>{{ $billOfLading->shipped_on_board_date?->locale('id')->translatedFormat('j M Y') ?: '-' }}</dd></div>
                    <div><dt>Jumlah kemasan</dt><dd>{{ $billOfLading->package_count ?: '-' }}</dd></div>
                    <div><dt>Berat kotor</dt><dd>{{ $billOfLading->gross_weight_kg ? number_format((float) $billOfLading->gross_weight_kg, 2).' kg' : '-' }}</dd></div>
                    <div><dt>Volume</dt><dd>{{ $cbm ? number_format((float) $cbm, 2).' CBM' : '-' }}</dd></div>
                    <div><dt>Kode HS</dt><dd>{{ $billOfLading->hs_code ?: '-' }}</dd></div>
                    <div><dt>Tanggal input</dt><dd>{{ $billOfLading->input_date->locale('id')->translatedFormat('j M Y') }}</dd></div>
                    <div class="fact-wide">
                        <dt>Barang</dt>
                        <dd>{{ $billOfLading->goods_description ?: $billOfLading->shipment_description }}</dd>
                    </div>
                    @if ($billOfLading->free_time_notes)
                        <div class="fact-wide"><dt>Free time</dt><dd>{{ $billOfLading->free_time_notes }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($hasRelatedParties)
                <section class="detail-section">
                    <div class="section-title">
                        <p class="eyebrow">Informasi pengiriman</p>
                        <h2>Pihak terkait</h2>
                    </div>

                    <dl class="facts-grid">
                        <div><dt>Shipper</dt><dd>{{ $billOfLading->shipper_name ?: '-' }}</dd></div>
                        <div><dt>Consignee</dt><dd>{{ $billOfLading->consignee_name ?: '-' }}</dd></div>
                        <div><dt>Notify party</dt><dd>{{ $billOfLading->notify_party_name ?: '-' }}</dd></div>
                    </dl>
                </section>
            @endif

            @if ($hasDestinationDetails)
                <section class="detail-section">
                    <div class="section-title">
                        <p class="eyebrow">Pengantaran</p>
                        <h2>Tujuan pengiriman</h2>
                    </div>

                    <dl class="facts-grid">
                        <div><dt>Port of discharge</dt><dd>{{ $pod }}</dd></div>
                        <div><dt>Place of delivery</dt><dd>{{ $billOfLading->place_of_delivery ?: '-' }}</dd></div>
                        <div><dt>Destination agent</dt><dd>{{ $billOfLading->destination_agent_name ?: '-' }}</dd></div>
                        <div class="fact-wide">
                            <dt>Alamat consignee</dt>
                            <dd>{!! $billOfLading->consignee_address ? nl2br(e($billOfLading->consignee_address)) : '-' !!}</dd>
                        </div>
                    </dl>
                </section>
            @endif

            <section class="detail-section">
                <div class="section-title section-title-row">
                    <div>
                        <p class="eyebrow">Peralatan</p>
                        <h2>Kontainer</h2>
                    </div>
                    <span>{{ $billOfLading->containers->count() }} total</span>
                </div>

                @if ($billOfLading->containers->isEmpty())
                    <div class="empty-state"><strong>Belum ada kontainer tercatat</strong></div>
                @else
                    <div class="table-wrap">
                        <table class="container-table">
                            <thead>
                                <tr>
                                    <th>Kontainer</th>
                                    <th>Segel</th>
                                    <th>Jenis</th>
                                    <th>Kemasan</th>
                                    <th>Berat</th>
                                    <th>CBM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($billOfLading->containers as $container)
                                    <tr>
                                        <td data-label="Kontainer"><strong>{{ $container->container_number }}</strong></td>
                                        <td data-label="Segel">{{ $container->seal_number ?: '-' }}</td>
                                        <td data-label="Jenis">{{ $container->container_type ?: '-' }}</td>
                                        <td data-label="Kemasan">{{ $container->package_count ?: '-' }}</td>
                                        <td data-label="Berat">{{ $container->gross_weight_kg ? number_format((float) $container->gross_weight_kg, 3).' kg' : '-' }}</td>
                                        <td data-label="CBM">{{ $container->measurement_cbm ? number_format((float) $container->measurement_cbm, 3) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </main>

        <aside class="history-section">
            <div class="section-title">
                <p class="eyebrow">Aktivitas</p>
                <h2>Pembaruan</h2>
            </div>

            @if ($billOfLading->updates->isEmpty())
                <div class="empty-state"><strong>Belum ada pembaruan</strong></div>
            @else
                <ol class="history-list">
                    @foreach ($billOfLading->updates->sortByDesc('created_at') as $update)
                        <li class="history-item">
                            <time datetime="{{ $update->created_at->toIso8601String() }}">{{ $update->created_at->locale('id')->translatedFormat('j M Y H:i') }}</time>
                            <strong>{{ $update->phase }}</strong>
                            @if ($update->note)
                                <p>{{ $update->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </aside>
    </div>
</x-customer.layout>
