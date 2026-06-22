<?php

namespace App\Http\Controllers;

use App\Models\BillOfLading;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public const PER_PAGE_OPTIONS = [10, 25, 100, 200];

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardCustomer()) {
            return $redirect;
        }

        /** @var User $customer */
        $customer = Auth::user();
        $perPage = $this->resolvePerPage($request);

        $filters = [
            'q' => trim((string) $request->query('q')),
            'status' => (string) $request->query('status', ''),
            'phase' => (string) $request->query('phase', ''),
            'month' => (string) $request->query('month', ''),
            'year' => (string) $request->query('year', ''),
            'per_page' => (string) $perPage,
        ];

        $billOfLadings = BillOfLading::query()
            ->whereBelongsTo($customer, 'customer')
            ->when($filters['q'] !== '', fn ($query) => $query->where('bl_number', 'like', "%{$filters['q']}%"))
            ->when(
                $filters['status'] !== '' && in_array($filters['status'], BillOfLading::STATUSES, true),
                fn ($query) => $query->where('status', $filters['status']),
            )
            ->when(
                $filters['phase'] !== '' && in_array($filters['phase'], BillOfLading::PHASES, true),
                fn ($query) => $query->where('phase', $filters['phase']),
            )
            ->when(
                $filters['month'] !== ''
                    && ctype_digit($filters['month'])
                    && (int) $filters['month'] >= 1
                    && (int) $filters['month'] <= 12,
                fn ($query) => $query->whereMonth('input_date', (int) $filters['month']),
            )
            ->when(
                $filters['year'] !== '' && ctype_digit($filters['year']),
                fn ($query) => $query->whereYear('input_date', (int) $filters['year']),
            )
            ->latest('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $availableYears = BillOfLading::query()
            ->whereBelongsTo($customer, 'customer')
            ->whereNotNull('input_date')
            ->get(['input_date'])
            ->map(fn (BillOfLading $billOfLading): int => $billOfLading->input_date->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('customer.dashboard', [
            'billOfLadings' => $billOfLadings,
            'customer' => $customer,
            'filters' => $filters,
            'availableYears' => $availableYears,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'hasBlSearch' => $filters['q'] !== '',
            'hasListingFilters' => $filters['status'] !== ''
                || $filters['phase'] !== ''
                || $filters['month'] !== ''
                || $filters['year'] !== '',
        ]);
    }

    public function show(BillOfLading $billOfLading): View|RedirectResponse
    {
        if ($redirect = $this->guardCustomer()) {
            return $redirect;
        }

        abort_unless($billOfLading->customer_id === Auth::id(), 404);

        return view('customer.bill-of-ladings.show', [
            'billOfLading' => $billOfLading->load(['customer', 'updates.user']),
        ]);
    }

    private function guardCustomer(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('customer.login');
        }

        abort_unless(Auth::user()->hasRole(User::ROLE_CUSTOMER), 403);

        return null;
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }
}
