<?php

namespace App\Http\Controllers;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'shipment_type' => (string) $request->query('shipment_type', ''),
            'month' => (string) $request->query('month', ''),
            'year' => (string) $request->query('year', ''),
            'per_page' => (string) $perPage,
        ];

        $billOfLadings = BillOfLading::query()
            ->whereBelongsTo($customer, 'customer')
            ->with('milestoneStates')
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

        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', input_date)"
            : 'YEAR(input_date)';

        $availableYears = BillOfLading::query()
            ->whereBelongsTo($customer, 'customer')
            ->whereNotNull('input_date')
            ->selectRaw("{$yearExpression} as input_year")
            ->distinct()
            ->orderByDesc('input_year')
            ->pluck('input_year')
            ->map(fn ($year): int => (int) $year);

        $statusCounts = BillOfLading::query()
            ->whereBelongsTo($customer, 'customer')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('customer.dashboard', [
            'billOfLadings' => $billOfLadings,
            'customer' => $customer,
            'filters' => $filters,
            'availableMonths' => collect([
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ])->mapWithKeys(fn (string $label, int $month): array => [
                (string) $month => $label,
            ]),
            'availableYears' => $availableYears,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'statusCounts' => $statusCounts,
            'totalCount' => $statusCounts->sum(),
            'hasBlSearch' => $filters['q'] !== '',
            'hasListingFilters' => $filters['status'] !== ''
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
            'timelineTracks' => $this->customerTimelineTracks($billOfLading),
            'laneClass' => match ($billOfLading->customs_lane) {
                'green' => 'lane-green',
                'yellow' => 'lane-yellow',
                'red' => 'lane-red',
                default => 'lane-neutral',
            },
        ]);
    }

    /**
     * @return Collection<int, array{title: string, nodes: Collection<int, array{label: string, state: string}>}>
     */
    private function customerTimelineTracks(BillOfLading $billOfLading): Collection
    {
        $visibleMilestones = $billOfLading->milestoneStates
            ->where('customer_visible', true)
            ->values();

        $processMilestones = $visibleMilestones
            ->reject(fn ($milestone): bool => $milestone->workflow_key === 'delivery')
            ->values();
        $deliveryMilestones = $visibleMilestones
            ->where('workflow_key', 'delivery')
            ->values();

        return collect([
            [
                'title' => $billOfLading->shipment_type === BillOfLading::TYPE_EXPORT
                    ? 'Proses ekspor'
                    : 'Proses impor',
                'nodes' => $processMilestones->map(fn ($milestone): array => [
                    'label' => $milestone->displayLabel(true),
                    'state' => $milestone->state,
                ]),
            ],
            [
                'title' => 'Proses delivery',
                'nodes' => $deliveryMilestones->map(fn ($milestone): array => [
                    'label' => $milestone->displayLabel(true),
                    'state' => $milestone->state,
                ]),
            ],
        ])->filter(fn (array $track): bool => $track['nodes']->isNotEmpty())->values();
    }

    private function guardCustomer(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('customer.login');
        }

        abort_unless(
            Auth::user()->is_active && Auth::user()->hasRole(User::ROLE_CUSTOMER),
            403,
        );

        return null;
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }
}
