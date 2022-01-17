<?php if (isset($component)) { $__componentOriginal460971c255a5548d979850a2a716258b837307f1 = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\Blade\fixtures\Components\Dependency::class, ['message' => $message]); ?>
<?php $component->withName('with-dependency'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal460971c255a5548d979850a2a716258b837307f1)): ?>
<?php $component = $__componentOriginal460971c255a5548d979850a2a716258b837307f1; ?>
<?php unset($__componentOriginal460971c255a5548d979850a2a716258b837307f1); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/framework/packages/blade/tests/fixtures/views/with-dependency-component.blade.php ENDPATH**/ ?>