<form method="POST" action="<?php echo e(route('roles.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="form-group">
        <label class="form-label">اسم الدور *</label>
        <input type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required maxlength="100" placeholder="مثال: مدير الفرع">
        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <button type="submit" class="btn btn-pr" style="margin-top:12px">إنشاء الدور</button>
</form>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/roles/partials/create-form.blade.php ENDPATH**/ ?>