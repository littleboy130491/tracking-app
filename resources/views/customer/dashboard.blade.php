<x-customer.layout :title="($customer->company_name ?? $customer->name).' Shipments'">
    <header class="page-heading">
        <div>
            <p class="eyebrow">Customer portal</p>
            <h1>{{ $customer->company_name ?? $customer->name }}</h1>
            <p class="account-email">{{ $customer->email }}</p>
        </div>
    </header>

    <nav class="status-overview" aria-label="Shipment status summary">
        <a class="status-stat {{ $filters['status'] === '' ? 'is-active' : '' }}" href="{{ route('customer.dashboard') }}">
            <span>All shipments</span>
            <strong>{{ number_format($totalCount) }}</strong>
        </a>
        @foreach ([
            \App\Models\BillOfLading::STATUS_IN_PROGRESS => 'In progress',
            \App\Models\BillOfLading::STATUS_ON_HOLD => 'On hold',
            \App\Models\BillOfLading::STATUS_COMPLETED => 'Completed',
        ] as $status => $label)
            <a
                class="status-stat {{ $filters['status'] === $status ? 'is-active' : '' }}"
                href="{{ route('customer.dashboard', ['status' => $status]) }}"
            >
                <span>{{ $label }}</span>
                <strong>{{ number_format((int) ($statusCounts[$status] ?? 0)) }}</strong>
            </a>
        @endforeach
    </nav>

    <section class="filter-bar" aria-label="Shipment filters">
        <form class="shipment-filters" method="GET" action="{{ route('customer.dashboard') }}">
            <div class="search-field">
                <label for="q">BL or container</label>
                <input
                    id="q"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Search number"
                >
            </div>

            <div class="compact-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\BillOfLading::STATUSES as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div class="compact-field">
                <label for="shipment_type">Type</label>
                <select id="shipment_type" name="shipment_type">
                    <option value="">Import & export</option>
                    @foreach (config('bl_workflows.shipment_types') as $type => $label)
                        <option value="{{ $type }}" @selected($filters['shipment_type'] === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="compact-field year-field">
                <label for="year">Year</label>
                <select id="year" name="year">
                    <option value="">All years</option>
                    @foreach ($availableYears as $year)
                        <option value="{{ $year }}" @selected($filters['year'] === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            @if ((int) $filters['per_page'] !== 10)
                <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
            @endif

            <button type="submit">Apply</button>

            @if ($hasBlSearch || $hasListingFilters)
                <a class="text-action" href="{{ route('customer.dashboard') }}">Clear</a>
            @endif
        </form>
    </section>

    <section class="records-section">
        <div class="records-heading">
            <div>
                <p class="eyebrow">Tracking records</p>
                <h2>Shipments</h2>
            </div>
            <span>{{ number_format($billOfLadings->total()) }} results</span>
        </div>

        @if ($billOfLadings->isEmpty())
            <div class="empty-state">
                <strong>No shipments found</strong>
                <a href="{{ route('customer.dashboard') }}">Reset filters</a>
            </div>
        @else
            <div class="shipment-list">
                @foreach ($billOfLadings as $billOfLading)
                    @php
                        $milestone = $billOfLading->currentMilestone();
                        $milestoneLabel = $milestone?->displayLabel(true) ?: $billOfLading->phase;
                        $routeStart = $billOfLading->port_of_loading ?: ($billOfLading->origin ?: '-');
                        $routeEnd = $billOfLading->port_of_discharge ?: ($billOfLading->destination ?: '-');
                        $statusClass = \Illuminate\Support\Str::slug($billOfLading->status);
                    @endphp
                    <article
                        class="shipment-card clickable-row"
                        tabindex="0"
                        role="link"
                        data-href="{{ route('customer.bill-of-ladings.show', $billOfLading) }}"
                    >
                        <div class="shipment-identity">
                            <div class="shipment-tags">
                                <span class="type-tag type-{{ $billOfLading->shipment_type }}">{{ $billOfLading->shipmentTypeLabel() }}</span>
                                <span class="status-tag status-{{ $statusClass }}">{{ $billOfLading->status }}</span>
                            </div>
                            <h3>{{ $billOfLading->bl_number }}</h3>
                            <p>{{ $billOfLading->carrier_name ?: 'Carrier not specified' }}</p>
                        </div>

                        <div class="shipment-route">
                            <span>{{ $routeStart }}</span>
                            <b aria-hidden="true">&rarr;</b>
                            <span>{{ $routeEnd }}</span>
                        </div>

                        <div class="shipment-progress">
                            <span>Current step</span>
                            <strong>{{ $milestoneLabel }}</strong>
                        </div>

                        <div class="shipment-updated">
                            <span>Updated</span>
                            <strong>{{ $billOfLading->updated_at->format('M j, Y') }}</strong>
                        </div>

                        <a class="record-link" href="{{ route('customer.bill-of-ladings.show', $billOfLading) }}">View tracking</a>
                    </article>
                @endforeach
            </div>

            <x-customer.pagination-controls
                :paginator="$billOfLadings"
                :filters="$filters"
                :per-page-options="$perPageOptions"
            />
        @endif
    </section>
</x-customer.layout>
