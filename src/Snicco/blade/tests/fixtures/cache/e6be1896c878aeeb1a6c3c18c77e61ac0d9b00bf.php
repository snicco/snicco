<?php if (isset($component)) { $__componentOriginal82e6c8fce319b7d244af8c78d6256ab9d262a6ed = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\Blade\fixtures\Components\Alert::class, ['type' => 'error','message' => $message]); ?>
<?php $component->withName('alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal82e6c8fce319b7d244af8c78d6256ab9d262a6ed)): ?>
<?php $component = $__componentOriginal82e6c8fce319b7d244af8c78d6256ab9d262a6ed; ?>
<?php unset($__componentOriginal82e6c8fce319b7d244af8c78d6256ab9d262a6ed); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/framework/packages/blade/tests/fixtures/views/alert-component.blade.php ENDPATH**/ ?>