<?php
namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('reports.view'); }
    public function export(User $user): bool { return $user->hasPermission('reports.export'); }
    public function save(User $user): bool { return $user->hasPermission('reports.save'); }
    public function schedule(User $user): bool { return $user->hasPermission('reports.schedule'); }
    public function viewFinancial(User $user): bool { return $user->hasPermission('reports.financial'); }
}
