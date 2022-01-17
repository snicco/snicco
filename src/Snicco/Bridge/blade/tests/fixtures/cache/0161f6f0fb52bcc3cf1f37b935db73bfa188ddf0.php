<?php $__env->startPush('parent'); ?>
	BAR
<?php $__env->stopPush(); ?>
<?php $__env->startPrepend('parent'); ?>
	BAZ
<?php $__env->stopPrepend(); ?>

<?php echo $__env->make('layouts.stack-parent', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/sniccowp/packages/blade/tests/fixtures/views/layouts/stack-child.blade.php ENDPATH**/ ?>