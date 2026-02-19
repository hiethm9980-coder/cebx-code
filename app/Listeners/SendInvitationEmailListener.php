<?php

namespace App\Listeners;

use App\Events\InvitationCreated;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvitationEmailListener implements ShouldQueue
{
    public function handle(InvitationCreated $event): void
    {
        $invitation = $event->invitation;

        // Log the invitation send attempt
        AuditLog::withoutGlobalScopes()->create([
            'account_id'  => $invitation->account_id,
            'user_id'     => $event->inviter->id,
            'action'      => 'invitation.email_sent',
            'entity_type' => 'Invitation',
            'entity_id'   => $invitation->id,
            'old_values'  => null,
            'new_values'  => [
                'email'   => $invitation->email,
                'role_id' => $invitation->role_id,
                'token'   => substr($invitation->token, 0, 8) . '...',
            ],
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);

        // TODO: Send actual email via Mail facade
        // Mail::to($invitation->email)->queue(new InvitationMail($invitation));
        // For now, logged for async processing.
    }
}
