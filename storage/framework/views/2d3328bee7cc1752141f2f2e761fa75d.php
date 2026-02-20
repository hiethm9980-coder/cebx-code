<div class="card">
    <div class="card-title">إعدادات الحساب</div>
    <form method="POST" action="<?php echo e(route('settings.update')); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <div class="form-grid">
            <div class="form-group"><label class="form-label">اسم الشركة</label><input name="company_name" class="form-control" value="<?php echo e(auth()->user()->account?->name ?? ''); ?>"></div>
            <div class="form-group"><label class="form-label">البريد</label><input name="email" type="email" class="form-control" value="<?php echo e(auth()->user()->email); ?>"></div>
            <div class="form-group"><label class="form-label">الدولة</label>
                <select name="country" class="form-control"><option value="sa">السعودية</option><option value="ae">الإمارات</option><option value="kw">الكويت</option></select>
            </div>
            <div class="form-group"><label class="form-label">العملة</label>
                <select name="currency" class="form-control"><option value="sar">SAR ريال</option><option value="usd">USD دولار</option></select>
            </div>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:12px">💾 حفظ الإعدادات</button>
    </form>
</div>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/components/settings-form.blade.php ENDPATH**/ ?>