<?php


    declare(strict_types = 1);


    namespace Tests\integration\Mail;

    use Snicco\Events\Event;
    use Snicco\Events\PendingMail;
    use Snicco\Mail\Mailable;
    use Tests\stubs\TestApp;
    use Tests\TestCase;

    class MailBuilderTest extends TestCase
    {


        protected function setUp() : void
        {

            $this->afterApplicationCreated(function () {
                Event::fake([PendingMail::class]);
            });
            parent::setUp();
        }


        /** @test */
        public function the_from_address_can_be_set()
        {

            $mail = TestApp::mail();

            $mail->to('c@web.de')
                 ->send(new MailWithView());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                return $event->mail instanceof MailWithView
                    && $event->mail->from === [
                        'name' => 'Calvin',
                        'email' => 'c@web.de',
                    ];

            });

        }

        /** @test */
        public function the_reply_to_address_can_be_set()
        {

            $mail = TestApp::mail();

            $mail->to('c@web.de')
                 ->send(new MailWithView());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                return $event->mail->reply_to === [
                        'name' => 'Company',
                        'email' => 'company@web.de',
                    ];

            });

        }

        /** @test */
        public function a_view_can_be_set()
        {

            $mail = TestApp::mail();

            $mail->to('c@web.de')
                 ->send(new MailWithView());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                return $event->mail->view === 'email.basic' && $event->mail->content_type === 'text/html';

            });

        }

        /** @test */
        public function a_plain_text_view_can_be_set()
        {

            $mail = TestApp::mail();

            $mail->to('c@web.de')
                 ->send(new PlainTextMail());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                return $event->mail->view === 'email.basic.plain' && $event->mail->content_type === 'text/plain';

            });

        }

        /** @test */
        public function attachments_can_be_set()
        {

            $mail = TestApp::mail();

            $mail->to('c@web.de')
                 ->send(new MailWithView());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                return $event->mail->attachments === ['file1', 'file2'];

            });

        }

        /** @test */
        public function a_subject_can_be_set()
        {

            $mail = TestApp::mail();

            $mail->to('c@web.de')
                 ->send(new MailWithView());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                return $event->mail->subject === 'Hello Calvin';

            });

        }

        /** @test */
        public function a_subject_can_be_a_closure()
        {

            $mail = TestApp::mail();

            $mail->to('c@web.de')
                 ->send(new PlainTextMail());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                 $event->mail->buildSubject( (object) ['name' => 'CALVIN']);

                 return $event->mail->subject === 'Hello CALVIN';

            });


        }

        /** @test */
        public function the_mail_can_be_sent_to_many_users () {


            $mail = TestApp::mail();

            $mail->to(['c@web.de', ['name'=>'John', 'email' =>'john@web.de']])
                 ->send(new PlainTextMail());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                $to = $event->mail->to;

                $first = $to[0];
                $second = $to[1];

                $first = $first->email === 'c@web.de' && $first->name === '';
                $second = $second->email = 'john@web.de' && $second->name === 'John';

                return $first && $second;

            });



        }

        /** @test */
        public function the_mail_can_be_sent_to_an_array_of_WP_USER_objects () {

            $mail = TestApp::mail();

            $calvin = $this->createAdmin([
                'user_email' => 'c@web.de',
                'first_name' => 'Calvin',
                'last_name' => 'Alkan',
            ]);

            $john = $this->createAdmin([
                'user_email' => 'john@web.de',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);



            $mail->to([$calvin, $john])
                 ->send(new PlainTextMail());

            Event::assertDispatched(PendingMail ::class, function (PendingMail $event) {

                $to = $event->mail->to;

                $first = $to[0];
                $second = $to[1];

                $first = $first->email === 'c@web.de' && $first->name === 'Calvin Alkan';
                $second = $second->email = 'john@web.de' && $second->name === 'John Doe';

                return $first && $second;

            });

        }

    }


    class MailWithView extends Mailable
    {

        public function build() : Mailable
        {

            return $this->from('c@web.de', 'Calvin')
                        ->reply_to('company@web.de', 'Company')
                        ->view('email.basic')
                        ->attach(['file1', 'file2'])
                        ->subject('Hello Calvin');
        }

        public function unique() : bool
        {

            return false;
        }

    }

    class PlainTextMail extends Mailable
    {

        public function build() : Mailable
        {

            return $this->from('c@web.de', 'Calvin')
                        ->reply_to('company@web.de', 'Company')
                        ->text('email.basic.plain');

        }

        public function subjectLine($recipient) {

            return 'Hello '.$recipient->name;

        }

        public function unique() : bool
        {

            return false;
        }

    }