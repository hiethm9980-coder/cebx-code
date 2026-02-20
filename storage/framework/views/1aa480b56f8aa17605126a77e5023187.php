<?php $__env->startSection('title', 'الدعم والمساعدة'); ?>

<?php $__env->startSection('content'); ?>
<h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0 0 24px">🎧 الدعم والمساعدة</h1>


<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '❓ الأسئلة الشائعة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '❓ الأسئلة الشائعة']); ?>
    <?php $__currentLoopData = [
        ['كيف أتتبع شحنتي؟', 'يمكنك تتبع شحنتك من خلال صفحة التتبع بإدخال رقم التتبع الخاص بك، أو من خلال قائمة شحناتي.'],
        ['كم يستغرق التوصيل؟', 'يعتمد وقت التوصيل على الخدمة المختارة والوجهة. عادة 1-3 أيام للشحن المحلي و5-10 أيام للدولي.'],
        ['كيف أسترجع شحنة؟', 'اذهب لتفاصيل الشحنة واختر "طلب إرجاع". سيتم ترتيب استلام الشحنة من المستلم.'],
        ['كيف أشحن رصيد المحفظة؟', 'من صفحة المحفظة، اضغط "شحن الرصيد" واختر المبلغ ووسيلة الدفع المناسبة.'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="border-bottom:1px solid var(--bd)">
            <button class="faq-toggle" onclick="toggleFaq(<?php echo e($i); ?>)" style="display:flex;justify-content:space-between;align-items:center;padding:16px 0;cursor:pointer;width:100%;background:none;border:none;text-align:right;font-family:inherit">
                <span style="font-weight:600;color:var(--tx);font-size:14px"><?php echo e($faq[0]); ?></span>
                <span style="color:var(--td);transition:transform 0.2s" id="faqIcon<?php echo e($i); ?>">▼</span>
            </button>
            <p id="faqAnswer<?php echo e($i); ?>" style="color:var(--tm);font-size:13px;margin:0 0 16px;line-height:1.8;display:none"><?php echo e($faq[1]); ?></p>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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


<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🎫 تذاكري']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🎫 تذاكري']); ?>
     <?php $__env->slot('action', null, []); ?> 
        <?php $ticketBtnStyle = $portalType === 'b2c' ? 'background:#0D9488' : ''; ?>
        <button class="btn btn-pr btn-sm" data-modal-open="new-ticket" style="<?php echo e($ticketBtnStyle); ?>">+ تذكرة جديدة</button>
     <?php $__env->endSlot(); ?>
    <?php $__empty_1 = true; $__currentLoopData = $tickets ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--bd)">
            <div>
                <span style="font-family:monospace;color:#0D9488;font-weight:600"><?php echo e($ticket->reference_number ?? '#TKT-' . str_pad($ticket->id, 3, '0', STR_PAD_LEFT)); ?></span>
                <div style="font-size:13px;color:var(--tx);margin-top:4px"><?php echo e($ticket->subject); ?></div>
            </div>
            <div style="text-align:left">
                <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $ticket->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ticket->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                <div style="font-size:11px;color:var(--td);margin-top:4px"><?php echo e($ticket->created_at->format('d/m')); ?></div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty-state">لا توجد تذاكر</div>
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


<div class="grid-3">
    <?php $__currentLoopData = [
        ['icon' => '📧', 'title' => 'البريد الإلكتروني', 'info' => 'support@ship.sa', 'desc' => 'الرد خلال 24 ساعة'],
        ['icon' => '📞', 'title' => 'الهاتف', 'info' => '920000XXX', 'desc' => 'أحد - خميس، 9ص - 6م'],
        ['icon' => '💬', 'title' => 'المحادثة المباشرة', 'info' => 'متاح الآن', 'desc' => 'متوسط الانتظار: 2 دقيقة'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <div style="text-align:center">
                <div style="font-size:36px;margin-bottom:12px"><?php echo e($contact['icon']); ?></div>
                <div style="font-weight:600;color:var(--tx);font-size:15px;margin-bottom:4px"><?php echo e($contact['title']); ?></div>
                <div style="color:#0D9488;font-size:14px;font-weight:600;margin-bottom:4px"><?php echo e($contact['info']); ?></div>
                <div style="color:var(--td);font-size:12px"><?php echo e($contact['desc']); ?></div>
            </div>
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
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'new-ticket','title' => 'تذكرة دعم جديدة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'new-ticket','title' => 'تذكرة دعم جديدة']); ?>
    <form method="POST" action="<?php echo e(route('support.store')); ?>">
        <?php echo csrf_field(); ?>
        <div style="margin-bottom:16px">
            <label class="form-label">نوع المشكلة</label>
            <select name="category" class="form-input">
                <option>مشكلة في شحنة</option><option>استفسار عام</option><option>مشكلة تقنية</option><option>اقتراح</option>
            </select>
        </div>
        <div style="margin-bottom:16px"><label class="form-label">رقم الشحنة (اختياري)</label><input type="text" name="shipment_ref" placeholder="TRK-XXXX" class="form-input"></div>
        <div style="margin-bottom:16px"><label class="form-label">الموضوع</label><input type="text" name="subject" placeholder="عنوان المشكلة" class="form-input" required></div>
        <div style="margin-bottom:16px">
            <label class="form-label">التفاصيل</label>
            <textarea name="message" rows="4" placeholder="اشرح المشكلة بالتفصيل..." class="form-input" style="resize:vertical" required></textarea>
        </div>
        <button type="submit" class="btn btn-pr" style="width:100%;<?php echo e($portalType === 'b2c' ? 'background:#0D9488' : ''); ?>">إرسال التذكرة</button>
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

<?php $__env->startPush('scripts'); ?>
<script>
function toggleFaq(i) {
    const a = document.getElementById('faqAnswer' + i);
    const icon = document.getElementById('faqIcon' + i);
    if (a.style.display === 'none') { a.style.display = 'block'; icon.style.transform = 'rotate(180deg)'; }
    else { a.style.display = 'none'; icon.style.transform = 'none'; }
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/support/index.blade.php ENDPATH**/ ?>