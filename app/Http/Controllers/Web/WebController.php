<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

abstract class WebController extends Controller
{
    protected function statusBadge(string $status): string
    {
        $labels = [
            'processing' => 'قيد المعالجة', 'shipped' => 'تم الشحن', 'in_transit' => 'في الطريق',
            'delivered' => 'تم التسليم', 'cancelled' => 'ملغي', 'pending' => 'معلّق',
            'fulfilled' => 'مكتمل', 'active' => 'نشط', 'suspended' => 'معطّل',
            'open' => 'مفتوح', 'resolved' => 'محلول', 'cleared' => 'مخلّص',
            'held' => 'محتجز', 'loading' => 'تحميل', 'sealed' => 'مختوم',
            'onduty' => 'في الخدمة', 'available' => 'متاح', 'offduty' => 'خارج الخدمة',
        ];
        $label = $labels[$status] ?? $status;
        return '<span class="badge st-' . $status . '">' . e($label) . '</span>';
    }
}
