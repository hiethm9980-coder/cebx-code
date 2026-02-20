@extends('layouts.app')
@section('title', 'الإعدادات')

@section('content')
<h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0 0 24px">⚙️ الإعدادات</h1>

<div class="grid-2-1">
    <div>
        @if($portalType === 'b2b')
            {{-- ═══ B2B: ORGANIZATION INFO ═══ --}}
            <x-card title="🏢 معلومات المنظمة">
                <form method="PUT" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <div class="grid-2">
                        <div style="margin-bottom:16px"><label class="form-label">اسم المنظمة</label><input type="text" name="org_name" class="form-input" value="{{ $account->name ?? '' }}" placeholder="شركة التقنية المتقدمة"></div>
                        <div style="margin-bottom:16px"><label class="form-label">السجل التجاري</label><input type="text" name="cr_number" class="form-input" value="{{ $account->cr_number ?? '' }}" placeholder="1010xxxxxx"></div>
                        <div style="margin-bottom:16px"><label class="form-label">الرقم الضريبي</label><input type="text" name="tax_number" class="form-input" value="{{ $account->tax_number ?? '' }}" placeholder="3xxxxxxxxxxxxxxx"></div>
                        <div style="margin-bottom:16px"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-input" value="{{ $account->email ?? '' }}" placeholder="info@company.sa"></div>
                        <div style="margin-bottom:16px"><label class="form-label">رقم الهاتف</label><input type="text" name="phone" class="form-input" value="{{ $account->phone ?? '' }}" placeholder="011xxxxxxx"></div>
                        <div style="margin-bottom:16px"><label class="form-label">المدينة</label><input type="text" name="city" class="form-input" value="{{ $account->city ?? '' }}" placeholder="الرياض"></div>
                    </div>
                    <button type="submit" class="btn btn-pr" style="margin-top:12px">حفظ التغييرات</button>
                </form>
            </x-card>

            {{-- B2B: API KEYS --}}
            <x-card title="🔑 مفاتيح API">
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>الاسم</th><th>المفتاح</th><th>الحالة</th><th>تاريخ الإنشاء</th><th></th></tr></thead>
                        <tbody>
                            @forelse($apiKeys ?? [] as $key)
                                <tr>
                                    <td>{{ $key->name }}</td>
                                    <td class="td-mono">{{ Str::mask($key->key ?? '', '*', 8) }}</td>
                                    <td><span style="color:var(--ac)">● نشط</span></td>
                                    <td>{{ $key->created_at->format('d/m/Y') }}</td>
                                    <td><button class="btn btn-dg btn-sm">إبطال</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty-state">لا توجد مفاتيح</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-pr btn-sm" style="margin-top:12px">+ إنشاء مفتاح جديد</button>
            </x-card>

            {{-- B2B: WEBHOOKS --}}
            <x-card title="🔗 Webhooks">
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <div style="margin-bottom:16px"><label class="form-label">Webhook URL</label><input type="url" name="webhook_url" placeholder="https://your-domain.com/webhook" class="form-input" value="{{ $account->webhook_url ?? '' }}"></div>
                    <div style="font-size:13px;color:var(--tm);margin-bottom:12px">الأحداث:</div>
                    <div class="grid-2" style="gap:8px">
                        @foreach(['shipment.created', 'shipment.updated', 'shipment.delivered', 'shipment.cancelled', 'order.created', 'wallet.charged'] as $event)
                            <label style="display:flex;align-items:center;gap:8px;color:var(--tm);font-size:12px;cursor:pointer">
                                <input type="checkbox" name="webhook_events[]" value="{{ $event }}" checked>
                                <code style="background:var(--sf);padding:2px 6px;border-radius:4px">{{ $event }}</code>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-pr" style="margin-top:16px">حفظ</button>
                </form>
            </x-card>
        @else
            {{-- ═══ B2C: PROFILE ═══ --}}
            <x-card title="👤 الملف الشخصي">
                <form method="PUT" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <div style="display:flex;gap:20px;align-items:center;margin-bottom:24px">
                        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#0D9488,#065F56);display:flex;align-items:center;justify-content:center;font-size:32px;color:#fff;font-weight:700">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div>
                            <div style="font-weight:600;color:var(--tx);font-size:16px">{{ auth()->user()->name }}</div>
                            <div style="font-size:13px;color:var(--td);margin-top:4px">عضو منذ {{ auth()->user()->created_at->format('F Y') }}</div>
                            <button type="button" class="btn btn-s" style="margin-top:8px">📷 تغيير الصورة</button>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div style="margin-bottom:16px"><label class="form-label">الاسم الأول</label><input type="text" name="first_name" class="form-input" value="{{ auth()->user()->first_name ?? '' }}"></div>
                        <div style="margin-bottom:16px"><label class="form-label">اسم العائلة</label><input type="text" name="last_name" class="form-input" value="{{ auth()->user()->last_name ?? '' }}"></div>
                        <div style="margin-bottom:16px"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-input" value="{{ auth()->user()->email }}"></div>
                        <div style="margin-bottom:16px"><label class="form-label">رقم الهاتف</label><input type="text" name="phone" class="form-input" value="{{ auth()->user()->phone ?? '' }}"></div>
                    </div>
                    <button type="submit" class="btn btn-pr" style="margin-top:8px;background:#0D9488">حفظ التغييرات</button>
                </form>
            </x-card>

            {{-- B2C: PASSWORD --}}
            <x-card title="🔒 تغيير كلمة المرور">
                <form method="POST" action="{{ route('settings.password') }}">
                    @csrf
                    <div style="margin-bottom:16px"><label class="form-label">كلمة المرور الحالية</label><input type="password" name="current_password" placeholder="••••••••" class="form-input"></div>
                    <div class="grid-2">
                        <div style="margin-bottom:16px"><label class="form-label">كلمة المرور الجديدة</label><input type="password" name="password" placeholder="••••••••" class="form-input"></div>
                        <div style="margin-bottom:16px"><label class="form-label">تأكيد كلمة المرور</label><input type="password" name="password_confirmation" placeholder="••••••••" class="form-input"></div>
                    </div>
                    <button type="submit" class="btn btn-pr" style="background:#0D9488">تحديث كلمة المرور</button>
                </form>
            </x-card>
        @endif

        {{-- ═══ NOTIFICATIONS (BOTH) ═══ --}}
        <x-card title="🔔 الإشعارات">
            @foreach([
                ['إشعارات البريد الإلكتروني', 'تلقي تحديثات الشحنات عبر البريد', 'email_notifications', true],
                ['إشعارات SMS', 'رسائل نصية عند تغير حالة الشحنة', 'sms_notifications', true],
                ['إشعارات التطبيق', 'إشعارات فورية داخل التطبيق', 'push_notifications', false],
            ] as $notif)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)">
                    <div>
                        <div style="font-size:14px;color:var(--tx)">{{ $notif[0] }}</div>
                        <div style="font-size:12px;color:var(--td);margin-top:2px">{{ $notif[1] }}</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="{{ $notif[2] }}" {{ $notif[3] ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            @endforeach
        </x-card>
    </div>

    <div>
        {{-- ═══ ACCOUNT INFO ═══ --}}
        <x-card title="📋 معلومات الحساب">
            @foreach([
                [$portalType === 'b2b' ? 'Account Slug' : 'نوع الحساب', $portalType === 'b2b' ? ($account->slug ?? '—') : 'B2C — أفراد'],
                ['نوع الحساب', $portalType === 'b2b' ? 'B2B — أعمال' : 'B2C — أفراد'],
                [$portalType === 'b2b' ? 'الباقة' : 'تاريخ التسجيل', $portalType === 'b2b' ? ($account->plan ?? 'Professional') : auth()->user()->created_at->format('d/m/Y')],
                ['إجمالي الشحنات', \App\Models\Shipment::count()],
                ['حالة الحساب', 'نشط ✅'],
            ] as $row)
                <x-info-row :label="$row[0]" :value="(string)$row[1]" />
            @endforeach
        </x-card>

        @if($portalType === 'b2c')
            {{-- B2C: ACTIVE SESSIONS --}}
            <x-card title="📱 الجلسات النشطة">
                @foreach($sessions ?? [['device' => 'Chrome — Windows', 'location' => 'الرياض', 'current' => true], ['device' => 'Safari — iPhone', 'location' => 'الرياض', 'current' => false]] as $session)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid rgba(31,42,64,0.1)">
                        <div>
                            <div style="font-size:13px;color:var(--tx)">{{ $session['device'] }}</div>
                            <div style="font-size:11px;color:var(--td)">📍 {{ $session['location'] }}</div>
                        </div>
                        @if($session['current'])
                            <span style="font-size:11px;color:#0D9488">الجلسة الحالية</span>
                        @else
                            <button class="btn btn-dg btn-sm">إنهاء</button>
                        @endif
                    </div>
                @endforeach
            </x-card>
        @endif

        {{-- ═══ DANGER ZONE ═══ --}}
        <x-card title="⚠️ منطقة الخطر">
            <form method="POST" action="#" style="margin-bottom:8px">
                @csrf
                <button type="submit" class="btn btn-dg" style="width:100%" onclick="return confirm('هل أنت متأكد؟')">تعطيل الحساب</button>
            </form>
            <button class="btn btn-dg" style="width:100%;opacity:0.5" disabled>حذف الحساب نهائياً</button>
        </x-card>
    </div>
</div>
@endsection
