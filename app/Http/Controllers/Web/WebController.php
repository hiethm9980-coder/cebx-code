<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

/**
 * Base Web Controller
 *
 * Detects B2C/B2B/Admin portal type from the authenticated user's account
 * and shares it with all Blade views.
 */
class WebController extends Controller
{
    protected string $portalType;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Determine portal type from user's account
            $user = auth()->user();
            $this->portalType = 'b2b'; // default

            if ($user && $user->account) {
                $this->portalType = match ($user->account->type) {
                    'individual' => 'b2c',
                    'admin'      => 'admin',
                    default      => 'b2b',
                };
            }

            // Also support role-based admin detection
            if ($user && ($user->is_super_admin || $user->role === 'admin')) {
                $this->portalType = 'admin';
            }

            // Share with all views
            View::share('portalType', $this->portalType);

            return $next($request);
        });
    }

    /**
     * Helper: Convert shipment status to Arabic badge HTML
     */
    protected function statusBadge(string $status): string
    {
        $map = [
            'pending'          => ['قيد الانتظار', 'st-pending'],
            'processing'       => ['قيد المعالجة', 'st-processing'],
            'ready'            => ['جاهز', 'badge-ac'],
            'shipped'          => ['تم الشحن', 'st-shipped'],
            'in_transit'       => ['قيد الشحن', 'st-intransit'],
            'out_for_delivery' => ['خرج للتوصيل', 'st-shipped'],
            'delivered'        => ['تم التسليم', 'st-delivered'],
            'cancelled'        => ['ملغي', 'st-cancelled'],
            'returned'         => ['مرتجع', 'st-cancelled'],
            'draft'            => ['مسودة', 'badge-td'],
            'active'           => ['نشط', 'st-active'],
            'open'             => ['مفتوحة', 'st-open'],
            'closed'           => ['مغلقة', 'badge-td'],
            'resolved'         => ['تم الحل', 'st-resolved'],
            'connected'        => ['متصل', 'st-connected'],
            'disconnected'     => ['غير متصل', 'st-cancelled'],
            'accepted'         => ['مقبولة', 'st-accepted'],
            'expired'          => ['منتهية', 'st-expired'],
            'failed'           => ['فشل', 'badge-dg'],
        ];

        $s = $map[$status] ?? [$status, 'badge-td'];
        return '<span class="badge ' . $s[1] . '">' . e($s[0]) . '</span>';
    }
}
