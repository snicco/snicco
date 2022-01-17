<?php if (isset($component)) { $__componentOriginal121a75b11a787e4036df127a8c11ce8a415e1592 = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\Blade\fixtures\Components\InlineComponent::class, ['content' => $content]); ?>
<?php $component->withName('inline'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
	CALVIN
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal121a75b11a787e4036df127a8c11ce8a415e1592)): ?>
<?php $component = $__componentOriginal121a75b11a787e4036df127a8c11ce8a415e1592; ?>
<?php unset($__componentOriginal121a75b11a787e4036df127a8c11ce8a415e1592); ?>
<?php endif; ?>
<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/framework/packages/blade/tests/fixtures/views/inline-component.blade.php ENDPATH**/ ?>