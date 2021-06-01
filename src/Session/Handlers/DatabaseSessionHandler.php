<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Handlers;

    use Carbon\Carbon;
    use Illuminate\Support\InteractsWithTime;
    use Psr\Http\Message\ServerRequestInterface;
    use wpdb;
    use WPEmerge\Facade\WP;
    use WPEmerge\Session\SessionHandler;

    class DatabaseSessionHandler implements SessionHandler
    {

        use InteractsWithTime;

        /**
         * @var wpdb
         */
        private $db;

        /**
         * @var int
         */
        private $lifetime;

        /**
         * @var ServerRequestInterface
         */
        private $request;

        /**
         * @var string
         */
        private $table;

        public function __construct(wpdb $db, string $table, int $lifetime)
        {

            $this->db = $db;
            $this->table = $this->db->prefix.$table;
            $this->lifetime = $lifetime;
        }

        public function close() : bool
        {

            return true;
        }

        public function destroy($id) : bool
        {
            $result = $this->db->delete($this->table, ['id' => $id], ['%s']);

            return $result !== false;
        }

        public function gc($max_lifetime) : bool
        {

            $must_be_newer_than = $this->currentTime() - $max_lifetime;

            $query = $this->db->prepare("DELETE FROM $this->table WHERE last_activity <= %d", $must_be_newer_than);

            return $this->db->query($query) !== false;


        }

        public function open($path, $name) : bool
        {

            return true;
        }

        public function read($id)
        {

            $session = $this->findSession($id);

            if ( ! isset($session->payload) || $this->isExpired($session)) {

                return '';

            }

            return base64_decode($session->payload);

        }

        public function write($id, $data) : bool
        {

            if ($this->exists($id)) {

                return $this->performUpdate($id, $data);

            }

            return $this->performInsert($id, $data);
        }

        public function setRequest(ServerRequestInterface $request)
        {

            $this->request = $request;
        }

        private function performInsert(string $session_id, string $payload) : bool
        {

            $data = $this->getPayloadData($session_id, $payload);

            $query = $this->db->prepare(
                "INSERT INTO `$this->table` 
    (`id`,`user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) 
    VALUES(%s, %d, %s, %s, %s, %d)",
                $data);

            return $this->db->query($query) !== false;

        }

        private function performUpdate(string $id, string $payload) : bool
        {

            $data = array_merge($this->getPayloadData($id, $payload), [$id]);

            $query = $this->db->prepare("UPDATE `$this->table` 
SET 
    id=%s, user_id=%d, ip_address=%s , user_agent=%s , payload = %s, last_activity = %d 
WHERE 
      id=%s",
                $data);

            return $this->db->query($query) !== false;
        }

        private function getPayloadData(string $session_id, string $payload) : array
        {

            return [
                'id' => $session_id,
                'user_id' => WP::userId(),
                'ip_address' => $this->request ? $this->request->getAttribute('ip_address') : '',
                'user_agent' => $this->userAgent(),
                'payload' => base64_encode($payload),
                'last_activity' => $this->currentTime(),
            ];
        }

        private function findSession(string $id) : object
        {

            $query = $this->db->prepare("SELECT * FROM `$this->table` WHERE `id` = %s", $id);

            return (object) $this->db->get_row($query);

        }

        private function isExpired(object $session) : bool
        {

            return isset($session->last_activity)
                && $session->last_activity < Carbon::now()->subMinutes($this->lifetime)
                                                   ->getTimestamp();
        }

        private function exists(string $session_id) : bool
        {

            $query = $this->db->prepare(
                "SELECT `id` FROM `$this->table` WHERE `id` = %s", $session_id
            );

            return $this->db->get_var($query) !== null;

        }

        private function userAgent() : string
        {

            if ( ! $this->request) {
                return '';
            }

            return substr($this->request->getHeaderLine('User-Agent'), 0, 500);
        }

    }