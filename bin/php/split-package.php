<?php

declare(strict_types=1);

use Snicco\Monorepo\Input;

require_once dirname(__DIR__, 2) . '/src/Monorepo/Input.php';

function info(string $message): void
{
    echo PHP_EOL . "\033[0;33m[NOTE] " . $message . "\033[0m" . PHP_EOL;
}

function error(string $message): void
{
    echo PHP_EOL . PHP_EOL . "\033[0;31m[ERROR] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}

function success(string $message): void
{
    echo PHP_EOL . PHP_EOL . "\033[0;32m[SUCCESS] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}

/**
 * @return string[]
 */
function execDebug(string $info, string $command): array
{
    if ('' !== $info) {
        $info .= PHP_EOL;
    }

    info($info . 'running: ' . $command);
    exec($command, $output, $code);

    if (0 !== $code) {
        throw new RuntimeException(sprintf('Command %s failed.', $command));
    }

    if ([] !== $output) {
        /** @var string[] $output */
        $command_output = implode(PHP_EOL, $output);
        echo $command_output . PHP_EOL;
    }

    return $output;
}

/**
 * @param non-empty-string $package_dir
 * @param non-empty-string $org_name
 * @param non-empty-string $repo_name
 * @param non-empty-string $access_token
 * @param non-empty-string $branch_name
 */
function splitPackage(
    string $package_dir,
    string $org_name,
    string $repo_name,
    string $access_token,
    string $commit_message,
    string $branch_name,
    ?string $tag
): void {
    $clone_dir = sys_get_temp_dir() . '/monorepo_split/clone_directory';
    $mono_repo_working_dir = getcwd();
    if (! is_string($mono_repo_working_dir)) {
        throw new RuntimeException('Could not retrieve current working directory');
    }

    execDebug(
        'Removing contents of clone directory',
        sprintf('rm -rf %s', $clone_dir)
    );

    execDebug(
        'Cloning remote into clone directory',
        sprintf('git clone -- https://%s@github.com/%s/%s.git %s', $access_token, $org_name, $repo_name, $clone_dir)
    );

    info(sprintf('Changing working directory to clone directory %s from %s', $clone_dir, $mono_repo_working_dir));
    $changed = chdir($clone_dir);
    if (! $changed) {
        throw new RuntimeException('Could not switch to working directory');
    }

    $remotes = execDebug(
        sprintf('Checking if remote branch %s exists', $branch_name),
        sprintf(
            'git ls-remote --heads https://%s@github.com/%s/%s.git %s',
            $access_token,
            $org_name,
            $repo_name,
            $branch_name
        )
    );
    if ([] === $remotes) {
        info(sprintf('Remote branch %s does not exist.', $branch_name));
        execDebug(
            sprintf('Creating and checking out new branch %s', $branch_name),
            sprintf('git checkout -b %s', $branch_name)
        );
    } else {
        info(sprintf('Remote branch %s already exists.', $branch_name));
        execDebug(
            sprintf('Checking out existing branch %s', $branch_name),
            sprintf('git checkout %s', $branch_name)
        );
    }

    execDebug(
        'rsync contents of monorepo package',
        // Note: The trailing "/" is important.
        sprintf(
            "rsync -avz --delete --exclude='**/.git/' %s %s",
            "{$mono_repo_working_dir}/{$package_dir}/",
            '.'
        ),
    );

    $files = execDebug(
        'Checking if files are modified',
        'git status --porcelain'
    );

    if ([] === $files) {
        info('No files are changed.');
    } else {
        execDebug(
            'Adding modified files',
            'git add .'
        );
        execDebug(
            'Committing changes',
            sprintf("git commit -m '%s'", $commit_message)
        );
        execDebug(
            'Pushing changes to remote repository',
            sprintf('git push origin %s', $branch_name)
        );
        success(sprintf('Commit successfully pushed to remote branch %s.', $branch_name));

        // Write to GitHub Actions summary if more than just composer.json was changed.
        $files = array_values($files);
        $only_composer_json_changed = 1 === count($files) && false !== strpos($files[0], 'composer.json');
        if (! $only_composer_json_changed) {
            $summary_message = sprintf(
                "Changes (excluding composer.json) detected and pushed to remote branch %s:\n\n%s",
                $branch_name,
                implode("\n", $files)
            );

            $step_summary_file = (string) @getenv('GITHUB_STEP_SUMMARY');
            if ('' !== $step_summary_file) {
                @file_put_contents($step_summary_file, $summary_message, FILE_APPEND);
            }
        }
    }

    if (null !== $tag) {
        execDebug(
            'Creating tag',
            sprintf('git tag %s -m "%s"', $tag, $commit_message)
        );
        execDebug(
            'Pushing tags',
            sprintf('git push origin %s', $tag)
        );
        success(sprintf('Tag %s successfully pushed to remote.', $tag));
    }

    info(sprintf('Changing back to previous working directory %s', $mono_repo_working_dir));
    $changed = chdir($mono_repo_working_dir);
    if (! $changed) {
        throw new RuntimeException('chdir returned false');
    }
}

/**
 * @psalm-assert non-empty-string $string
 */
function assertStringNotEmpty(string $string, string $message): void
{
    if ('' === $string) {
        throw new InvalidArgumentException($message);
    }
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}, E_ALL);

try {
    $input = new Input($argv);
    $package_dir = $input->mainArg();
    $token = (string) ($_SERVER['GITHUB_TOKEN'] ?? '');
    $commit_message = (string) ($_SERVER['COMMIT_MESSAGE'] ?? '');
    $organization = (string) ($_SERVER['ORGANIZATION'] ?? '');
    $repository = (string) ($_SERVER['REPOSITORY'] ?? '');
    $tag = (string) ($_SERVER['TAG'] ?? '');
    $branch_name = (string) ($_SERVER['BRANCH'] ?? 'master');

    assertStringNotEmpty($package_dir, 'package directory cant be empty string');
    if (! is_readable($package_dir)) {
        throw new InvalidArgumentException('package directory is not readable.');
    }

    assertStringNotEmpty($token, 'GITHUB_TOKEN environment variable cant be empty string');
    assertStringNotEmpty($commit_message, 'COMMIT_MESSAGE environment variable cant be empty string');
    assertStringNotEmpty($organization, 'ORGANIZATION environment variable cant be empty string');
    assertStringNotEmpty($repository, 'REPOSITORY environment variable cant be empty string');
    assertStringNotEmpty($branch_name, 'BRANCH environment variable cant be empty string');

    splitPackage(
        $package_dir,
        $organization,
        $repository,
        $token,
        $commit_message,
        $branch_name,
        empty($tag) ? null : $tag
    );
} catch (Throwable $e) {
    error($e->getMessage());
    exit(1);
}

exit(0);
