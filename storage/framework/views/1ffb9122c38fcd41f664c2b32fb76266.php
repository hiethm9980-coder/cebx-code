<form method="POST" action="<?php echo e(route('support.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="form-group">
        <label class="form-label">الموضوع *</label>
        <input type="text" name="subject" class="form-control" value="<?php echo e(old('subject')); ?>" required placeholder="موضوع التذكرة">
        <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="form-group">
        <label class="form-label">الأولوية</label>
        <select name="priority" class="form-control">
            <option value="low" <?php echo e(old('priority', 'medium') === 'low' ? 'selected' : ''); ?>>منخفض</option>
            <option value="medium" <?php echo e(old('priority', 'medium') === 'medium' ? 'selected' : ''); ?>>متوسط</option>
            <option value="high" <?php echo e(old('priority') === 'high' ? 'selected' : ''); ?>>عالي</option>
        </select>
    </div>
    <button type="submit" class="btn btn-pr" style="margin-top:12px">إنشاء تذكرة</button>
</form>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/support/partials/create-form.blade.php ENDPATH**/ ?>