<?php


    declare(strict_types = 1);


    namespace Snicco\Mail;

    use BetterWpHooks\Contracts\Dispatcher;
    use Contracts\ContainerAdapter;
    use Snicco\Events\PendingMail;
    use Snicco\Support\Arr;

    class MailBuilder
    {

        private Dispatcher       $dispatcher;
        private ContainerAdapter $container;

        /**
         * @var object[]
         */
        private array $to = [];

        /**
         * @var object[]
         */
        private array $cc = [];

        /**
         * @var object[]
         */
        private array $bcc = [];

        /**
         * @var <string, callable>
         */
        private $override = [];

        public function __construct(Dispatcher $dispatcher, ContainerAdapter $container)
        {

            $this->dispatcher = $dispatcher;
            $this->container = $container;
        }

        public function setOverrides(array $override)
        {

            $this->override = $override;

        }

        public function send(Mailable $mail)
        {

            return $this->dispatcher->dispatch(new PendingMail($this->fillAttributes($mail)));
        }

        public function to($recipients) : MailBuilder
        {

            $this->to = Arr::wrap($recipients);

            return $this;
        }

        public function cc($recipients) : MailBuilder
        {

            $this->cc = Arr::wrap($recipients);

            return $this;
        }

        public function bcc($recipients) : MailBuilder
        {

            $this->bcc = Arr::wrap($recipients);

            return $this;
        }

        private function fillAttributes(Mailable $mail) : Mailable
        {

            $mail = $mail
                ->to($this->to)
                ->cc($this->cc)
                ->bcc($this->bcc);

            $mail = $this->container->call([$mail, 'build']);

            return $this->hasOverride($mail) ? $this->getOverride($mail) : $mail;

        }

        private function hasOverride(Mailable $mail) : bool
        {

            return isset($this->override[get_class($mail)]);
        }

        private function getOverride(Mailable $mail)
        {

            $func = $this->override[$original_class = get_class($mail)];

            $new_mailable = call_user_func($func, $mail);

            if (get_class($new_mailable) !== $original_class) {

                $new_mailable = $this->container->call([$new_mailable, 'build']);

            }

            return $new_mailable;

        }

    }