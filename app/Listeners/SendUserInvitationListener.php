<?php

namespace App\Listeners;

use App\Events\UserInvited;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendUserInvitationListener implements ShouldQueue
{
    public function handle(UserInvited $event): void
    {
        $invitedUser = $event->user;
        $inviter = $event->invitedBy;
        $accountName = (string) ($invitedUser->account?->name ?? 'CEBX Gateway');

        $subject = 'Account Invitation - ' . $accountName;
        $message = "Hello {$invitedUser->name},\n\n"
            . "{$inviter->name} has invited you to join {$accountName}.\n"
            . "Please sign in to your portal account to continue.\n\n"
            . "Regards,\nCEBX Gateway";

        Mail::raw($message, function ($mail) use ($invitedUser, $subject): void {
            $mail->to($invitedUser->email)->subject($subject);
        });

        Log::info('User invitation sent', [
            'user_id'    => $invitedUser->id,
            'email'      => $invitedUser->email,
            'invited_by' => $inviter->id,
            'account_id' => $invitedUser->account_id,
            'channel'    => 'email',
            'status'     => 'queued_or_sent',
        ]);
    }
}
