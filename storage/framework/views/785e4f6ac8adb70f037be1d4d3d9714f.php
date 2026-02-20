<?php $__env->startSection('title', 'دخول الإدارة — Admin'); ?>

<?php $__env->startSection('portal-styles'); ?>
    .form-group input:focus { border-color: #7C3AED; box-shadow: 0 0 0 4px rgba(124,58,237,0.1); }
<?php $__env->stopSection(); ?>

<?php $__env->startSection('brand-bg', 'background: linear-gradient(160deg, #2E1065 0%, #4C1D95 40%, #7C3AED 100%)'); ?>

<?php $__env->startSection('brand-content'); ?>
    <div class="brand-logo" style="background:linear-gradient(135deg,#7C3AED,#4C1D95);box-shadow:0 8px 32px rgba(124,58,237,0.4)">SYS</div>
    <span class="brand-badge" style="background:rgba(255,255,255,0.15);color:#C4B5FD">SYSTEM ADMIN</span>
    <h2 class="brand-title">لوحة الإدارة</h2>
    <p class="brand-desc">التحكم الكامل بالنظام — إدارة المنظمات، اللوجستيات، الامتثال، التسعير، والتدقيق.</p>
    <ul class="brand-features">
        <li><span>🏢</span> إدارة المنظمات والحسابات</li>
        <li><span>🚢</span> اللوجستيات: سفن، حاويات، جمارك</li>
        <li><span>🪪</span> الامتثال: KYC، بضائع خطرة، مخاطر</li>
        <li><span>🏷️</span> التسعير وقواعد الشحن</li>
        <li><span>📜</span> سجل التدقيق والمراجعة</li>
    </ul>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('form-title', 'دخول الإدارة'); ?>
<?php $__env->startSection('form-subtitle', 'سجّل دخولك بحساب المسؤول لإدارة النظام'); ?>
<?php $__env->startSection('form-action', route('admin.login.submit')); ?>
<?php $__env->startSection('email-placeholder', 'admin@system.sa'); ?>
<?php $__env->startSection('input-focus-style', ''); ?>
<?php $__env->startSection('link-color', 'color:#7C3AED'); ?>
<?php $__env->startSection('btn-style', 'background:linear-gradient(135deg,#7C3AED,#4C1D95);box-shadow:0 4px 16px rgba(124,58,237,0.4)'); ?>
<?php $__env->startSection('btn-text', '🛡️ دخول لوحة الإدارة'); ?>

<?php $__env->startSection('demo-credentials'); ?>
<div class="demo-credentials">
    <div class="demo-title">🔑 بيانات تجريبية</div>
    <div class="demo-row"><span>البريد:</span> <code>admin@system.sa</code></div>
    <div class="demo-row"><span>كلمة المرور:</span> <code>admin</code></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/auth/login-admin.blade.php ENDPATH**/ ?>