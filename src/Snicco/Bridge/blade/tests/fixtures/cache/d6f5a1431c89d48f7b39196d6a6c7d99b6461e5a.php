<?php
	$match = $foo === 'foo'
?>

<?php echo $__env->renderWhen(! $match, 'blade-features.child', ['name' => 'Calvin', 'greeting' => $greeting], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>
<?php /**PATH /Users/calvinalkan/wpvaletsites/sniccowp/sniccowp/packages/blade/tests/fixtures/views/blade-features/include-unless.blade.php ENDPATH**/ ?>