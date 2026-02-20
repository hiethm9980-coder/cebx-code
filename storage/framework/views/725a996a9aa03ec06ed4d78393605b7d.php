<?php $__env->startSection('title', 'الإعدادات'); ?>

<?php $__env->startSection('content'); ?>
<h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0 0 24px">⚙️ الإعدادات</h1>

<div class="grid-2-1">
    <div>
        <?php if($portalType === 'b2b'): ?>
            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🏢 معلومات المنظمة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🏢 معلومات المنظمة']); ?>
                <form method="PUT" action="<?php echo e(route('settings.update')); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <div class="grid-2">
                        <div style="margin-bottom:16px"><label class="form-label">اسم المنظمة</label><input type="text" name="org_name" class="form-input" value="<?php echo e($account->name ?? ''); ?>" placeholder="شركة التقنية المتقدمة"></div>
                        <div style="margin-bottom:16px"><label class="form-label">السجل التجاري</label><input type="text" name="cr_number" class="form-input" value="<?php echo e($account->cr_number ?? ''); ?>" placeholder="1010xxxxxx"></div>
                        <div style="margin-bottom:16px"><label class="form-label">الرقم الضريبي</label><input type="text" name="tax_number" class="form-input" value="<?php echo e($account->tax_number ?? ''); ?>" placeholder="3xxxxxxxxxxxxxxx"></div>
                        <div style="margin-bottom:16px"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-input" value="<?php echo e($account->email ?? ''); ?>" placeholder="info@company.sa"></div>
                        <div style="margin-bottom:16px"><label class="form-label">رقم الهاتف</label><input type="text" name="phone" class="form-input" value="<?php echo e($account->phone ?? ''); ?>" placeholder="011xxxxxxx"></div>
                        <div style="margin-bottom:16px"><label class="form-label">المدينة</label><input type="text" name="city" class="form-input" value="<?php echo e($account->city ?? ''); ?>" placeholder="الرياض"></div>
                    </div>
                    <button type="submit" class="btn btn-pr" style="margin-top:12px">حفظ التغييرات</button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🔑 مفاتيح API']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🔑 مفاتيح API']); ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>الاسم</th><th>المفتاح</th><th>الحالة</th><th>تاريخ الإنشاء</th><th></th></tr></thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $apiKeys ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($key->name); ?></td>
                                    <td class="td-mono"><?php echo e(Str::mask($key->key ?? '', '*', 8)); ?></td>
                                    <td><span style="color:var(--ac)">● نشط</span></td>
                                    <td><?php echo e($key->created_at->format('d/m/Y')); ?></td>
                                    <td><button class="btn btn-dg btn-sm">إبطال</button></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="5" class="empty-state">لا توجد مفاتيح</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-pr btn-sm" style="margin-top:12px">+ إنشاء مفتاح جديد</button>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🔗 Webhooks']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🔗 Webhooks']); ?>
                <form method="POST" action="<?php echo e(route('settings.update')); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <div style="margin-bottom:16px"><label class="form-label">Webhook URL</label><input type="url" name="webhook_url" placeholder="https://your-domain.com/webhook" class="form-input" value="<?php echo e($account->webhook_url ?? ''); ?>"></div>
                    <div style="font-size:13px;color:var(--tm);margin-bottom:12px">الأحداث:</div>
                    <div class="grid-2" style="gap:8px">
                        <?php $__currentLoopData = ['shipment.created', 'shipment.updated', 'shipment.delivered', 'shipment.cancelled', 'order.created', 'wallet.charged']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label style="display:flex;align-items:center;gap:8px;color:var(--tm);font-size:12px;cursor:pointer">
                                <input type="checkbox" name="webhook_events[]" value="<?php echo e($event); ?>" checked>
                                <code style="background:var(--sf);padding:2px 6px;border-radius:4px"><?php echo e($event); ?></code>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <button type="submit" class="btn btn-pr" style="margin-top:16px">حفظ</button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
        <?php else: ?>
            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '👤 الملف الشخصي']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '👤 الملف الشخصي']); ?>
                <form method="PUT" action="<?php echo e(route('settings.update')); ?>">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                    <div style="display:flex;gap:20px;align-items:center;margin-bottom:24px">
                        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#0D9488,#065F56);display:flex;align-items:center;justify-content:center;font-size:32px;color:#fff;font-weight:700">
                            <?php echo e(mb_substr(auth()->user()->name, 0, 1)); ?>

                        </div>
                        <div>
                            <div style="font-weight:600;color:var(--tx);font-size:16px"><?php echo e(auth()->user()->name); ?></div>
                            <div style="font-size:13px;color:var(--td);margin-top:4px">عضو منذ <?php echo e(auth()->user()->created_at->format('F Y')); ?></div>
                            <button type="button" class="btn btn-s" style="margin-top:8px">📷 تغيير الصورة</button>
                        </div>
                    </div>
                    <div class="grid-2">
                        <div style="margin-bottom:16px"><label class="form-label">الاسم الأول</label><input type="text" name="first_name" class="form-input" value="<?php echo e(auth()->user()->first_name ?? ''); ?>"></div>
                        <div style="margin-bottom:16px"><label class="form-label">اسم العائلة</label><input type="text" name="last_name" class="form-input" value="<?php echo e(auth()->user()->last_name ?? ''); ?>"></div>
                        <div style="margin-bottom:16px"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-input" value="<?php echo e(auth()->user()->email); ?>"></div>
                        <div style="margin-bottom:16px"><label class="form-label">رقم الهاتف</label><input type="text" name="phone" class="form-input" value="<?php echo e(auth()->user()->phone ?? ''); ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-pr" style="margin-top:8px;background:#0D9488">حفظ التغييرات</button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🔒 تغيير كلمة المرور']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🔒 تغيير كلمة المرور']); ?>
                <form method="POST" action="<?php echo e(route('settings.password')); ?>">
                    <?php echo csrf_field(); ?>
                    <div style="margin-bottom:16px"><label class="form-label">كلمة المرور الحالية</label><input type="password" name="current_password" placeholder="••••••••" class="form-input"></div>
                    <div class="grid-2">
                        <div style="margin-bottom:16px"><label class="form-label">كلمة المرور الجديدة</label><input type="password" name="password" placeholder="••••••••" class="form-input"></div>
                        <div style="margin-bottom:16px"><label class="form-label">تأكيد كلمة المرور</label><input type="password" name="password_confirmation" placeholder="••••••••" class="form-input"></div>
                    </div>
                    <button type="submit" class="btn btn-pr" style="background:#0D9488">تحديث كلمة المرور</button>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
        <?php endif; ?>

        
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🔔 الإشعارات']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🔔 الإشعارات']); ?>
            <?php $__currentLoopData = [
                ['إشعارات البريد الإلكتروني', 'تلقي تحديثات الشحنات عبر البريد', 'email_notifications', true],
                ['إشعارات SMS', 'رسائل نصية عند تغير حالة الشحنة', 'sms_notifications', true],
                ['إشعارات التطبيق', 'إشعارات فورية داخل التطبيق', 'push_notifications', false],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)">
                    <div>
                        <div style="font-size:14px;color:var(--tx)"><?php echo e($notif[0]); ?></div>
                        <div style="font-size:12px;color:var(--td);margin-top:2px"><?php echo e($notif[1]); ?></div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="<?php echo e($notif[2]); ?>" <?php echo e($notif[3] ? 'checked' : ''); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    </div>

    <div>
        
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📋 معلومات الحساب']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📋 معلومات الحساب']); ?>
            <?php $__currentLoopData = [
                [$portalType === 'b2b' ? 'Account Slug' : 'نوع الحساب', $portalType === 'b2b' ? ($account->slug ?? '—') : 'B2C — أفراد'],
                ['نوع الحساب', $portalType === 'b2b' ? 'B2B — أعمال' : 'B2C — أفراد'],
                [$portalType === 'b2b' ? 'الباقة' : 'تاريخ التسجيل', $portalType === 'b2b' ? ($account->plan ?? 'Professional') : auth()->user()->created_at->format('d/m/Y')],
                ['إجمالي الشحنات', \App\Models\Shipment::count()],
                ['حالة الحساب', 'نشط ✅'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => $row[0],'value' => (string)$row[1]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row[0]),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((string)$row[1])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $attributes = $__attributesOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__attributesOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $component = $__componentOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__componentOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>

        <?php if($portalType === 'b2c'): ?>
            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📱 الجلسات النشطة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📱 الجلسات النشطة']); ?>
                <?php $__currentLoopData = $sessions ?? [['device' => 'Chrome — Windows', 'location' => 'الرياض', 'current' => true], ['device' => 'Safari — iPhone', 'location' => 'الرياض', 'current' => false]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid rgba(31,42,64,0.1)">
                        <div>
                            <div style="font-size:13px;color:var(--tx)"><?php echo e($session['device']); ?></div>
                            <div style="font-size:11px;color:var(--td)">📍 <?php echo e($session['location']); ?></div>
                        </div>
                        <?php if($session['current']): ?>
                            <span style="font-size:11px;color:#0D9488">الجلسة الحالية</span>
                        <?php else: ?>
                            <button class="btn btn-dg btn-sm">إنهاء</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
        <?php endif; ?>

        
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '⚠️ منطقة الخطر']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '⚠️ منطقة الخطر']); ?>
            <form method="POST" action="#" style="margin-bottom:8px">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-dg" style="width:100%" onclick="return confirm('هل أنت متأكد؟')">تعطيل الحساب</button>
            </form>
            <button class="btn btn-dg" style="width:100%;opacity:0.5" disabled>حذف الحساب نهائياً</button>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/settings/index.blade.php ENDPATH**/ ?>