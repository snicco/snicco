<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing\Assertable;

    use WP_User;
    use WPEmerge\Events\PendingMail;
    use PHPUnit\Framework\Assert as PHPUnit;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    class AssertableMail
    {

        /**
         * @var PendingMail
         */
        private $mail;
        /**
         * @var ViewFactory
         */
        private $view_factory;

        public function __construct(PendingMail $mail_event, ViewFactory $view_factory)
        {

            $this->mail = $mail_event->mail;
            $this->view_factory = $view_factory;
        }

        /**
         * @param  array|WP_User|string  $user_email
         */
        public function assertTo($user_email, bool $ignore_count = false) : AssertableMail
        {

            $expected_recipients = collect(Arr::wrap($user_email))->map(function ($user_email) {

                return $user_email instanceof WP_User
                    ? $user_email->user_email
                    : $user_email;

            });

            $actual_recipients = collect($this->mail->to)->map(function (\stdClass $to) {

                return $to->email;

            });

            $expected_recipients->each(function (string $email) use ($actual_recipients) {

                PHPUnit::assertTrue($actual_recipients->containsStrict($email), "The email was not sent to [$email].");

            });

            if ( ! $ignore_count) {

                PHPUnit::assertSame(
                    $expected_c = $expected_recipients->count(),
                    $actual_c = $actual_recipients->count(),
                    "The email was expected to be sent to [$expected_c] recipient/s but was sent to [$actual_c] recipient/s.");

            }

            return $this;

        }

        public function assertView(string $view) : AssertableMail
        {

            PHPUnit::assertSame($view, $this->mail->view);

            return $this;
        }

        public function assertViewHas(array $data) : AssertableMail
        {

            foreach ($data as $key => $value) {

                PHPUnit::assertTrue(Arr::has($this->mail->buildViewData(), $key), "The mail view does not have any context for [$key].");
                PHPUnit::assertSame($value, Arr::get($this->mail->buildViewData(), $key));

            }

            return $this;

        }

        public function assertSeeText(string $content) : AssertableMail
        {

            $this->renderMailContent();

            PHPUnit::assertStringContainsString($content, strip_tags($this->mail->message));

            return $this;
        }

        public function assertNotSeeText(string $content) : AssertableMail
        {

            $this->renderMailContent();

            PHPUnit::assertStringNotContainsString($content, strip_tags($this->mail->message));

            return $this;
        }

        public function assertSee(string $content) : AssertableMail
        {

            $this->renderMailContent();

            PHPUnit::assertStringContainsString($content, $this->mail->message);

            return $this;
        }

        public function assertNotSee(string $content) : AssertableMail
        {

            $this->renderMailContent();

            PHPUnit::assertStringNotContainsString($content, $this->mail->message);

            return $this;
        }

        private function renderMailContent()
        {

            $context = array_merge(['recipient' => $this->mail->to[0]], $this->mail->buildViewData());

            $this->mail->message = $this->mail->view
                ? $this->view_factory->render($this->mail->view, $context)
                : $this->mail->message;

        }

    }