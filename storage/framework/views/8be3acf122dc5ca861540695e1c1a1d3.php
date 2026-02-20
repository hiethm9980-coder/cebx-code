<?php $__env->startSection('title', 'الأدوار والصلاحيات'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🔐 الأدوار والصلاحيات</h1>
    <button class="btn btn-pr" data-modal-open="create-role">+ إنشاء دور</button>
</div>


<div class="grid-4" style="margin-bottom:24px">
    <?php
        $roleConfig = [
            ['name' => 'مدير', 'icon' => '👑', 'desc' => 'صلاحيات كاملة', 'color' => '#3B82F6'],
            ['name' => 'مشرف', 'icon' => '⭐', 'desc' => 'إدارة الشحنات والطلبات', 'color' => '#8B5CF6'],
            ['name' => 'مشغّل', 'icon' => '⚙️', 'desc' => 'إنشاء ومتابعة الشحنات', 'color' => '#10B981'],
            ['name' => 'مُطلع', 'icon' => '👁️', 'desc' => 'عرض فقط', 'color' => '#64748B'],
        ];
    ?>
    <?php $__currentLoopData = $roles ?? $roleConfig; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $rc = $roleConfig[$i] ?? $roleConfig[0]; ?>
        <div class="entity-card" style="border-top:3px solid <?php echo e($rc['color']); ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <span style="font-size:28px"><?php echo e($rc['icon']); ?></span>
                <span style="background:<?php echo e($rc['color']); ?>22;color:<?php echo e($rc['color']); ?>;padding:3px 10px;border-radius:12px;font-size:12px">
                    <?php echo e(is_array($role) ? ($role['users_count'] ?? 0) : ($role->users_count ?? 0)); ?> مستخدم
                </span>
            </div>
            <div style="font-weight:700;color:var(--tx);font-size:16px;margin-bottom:4px"><?php echo e(is_array($role) ? $role['name'] : $role->name); ?></div>
            <div style="font-size:12px;color:var(--td)"><?php echo e($rc['desc']); ?></div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'مصفوفة الصلاحيات']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'مصفوفة الصلاحيات']); ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:right">الصلاحية</th>
                    <th style="text-align:center">👑 مدير</th>
                    <th style="text-align:center">⭐ مشرف</th>
                    <th style="text-align:center">⚙️ مشغّل</th>
                    <th style="text-align:center">👁️ مُطلع</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = [
                    ['عرض الشحنات', [1,1,1,1]],
                    ['إنشاء شحنة', [1,1,1,0]],
                    ['إلغاء شحنة', [1,1,0,0]],
                    ['إدارة الطلبات', [1,1,1,0]],
                    ['ربط المتاجر', [1,1,0,0]],
                    ['عرض المحفظة', [1,1,1,1]],
                    ['شحن الرصيد', [1,1,0,0]],
                    ['عرض التقارير', [1,1,1,1]],
                    ['إدارة المستخدمين', [1,0,0,0]],
                    ['إدارة الأدوار', [1,0,0,0]],
                    ['إعدادات المنظمة', [1,0,0,0]],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td style="font-size:13px;color:var(--tx)"><?php echo e($perm[0]); ?></td>
                        <?php $__currentLoopData = $perm[1]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td style="text-align:center">
                                <?php if($val): ?>
                                    <span style="color:var(--ac);font-size:18px">✓</span>
                                <?php else: ?>
                                    <span style="color:var(--bd);font-size:18px">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
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

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'create-role','title' => 'إنشاء دور جديد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'create-role','title' => 'إنشاء دور جديد']); ?>
    <form method="POST" action="<?php echo e(route('roles.store')); ?>">
        <?php echo csrf_field(); ?>
        <div style="margin-bottom:16px"><label class="form-label">اسم الدور</label><input type="text" name="name" placeholder="مثال: محاسب" class="form-input" required></div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr">إنشاء</button>
        </div>
    </form>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/roles/index.blade.php ENDPATH**/ ?>