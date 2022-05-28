<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\wordpress\fixtures;

use Psr\Http\Message\UploadedFileInterface;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\MutableSession;

use function strpos;

final class WebTestCaseController extends Controller
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function __invoke(): Response
    {
        return $this->respondWith()
            ->html('<h1>' . self::class . '</h1>');
    }

    public function queryParams(Request $request): Response
    {
        return $this->respondWith()
            ->json($request->getQueryParams());
    }

    public function cookiesAsJson(Request $request): Response
    {
        return $this->respondWith()
            ->json($request->getCookieParams());
    }

    public function headersAsJson(Request $request): Response
    {
        return $this->respondWith()
            ->json($request->getHeaders());
    }

    public function bodyAsJson(Request $request): Response
    {
        return $this->respondWith()
            ->json($request->getParsedBody());
    }

    public function filesAsJson(Request $request): Response
    {
        $info = [];

        /**
         * @var UploadedFileInterface $file
         */
        foreach ($request->getUploadedFiles() as $file) {
            $info[] = [
                'size' => $file->getSize(),
                'name' => $file->getClientFilename(),
            ];
        }

        return $this->respondWith()
            ->json($info);
    }

    public function rawBody(Request $request): Response
    {
        return $this->respondWith()
            ->html((string) $request->getBody());
    }

    public function admin(): Response
    {
        return $this->respondWith()
            ->html('admin');
    }

    public function checkIfApi(Request $request): Response
    {
        return $this->respondWith()
            ->html($request->isToApiEndpoint() ? 'true' : 'false');
    }

    public function serverVars(Request $request): Response
    {
        $string = '';

        /**
         * @var string $value
         */
        foreach ($request->getServerParams() as $name => $value) {
            if (0 === strpos((string) $name, 'X-')) {
                $string .= sprintf('%s=%s', $name, $value);
            }
        }

        return $this->respondWith()
            ->html($string);
    }

    public function fullUrl(Request $request): Response
    {
        return $this->respondWith()
            ->html($request->fullUrl());
    }

    public function sendMail(Request $request): Response
    {
        $email = new Email();
        $email = $email
            ->withTo((string) $request->post('to'))
            ->withTextBody((string) $request->post('message'));

        $this->mailer->send($email);

        return $this->respondWith()
            ->html('Mail sent!');
    }

    public function incrementCounter(Request $request): Response
    {
        /** @var MutableSession $session */
        $session = $request->getAttribute(MutableSession::class);

        $session->increment('counter');

        /** @var ImmutableSession $immutable_session */
        $immutable_session = $request->getAttribute(ImmutableSession::class);

        $info = [
            'id' => $immutable_session->id()
                ->asString(),
            'counter' => $immutable_session->get('counter'),
        ];

        return $this->respondWith()
            ->json($info);
    }
}
