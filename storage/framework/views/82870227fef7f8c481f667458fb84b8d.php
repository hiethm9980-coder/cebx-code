<?php $__env->startSection('title', 'المستخدمين'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'المستخدمين','subtitle' => $users->total() . ' مستخدم']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'المستخدمين','subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($users->total() . ' مستخدم')]); ?>
    <button class="btn btn-pr" data-modal-open="create-user">+ إضافة</button>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<div class="table-wrap"><table>
    <thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th>إجراء</th></tr></thead>
    <tbody>
        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><div style="display:flex;align-items:center;gap:8px"><div class="user-avatar"><?php echo e(mb_substr($u->name, 0, 1)); ?></div><span style="font-weight:600"><?php echo e($u->name); ?></span></div></td>
                <td><?php echo e($u->email); ?></td>
                <td><span class="badge badge-pp"><?php echo e($u->roles->first()?->name ?? '—'); ?></span></td>
                <td><?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $u->status ?? 'active']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($u->status ?? 'active')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?></td>
                <td><?php echo e($u->last_login_at?->format('Y-m-d H:i') ?? '—'); ?></td>
                <td class="td-actions">
                    <form action="<?php echo e(route('users.toggle', $u)); ?>" method="POST"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <button class="btn <?php echo e(($u->status ?? 'active') === 'active' ? 'btn-wn' : 'btn-ac'); ?>"><?php echo e(($u->status ?? 'active') === 'active' ? 'تعطيل' : 'تفعيل'); ?></button>
                    </form>
                    <form action="<?php echo e(route('users.destroy', $u)); ?>" method="POST" data-confirm="حذف المستخدم؟"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?> <button class="btn btn-dg">🗑</button></form>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table></div>
<div style="margin-top:14px"><?php echo e($users->links()); ?></div>

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'create-user','title' => 'إضافة مستخدم']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'create-user','title' => 'إضافة مستخدم']); ?>
    <form method="POST" action="<?php echo e(route('users.store')); ?>"><?php echo csrf_field(); ?>
        <div class="form-grid">
            <div class="form-group"><label class="form-label">الاسم *</label><input name="name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">البريد *</label><input name="email" type="email" class="form-control" required></div>
            <div class="form-group"><label class="form-label">كلمة المرور *</label><input name="password" type="password" class="form-control" required></div>
            <div class="form-group"><label class="form-label">الدور</label>
                <select name="role" class="form-control"><?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($r->id); ?>"><?php echo e($r->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
            </div>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:10px">إضافة</button>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\hamzah\Documents\shipping-gateway-blade\resources\views/pages/users/index.blade.php ENDPATH**/ ?>