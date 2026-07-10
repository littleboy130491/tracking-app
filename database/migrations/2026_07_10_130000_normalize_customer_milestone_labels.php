<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $importLabels = [
            'receive_docs' => 'Documents received',
            'draft_pib' => 'Draft PIB',
            'process_do' => 'Delivery order',
            'do_release' => 'DO released',
            'transfer_pib' => 'PIB transfer',
            'send_billing' => 'Billing sent',
            'pib_response' => 'Customs response',
            'lane_notice' => null,
            'submit_docs' => 'Documents submitted',
            'physical_inspection' => 'Physical inspection',
            'sppb' => 'SPPB',
            'deliver_container' => 'Container delivery',
        ];

        foreach ($importLabels as $key => $label) {
            $query = DB::table('bill_of_lading_milestone_states')
                ->where('milestone_key', $key)
                ->where('workflow_key', 'like', 'import%');

            if ($key === 'lane_notice') {
                $query->get(['id', 'label'])->each(fn ($row) => DB::table('bill_of_lading_milestone_states')
                    ->where('id', $row->id)
                    ->update(['customer_label' => $row->label]));

                continue;
            }

            $query->update(['customer_label' => $label]);
        }

        $exportLabels = [
            'receive_docs' => 'Documents received',
            'draft_peb' => 'Draft PEB',
            'process_do' => 'Export delivery order',
            'loading_unloading' => 'Loading and unloading',
            'down_to_depot' => 'Down to depot',
            'loading_shipment' => 'Loading to shipment',
            'load_container' => 'Container loading',
            'process_peb' => 'PEB process',
            'npe_response' => 'NPE response',
            'export_card' => 'Export card',
            'stock_to_port' => 'Container to port',
        ];

        foreach ($exportLabels as $key => $label) {
            DB::table('bill_of_lading_milestone_states')
                ->where('workflow_key', 'export')
                ->where('milestone_key', $key)
                ->update(['customer_label' => $label]);
        }
    }

    public function down(): void
    {
        // Labels are presentation snapshots; reverting would reintroduce ambiguous customer wording.
    }
};
