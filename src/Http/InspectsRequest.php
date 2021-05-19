<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    trait InspectsRequest
    {

        public function isGet() : bool
        {

            return $this->isMethod('GET');

        }

        public function isHead() : bool
        {

            return $this->isMethod('HEAD');

        }

        public function isPost() : bool
        {

            return $this->isMethod('POST');

        }

        public function isPut() : bool
        {

            return $this->isMethod('PUT');

        }

        public function isPatch() : bool
        {

            return $this->isMethod('PATCH');

        }

        public function isDelete() : bool
        {

            return $this->isMethod('DELETE');

        }

        public function isOptions() : bool
        {

            return $this->isMethod('OPTIONS');

        }

        private function isMethod (string $method) : bool
        {

            return strtoupper($this->getMethod()) === strtoupper($method);

        }

        public function isReadVerb() : bool
        {

            return $this->isMethodSafe();

        }

        public function isMethodSafe () : bool
        {

            return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']);

        }

        /** @todo ajax detection needs improvement. See Illuminate Request */
        public function isAjax() : bool
        {

            return $this->isXmlHttpRequest();

        }

        public function isXmlHttpRequest() :bool
        {
            return 'XMLHttpRequest' == $this->getHeaderLine('X-Requested-With');
        }

    }