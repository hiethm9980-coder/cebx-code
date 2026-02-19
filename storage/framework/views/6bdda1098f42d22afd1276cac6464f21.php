<?php $__env->startSection('title', 'تسجيل الدخول'); ?>
<?php $__env->startSection('content'); ?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">SG</div>
        <h1 style="color:var(--tx);font-size:20px;font-weight:700;margin:0 0 4px">بوابة إدارة الشحن</h1>
        <p style="color:var(--td);font-size:11px;margin-bottom:24px">Shipping Gateway Platform</p>
        <form method="POST" action="<?php echo e(route('login')); ?>" style="text-align:right">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" required autofocus>
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span style="color:var(--dg);font-size:10px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="form-group">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span style="color:var(--dg);font-size:10px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <button type="submit" class="btn btn-pr btn-block btn-lg" style="margin-top:10px">تسجيل الدخول</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\hamzah\Documents\shipping-gateway-blade\resources\views/pages/auth/login.blade.php ENDPATH**/ ?>