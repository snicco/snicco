Name:<?php echo $__env->yieldContent('title'); ?>,
SIDEBAR:
<?php $__env->startSection('sidebar'); ?>
	parent_sidebar.
<?php echo $__env->yieldSection(); ?>
,BODY:<?php echo $__env->yieldContent('body'); ?>,
FOOTER:<?php echo $__env->yieldContent('footer', 'default_footer'); ?>
<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/sniccowp/packages/blade/tests/fixtures/views/layouts/parent.blade.php ENDPATH**/ ?>