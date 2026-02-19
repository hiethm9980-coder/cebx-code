<?php
namespace App\Policies;

use App\Models\User;

class KycPolicy
{
    public function view(User $user): bool { return $user->hasPermission('kyc.view'); }
    public function upload(User $user): bool { return $user->hasPermission('kyc.upload'); }
    public function submit(User $user): bool { return $user->hasPermission('kyc.submit'); }
    public function review(User $user): bool { return $user->hasPermission('kyc.review'); }
}
