<?php $__env->startSection('title', 'دخول الأعمال — B2B'); ?>

<?php $__env->startSection('portal-styles'); ?>
    .form-group input:focus { border-color: #3B82F6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('brand-bg', 'background: linear-gradient(160deg, #1E3A5F 0%, #1E40AF 40%, #3B82F6 100%)'); ?>

<?php $__env->startSection('brand-content'); ?>
    <div class="brand-logo" style="background:linear-gradient(135deg,#3B82F6,#1D4ED8);box-shadow:0 8px 32px rgba(59,130,246,0.4)">B2B</div>
    <span class="brand-badge" style="background:rgba(255,255,255,0.15);color:#93C5FD">BUSINESS PORTAL</span>
    <h2 class="brand-title">بوابة الأعمال</h2>
    <p class="brand-desc">منصة متكاملة لإدارة شحنات شركتك — ربط المتاجر، إدارة الفريق، والتقارير التحليلية في مكان واحد.</p>
    <ul class="brand-features">
        <li><span>📦</span> إدارة الشحنات والتتبع المباشر</li>
        <li><span>🏪</span> ربط المتاجر الإلكترونية (سلة، زد، Shopify)</li>
        <li><span>👥</span> إدارة فريق العمل والأدوار</li>
        <li><span>📊</span> تقارير وتحليلات متقدمة</li>
        <li><span>💰</span> المحفظة الإلكترونية والفوترة</li>
    </ul>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('form-title', 'دخول الأعمال'); ?>
<?php $__env->startSection('form-subtitle', 'سجّل دخولك بحساب شركتك في بوابة B2B'); ?>
<?php $__env->startSection('form-action', route('b2b.login.submit')); ?>
<?php $__env->startSection('email-placeholder', 'you@company.sa'); ?>
<?php $__env->startSection('input-focus-style', ''); ?>
<?php $__env->startSection('link-color', 'color:#3B82F6'); ?>
<?php $__env->startSection('btn-style', 'background:linear-gradient(135deg,#3B82F6,#1D4ED8);box-shadow:0 4px 16px rgba(59,130,246,0.4)'); ?>
<?php $__env->startSection('btn-text', '🏢 دخول بوابة الأعمال'); ?>

<?php $__env->startSection('demo-credentials'); ?>
<div class="demo-credentials">
    <div class="demo-title">🔑 بيانات تجريبية</div>
    <div class="demo-row"><span>البريد:</span> <code>sultan@techco.sa</code></div>
    <div class="demo-row"><span>كلمة المرور:</span> <code>password</code></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/auth/login-b2b.blade.php ENDPATH**/ ?>