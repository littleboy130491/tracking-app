<x-customer.layout title="{{ $billOfLading->bl_number }}">
    @php
        $currentNode = $timelineNodes->firstWhere('state', 'in_progress')
            ?? $timelineNodes->last(fn ($node) => $node['state'] === 'completed');
        $completedCount = $timelineNodes->where('state', 'completed')->count();
        $totalCount = $timelineNodes->count();
        $pol = $billOfLading->port_of_loading ?: ($billOfLading->origin ?: '-');
        $pod = $billOfLading->port_of_discharge ?: ($billOfLading->destination ?: '-');
        $cbm = $billOfLading->measurement_cbm ?: $billOfLading->volume_cbm;
        $latestNote = $billOfLading->customer_note ?: $billOfLading->note;
    @endphp

    <div class="detail-top">
        <a class="back-link" href="{{ route('customer.dashboard') }}">← Back to list</a>

        <div class="status-hero {{ $laneClass }}">
            <div class="status-hero-main">
                <p class="status-kicker">Current status</p>
                <p class="shipment-type type-{{ $billOfLading->shipment_type }}">{{ $billOfLading->shipmentTypeLabel() }}</p>
                <h1>
                    <button
                        type="button"
                        class="copyable-bl"
                        data-copy="{{ $billOfLading->bl_number }}"
                        title="Click to copy BL number"
                        aria-label="Copy BL number {{ $billOfLading->bl_number }}"
                    >
                        {{ $billOfLading->bl_number }}
                    </button>
                </h1>
                <p class="status-step">
                    {{ $currentNode['label'] ?? ($billOfLading->phase ?: $billOfLading->status) }}
                </p>
                <p class="status-route">{{ $pol }} → {{ $pod }}</p>
                @if ($billOfLading->shipment_description)
                    <p class="status-summary">{{ $billOfLading->shipment_description }}</p>
                @endif
                <div class="header-badges">
                    <span class="badge">{{ $billOfLading->status }}</span>
                    @if ($billOfLading->customsLaneLabel())
                        <span class="badge lane {{ $laneClass }}">{{ $billOfLading->customsLaneLabel() }}</span>
                    @endif
                </div>
            </div>

            <div class="status-hero-side">
                @if ($totalCount > 0)
                    <div class="status-meta">
                        <strong>Progress</strong>
                        <p>{{ $completedCount }} of {{ $totalCount }} steps done</p>
                    </div>
                @endif
                <div class="status-meta">
                    <strong>Last update</strong>
                    <p>{{ $billOfLading->updated_at->format('M j, Y H:i') }}</p>
                </div>
                @if ($billOfLading->gps_tracking_url)
                    <a
                        class="button gps-button"
                        href="{{ $billOfLading->gps_tracking_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Open GPS tracking
                    </a>
                @endif
                @if ($latestNote)
                    <div class="status-note">
                        <strong>Latest note</strong>
                        <p>{{ $latestNote }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="stack detail-stack">
        <section class="panel">
            <div class="section-heading">
                <h2>Progress</h2>
                <p class="section-help">Follow the steps from top to bottom.</p>
            </div>

            @if ($timelineNodes->isEmpty())
                <div class="empty">No progress steps yet.</div>
            @else
                <ol class="step-list {{ $laneClass }}">
                    @foreach ($timelineNodes as $index => $node)
                        <li class="step-item state-{{ $node['state'] }}">
                            <div class="step-rail" aria-hidden="true">
                                <span class="step-dot">
                                    @if ($node['state'] === 'completed')
                                        ✓
                                    @elseif ($node['state'] === 'in_progress')
                                        {{ $index + 1 }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                            </div>
                            <div class="step-body">
                                <div class="step-title-row">
                                    <span class="step-title">{{ $node['label'] }}</span>
                                    <span class="step-state">
                                        @if ($node['state'] === 'completed')
                                            Done
                                        @elseif ($node['state'] === 'in_progress')
                                            In progress
                                        @else
                                            Upcoming
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Shipment summary</h2>
            </div>

            <div class="summary-grid">
                <div>
                    <strong>Carrier</strong>
                    <p>{{ $billOfLading->carrier_name ?: '-' }}</p>
                </div>
                <div>
                    <strong>Vessel / Voyage</strong>
                    <p>
                        @if ($billOfLading->vessel_name)
                            {{ $billOfLading->vessel_name }}{{ $billOfLading->voyage_number ? ' / '.$billOfLading->voyage_number : '' }}
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div>
                    <strong>Shipped on board</strong>
                    <p>{{ $billOfLading->shipped_on_board_date?->format('M j, Y') ?: '-' }}</p>
                </div>
                <div>
                    <strong>Packages</strong>
                    <p>{{ $billOfLading->package_count ?: ($billOfLading->quantity ?: '-') }}</p>
                </div>
                <div>
                    <strong>Gross weight</strong>
                    <p>
                        @if ($billOfLading->gross_weight_kg)
                            {{ number_format((float) $billOfLading->gross_weight_kg, 2) }} kg
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div>
                    <strong>Measurement</strong>
                    <p>
                        @if ($cbm)
                            {{ number_format((float) $cbm, 2) }} CBM
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div>
                    <strong>HS code</strong>
                    <p>{{ $billOfLading->hs_code ?: '-' }}</p>
                </div>
                <div>
                    <strong>Input date</strong>
                    <p>{{ $billOfLading->input_date->format('M j, Y') }}</p>
                </div>
                <div class="full-width">
                    <strong>Goods</strong>
                    <p>{{ $billOfLading->goods_description ?: ($billOfLading->items_description ?: ($billOfLading->shipment_description ?: '-')) }}</p>
                </div>
                @if ($billOfLading->free_time_notes)
                    <div class="full-width">
                        <strong>Free time notes</strong>
                        <p>{{ $billOfLading->free_time_notes }}</p>
                    </div>
                @endif
            </div>
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Containers</h2>
            </div>

            @if ($billOfLading->containers->isEmpty())
                <div class="empty">No containers recorded.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Container</th>
                                <th>Seal</th>
                                <th>Type</th>
                                <th>Packages</th>
                                <th>Weight (kg)</th>
                                <th>CBM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($billOfLading->containers as $container)
                                <tr>
                                    <td>{{ $container->container_number }}</td>
                                    <td>{{ $container->seal_number ?: '-' }}</td>
                                    <td>{{ $container->container_type ?: '-' }}</td>
                                    <td>{{ $container->package_count ?: '-' }}</td>
                                    <td>{{ $container->gross_weight_kg ? number_format((float) $container->gross_weight_kg, 3) : '-' }}</td>
                                    <td>{{ $container->measurement_cbm ? number_format((float) $container->measurement_cbm, 3) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="section-heading">
                <h2>Update history</h2>
                <p class="section-help">Recent changes on this shipment.</p>
            </div>

            @if ($billOfLading->updates->isEmpty())
                <div class="empty">No update history yet.</div>
            @else
                <ol class="history-list">
                    @foreach ($billOfLading->updates->sortByDesc('created_at') as $update)
                        <li class="history-item">
                            <time datetime="{{ $update->created_at->toIso8601String() }}">
                                {{ $update->created_at->format('M j, Y H:i') }}
                            </time>
                            <div class="history-body">
                                <div class="timeline-meta">
                                    <span class="badge">{{ $update->status }}</span>
                                    <span class="badge phase">{{ $update->phase }}</span>
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
    </div>
</x-customer.layout>
