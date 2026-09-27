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
            'material_id' => 1,
            'material_name' => 'Cartolina',
            'category' => 'Paper',
            'unit' => 'sheets',
            'stock_quantity' => 25,
            'description' => 'Assorted colors for event displays.',
        ],
        [
            'material_id' => 2,
            'material_name' => 'Scissors',
            'category' => 'Tools',
            'unit' => 'pieces',
            'stock_quantity' => 8,
            'description' => 'General purpose scissors.',
        ],
        [
            'material_id' => 3,
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
            'borrower_id' => 1,
            'borrower_name' => 'Juan Dela Cruz',
            'contact' => '0917 123 4567',
            'department' => 'Design Committee',
        ],
        [
            'borrower_id' => 2,
            'borrower_name' => 'Maria Santos',
            'contact' => '0918 234 5678',
            'department' => 'Events Committee',
        ],
        [
            'borrower_id' => 3,
            'borrower_name' => 'Alex Reyes',
            'contact' => 'alex.reyes@example.edu',
            'department' => 'Department Office',
        ],
    ];
}
