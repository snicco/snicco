<?php if (isset($component)) { $__componentOriginal28384859de73175561c1d7a65a10493b77658ef2 = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\Blade\fixtures\Components\ToUppercaseComponent::class, []); ?>
<?php $component->withName('uppercase'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
	 <?php $__env->slot('title', null, []); ?> CALVIN <?php $__env->endSlot(); ?>
	<?php echo e($content); ?>


	<?php if(isset($scoped)): ?>
		 <?php $__env->slot('scoped', null, []); ?> 
			<?php echo e($component->toUpper($scoped)); ?>

		 <?php $__env->endSlot(); ?>
	<?php endif; ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal28384859de73175561c1d7a65a10493b77658ef2)): ?>
<?php $component = $__componentOriginal28384859de73175561c1d7a65a10493b77658ef2; ?>
<?php unset($__componentOriginal28384859de73175561c1d7a65a10493b77658ef2); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/sniccowp/packages/blade/tests/fixtures/views/uppercase-component.blade.php ENDPATH**/ ?>