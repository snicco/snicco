<?php if (isset($component)) { $__componentOriginal0914839f7b5841869e04a728a70ab2e7e4ac49f6 = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\Blade\fixtures\Components\AlertAttributes::class, ['type' => 'error','message' => $message]); ?>
<?php $component->withName('alert-attributes'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes(['class' => 'mt-4','id' => 'alert-component']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0914839f7b5841869e04a728a70ab2e7e4ac49f6)): ?>
<?php $component = $__componentOriginal0914839f7b5841869e04a728a70ab2e7e4ac49f6; ?>
<?php unset($__componentOriginal0914839f7b5841869e04a728a70ab2e7e4ac49f6); ?>
<?php endif; ?>
<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/framework/packages/blade/tests/fixtures/views/alert-attributes-component.blade.php ENDPATH**/ ?>