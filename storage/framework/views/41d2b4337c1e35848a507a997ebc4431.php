<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'تسجيل الدخول'); ?> — Shipping Gateway</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">
    <?php echo $__env->make('components.pwa-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <meta name="pwa-sw-url" content="<?php echo e(asset('sw.js')); ?>">
    <?php if (! empty(trim($__env->yieldContent('portal-styles')))): ?>
    <style><?php echo $__env->yieldContent('portal-styles'); ?></style>
    <?php endif; ?>
</head>
<body>
<div class="login-page">
    
    <div class="login-brand" style="<?php echo $__env->yieldContent('brand-bg', 'background:#1E3A5F'); ?>">
        <?php echo $__env->yieldContent('brand-content'); ?>
    </div>

    
    <div class="login-form-panel">
        <div class="login-form-card">
            <h1 class="login-form-title"><?php echo $__env->yieldContent('form-title', 'تسجيل الدخول'); ?></h1>
            <p class="login-form-subtitle"><?php echo $__env->yieldContent('form-subtitle', 'أدخل بياناتك للدخول'); ?></p>

            <?php if($errors->any()): ?>
                <div class="login-errors">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo $__env->yieldContent('form-action'); ?>" class="login-form">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" value="<?php echo e(old('email')); ?>" placeholder="<?php echo $__env->yieldContent('email-placeholder', 'you@company.sa'); ?>" class="form-input" required autofocus style="<?php echo $__env->yieldContent('input-focus-style'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" placeholder="••••••••" class="form-input" required>
                </div>
                <div class="login-form-options">
                    <label class="login-remember">
                        <input type="checkbox" name="remember"> تذكرني
                    </label>
                </div>
                <button type="submit" class="login-submit-btn" style="<?php echo $__env->yieldContent('btn-style', 'background:var(--pr);color:#fff'); ?>">
                    <?php echo $__env->yieldContent('btn-text', 'دخول'); ?>
                </button>
            </form>

            <div class="back-link" style="<?php echo $__env->yieldContent('link-color'); ?>">
                <a href="<?php echo e(route('login')); ?>">← العودة لاختيار البوابة</a>
            </div>

            <?php echo $__env->yieldContent('demo-credentials'); ?>
        </div>
    </div>
</div>

<?php echo $__env->yieldContent('content'); ?>

<style>
.login-page {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
    font-family: 'Tajawal', sans-serif;
    direction: rtl;
}
@media (max-width: 900px) {
    .login-page { grid-template-columns: 1fr; }
    .login-brand { min-height: 220px; padding: 32px 24px !important; }
}
.login-brand {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 60px 48px;
    color: #fff;
    text-align: center;
}
.login-brand .brand-logo {
    width: 72px; height: 72px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 24px;
    margin-bottom: 12px;
}
.login-brand .brand-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    margin-bottom: 16px;
}
.login-brand .brand-title { font-size: 22px; font-weight: 800; margin: 0 0 8px; }
.login-brand .brand-desc { font-size: 14px; opacity: .9; line-height: 1.6; margin: 0 0 24px; max-width: 320px; }
.login-brand .brand-features { list-style: none; margin: 0; padding: 0; text-align: right; }
.login-brand .brand-features li { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 13px; opacity: .95; }
.login-brand .brand-features li span { font-size: 18px; }
.login-form-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px;
    background: var(--bg, #F8FAFC);
}
.login-form-card {
    width: 100%;
    max-width: 400px;
    background: #fff;
    border-radius: 16px;
    padding: 36px 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
    border: 1px solid var(--bd, #E2E8F0);
}
.login-form-title { font-size: 22px; font-weight: 800; color: var(--tx,#1E293B); margin: 0 0 6px; }
.login-form-subtitle { font-size: 14px; color: var(--td,#64748B); margin: 0 0 24px; }
.login-errors {
    padding: 12px 14px;
    background: #FEE2E2;
    border: 1px solid #FECACA;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #991B1B;
}
.login-form .form-group { margin-bottom: 18px; }
.login-form-options { margin-bottom: 20px; }
.login-remember { font-size: 13px; color: var(--td); cursor: pointer; display: flex; align-items: center; gap: 8px; }
.login-submit-btn {
    width: 100%;
    padding: 14px 20px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: transform .15s, box-shadow .15s;
}
.login-submit-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.back-link { margin-top: 20px; text-align: center; font-size: 13px; }
.back-link a { text-decoration: none; font-weight: 600; }
.back-link a:hover { text-decoration: underline; }
.demo-credentials {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--bd,#E2E8F0);
}
.demo-title { font-size: 12px; font-weight: 700; color: var(--td); margin-bottom: 10px; }
.demo-row { font-size: 13px; color: var(--tx); margin-bottom: 4px; }
.demo-row code { background: var(--sf,#F1F5F9); padding: 2px 8px; border-radius: 6px; font-size: 12px; }
</style>

<script>window.PWA={swUrl:'<?php echo e(asset("sw.js")); ?>',scope:'<?php echo e(rtrim(url("/"), "/")); ?>/'};</script>
<script src="<?php echo e(asset('js/pwa.js')); ?>" defer></script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/layouts/auth.blade.php ENDPATH**/ ?>