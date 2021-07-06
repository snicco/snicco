<?php
    $match = $foo === 'foo';
?>

<?php echo $__env->renderWhen(! $match, 'child', ['name' => 'Calvin', 'greeting' => $greeting], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>
<?php /**PATH /Users/calvinalkan/valet/wpemerge/wp-mvc/tests/integration/Blade/views/blade-features/include-unless.blade.php ENDPATH**/ ?>