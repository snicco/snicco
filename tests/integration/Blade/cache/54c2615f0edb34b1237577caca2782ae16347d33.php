Name:<?php echo $__env->yieldContent('title'); ?>,
SIDEBAR:
<?php $__env->startSection('sidebar'); ?>
    parent_sidebar.
<?php echo $__env->yieldSection(); ?>
,BODY:<?php echo $__env->yieldContent('body'); ?>,
FOOTER:<?php echo $__env->yieldContent('footer', 'default_footer'); ?>
<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/layouts/parent.blade.php ENDPATH**/ ?>