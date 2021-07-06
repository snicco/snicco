<?php if (isset($component)) { $__componentOriginalbd99bf1d78b8f4d79ec19224fa137bc01d3ff14a = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\integration\Blade\Components\Alert::class, ['type' => 'error','message' => $message]); ?>
<?php $component->withName('alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?> <?php if (isset($__componentOriginalbd99bf1d78b8f4d79ec19224fa137bc01d3ff14a)): ?>
<?php $component = $__componentOriginalbd99bf1d78b8f4d79ec19224fa137bc01d3ff14a; ?>
<?php unset($__componentOriginalbd99bf1d78b8f4d79ec19224fa137bc01d3ff14a); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/alert-component.blade.php ENDPATH**/ ?>