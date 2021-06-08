<?php


    declare(strict_types = 1);


    namespace WPEmerge\Listeners;

    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Contracts\Mailer;
    use WPEmerge\Events\PendingMail;
    use WPEmerge\Mail\Mailable;
    use WPEmerge\View\ViewFactory;

    class SendMail
    {

        /**
         * @var Mailer
         */
        private $mailer;

        /**
         * @var ViewFactory
         */
        private $view_factory;
        /**
         * @var ApplicationConfig
         */
        private $config;

        public function __construct(Mailer $mailer, ViewFactory $view_factory, ApplicationConfig $config)
        {
            $this->mailer = $mailer;
            $this->view_factory = $view_factory;
            $this->config = $config;
        }

        public function handleEvent( PendingMail $event) : bool
        {

            $mailable = $event->mail;

            $this->fillDefaults($mailable);

            if ( $mailable->hasMultipleRecipients() && $mailable->unique() ) {

               return $this->sendWithUniqueView($mailable);

            }

            return $this->sendWithSameView($mailable);


        }

        private function sendWithUniqueView(Mailable $mailable) :bool
        {

            $all_sent = true;

            $recipients = $mailable->to;

            foreach ( $recipients as $recipient ) {

                $mail = clone $mailable;

                $mail->to = [$recipient];
                $data = $mailable->buildViewData();
                $context = array_merge($data, ['recipient' => $recipient]);

                $mail->message = $this->view_factory->render(
                    $mailable->view, $context
                );

                $mail->buildSubject($recipient);

                $all_sent = $all_sent && $this->mailer->send($mail);

            }

            return $all_sent;


        }

        private function sendWithSameView(Mailable $mail) :bool
        {

            $context = array_merge(['recipient' => $mail->to[0]], $mail->buildViewData());

            $mail->message = $mail->view
                ? $this->view_factory->render($mail->view, $context)
                : $mail->message;

            return $this->mailer->send($mail);
        }

        private function fillDefaults(Mailable $mailable)
        {

            $mailable->reply_to = $mailable->reply_to ?? $this->config->get('mail.reply_to');
            $mailable->from = $mailable->from ?? $this->config->get('mail.from');

        }

    }