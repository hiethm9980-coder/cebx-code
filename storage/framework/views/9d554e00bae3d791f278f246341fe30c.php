<form method="POST" action="<?php echo e(route('organizations.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">الاسم القانوني *</label>
            <input type="text" name="legal_name" class="form-control" value="<?php echo e(old('legal_name')); ?>" required maxlength="300">
            <?php $__errorArgs = ['legal_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="form-label">الاسم التجاري</label>
            <input type="text" name="trade_name" class="form-control" value="<?php echo e(old('trade_name')); ?>" maxlength="300">
            <?php $__errorArgs = ['trade_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="form-label">رقم السجل التجاري</label>
            <input type="text" name="registration_number" class="form-control" value="<?php echo e(old('registration_number')); ?>" maxlength="100">
            <?php $__errorArgs = ['registration_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="form-label">الرقم الضريبي</label>
            <input type="text" name="tax_number" class="form-control" value="<?php echo e(old('tax_number')); ?>" maxlength="100">
            <?php $__errorArgs = ['tax_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="form-label">الدولة</label>
            <select name="country_code" class="form-control">
                <option value="SA" <?php echo e(old('country_code', 'SA') === 'SA' ? 'selected' : ''); ?>>السعودية</option>
                <option value="AE" <?php echo e(old('country_code') === 'AE' ? 'selected' : ''); ?>>الإمارات</option>
                <option value="EG" <?php echo e(old('country_code') === 'EG' ? 'selected' : ''); ?>>مصر</option>
                <option value="KW" <?php echo e(old('country_code') === 'KW' ? 'selected' : ''); ?>>الكويت</option>
                <option value="BH" <?php echo e(old('country_code') === 'BH' ? 'selected' : ''); ?>>البحرين</option>
                <option value="OM" <?php echo e(old('country_code') === 'OM' ? 'selected' : ''); ?>>عمان</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">الهاتف</label>
            <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone')); ?>" maxlength="20">
            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="form-label">البريد الإلكتروني للفوترة</label>
            <input type="email" name="billing_email" class="form-control" value="<?php echo e(old('billing_email')); ?>" maxlength="200">
            <?php $__errorArgs = ['billing_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="form-label">الموقع الإلكتروني</label>
            <input type="url" name="website" class="form-control" value="<?php echo e(old('website')); ?>" placeholder="https://" maxlength="300">
            <?php $__errorArgs = ['website'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group" style="grid-column: 1 / -1">
            <label class="form-label">عنوان الفوترة</label>
            <textarea name="billing_address" class="form-control" rows="2" maxlength="500"><?php echo e(old('billing_address')); ?></textarea>
            <?php $__errorArgs = ['billing_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-danger" style="font-size:11px"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>
    <button type="submit" class="btn btn-pr" style="margin-top:12px">إنشاء المنظمة</button>
</form>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/organizations/partials/create-form.blade.php ENDPATH**/ ?>