<?php if (isset($component)) { $__componentOriginala89d0b33754fbde03ca6bf37d6c5ed784c2022a6 = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\integration\Blade\Components\Dependency::class, ['message' => $message]); ?>
<?php $component->withName('with-dependency'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?> <?php if (isset($__componentOriginala89d0b33754fbde03ca6bf37d6c5ed784c2022a6)): ?>
<?php $component = $__componentOriginala89d0b33754fbde03ca6bf37d6c5ed784c2022a6; ?>
<?php unset($__componentOriginala89d0b33754fbde03ca6bf37d6c5ed784c2022a6); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/with-dependency-component.blade.php ENDPATH**/ ?>