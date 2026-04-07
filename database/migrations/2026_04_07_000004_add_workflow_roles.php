<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'Accountant',
            'Sales Manager',
            'Production Manager',
            'Logistics Manager',
            'Dispatcher',
        ];

        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        // Do not delete roles on rollback — they may have users assigned
    }
};
