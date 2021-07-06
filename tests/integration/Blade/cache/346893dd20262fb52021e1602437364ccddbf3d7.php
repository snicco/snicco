<?php if (isset($component)) { $__componentOriginalffe85cfc0a040249c862e342236797166c55bfe4 = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\integration\Blade\Components\HelloWorld::class, []); ?>
<?php $component->withName('hello-world'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>

 <?php if (isset($__componentOriginalffe85cfc0a040249c862e342236797166c55bfe4)): ?>
<?php $component = $__componentOriginalffe85cfc0a040249c862e342236797166c55bfe4; ?>
<?php unset($__componentOriginalffe85cfc0a040249c862e342236797166c55bfe4); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/class-component.blade.php ENDPATH**/ ?>