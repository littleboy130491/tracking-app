<x-customer.layout :title="($customer->company_name ?? $customer->name).' Dashboard'">
    <div class="header">
        <div>
            <h1>{{ $customer->company_name ?? $customer->name }}</h1>
            <p class="muted">Signed in with {{ $customer->email }}</p>
            <p class="intro">Review your bill of lading records, filter by status or date, and open any row for full shipment details.</p>
        </div>
    </div>

    <div class="stack">
        <section class="panel bl-search-panel">
            <h2>Search BL Number</h2>
            <p class="help">Enter part or all of a BL number to find a specific record quickly.</p>

            <form class="bl-search" method="GET" action="{{ route('customer.dashboard') }}">
                @foreach (['status', 'phase', 'month', 'year', 'per_page'] as $filterKey)
                    @if ($filters[$filterKey] !== '' && ! ($filterKey === 'per_page' && (int) $filters['per_page'] === 10))
                        <input type="hidden" name="{{ $filterKey }}" value="{{ $filters[$filterKey] }}">
                    @endif
                @endforeach

                <input
                    id="q"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Example: BL-ACME-1001"
                    aria-label="Search BL number"
                >
                <button type="submit">Search</button>
                @if ($hasBlSearch)
                    <a
                        class="button secondary"
                        href="{{ route('customer.dashboard', array_filter([
                            'status' => $filters['status'] ?: null,
                            'phase' => $filters['phase'] ?: null,
                            'month' => $filters['month'] ?: null,
                            'year' => $filters['year'] ?: null,
                            'per_page' => (int) $filters['per_page'] === 10 ? null : $filters['per_page'],
                        ])) }}"
                    >
                        Clear search
                    </a>
                @endif
            </form>
        </section>

        <section class="panel">
            <h2>Your BL Records</h2>

            <form class="filters" method="GET" action="{{ route('customer.dashboard') }}">
                @if ($filters['q'] !== '')
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                @endif
                @if ((int) $filters['per_page'] !== 10)
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                @endif

                <div class="filters-grid">
                    <div class="field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All statuses</option>
                            @foreach (\App\Models\BillOfLading::STATUSES as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="phase">Phase</label>
                        <select id="phase" name="phase">
                            <option value="">All phases</option>
                            @foreach (\App\Models\BillOfLading::PHASES as $phase)
                                <option value="{{ $phase }}" @selected($filters['phase'] === $phase)>{{ $phase }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="month">Month</label>
                        <select id="month" name="month">
                            <option value="">All months</option>
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}" @selected($filters['month'] === (string) $month)>
                                    {{ \Illuminate\Support\Carbon::create()->month($month)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="year">Year</label>
                        <select id="year" name="year">
                            <option value="">All years</option>
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected($filters['year'] === (string) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="filters-actions">
                    <button type="submit">Apply filters</button>
                    @if ($hasListingFilters)
                        <a
                            class="button secondary"
                            href="{{ route('customer.dashboard', array_filter([
                                'q' => $filters['q'] ?: null,
                                'per_page' => (int) $filters['per_page'] === 10 ? null : $filters['per_page'],
                            ])) }}"
                        >
                            Clear filters
                        </a>
                    @endif
                </div>
            </form>

            @if ($billOfLadings->isEmpty())
                <div class="empty">
                    No BL records found.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>BL Number</th>
                            <th>Status</th>
                            <th>Phase</th>
                            <th>Destination</th>
                            <th>Input Date</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($billOfLadings as $billOfLading)
                            <tr
                                class="clickable-row"
                                tabindex="0"
                                role="link"
                                data-href="{{ route('customer.bill-of-ladings.show', $billOfLading) }}"
                            >
                                <td>{{ $billOfLading->bl_number }}</td>
                                <td><span class="badge">{{ $billOfLading->status }}</span></td>
                                <td><span class="badge phase">{{ $billOfLading->phase }}</span></td>
                                <td>{{ $billOfLading->destination ?: 'Not provided' }}</td>
                                <td>{{ $billOfLading->input_date->format('M j, Y') }}</td>
                                <td>{{ $billOfLading->updated_at->format('M j, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <x-customer.pagination-controls
                    :paginator="$billOfLadings"
                    :filters="$filters"
                    :per-page-options="$perPageOptions"
                />
            @endif
        </section>
    </div>
</x-customer.layout>
