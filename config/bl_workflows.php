<?php

return [
    'shipment_types' => [
        'import' => 'Impor',
        'export' => 'Ekspor',
    ],

    'shipping_methods' => [
        'fcl' => 'FCL',
        'lcl' => 'LCL',
        'air' => 'Air Shipment',
    ],

    'customs_lanes' => [
        'green' => 'Jalur Hijau (SPJH)',
        'yellow' => 'Jalur Kuning (SPJK)',
        'red' => 'Jalur Merah (SPJM)',
    ],

    'milestone_states' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'skipped' => 'Skipped',
    ],

    'visibilities' => [
        'customer' => 'Customer',
        'admin_only' => 'Admin only',
    ],

    'retention_years' => 3,

    'milestone_roles' => [
        'receive_docs' => 'workflow_documents',
        'draft_pib' => 'workflow_customs',
        'transfer_pib' => 'workflow_customs',
        'send_billing' => 'workflow_billing',
        'pib_response' => 'workflow_customs',
        'lane_notice' => 'workflow_customs',
        'submit_docs' => 'workflow_customs',
        'physical_inspection' => 'workflow_customs',
        'sppb' => 'workflow_customs',
        'draft_peb' => 'workflow_export',
        'process_peb' => 'workflow_export',
        'npe_response' => 'workflow_export',
        'export_card' => 'workflow_export',
        'stock_to_port' => 'workflow_export',
        'process_do' => 'workflow_operations',
        'do_release' => 'workflow_operations',
        'loading_unloading' => 'workflow_operations',
        'down_to_depot' => 'workflow_operations',
        'loading_shipment' => 'workflow_operations',
        'load_container' => 'workflow_operations',
        'deliver_container' => 'workflow_delivery',
        'finalize_docs' => 'workflow_delivery',
        'exim_card' => 'workflow_delivery',
        'switch_to_driver' => 'workflow_delivery',
        'process_depot' => 'workflow_delivery',
        'pickup_container' => 'workflow_delivery',
        'on_the_way' => 'workflow_delivery',
        'loading' => 'workflow_delivery',
        'down_container_depot' => 'workflow_delivery',
    ],

    /*
    |--------------------------------------------------------------------------
    | Import pre-lane milestones
    |--------------------------------------------------------------------------
    */
    'import_pre_lane' => [
        [
            'key' => 'receive_docs',
            'label' => 'Penerimaan dokumen customer',
            'customer_label' => 'Dokumen diterima',
            'customer_visible' => true,
        ],
        [
            'key' => 'draft_pib',
            'label' => 'Pembuatan draft PIB',
            'customer_label' => 'Draft PIB',
            'customer_visible' => true,
        ],
        [
            'key' => 'process_do',
            'label' => 'Proses DO',
            'customer_label' => 'Delivery Order (DO)',
            'customer_visible' => true,
        ],
        [
            'key' => 'do_release',
            'label' => 'DO Release',
            'customer_label' => 'DO dirilis',
            'customer_visible' => true,
        ],
        [
            'key' => 'transfer_pib',
            'label' => 'Proses transfer PIB',
            'customer_label' => 'Transfer PIB',
            'customer_visible' => true,
        ],
        [
            'key' => 'send_billing',
            'label' => 'Pengiriman billing',
            'customer_label' => 'Billing dikirim',
            'customer_visible' => true,
        ],
        [
            'key' => 'pib_response',
            'label' => 'Proses respon PIB',
            'customer_label' => 'Respon bea cukai',
            'customer_visible' => true,
        ],
    ],

    'import_lanes' => [
        'green' => [
            [
                'key' => 'sppb',
                'label' => 'SPPB',
                'customer_label' => 'SPPB',
                'customer_visible' => true,
            ],
            [
                'key' => 'deliver_container',
                'label' => 'Pengiriman kontainer',
                'customer_label' => 'Pengiriman kontainer',
                'customer_visible' => true,
            ],
        ],
        'yellow' => [
            [
                'key' => 'lane_notice',
                'label' => 'SPJK',
                'customer_label' => 'SPJK',
                'customer_visible' => true,
            ],
            [
                'key' => 'submit_docs',
                'label' => 'Submit dokumen',
                'customer_label' => 'Dokumen diserahkan',
                'customer_visible' => true,
            ],
            [
                'key' => 'sppb',
                'label' => 'SPPB',
                'customer_label' => 'SPPB',
                'customer_visible' => true,
            ],
            [
                'key' => 'deliver_container',
                'label' => 'Pengiriman kontainer',
                'customer_label' => 'Pengiriman kontainer',
                'customer_visible' => true,
            ],
        ],
        'red' => [
            [
                'key' => 'lane_notice',
                'label' => 'SPJM',
                'customer_label' => 'SPJM',
                'customer_visible' => true,
            ],
            [
                'key' => 'submit_docs',
                'label' => 'Submit dokumen',
                'customer_label' => 'Dokumen diserahkan',
                'customer_visible' => true,
            ],
            [
                'key' => 'physical_inspection',
                'label' => 'Periksa fisik',
                'customer_label' => 'Pemeriksaan fisik',
                'customer_visible' => true,
            ],
            [
                'key' => 'sppb',
                'label' => 'SPPB',
                'customer_label' => 'SPPB',
                'customer_visible' => true,
            ],
            [
                'key' => 'deliver_container',
                'label' => 'Pengiriman kontainer',
                'customer_label' => 'Pengiriman kontainer',
                'customer_visible' => true,
            ],
        ],
    ],

    'export' => [
        [
            'key' => 'receive_docs',
            'label' => 'Penerimaan dokumen customer',
            'customer_label' => 'Dokumen diterima',
            'customer_visible' => true,
        ],
        [
            'key' => 'draft_peb',
            'label' => 'Pembuatan draft PEB',
            'customer_label' => 'Draft PEB',
            'customer_visible' => true,
        ],
        [
            'key' => 'process_do',
            'label' => 'Proses DO',
            'customer_label' => 'Delivery Order ekspor',
            'customer_visible' => true,
        ],
        [
            'key' => 'loading_unloading',
            'label' => 'Bongkar muat',
            'customer_label' => 'Bongkar muat',
            'customer_visible' => true,
        ],
        [
            'key' => 'down_to_depot',
            'label' => 'Down to depot',
            'customer_label' => 'Turun ke depot',
            'customer_visible' => true,
        ],
        [
            'key' => 'loading_shipment',
            'label' => 'Loading shipment',
            'customer_label' => 'Muat ke pengiriman',
            'customer_visible' => true,
        ],
        [
            'key' => 'load_container',
            'label' => 'Muat container',
            'customer_label' => 'Muat kontainer',
            'customer_visible' => true,
        ],
        [
            'key' => 'process_peb',
            'label' => 'Proses PEB',
            'customer_label' => 'Proses PEB',
            'customer_visible' => true,
        ],
        [
            'key' => 'npe_response',
            'label' => 'Respon NPE',
            'customer_label' => 'Respon NPE',
            'customer_visible' => true,
        ],
        [
            'key' => 'export_card',
            'label' => 'Pembuatan export card',
            'customer_label' => 'Kartu ekspor',
            'customer_visible' => true,
        ],
        [
            'key' => 'stock_to_port',
            'label' => 'Stocking container ke pelabuhan',
            'customer_label' => 'Kontainer ke pelabuhan',
            'customer_visible' => true,
        ],
    ],

    'delivery' => [
        [
            'key' => 'finalize_docs',
            'label' => 'Finalisasi dokumen',
            'customer_label' => 'Finalisasi dokumen',
            'customer_visible' => true,
        ],
        [
            'key' => 'exim_card',
            'label' => 'Proses kartu exim',
            'customer_label' => 'Proses kartu ekspor/impor',
            'customer_visible' => true,
        ],
        [
            'key' => 'switch_to_driver',
            'label' => 'Switch to driver',
            'customer_label' => 'Serah ke sopir',
            'customer_visible' => true,
        ],
        [
            'key' => 'process_depot',
            'label' => 'Proses depot',
            'customer_label' => 'Proses depot',
            'customer_visible' => true,
        ],
        [
            'key' => 'pickup_container',
            'label' => 'Pengambilan container',
            'customer_label' => 'Ambil kontainer',
            'customer_visible' => true,
        ],
        [
            'key' => 'on_the_way',
            'label' => 'On the way shipment',
            'customer_label' => 'Dalam perjalanan',
            'customer_visible' => true,
        ],
        [
            'key' => 'loading',
            'label' => 'Loading',
            'customer_label' => 'Pemuatan',
            'customer_visible' => true,
        ],
        [
            'key' => 'down_container_depot',
            'label' => 'Down container to depot',
            'customer_label' => 'Turunkan kontainer ke depot',
            'customer_visible' => true,
        ],
    ],
];
