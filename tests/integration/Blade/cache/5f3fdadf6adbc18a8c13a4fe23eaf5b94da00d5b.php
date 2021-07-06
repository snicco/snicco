<?php if (isset($component)) { $__componentOriginal6b36764ae444cdd9a849709bc519bf8455e7ca8f = $component; } ?>
<?php $component = $__env->getContainer()->make(Tests\integration\Blade\Components\ToUppercaseComponent::class, []); ?>
<?php $component->withName('uppercase'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('title'); ?> CALVIN <?php $__env->endSlot(); ?>
    <?php echo e($content); ?>


    <?php if(isset($scoped)): ?>
     <?php $__env->slot('scoped'); ?> 
        <?php echo e($component->toUpper($scoped)); ?>

     <?php $__env->endSlot(); ?>
    <?php endif; ?>

 <?php if (isset($__componentOriginal6b36764ae444cdd9a849709bc519bf8455e7ca8f)): ?>
<?php $component = $__componentOriginal6b36764ae444cdd9a849709bc519bf8455e7ca8f; ?>
<?php unset($__componentOriginal6b36764ae444cdd9a849709bc519bf8455e7ca8f); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>

<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/uppercase-component.blade.php ENDPATH**/ ?>