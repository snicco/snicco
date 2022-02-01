<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\helpers;

use RuntimeException;
use Snicco\Component\Core\Application;
use Symfony\Component\Finder\Finder;

use function unlink;

trait WriteTestConfig
{

    protected function cleanDirs(array $dirs): void
    {
        $files = Finder::create()->in($dirs);
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }
    }

    private function writeConfig(Application $application, array $data): void
    {
        if (!$application->env()->isProduction() && !$application->env()->isStaging()) {
            throw new RuntimeException('app is not in cacheable env.');
        }

        if (!isset($data['app'])) {
            $data['app'] = [];
        }

        $res = file_put_contents(
            $application->directories()->cacheDir()
            . '/'
            . $application->env()->asString() . '.config.php',
            '<?php return ' . var_export($data, true) . ';'
        );

        if ($res === false) {
            throw new RuntimeException('Could not write test config.');
        }

        if (!$application->isConfigurationCached()) {
            throw new RuntimeException('App configuration was not cached');
        }
    }

}