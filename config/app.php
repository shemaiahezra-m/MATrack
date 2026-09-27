<?php
/**
 * Set this to true after the shared PostgreSQL database is ready.
 * In demo mode, pages use sample rows and do not call the database.
 */
const DATABASE_ENABLED = false;

function getDemoMaterials(): array
{
    return [
        [
            'material_id' => 'MAT-001',
            'material_name' => 'Cartolina',
            'category' => 'Paper',
            'unit' => 'sheets',
            'stock_quantity' => 25,
            'description' => 'Assorted colors for event displays.',
        ],
        [
            'material_id' => 'MAT-002',
            'material_name' => 'Scissors',
            'category' => 'Tools',
            'unit' => 'pieces',
            'stock_quantity' => 8,
            'description' => 'General purpose scissors.',
        ],
        [
            'material_id' => 'MAT-003',
            'material_name' => 'Markers',
            'category' => 'Art supplies',
            'unit' => 'sets',
            'stock_quantity' => 6,
            'description' => 'Assorted color markers.',
        ],
    ];
}

function getDemoBorrowers(): array
{
    return [
        [
            'borrower_id' => 'BOR-001',
            'borrower_name' => 'Juan Dela Cruz',
            'contact' => '0917 123 4567',
            'department' => 'Design Committee',
        ],
        [
            'borrower_id' => 'BOR-002',
            'borrower_name' => 'Maria Santos',
            'contact' => '0918 234 5678',
            'department' => 'Events Committee',
        ],
        [
            'borrower_id' => 'BOR-003',
            'borrower_name' => 'Alex Reyes',
            'contact' => 'alex.reyes@example.edu',
            'department' => 'Department Office',
        ],
    ];
}

function getDemoTransactions(): array
{
    return [
        [
            'transaction_id' => 'TRX-001',
            'material_id' => 'MAT-001',
            'borrower_id' => 'BOR-001',
            'transaction_type' => 'BORROWED',
            'quantity' => 3,
            'transaction_date' => '2026-09-27 09:00:00',
            'expected_return_date' => '2026-10-02',
            'status' => 'ACTIVE',
            'notes' => 'Three Cartolina sheets borrowed for event posters.',
        ],
        [
            'transaction_id' => 'TRX-002',
            'material_id' => 'MAT-002',
            'borrower_id' => 'BOR-002',
            'transaction_type' => 'BORROWED',
            'quantity' => 2,
            'transaction_date' => '2026-09-26 13:30:00',
            'expected_return_date' => '2026-10-01',
            'status' => 'ACTIVE',
            'notes' => 'Scissors for booth setup.',
        ],
        [
            'transaction_id' => 'TRX-003',
            'material_id' => 'MAT-001',
            'borrower_id' => 'BOR-001',
            'transaction_type' => 'RETURNED',
            'quantity' => 2,
            'transaction_date' => '2026-09-26 16:15:00',
            'expected_return_date' => null,
            'status' => 'RETURNED',
            'notes' => 'Two Cartolina sheets returned.',
        ],
        [
            'transaction_id' => 'TRX-004',
            'material_id' => 'MAT-001',
            'borrower_id' => null,
            'transaction_type' => 'USED',
            'quantity' => 1,
            'transaction_date' => '2026-09-25 10:00:00',
            'expected_return_date' => null,
            'status' => 'ACTIVE',
            'notes' => 'One Cartolina sheet used for a department sign.',
        ],
        [
            'transaction_id' => 'TRX-005',
            'material_id' => 'MAT-002',
            'borrower_id' => null,
            'transaction_type' => 'ADDED',
            'quantity' => 5,
            'transaction_date' => '2026-09-24 14:00:00',
            'expected_return_date' => null,
            'status' => 'ACTIVE',
            'notes' => 'New stock received.',
        ],
    ];
}
