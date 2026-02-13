<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS roles_name_guard_name_unique ON roles (name, guard_name)");
    DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS permissions_name_guard_name_unique ON permissions (name, guard_name)");
  }

  public function down(): void
  {
    DB::statement("DROP INDEX IF EXISTS roles_name_guard_name_unique");
    DB::statement("DROP INDEX IF EXISTS permissions_name_guard_name_unique");
  }
};
