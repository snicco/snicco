<?php if (isset($component)) { $__componentOriginal366b91b3bddfb3e4d57ef22bc451bbb3c0bd9c5b = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\integration\Blade\Components\AlertAttributes::class, ['type' => 'error','message' => $message]); ?>
<?php $component->withName('alert-attributes'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes(['class' => 'mt-4','id' => 'alert-component']); ?> <?php if (isset($__componentOriginal366b91b3bddfb3e4d57ef22bc451bbb3c0bd9c5b)): ?>
<?php $component = $__componentOriginal366b91b3bddfb3e4d57ef22bc451bbb3c0bd9c5b; ?>
<?php unset($__componentOriginal366b91b3bddfb3e4d57ef22bc451bbb3c0bd9c5b); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/alert-attributes-component.blade.php ENDPATH**/ ?>