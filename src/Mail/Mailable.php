<?php


    declare(strict_types = 1);


    namespace WPEmerge\Mail;

    use Closure;
    use ReflectionClass;
    use ReflectionProperty;
    use WP_User;
    use WPEmerge\Support\Arr;

    abstract class Mailable
    {

        /**
         * @var object[]
         */
        public $to = [];

        /**
         * @var object[]
         */
        public $cc = [];

        /**
         * @var object[]
         */
        public $bcc = [];

        /**
         * @var array
         */
        public $from;

        /**
         * @var array
         */
        public $reply_to;

        /**
         * @var string[]
         */
        public $attachments = [];

        /**
         * @var string
         */
        public $view;

        /** @var string|Closure */
        public $subject;

        /**
         * @var string
         */
        public $content_type;

        /**
         * @var array
         */
        public $view_data = [];

        /** @var string */
        public $message = '';

        abstract public function unique(): bool;

        public function to($recipients) : Mailable
        {

            $this->to = $this->normalizeAddress($recipients);

            return $this;
        }

        public function cc($recipients) : Mailable
        {

            $this->cc = $this->normalizeAddress($recipients);

            return $this;
        }

        public function bcc($recipients) : Mailable
        {

            $this->bcc = $this->normalizeAddress($recipients);

            return $this;
        }

        public function buildSubject(object $recipient) {

            $subject = $this->subject;

            $this->subject = method_exists($this, 'subjectLine')
                ? $this->subjectLine($recipient)
                : $subject;

        }

        public function hasMultipleRecipients() : bool
        {

            return count($this->to) > 1;

        }

        public function buildViewData() : array
        {

            $data = $this->view_data;

            $properties = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                if ($property->getDeclaringClass()->getName() !== self::class) {
                    $data[$property->getName()] = $property->getValue($this);
                }
            }

            return $data;
        }

        protected function message(string $message) : Mailable
        {
            $this->message = $message;
            return $this;
        }

        protected function from (string $email, string $name ='' ) : Mailable
        {

            $this->from = ['name' => $name, 'email' => $email];

            return $this;
        }

        protected function reply_to (string $email, string $name ='' ) : Mailable
        {

            $this->reply_to = ['name' => $name, 'email' => $email];
            return $this;

        }

        protected function attach ( $file_path ) : Mailable
        {

            $this->attachments = array_merge($this->attachments, Arr::wrap($file_path));
            return $this;

        }

        protected function view( string $view_name ) : Mailable
        {

            $this->view = $view_name;
            $this->content_type = 'text/html';
            return $this;

        }

        protected function subject( string $subject ) :Mailable {

            $this->subject = $subject;
            return $this;

        }

        protected function text( string $view_name ) : Mailable
        {

            $this->view = $view_name;
            $this->content_type = 'text/plain';
            return $this;

        }

        protected function with($key, $value = null) : Mailable
        {
            if (is_array($key)) {
                $this->view_data = array_merge($this->view_data, $key);
            } else {
                $this->view_data[$key] = $value;
            }

            return $this;
        }

        private function normalizeAddress($recipients) : array
        {

            $recipients = Arr::wrap($recipients);

            $recipients = collect($recipients)
                ->map(function ($recipient) {

                    if ($recipient instanceof WP_User) {

                        return (object) [
                            'name' => $this->userFullName($recipient),
                            'email' => $recipient->user_email,
                        ];

                    }

                    if ( is_string($recipient)) {

                        return (object) [
                            'name' => '', 'email' => $recipient
                        ];

                    }

                    if (is_array($recipient)) {

                        return (object) [
                            'name' => $recipient['name'] ?? '', 'email' => $recipient['email'],
                        ];

                    }

                })
                ->filter(function (object $recipient) {

                    return filter_var($recipient->email, FILTER_VALIDATE_EMAIL);

                });

            return $recipients->all();


        }

        private function userFullName(WP_User $user) : string
        {

            if ($user->first_name) {

                return ucwords($user->first_name).' '.ucwords($user->last_name ?? '');


            }

            return $user->display_name;


        }

    }