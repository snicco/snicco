<?php if (isset($component)) { $__componentOriginalcd1c3723c6134c6cbbc5f7bc958c7c09ce16df51 = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\integration\Blade\Components\InlineComponent::class, ['content' => $content]); ?>
<?php $component->withName('inline'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
    CALVIN
 <?php if (isset($__componentOriginalcd1c3723c6134c6cbbc5f7bc958c7c09ce16df51)): ?>
<?php $component = $__componentOriginalcd1c3723c6134c6cbbc5f7bc958c7c09ce16df51; ?>
<?php unset($__componentOriginalcd1c3723c6134c6cbbc5f7bc958c7c09ce16df51); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/inline-component.blade.php ENDPATH**/ ?>