@props([
    'paginator',
    'filters',
    'perPageOptions',
])

@php
    $queryParams = array_filter([
        'q' => $filters['q'] ?: null,
        'status' => $filters['status'] ?: null,
        'shipment_type' => $filters['shipment_type'] ?: null,
        'month' => $filters['month'] ?: null,
        'year' => $filters['year'] ?: null,
        'per_page' => $filters['per_page'] ?: null,
    ]);
@endphp

<div class="pagination-bar">
    <form class="per-page-form" method="GET" action="{{ route('customer.dashboard') }}">
        @foreach ($queryParams as $key => $value)
            @if ($key !== 'per_page')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <label for="per_page">Tampilkan</label>
        <select id="per_page" name="per_page" onchange="this.form.submit()">
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <span>per halaman</span>
    </form>

    @if ($paginator->total() > 0)
        <p class="pagination-summary">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </p>
    @endif

    @if ($paginator->hasPages())
        <nav class="pagination-links" aria-label="Navigasi halaman BL">
            @if ($paginator->onFirstPage())
                <span class="pagination-link disabled">Sebelumnya</span>
            @else
                <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>
            @endif

            <span class="pagination-status">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="pagination-link disabled">Berikutnya</span>
            @endif
        </nav>
    @endif
</div>
