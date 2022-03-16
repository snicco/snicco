<?php

declare(strict_types=1);

namespace Snicco\Monorepo\GitHub;

use Github\Api\Repo;
use Github\AuthMethod;
use Github\Client;
use Snicco\Monorepo\Package\Package;
use Throwable;
use Webmozart\Assert\Assert;

final class CreateRepository
{
    private Repo $github_client;

    public function __construct(string $access_token)
    {
        Assert::stringNotEmpty($access_token);
        $client = new Client();
        $client->authenticate($access_token, AuthMethod::ACCESS_TOKEN);

        $this->github_client = $client->repo();
    }

    /**
     * @psalm-return  non-empty-string
     */
    public function __invoke(Package $package): string
    {
        $repo_url = $this->findGitHubRepo($package);
        if (null === $repo_url) {
            return $this->createRepository($package);
        }

        throw new AlreadyARepository($repo_url, $package->full_name);
    }

    private function findGitHubRepo(Package $package): ?string
    {
        try {
            $repo = $this->github_client->show($package->vendor_name, $package->name);
            Assert::true(isset($repo['html_url']));
            Assert::stringNotEmpty($repo['html_url']);

            return $repo['html_url'];
        } catch (Throwable $e) {
            if (404 === $e->getCode()) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * @psalm-return  non-empty-string
     */
    private function createRepository(Package $package): string
    {
        $repo = $this->github_client->create(
            $package->name,
            sprintf('[READ ONLY] Subtree split of the %s package (see sniccowp/sniccowp).', $package->full_name),
            'https://github.com/sniccowp/sniccowp',
            true,
            $package->vendor_name,
            false,
            false,
            true,
            null,
            true,
            false
        );

        Assert::true(isset($repo['html_url']));
        Assert::stringNotEmpty($repo['html_url']);

        return $repo['html_url'];
    }
}
