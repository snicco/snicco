<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Packagist;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Snicco\Monorepo\Package\Package;
use Webmozart\Assert\Assert;

use function sprintf;

final class CreatePackage
{
    private string $username;

    private string $api_token;

    private Client $client;

    public function __construct(string $username, string $api_token)
    {
        Assert::stringNotEmpty($username);
        Assert::stringNotEmpty($api_token);
        $this->username = $username;
        $this->api_token = $api_token;
        $this->client = new Client();
    }

    public function __invoke(Package $package): string
    {
        if ($this->isPackagistRepo($package)) {
            throw new AlreadyAtPackagist($package->full_name, $this->packageUrlHtml($package));
        }

        return $this->creatPackagistPackage($package);
    }

    private function packageUrlJson(Package $package): string
    {
        return sprintf('https://packagist.org/packages/%s.json', $package->full_name);
    }

    private function packageUrlHtml(Package $package): string
    {
        return sprintf('https://packagist.org/packages/%s', $package->full_name);
    }

    private function isPackagistRepo(Package $package): bool
    {
        try {
            $response = $this->client->get($this->packageUrlJson($package), [
                'headers' => [
                    'User-Agent' => 'sniccowp/sniccowp',
                    'Accept' => 'application/json',
                ],
            ]);

            return 200 === $response->getStatusCode();
        } catch (ClientException $e) {
            if (404 === $e->getCode()) {
                return false;
            }

            throw $e;
        }
    }

    private function creatPackagistPackage(Package $package): string
    {
        $response = $this->client->post('https://packagist.org/api/create-package', [
            'headers' => [
                'User-Agent' => 'sniccowp/sniccowp monorepo',
                'Accept' => 'application/json',
            ],
            'query' => [
                'username' => $this->username,
                'apiToken' => $this->api_token,
            ],
            'json' => [
                'repository' => [
                    'url' => 'https://github.com/' . $package->full_name,
                ],
            ],
        ]);
        if (202 !== $response->getStatusCode()) {
            throw new RuntimeException(
                sprintf(
                    'Packagist returned unexpected status code [%s] while creating package [%s].',
                    $response->getStatusCode(),
                    $package->full_name
                )
            );
        }

        return $this->packageUrlJson($package);
    }
}
