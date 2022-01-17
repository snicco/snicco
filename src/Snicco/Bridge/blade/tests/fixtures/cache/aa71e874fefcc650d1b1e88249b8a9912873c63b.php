<?php $__env->startSection('title', 'foo'); ?>
<?php $__env->startSection('sidebar'); ?>
	##parent-placeholder-19bd1503d9bad449304cc6b4e977b74bac6cc771##
	appended
<?php $__env->stopSection(); ?>
<?php $__env->startSection('body'); ?>
	foobar
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.parent', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/sniccowp/packages/blade/tests/fixtures/views/layouts/child.blade.php ENDPATH**/ ?>