<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'تسجيل الدخول'); ?> — Shipping Gateway</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
</head>
<body>
    <?php echo $__env->yieldContent('content'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\resources\views/layouts/auth.blade.php ENDPATH**/ ?>