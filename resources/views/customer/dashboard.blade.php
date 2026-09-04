<x-customer.layout title="Pengiriman">
    <header class="page-heading">
        <div>
            <p class="eyebrow">Portal pelanggan</p>
            <h1>Pengiriman</h1>
            <p class="account-email">{{ $customer->email }}</p>
        </div>
    </header>

    <nav class="status-overview" aria-label="Ringkasan status pengiriman">
        <a class="status-stat {{ $filters['status'] === '' ? 'is-active' : '' }}" href="{{ route('customer.dashboard') }}">
            <span>Semua pengiriman</span>
            <strong>{{ number_format($totalCount) }}</strong>
        </a>
        @foreach ([
            \App\Models\BillOfLading::STATUS_IN_PROGRESS,
            \App\Models\BillOfLading::STATUS_ON_HOLD,
            \App\Models\BillOfLading::STATUS_COMPLETED,
        ] as $status)
            <a
                class="status-stat {{ $filters['status'] === $status ? 'is-active' : '' }}"
                href="{{ route('customer.dashboard', ['status' => $status]) }}"
            >
                <span>{{ \App\Models\BillOfLading::statusLabel($status) }}</span>
                <strong>{{ number_format((int) ($statusCounts[$status] ?? 0)) }}</strong>
            </a>
        @endforeach
    </nav>

    <section class="filter-bar" aria-label="Filter pengiriman">
        <form class="shipment-filters" method="GET" action="{{ route('customer.dashboard') }}">
            <div class="search-field">
                <label for="q">BL atau kontainer</label>
                <input
                    id="q"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Cari nomor"
                >
            </div>

            <div class="compact-field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\BillOfLading::STATUSES as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ \App\Models\BillOfLading::statusLabel($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="compact-field">
                <label for="shipment_type">Jenis</label>
                <select id="shipment_type" name="shipment_type">
                    <option value="">Impor & ekspor</option>
                    @foreach (config('bl_workflows.shipment_types') as $type => $label)
                        <option value="{{ $type }}" @selected($filters['shipment_type'] === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($companies->isNotEmpty())
                <div class="compact-field">
                    <label for="company_id">Perusahaan</label>
                    <select id="company_id" name="company_id">
                        <option value="">Semua perusahaan</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected($filters['company_id'] === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="compact-field">
                <label for="month">Bulan</label>
                <select id="month" name="month">
                    <option value="">Semua bulan</option>
                    @foreach ($availableMonths as $month => $label)
                        <option value="{{ $month }}" @selected($filters['month'] === (string) $month)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="compact-field year-field">
                <label for="year">Tahun</label>
                <select id="year" name="year">
                    <option value="">Semua tahun</option>
                    @foreach ($availableYears as $year)
                        <option value="{{ $year }}" @selected($filters['year'] === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            @if ((int) $filters['per_page'] !== 10)
                <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
            @endif

            <button type="submit">Terapkan</button>

            @if ($hasBlSearch || $hasListingFilters)
                <a class="text-action" href="{{ route('customer.dashboard') }}">Hapus filter</a>
            @endif
        </form>
    </section>

    <section class="records-section">
        <div class="records-heading">
            <div>
                <p class="eyebrow">Data pelacakan</p>
                <h2>Pengiriman</h2>
            </div>
            <span>{{ number_format($billOfLadings->total()) }} hasil</span>
        </div>

        @if ($billOfLadings->isEmpty())
            <div class="empty-state">
                <strong>Tidak ada pengiriman ditemukan</strong>
                <a href="{{ route('customer.dashboard') }}">Atur ulang filter</a>
            </div>
        @else
            <div class="shipment-list">
                @foreach ($billOfLadings as $billOfLading)
                    @php
                        $milestone = $billOfLading->currentMilestone();
                        $milestoneLabel = $milestone?->displayLabel(true) ?: $billOfLading->phase;
                        $routeStart = $billOfLading->port_of_loading ?: '-';
                        $routeEnd = $billOfLading->port_of_discharge ?: '-';
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
                                <span class="type-tag">{{ $billOfLading->shippingMethodLabel() }}</span>
                                <span class="status-tag status-{{ $statusClass }}">{{ $billOfLading->displayStatus() }}</span>
                            </div>
                            <h3>{{ $billOfLading->bl_number }}</h3>
                            <p class="shipment-company">{{ $billOfLading->company?->name ?: 'Perusahaan belum ditentukan' }}</p>
                            <p>{{ $billOfLading->carrier_name ?: 'Carrier belum ditentukan' }}</p>
                        </div>

                        <div class="shipment-route">
                            <span>{{ $routeStart }}</span>
                            <b aria-hidden="true">&rarr;</b>
                            <span>{{ $routeEnd }}</span>
                        </div>

                        <div class="shipment-progress">
                            <span>Langkah saat ini</span>
                            <strong>{{ $milestoneLabel }}</strong>
                        </div>

                        <div class="shipment-updated">
                            <span>Diperbarui</span>
                            <strong>{{ $billOfLading->updated_at->locale('id')->translatedFormat('j M Y') }}</strong>
                        </div>

                        <a class="record-link" href="{{ route('customer.bill-of-ladings.show', $billOfLading) }}">Lihat pelacakan</a>
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
