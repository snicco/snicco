<?php if (isset($component)) { $__componentOriginalcb90fe26342a6397e7ba50549b4836461e8c0e8f = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\Blade\fixtures\Components\HelloWorld::class, []); ?>
<?php $component->withName('hello-world'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb90fe26342a6397e7ba50549b4836461e8c0e8f)): ?>
<?php $component = $__componentOriginalcb90fe26342a6397e7ba50549b4836461e8c0e8f; ?>
<?php unset($__componentOriginalcb90fe26342a6397e7ba50549b4836461e8c0e8f); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/sniccowp/packages/blade/tests/fixtures/views/class-component.blade.php ENDPATH**/ ?>