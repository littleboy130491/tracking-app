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

        <label for="per_page">Show</label>
        <select id="per_page" name="per_page" onchange="this.form.submit()">
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <span>per page</span>
    </form>

    @if ($paginator->total() > 0)
        <p class="pagination-summary">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} records
        </p>
    @endif

    @if ($paginator->hasPages())
        <nav class="pagination-links" aria-label="BL records pagination">
            @if ($paginator->onFirstPage())
                <span class="pagination-link disabled">Previous</span>
            @else
                <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}">Previous</a>
            @endif

            <span class="pagination-status">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}">Next</a>
            @else
                <span class="pagination-link disabled">Next</span>
            @endif
        </nav>
    @endif
</div>
