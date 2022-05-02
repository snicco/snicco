<?php

declare(strict_types=1);

use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;

return function (SniccoStyle $style, Output $output): void {
    $output->write('foo');

    $style->title('Title');
    $style->section('Section');

    $style->error('Error');
    $style->error(['Error1', 'Error2']);

    $style->warning('Warning');
    $style->warning(['Warning1', 'Warning2']);

    $style->success('Success');
    $style->success(['Success1', 'Success2']);

    $output->writeln(['1', '2', '3', '4', '5', '6']);
};
