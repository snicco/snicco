<?php


    declare(strict_types = 1);


    namespace Snicco\Listeners;

    use Snicco\Application\Config;
    use Snicco\Contracts\Mailer;
    use Snicco\Events\PendingMail;
    use Snicco\Mail\Mailable;
    use Snicco\Support\WP;
    use Snicco\View\ViewFactory;

    class SendMail
    {

        private Mailer $mailer;
        private ViewFactory $view_factory;
        private Config $config;

        public function __construct(Mailer $mailer, ViewFactory $view_factory, Config $config)
        {

            $this->mailer = $mailer;
            $this->view_factory = $view_factory;
            $this->config = $config;
        }

        public function handleEvent(PendingMail $event) : bool
        {

            $mailable = $event->mail;

            $this->fillDefaults($mailable);

            if ($mailable->hasMultipleRecipients() && $mailable->unique()) {

                return $this->sendWithUniqueView($mailable);

            }

            return $this->sendWithSameView($mailable);


        }

        private function sendWithUniqueView(Mailable $mailable) : bool
        {

            $all_sent = true;

            $recipients = $mailable->to;

            foreach ($recipients as $recipient) {

                $mail = clone $mailable;

                $mail->to = [$recipient];
                $data = $mailable->buildViewData();
                $context = array_merge($data, ['recipient' => $recipient]);

                $mail->message = $mail->view
                    ? $this->view_factory->render($mailable->view, $context)
                    : $mail->message;

                $mail->buildSubject($recipient);

                $all_sent = $all_sent && $this->mailer->send($mail);

            }

            return $all_sent;


        }

        private function sendWithSameView(Mailable $mail) : bool
        {

            $context = array_merge(['recipient' => $mail->to[0]], $mail->buildViewData());

            $mail->message = isset($mail->view)
                ? $this->view_factory->render($mail->view, $context)
                : $mail->message;

            return $this->mailer->send($mail);
        }

        private function fillDefaults(Mailable $mailable)
        {

            $mailable->reply_to = $mailable->reply_to ?? [
                    'name' => $this->config->get('mail.reply_to.name', WP::siteName()),
                    'email' => $this->config->get('mail.reply_to.email', WP::adminEmail()),
                ];

            $mailable->from = $mailable->from ?? [
                    'name' => $this->config->get('mail.from.name', WP::siteName()),
                    'email' => $this->config->get('mail.from.email', WP::adminEmail()),
                ];

        }

    }