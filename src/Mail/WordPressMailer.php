<?php


    declare(strict_types = 1);


    namespace WPMvc\Mail;

    use WPMvc\Contracts\Mailer;
    use WPMvc\Support\WP;

    class WordPressMailer implements Mailer
    {

        public function send(Mailable $mail) : bool
        {

            $recipients = $this->formatRecipients($mail);
            $headers = $this->formatHeaders($mail);

            return WP::mail($recipients, $mail->subject , $mail->message, $headers, $mail->attachments);

        }

        private function formatRecipients(Mailable $mail) : array
        {

            $recipients = collect($mail->to)->map(function ( object $recipient) {

                return $recipient->name
                    ? $recipient->name . ' <' . $recipient->email . '>'
                    : $recipient->email;

            });

            return $recipients->all();

        }

        private function formatHeaders(Mailable $mail) : array
        {

            $headers[] = "Content-Type: {$mail->content_type}; charset=UTF-8";

            foreach ($mail->cc as $cc) {

                $value = $cc->name
                    ? $cc->name . ' <' . $cc->email . '>'
                    : $cc->email;

                $headers[] = 'Cc: ' . $value;
            }

            foreach ($mail->bcc as $bcc) {

                 $value = $bcc->name
                     ? $bcc->name . ' <' . $bcc->email . '>'
                     : $bcc->email;

                 $headers[] = 'Bcc: ' . $value;

            }

            $from_value = isset($mail->from['name'])
                ? $mail->from['name'] . ' <'.$mail->from['email'] .'>'
                : $mail->from['email'];

            $headers[] = 'From: ' . $from_value;

            $reply_to_value = isset($mail->reply_to['name'])
                ? $mail->reply_to['name'] . ' <'.$mail->reply_to['email'] .'>'
                : $mail->reply_to['email'];

            $headers[] = 'Reply-To: ' . $reply_to_value;

            return $headers;



        }


    }