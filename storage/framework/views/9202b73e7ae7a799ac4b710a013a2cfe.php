<?php $__env->startSection('title', 'المحفظة'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0">💰 المحفظة</h1>
    <button type="button" class="btn btn-pr" data-modal-open="topup">+ شحن الرصيد</button>
</div>


<div style="background:linear-gradient(135deg,#3B82F6 0%,#1D4ED8 100%);border-radius:20px;padding:32px 36px;color:#fff;margin-bottom:24px">
    <div style="font-size:14px;opacity:.8;margin-bottom:8px">الرصيد المتاح</div>
    <div style="font-size:42px;font-weight:800;letter-spacing:-1px">SAR <?php echo e(number_format($wallet->available_balance ?? 0, 2)); ?></div>
    <?php if(($wallet->locked_balance ?? 0) > 0): ?>
        <div style="font-size:13px;opacity:.7;margin-top:8px">رصيد معلّق: SAR <?php echo e(number_format($wallet->locked_balance ?? 0, 2)); ?></div>
    <?php endif; ?>
</div>


<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🧾 العمليات الأخيرة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🧾 العمليات الأخيرة']); ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>المعرّف</th><th>الوصف</th><th>المبلغ</th><th>الرصيد بعد</th><th>التاريخ</th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="td-mono" style="font-size:12px"><?php echo e($tx->reference_number ?? '—'); ?></td>
                        <td><?php echo e($tx->description); ?></td>
                        <td style="font-weight:700;color:<?php echo e($tx->amount > 0 ? 'var(--ac)' : 'var(--dg)'); ?>">
                            <?php echo e($tx->amount > 0 ? '+' : ''); ?><?php echo e(number_format($tx->amount, 2)); ?> SAR
                        </td>
                        <td style="font-size:12px;color:var(--td)"><?php echo e(number_format($tx->balance_after, 2)); ?> SAR</td>
                        <td style="font-size:12px;color:var(--tm)"><?php echo e($tx->created_at->format('Y-m-d H:i')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="empty-state">لا توجد عمليات</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($transactions->hasPages()): ?>
        <div style="margin-top:14px"><?php echo e($transactions->links()); ?></div>
    <?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>


<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'topup','title' => 'شحن الرصيد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'topup','title' => 'شحن الرصيد']); ?>
    <form method="POST" action="<?php echo e(route('wallet.topup')); ?>">
        <?php echo csrf_field(); ?>
        <div style="margin-bottom:16px">
            <label class="form-label">المبلغ (ريال)</label>
            <input type="number" name="amount" class="form-input" min="10" step="0.01" required placeholder="100.00">
            <div style="display:flex;gap:8px;margin-top:10px">
                <?php $__currentLoopData = [100, 250, 500, 1000, 5000]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button type="button" class="btn btn-s" style="font-size:12px" onclick="this.form.amount.value=<?php echo e($amt); ?>"><?php echo e($amt); ?></button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">طريقة الدفع</label>
            <select name="payment_method" class="form-input">
                <option value="bank_transfer">تحويل بنكي</option>
                <option value="credit_card">بطاقة ائتمان</option>
                <option value="mada">مدى</option>
                <option value="stc_pay">STC Pay</option>
            </select>
        </div>
        <button type="submit" class="btn btn-pr" style="width:100%">تأكيد الشحن</button>
    </form>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/wallet/index.blade.php ENDPATH**/ ?>