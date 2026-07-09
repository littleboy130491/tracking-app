<?php

namespace App\Http\Controllers;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'milestone' => (string) $request->query('milestone', ''),
            'shipment_type' => (string) $request->query('shipment_type', ''),
            'month' => (string) $request->query('month', ''),
            'year' => (string) $request->query('year', ''),
            'per_page' => (string) $perPage,
        ];

        $billOfLadings = BillOfLading::query()
            ->whereBelongsTo($customer, 'customer')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters): void {
                    $inner->where('bl_number', 'like', "%{$filters['q']}%")
                        ->orWhereHas('containers', fn ($containerQuery) => $containerQuery
                            ->where('container_number', 'like', "%{$filters['q']}%"));
                });
            })
            ->when(
                $filters['status'] !== '' && in_array($filters['status'], BillOfLading::STATUSES, true),
                fn ($query) => $query->where('status', $filters['status']),
            )
            ->when(
                $filters['milestone'] !== '' && array_key_exists($filters['milestone'], BillOfLading::milestoneOptions()),
                fn ($query) => $query->where('current_milestone_key', $filters['milestone']),
            )
            ->when(
                $filters['shipment_type'] !== ''
                    && array_key_exists($filters['shipment_type'], config('bl_workflows.shipment_types', [])),
                fn ($query) => $query->where('shipment_type', $filters['shipment_type']),
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
                || $filters['milestone'] !== ''
                || $filters['shipment_type'] !== ''
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

        $billOfLading->load([
            'customer',
            'containers',
            'milestoneStates',
            'updates' => fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->whereNull('visibility')
                    ->orWhere('visibility', BillOfLadingUpdate::VISIBILITY_CUSTOMER))
                ->with('user'),
        ]);

        return view('customer.bill-of-ladings.show', [
            'billOfLading' => $billOfLading,
            'timelineNodes' => $this->customerTimelineNodes($billOfLading),
            'laneClass' => match ($billOfLading->customs_lane) {
                'green' => 'lane-green',
                'yellow' => 'lane-yellow',
                'red' => 'lane-red',
                default => 'lane-neutral',
            },
        ]);
    }

    /**
     * @return Collection<int, array{label: string, state: string}>
     */
    private function customerTimelineNodes(BillOfLading $billOfLading): Collection
    {
        return $billOfLading->milestoneStates
            ->where('customer_visible', true)
            ->values()
            ->reduce(function ($nodes, $milestone) {
                $label = $milestone->displayLabel(true);
                $lastIndex = $nodes->count() - 1;
                $last = $nodes->get($lastIndex);

                if ($last && $last['label'] === $label) {
                    $last['state'] = $this->mergeTimelineState($last['state'], $milestone->state);
                    $nodes->put($lastIndex, $last);

                    return $nodes;
                }

                $nodes->push([
                    'label' => $label,
                    'state' => $milestone->state,
                ]);

                return $nodes;
            }, collect());
    }

    private function mergeTimelineState(string $existing, string $incoming): string
    {
        if ($existing === 'in_progress' || $incoming === 'in_progress') {
            return 'in_progress';
        }

        if ($existing === 'pending' || $incoming === 'pending') {
            return 'pending';
        }

        return $existing;
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
