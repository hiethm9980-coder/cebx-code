<?php $__env->startSection('title', 'المحفظة'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">💰 <?php echo e($portalType === 'b2c' ? 'محفظتي' : 'المحفظة'); ?></h1>
    <button class="btn btn-pr" data-modal-open="topup-wallet"
            <?php if($portalType === 'b2c'): ?> style="background:#0D9488" <?php endif; ?>>+ شحن الرصيد</button>
</div>


<div style="background:linear-gradient(135deg,<?php echo e($portalType === 'b2c' ? '#0D9488,#065F56,#134E4A' : '#3B82F6,#1D4ED8,#7C3AED'); ?>);border-radius:20px;padding:36px 32px;margin-bottom:28px;position:relative;overflow:hidden">
    <div style="position:absolute;top:-30px;left:-30px;width:140px;height:140px;background:rgba(255,255,255,0.05);border-radius:50%"></div>
    <div style="position:absolute;bottom:-40px;right:40px;width:100px;height:100px;background:rgba(255,255,255,0.03);border-radius:50%"></div>
    <div style="position:relative">
        <div style="font-size:14px;color:rgba(255,255,255,0.73)">الرصيد المتاح</div>
        <div style="font-size:48px;font-weight:800;color:#fff;font-family:monospace;margin:8px 0">
            <?php echo e(number_format($wallet->available_balance ?? 0, 2)); ?> <span style="font-size:20px">ر.س</span>
        </div>
        <?php if($portalType === 'b2b'): ?>
            <div style="font-size:13px;color:rgba(255,255,255,0.66)">آخر عملية: <?php echo e($lastTransaction?->description ?? '—'); ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if($portalType === 'b2b'): ?>
    <div class="stats-grid" style="margin-bottom:24px">
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '💸','label' => 'مصروفات الشهر','value' => number_format($monthlyExpenses ?? 0)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '💸','label' => 'مصروفات الشهر','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($monthlyExpenses ?? 0))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '💳','label' => 'إيداعات الشهر','value' => number_format($monthlyDeposits ?? 0)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '💳','label' => 'إيداعات الشهر','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($monthlyDeposits ?? 0))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🔄','label' => 'عدد المعاملات','value' => $transactionCount ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🔄','label' => 'عدد المعاملات','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactionCount ?? 0)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    </div>
<?php endif; ?>


<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📋 '.e($portalType === 'b2c' ? 'آخر المعاملات' : 'سجل المعاملات').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📋 '.e($portalType === 'b2c' ? 'آخر المعاملات' : 'سجل المعاملات').'']); ?>
    <?php if($portalType === 'b2b'): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>النوع</th><th>الوصف</th><th>المبلغ</th><th>الرصيد بعد</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $isCredit = in_array($tx->type, ['topup', 'refund']); ?>
                        <tr>
                            <td><span class="badge <?php echo e($isCredit ? 'badge-ac' : 'badge-dg'); ?>"><?php echo e($isCredit ? 'إيداع' : 'خصم'); ?></span></td>
                            <td><?php echo e($tx->description); ?></td>
                            <td style="color:<?php echo e($isCredit ? 'var(--ac)' : 'var(--dg)'); ?>;font-family:monospace;font-weight:600"><?php echo e($isCredit ? '+' : '-'); ?><?php echo e(number_format($tx->amount, 2)); ?></td>
                            <td style="font-family:monospace"><?php echo e(number_format($tx->running_balance ?? 0, 2)); ?></td>
                            <td><?php echo e($tx->created_at->format('d/m/Y')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="empty-state">لا توجد معاملات</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0">
            <?php $__empty_1 = true; $__currentLoopData = $transactions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $isCredit = in_array($tx->type, ['topup', 'refund']); ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 0;border-bottom:1px solid var(--bd)">
                    <div style="display:flex;gap:14px;align-items:center">
                        <div style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;background:<?php echo e($isCredit ? 'rgba(16,185,129,0.13)' : 'rgba(239,68,68,0.13)'); ?>">
                            <?php echo e($isCredit ? '↑' : '↓'); ?>

                        </div>
                        <div>
                            <div style="font-size:14px;color:var(--tx)"><?php echo e($tx->description); ?></div>
                            <div style="font-size:12px;color:var(--td);margin-top:2px"><?php echo e($tx->created_at->format('d/m')); ?></div>
                        </div>
                    </div>
                    <span style="font-family:monospace;font-weight:700;font-size:16px;color:<?php echo e($isCredit ? '#10B981' : '#EF4444'); ?>">
                        <?php echo e($isCredit ? '+' : '-'); ?><?php echo e(number_format($tx->amount, 2)); ?>

                    </span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty-state">لا توجد معاملات</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if(isset($transactions) && $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator): ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'topup-wallet','title' => 'شحن الرصيد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'topup-wallet','title' => 'شحن الرصيد']); ?>
    <form method="POST" action="<?php echo e(route('wallet.topup')); ?>">
        <?php echo csrf_field(); ?>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px">
            <?php $__currentLoopData = [100, 250, 500, 1000]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button" class="amount-btn"
                        style="padding:14px;background:var(--sf);border:1px solid var(--bd);border-radius:8px;color:var(--tx);font-weight:600;font-size:16px;cursor:pointer;font-family:monospace"
                        onclick="document.getElementById('topupAmount').value=<?php echo e($amount); ?>">
                    <?php echo e($amount); ?>

                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">مبلغ مخصص</label>
            <input type="number" name="amount" id="topupAmount" placeholder="0.00 ر.س" step="0.01" class="form-input" value="500">
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">وسيلة الدفع</label>
            <select name="payment_method" class="form-input">
                <?php if($portalType === 'b2b'): ?>
                    <option>تحويل بنكي</option>
                <?php endif; ?>
                <option>مدى</option>
                <option>فيزا/ماستركارد</option>
                <option>Apple Pay</option>
                <option>STC Pay</option>
            </select>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr" <?php if($portalType === 'b2c'): ?> style="background:#0D9488" <?php endif; ?>>شحن الرصيد</button>
        </div>
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