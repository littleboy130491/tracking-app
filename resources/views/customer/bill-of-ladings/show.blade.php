<x-customer.layout title="{{ $billOfLading->bl_number }}">
    <div class="header">
        <div>
            <h1>{{ $billOfLading->bl_number }}</h1>
            <p class="muted">{{ $billOfLading->shipment_description }}</p>
        </div>
        <a class="button secondary" href="{{ route('customer.dashboard') }}">Back</a>
    </div>

    <div class="stack">
        <section class="panel">
            <h2>Shipment Details</h2>
            <div class="grid">
                <div>
                    <strong>Input Date</strong>
                    <p>{{ $billOfLading->input_date->format('M j, Y') }}</p>
                </div>
                <div>
                    <strong>Origin</strong>
                    <p>{{ $billOfLading->origin ?: 'Not provided' }}</p>
                </div>
                <div>
                    <strong>Destination</strong>
                    <p>{{ $billOfLading->destination ?: 'Not provided' }}</p>
                </div>
                <div>
                    <strong>Quantity</strong>
                    <p>{{ $billOfLading->quantity ?: 'Not provided' }}</p>
                </div>
                <div>
                    <strong>Gross Weight</strong>
                    <p>
                        @if ($billOfLading->gross_weight_kg)
                            {{ number_format((float) $billOfLading->gross_weight_kg, 2) }} kg
                        @else
                            <span class="muted">Not provided</span>
                        @endif
                    </p>
                </div>
                <div>
                    <strong>Volume</strong>
                    <p>
                        @if ($billOfLading->volume_cbm)
                            {{ number_format((float) $billOfLading->volume_cbm, 2) }} CBM
                        @else
                            <span class="muted">Not provided</span>
                        @endif
                    </p>
                </div>
                <div class="full-width">
                    <strong>Items Information</strong>
                    <p>{{ $billOfLading->items_description ?: 'Not provided' }}</p>
                </div>
            </div>
        </section>

        <div class="tracking-row">
            <section class="panel">
                <h2>Update History</h2>

                @if ($billOfLading->updates->isEmpty())
                    <div class="empty">No update history yet.</div>
                @else
                    <ol class="timeline">
                        @foreach ($billOfLading->updates as $update)
                            <li class="timeline-item">
                                <div class="timeline-marker" aria-hidden="true"></div>
                                <div class="timeline-content">
                                    <time datetime="{{ $update->created_at->toIso8601String() }}">
                                        {{ $update->created_at->format('M j, Y H:i') }}
                                    </time>
                                    <div class="timeline-meta">
                                        <span class="badge">{{ $update->status }}</span>
                                        <span class="badge phase">{{ $update->phase }}</span>
                                        <span class="timeline-by">{{ $update->user?->name ?? 'System' }}</span>
                                    </div>
                                    @if ($update->note)
                                        <p class="timeline-note">{{ $update->note }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            <section class="panel">
                <h2>Current Tracking</h2>
                <div class="detail-list">
                    <div>
                        <strong>Status</strong>
                        <p><span class="badge">{{ $billOfLading->status }}</span></p>
                    </div>
                    <div>
                        <strong>Phase</strong>
                        <p><span class="badge phase">{{ $billOfLading->phase }}</span></p>
                    </div>
                    <div>
                        <strong>Last Update</strong>
                        <p>{{ $billOfLading->updated_at->format('M j, Y H:i') }}</p>
                    </div>
                    <div>
                        <strong>GPS Tracking URL</strong>
                        <p>
                            @if ($billOfLading->gps_tracking_url)
                                <a href="{{ $billOfLading->gps_tracking_url }}" target="_blank" rel="noopener noreferrer">Open tracking link</a>
                            @else
                                <span class="muted">Not provided</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <strong>Current Note</strong>
                        <p>{{ $billOfLading->note ?: 'No current note' }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-customer.layout>
