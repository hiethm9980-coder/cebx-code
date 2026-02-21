<?php $__env->startSection('title', 'دخول الأفراد — B2C'); ?>

<?php $__env->startSection('portal-styles'); ?>
    .form-group input:focus { border-color: #0D9488; box-shadow: 0 0 0 4px rgba(13,148,136,0.1); }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('brand-bg', 'background: linear-gradient(160deg, #134E4A 0%, #0F766E 40%, #0D9488 100%)'); ?>

<?php $__env->startSection('brand-content'); ?>
    <div class="brand-logo" style="background:linear-gradient(135deg,#0D9488,#065F56);box-shadow:0 8px 32px rgba(13,148,136,0.4)">B2C</div>
    <span class="brand-badge" style="background:rgba(255,255,255,0.15);color:#5EEAD4">PERSONAL SHIPPING</span>
    <h2 class="brand-title">بوابة الأفراد</h2>
    <p class="brand-desc">أرسل واستلم شحناتك الشخصية بكل سهولة — تتبع مباشر، دفتر عناوين، ومحفظة إلكترونية.</p>
    <ul class="brand-features">
        <li><span>📦</span> إنشاء شحنات وتتبعها بسهولة</li>
        <li><span>🔍</span> تتبع لحظي بالوقت الفعلي</li>
        <li><span>📒</span> دفتر عناوين محفوظ</li>
        <li><span>💳</span> محفظة إلكترونية سريعة</li>
        <li><span>🎧</span> دعم فني على مدار الساعة</li>
    </ul>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('form-title', 'دخول الأفراد'); ?>
<?php $__env->startSection('form-subtitle', 'سجّل دخولك بحسابك الشخصي في بوابة الأفراد'); ?>
<?php $__env->startSection('form-action', route('b2c.login.submit')); ?>
<?php $__env->startSection('email-placeholder', 'you@example.sa'); ?>
<?php $__env->startSection('input-focus-style', ''); ?>
<?php $__env->startSection('link-color', 'color:#0D9488'); ?>
<?php $__env->startSection('btn-style', 'background:linear-gradient(135deg,#0D9488,#065F56);box-shadow:0 4px 16px rgba(13,148,136,0.4)'); ?>
<?php $__env->startSection('btn-text', '👤 دخول بوابة الأفراد'); ?>

<?php $__env->startSection('demo-credentials'); ?>
<div class="demo-credentials">
    <div class="demo-title">🔑 بيانات تجريبية</div>
    <div class="demo-row"><span>البريد:</span> <code>mohammed@example.sa</code></div>
    <div class="demo-row"><span>كلمة المرور:</span> <code>password</code></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/auth/login-b2c.blade.php ENDPATH**/ ?>