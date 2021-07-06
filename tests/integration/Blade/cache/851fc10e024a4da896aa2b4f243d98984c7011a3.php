<?php $attributes = $attributes->exceptProps(['type','message']); ?>
<?php foreach (array_filter((['type','message']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

ID:<?php echo e($attributes['id']); ?>,
CLASS:<?php echo e($attributes['class']); ?>,
MESSAGE:<?php echo e($attributes['message'] ?'FAILED: MESSAGE_WAS_NOT_MOVED_TO_PROPS' : $message); ?>,
TYPE:<?php echo e($attributes['type'] ?'FAILED: ERROR_WAS_NOT_MOVED_TO_PROPS' : $type); ?>

<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/components/anonymous-props.blade.php ENDPATH**/ ?>