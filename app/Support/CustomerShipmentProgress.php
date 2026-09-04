<?php

namespace App\Support;

use App\Models\BillOfLading;
use App\Models\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CustomerShipmentProgress
{
    public function __construct(private readonly BillOfLading $billOfLading) {}

    public static function from(BillOfLading $billOfLading): self
    {
        return new self($billOfLading);
    }

    public function heading(): string
    {
        return $this->billOfLading->isExport()
            ? 'Tracking Progress EXPORT'
            : 'Tracking Progress IMPORT';
    }

    /**
     * @return list<array{
     *     kicker: string,
     *     title: string,
     *     state: string,
     *     fields: list<array{label: string, value: string, url?: string|null, wide?: bool}>,
     *     schedule: ?string,
     *     spjm: list<array{label: string, value: string, url?: string|null}>,
     *     containers: list<array{container: Container, url: string, fields: list<array{label: string, value: string, url?: string|null}>, photos: array<string, string|null>}>
     * }>
     */
    public function processes(): array
    {
        $processes = $this->billOfLading->isExport()
            ? $this->exportProcesses()
            : $this->importProcesses();

        return $this->markCurrent($processes);
    }

    public function progressPercent(): int
    {
        $processes = $this->processes();
        $total = count($processes);

        if ($total === 0) {
            return 0;
        }

        $completed = collect($processes)->where('state', 'completed')->count();
        $current = collect($processes)->search(fn (array $process): bool => $process['state'] === 'current');

        if ($completed === $total) {
            return 100;
        }

        if ($current === false) {
            return 8;
        }

        return (int) round((($completed + 0.45) / $total) * 100);
    }

    /**
     * @return array{kicker: string, title: string}|null
     */
    public function currentProcess(): ?array
    {
        $current = collect($this->processes())->firstWhere('state', 'current');

        if (! $current) {
            return null;
        }

        return [
            'kicker' => $current['kicker'],
            'title' => $current['title'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportProcesses(): array
    {
        $bl = $this->billOfLading;

        return [
            $this->process(
                'OUTPUT — Process 1',
                'Document Received',
                $bl->booking_order_checked,
                [
                    $this->field('Exporter Name (customer)', $bl->exporterDisplayName()),
                    $this->field('Checking Booking Order', $bl->booking_order_checked ? 'Sudah dicek' : 'Belum'),
                    $this->field('No. DO', $bl->do_number),
                    $this->field('Shipping Line', $bl->carrier_name),
                    $this->field('Closing time at Depo', $this->formatDateTime($bl->depot_closing_at)),
                    $this->field('Closing time at CY (pelabuhan)', $this->formatDateTime($bl->cy_closing_at)),
                    $this->field('Vessel Name', $bl->vessel_name),
                    $this->field('Voyage', $bl->voyage_number),
                    $this->field('Size Container', $bl->container_size),
                    $this->field('Port of Discharging', $bl->port_of_discharge),
                ],
            ),
            $this->process(
                'INPUT — Process 2',
                'Pick Up Empty Cont at Depot',
                filled($bl->pickup_depot) || $this->containers()->contains(fn (Container $container): bool => filled($container->driver_name) || collect($container->documentationPhotos())->filter()->isNotEmpty()),
                [
                    $this->field('Pick Up Depot', $bl->pickup_depot),
                    $this->field('Date of Stuffing', $this->formatDate($bl->stuffing_date)),
                    $this->field('Stuffing Destination', $bl->stuffing_destination),
                ],
                containers: $this->containerBlocks(fn (Container $container): array => [
                    $this->field('No. Seal', $container->seal_number),
                    $this->field('Driver Name', $container->driver_name),
                    $this->field('No. License', $container->license_number),
                ], withPhotos: true),
            ),
            $this->process(
                'FINAL — Process 3',
                'Factory, PEB/NPE, and Gate In CY',
                $bl->peb_npe_checked || $bl->gate_in_cy_processed || $bl->on_the_way_factory_at,
                [
                    $this->field('Container On The Way Factory', $this->formatDateTime($bl->on_the_way_factory_at)),
                    $this->field('Checking PEB and NPE', $bl->peb_npe_checked ? 'Sudah dicek' : 'Belum'),
                    $this->field('Process Gate In CY', $bl->gate_in_cy_processed ? 'Selesai' : 'Belum'),
                    $this->field('Final Checking Details Shipment', $bl->final_checking_notes, wide: true),
                ],
                containers: $this->containerBlocks(fn (Container $container): array => [
                    $this->field('Tracking Position Driver', $container->driver_tracking_url ? 'Buka tautan' : null, url: $container->driver_tracking_url),
                    $this->field('Progress Stuffing at Factory', $container->stuffingProgressLabel()),
                    $this->field('Gate in CY — Port of Loading', $container->gate_in_pol),
                    $this->field('Gate in CY Date', $this->formatDateTime($container->gate_in_cy_at)),
                    $this->field('Amount of VGM', $container->vgm_kg ? number_format((float) $container->vgm_kg, 3).' kg' : null),
                    $this->field(
                        'Final Checking',
                        $container->final_checked
                            ? 'Sudah dicek'.($container->final_checked_at ? ' · '.$this->formatDate($container->final_checked_at) : '')
                            : 'Belum',
                    ),
                ]),
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function importProcesses(): array
    {
        $bl = $this->billOfLading;
        $checked = fn (?bool $value): string => $value ? 'Sudah' : 'Belum';
        $schedule = $bl->shipping_schedule;

        $spjm = [];
        if ($bl->isSpjmResponse()) {
            $documents = collect($bl->importDocumentUrls())
                ->map(fn (string $url, int $index): array => $this->field('Upload All Document', 'Dokumen', url: $url))
                ->all();

            $spjm = [
                ...($documents !== [] ? $documents : [$this->field('Upload All Document', 'Belum')]),
                $this->field('Waiting Process Bahandle', $checked($bl->waiting_bahandle)),
                $this->field('Payment Bahandle', $checked($bl->bahandle_paid)),
                $this->field('Container Inspection', $checked($bl->container_inspected)),
                $this->field('Waiting SPJM to SPPB', $checked($bl->waiting_spjm_to_sppb)),
            ];
        }

        return [
            $this->process(
                'OUTPUT — Process 1',
                'Document Received',
                $bl->document_checked,
                [
                    $this->field('Importir Name (customer)', $bl->importerDisplayName()),
                    $this->field('Checking Document', $checked($bl->document_checked)),
                    $this->field('No. BL', $bl->bl_number),
                ],
                schedule: $schedule,
            ),
            $this->process(
                'INPUT — Process 2',
                'Draft PIB',
                $bl->draft_pib_checked || $bl->pib_sent_to_customs || $bl->billing_issued || filled($bl->customs_response),
                [
                    $this->field('Shipping Line', $bl->carrier_name),
                    $this->field('Checking draft PIB to Importir', $checked($bl->draft_pib_checked)),
                    $this->field('Vessel Name', $bl->vessel_name),
                    $this->field('Konfirmasi customer', $checked($bl->customer_confirmed)),
                    $this->field('Final Sending PIB to Custom', $checked($bl->pib_sent_to_customs)),
                    $this->field('Voyage', $bl->voyage_number),
                    $this->field('Status billing', $bl->billing_issued ? 'Sudah terbit' : 'Belum terbit'),
                    $this->field('Process payment THC', $checked($bl->thc_paid)),
                    $this->field('Port of Loading', $bl->port_of_loading),
                    $this->field('Waiting release DO', $checked($bl->waiting_do_release)),
                    $this->field('Departure Date', $this->formatDate($bl->departure_date)),
                    $this->field(
                        'DO Release',
                        $checked($bl->do_released).($bl->do_release_date ? ' · '.$this->formatDate($bl->do_release_date) : ''),
                    ),
                    $this->field('Port of Discharging', $bl->port_of_discharge),
                    $this->field('Payment Billing', $checked($bl->billing_paid)),
                    $this->field('Arrival Time / ETA', $this->formatDateTime($bl->eta_at)),
                    $this->field('Response Billing', $bl->customsResponseLabel()),
                    $this->field('Nomor Aju', $bl->aju_number),
                    $this->field('Description of Goods', $bl->goods_description),
                    $this->field('HS Code', $bl->hs_code),
                    $this->field('Packages', $bl->package_count),
                    $this->field('Terminal Name', $bl->terminal_name),
                    $this->field('Date of Loading', $this->formatDate($bl->loading_date)),
                    $this->field('Loading Destination', $bl->loading_destination),
                ],
                schedule: $schedule,
                spjm: $spjm,
                containers: $this->containerBlocks(fn (Container $container): array => [
                    $this->field('Size', $container->container_type),
                    $this->field('Gross Weight', $container->gross_weight_kg ? number_format((float) $container->gross_weight_kg, 3).' kg' : null),
                    $this->field('CBM / Measurement', $container->measurement_cbm ? number_format((float) $container->measurement_cbm, 3) : null),
                ]),
            ),
            $this->process(
                'FINAL — Process 3',
                'Gate Out from Inbound Terminal for Delivery to Consignee',
                $bl->on_the_way_factory_at || $bl->arrived_at_factory_at || $bl->empty_container_returned,
                [
                    $this->field('Container On The Way Factory', $this->formatDateTime($bl->on_the_way_factory_at)),
                    $this->field('Container Arrived in Factory', $this->formatDateTime($bl->arrived_at_factory_at)),
                    $this->field('Empty Container Returned', $checked($bl->empty_container_returned)),
                ],
                schedule: $schedule,
                containers: $this->containerBlocks(fn (Container $container): array => [
                    $this->field('Gate out CY', $this->formatDateTime($container->gate_out_cy_at)),
                    $this->field('Driver Name', $container->driver_name),
                    $this->field('No. License', $container->license_number),
                    $this->field('Tracking Position Driver', $container->driver_tracking_url ? 'Buka tautan' : null, url: $container->driver_tracking_url),
                    $this->field('Loading in Factory', $container->factoryLoadingProgressLabel()),
                    $this->field(
                        'Return Empty Cont in Depot',
                        trim(implode(' · ', array_filter([
                            $container->empty_return_depot,
                            $this->formatDate($container->empty_return_at),
                        ]))),
                    ),
                ]),
            ),
        ];
    }

    /**
     * @param  list<array{label: string, value: string, url?: string|null, wide?: bool}>  $fields
     * @param  list<array{label: string, value: string, url?: string|null}>  $spjm
     * @param  list<array{container: Container, url: string, fields: list<array{label: string, value: string, url?: string|null}>, photos: array<string, string|null>}>  $containers
     * @return array<string, mixed>
     */
    private function process(
        string $kicker,
        string $title,
        mixed $done,
        array $fields,
        ?string $schedule = null,
        array $spjm = [],
        array $containers = [],
    ): array {
        return [
            'kicker' => $kicker,
            'title' => $title,
            'done' => (bool) $done,
            'state' => $done ? 'completed' : 'pending',
            'fields' => $fields,
            'schedule' => $schedule,
            'spjm' => $spjm,
            'containers' => $containers,
        ];
    }

    /**
     * @return array{label: string, value: string, url?: string|null, wide?: bool}
     */
    private function field(string $label, ?string $value, ?string $url = null, bool $wide = false): array
    {
        $field = [
            'label' => $label,
            'value' => filled($value) ? $value : '-',
        ];

        if ($url) {
            $field['url'] = $url;
        }

        if ($wide) {
            $field['wide'] = true;
        }

        return $field;
    }

    /**
     * @param  callable(Container): list<array{label: string, value: string, url?: string|null}>  $callback
     * @return list<array{container: Container, url: string, fields: list<array{label: string, value: string, url?: string|null}>, photos: array<string, string|null>}>
     */
    private function containerBlocks(callable $callback, bool $withPhotos = false): array
    {
        return $this->containers()->map(function (Container $container) use ($callback, $withPhotos): array {
            return [
                'container' => $container,
                'url' => route('customer.containers.show', [$this->billOfLading, $container]),
                'fields' => $callback($container),
                'photos' => $withPhotos ? $container->documentationPhotos() : [],
            ];
        })->all();
    }

    /**
     * @param  list<array<string, mixed>>  $processes
     * @return list<array<string, mixed>>
     */
    private function markCurrent(array $processes): array
    {
        $currentIndex = null;

        foreach ($processes as $index => $process) {
            if ($process['done']) {
                $currentIndex = $index;
            }
        }

        if ($currentIndex === null) {
            if ($processes !== []) {
                $processes[0]['state'] = 'current';
            }

            return $processes;
        }

        $nextPending = null;
        foreach ($processes as $index => $process) {
            if ($index > $currentIndex && ! $process['done']) {
                $nextPending = $index;
                break;
            }
        }

        $processes[$nextPending ?? $currentIndex]['state'] = 'current';

        return $processes;
    }

    /**
     * @return Collection<int, Container>
     */
    private function containers(): Collection
    {
        return $this->billOfLading->relationLoaded('containers')
            ? $this->billOfLading->containers
            : $this->billOfLading->containers()->get();
    }

    private function formatDateTime(mixed $value): ?string
    {
        if (! $value instanceof Carbon) {
            return null;
        }

        return $value->locale('id')->translatedFormat($value->format('H:i:s') === '00:00:00' ? 'j M Y' : 'j M Y H:i');
    }

    private function formatDate(mixed $value): ?string
    {
        if (! $value instanceof Carbon) {
            return null;
        }

        return $value->locale('id')->translatedFormat('j M Y');
    }
}
