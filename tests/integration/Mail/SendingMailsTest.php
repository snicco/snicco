<?php


    declare(strict_types = 1);


    namespace Tests\integration\Mail;

    use Tests\fixtures\Mail\ConfirmAccountTestMail;
    use Tests\fixtures\Mail\DiscountMail;
    use Tests\fixtures\Mail\WeAreClosing;
    use Tests\fixtures\Mail\WelcomeMail;
    use Tests\fixtures\Mail\WelcomePlainText;
    use Tests\stubs\TestApp;
    use Tests\TestCase;
    use Snicco\Mail\Mailable;

    class SendingMailsTest extends TestCase
    {

        /** @test */
        public function a_html_email_can_be_send()
        {

            $mail = TestApp::mail();
            $mail
                ->to([['name' => 'Calvin', 'email' => 'c@web.de']])
                ->cc('john@web.de')
                ->bcc(['jane@web.de', 'tom@web.de'])
                ->send(new WelcomeMail());

            $mail = $this->mail_data[0];

            $this->assertSame('Calvin <c@web.de>', $mail['to'][0]);
            $this->assertSame('welcome to our site', $mail['subject']);
            $this->assertViewContent('<h1>Hello Calvin</h1>', $mail['message']);

            $headers = $mail['headers'];

            $this->assertContains('From: Calvin INC <c@web.de>', $headers);
            $this->assertContains('Reply-To: Front Office <office@web.de>', $headers);
            $this->assertContains('Content-Type: text/html; charset=UTF-8', $headers);
            $this->assertContains('Cc: john@web.de', $headers);
            $this->assertContains('Bcc: tom@web.de', $headers);
            $this->assertContains('Bcc: jane@web.de', $headers);

            $this->assertSame(['file1', 'file2'], $mail['attachments']);

        }

        /** @test */
        public function a_plain_text_mail_can_be_sent()
        {

            $mail = TestApp::mail();
            $mail
                ->to([['name' => 'Calvin', 'email' => 'c@web.de']])
                ->cc('john@web.de')
                ->bcc(['jane@web.de', 'tom@web.de'])
                ->send(new WelcomePlainText());

            $mail = $this->mail_data[0];

            $this->assertSame('Calvin <c@web.de>', $mail['to'][0]);
            $this->assertSame('welcome to our site', $mail['subject']);
            $this->assertViewContent('Hello Calvin', $mail['message']);

            $headers = $mail['headers'];

            $this->assertContains('From: Calvin INC <c@web.de>', $headers);
            $this->assertContains('Reply-To: Front Office <office@web.de>', $headers);
            $this->assertContains('Content-Type: text/plain; charset=UTF-8', $headers);
            $this->assertContains('Cc: john@web.de', $headers);
            $this->assertContains('Bcc: tom@web.de', $headers);
            $this->assertContains('Bcc: jane@web.de', $headers);

            $this->assertSame(['file1', 'file2'], $mail['attachments']);

        }

        /** @test */
        public function a_mail_can_be_sent_to_multiple_recipients_and_the_mail_is_resend_for_every_recipient_if_unique_is_true()
        {

            $mail = TestApp::mail();
            $mail
                ->to([
                    ['name' => 'Calvin', 'email' => 'c@web.de'],
                    ['name' => 'John', 'email' => 'john@web.de'],
                ])
                ->send(new WelcomePlainText());

            $first_mail = $this->mail_data[0];
            $headers = $first_mail['headers'];

            $this->assertSame(['Calvin <c@web.de>'], $first_mail['to']);
            $this->assertSame('welcome to our site Calvin', $first_mail['subject']);
            $this->assertViewContent('Hello Calvin', $first_mail['message']);
            $this->assertContains('From: Calvin INC <c@web.de>', $headers);
            $this->assertContains('Reply-To: Front Office <office@web.de>', $headers);
            $this->assertContains('Content-Type: text/plain; charset=UTF-8', $headers);
            $this->assertSame(['file1', 'file2'], $first_mail['attachments']);

            $second_mail = $this->mail_data[1];
            $headers = $second_mail['headers'];

            $this->assertSame(['John <john@web.de>'], $second_mail['to']);
            $this->assertSame('welcome to our site John', $second_mail['subject']);
            $this->assertViewContent('Hello John', $second_mail['message']);
            $this->assertContains('From: Calvin INC <c@web.de>', $headers);
            $this->assertContains('Reply-To: Front Office <office@web.de>', $headers);
            $this->assertContains('Content-Type: text/plain; charset=UTF-8', $headers);
            $this->assertSame(['file1', 'file2'], $second_mail['attachments']);


        }

        /** @test */
        public function a_mail_can_be_sent_to_multiple_recipients_but_all_receive_the_same_view()
        {

            $mail = TestApp::mail();
            $mail
                ->to([
                    ['name' => 'Calvin', 'email' => 'c@web.de'],
                    ['name' => 'John', 'email' => 'john@web.de'],
                ])
                ->send(new WeAreClosing());

            $mail = $this->mail_data[0];
            $this->assertCount(1, $this->mail_data);

            $this->assertSame(['Calvin <c@web.de>', 'John <john@web.de>'], $mail['to']);
            $this->assertSame('We have to close soon.', $mail['subject']);
            $this->assertViewContent('We are closing services!', $mail['message']);

            $headers = $mail['headers'];

            $this->assertContains('Content-Type: text/plain; charset=UTF-8', $headers);

        }

        /** @test */
        public function public_properties_of_the_mailable_are_available_to_views()
        {

            $mail = TestApp::mail();
            $mail
                ->to('c@web.de')
                ->send(new DiscountMail('Iphones'));

            $mail = $this->mail_data[0];
            $this->assertViewContent('We are launching a new discount for Iphones', $mail['message']);

            $this->clearAllMails();

            $mail = TestApp::mail();
            $mail
                ->to('c@web.de')
                ->send(new DiscountMail('Shoes'));

            $mail = $this->mail_data[0];
            $this->assertViewContent('We are launching a new discount for Shoes', $mail['message']);


        }

        /** @test */
        public function the_build_method_is_resolved_from_the_service_container()
        {

            $mail = TestApp::mail();
            $mail
                ->to('c@web.de')
                ->send(new ConfirmAccountTestMail());

            $url = TestApp::url()->to('/foo');

            $mail = $this->mail_data[0];
            $this->assertViewContent('Please confirm your account by visiting '.$url, $mail['message']);


        }

        /** @test */
        public function a_mailable_can_be_swapped_out_for_another_mailable_class()
        {

            $mail = TestApp::mail();

            $mail->setOverrides([
                ConfirmAccountTestMail::class => function (ConfirmAccountTestMail $mail) {

                    $new = new OverrideMailable();
                    $new->to = $mail->to;
                    return $new;

                }
            ]);

            $mail->to('c@web.de')
                 ->send(new ConfirmAccountTestMail());

            $mail = $this->mail_data[0];
            $this->assertViewContent('New Message', $mail['message']);

        }


        private function clearAllMails()
        {

            $this->mail_data = [];
        }

    }


    class OverrideMailable extends Mailable
    {

        public function build() : OverrideMailable
        {

            return $this->subject('New subject')
                        ->message('New Message');
        }

        public function unique() : bool
        {

            return true;
        }


    }