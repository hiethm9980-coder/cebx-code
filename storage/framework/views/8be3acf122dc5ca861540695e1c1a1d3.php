<?php $__env->startSection('title', 'الأدوار والصلاحيات'); ?>

<?php $__env->startSection('content'); ?>
<h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0 0 24px">🔐 الأدوار والصلاحيات</h1>

<?php
$permissions = ['عرض الشحنات','إنشاء شحنة','إلغاء شحنة','عرض الطلبات','إدارة المتاجر','عرض المحفظة','شحن الرصيد','عرض التقارير','إدارة المستخدمين','إدارة الأدوار','الإعدادات'];
$roles = [
    ['name' => 'مدير',   'perms' => [1,1,1,1,1,1,1,1,1,1,1]],
    ['name' => 'مشرف',   'perms' => [1,1,1,1,1,1,1,1,0,0,0]],
    ['name' => 'مشغّل',  'perms' => [1,1,1,1,0,0,0,0,0,0,0]],
    ['name' => 'مُطلع',  'perms' => [1,0,0,1,0,0,0,1,0,0,0]],
];
?>

<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th style="font-weight:700;color:var(--tx);font-size:13px;position:sticky;right:0;background:#fff">الصلاحية</th>
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <th style="text-align:center;color:var(--pr);font-weight:700;font-size:13px"><?php echo e($role['name']); ?></th>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tr></thead>
            <tbody>
                <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pi => $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr style="background:<?php echo e($pi % 2 === 0 ? '#FAFBFE' : '#fff'); ?>">
                        <td style="padding:12px;font-size:13px;font-weight:500"><?php echo e($perm); ?></td>
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <td style="text-align:center;padding:12px">
                                <span style="font-size:18px"><?php echo e($role['perms'][$pi] ? '✅' : '❌'); ?></span>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/roles/index.blade.php ENDPATH**/ ?>