<?php


    declare(strict_types = 1);


    namespace WPEmerge\EnhancedAuth;

    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Session\HasLottery;

    class DatabaseMagicLink extends MagicLink
    {

        use InteractsWithTime;
        use HasLottery;

        /**
         * @var string
         */
        private $table;

        /**
         * @var \wpdb
         */
        private $wpdb;

        /**
         * @var array|int[]
         */
        private $lottery;

        /**
         * @var int
         */
        private $grace_period_in_ms;

        public function __construct(string $table, $grace_period_in_ms = 500, array $lottery = [2, 100])
        {

            global $wpdb;

            $this->wpdb = $wpdb;
            $this->table = $this->wpdb->prefix.$table;
            $this->lottery = $lottery;
            $this->grace_period_in_ms = $grace_period_in_ms;

        }

        public function notUsed(string $url) : bool
        {

            $hash = md5($url);

            $query = $this->wpdb->prepare("SELECT EXISTS(SELECT 1 FROM $this->table WHERE link = %s LIMIT 1)", $hash);

            $exists = $this->wpdb->get_var($query);

            return (is_string($exists) && $exists === '1');

        }

        public function create(string $url, int $expires) : string
        {

            $signed_url = $this->hash($url);

            if ($this->hitsLottery($this->lottery)) {

                $this->gc();

            }

            $query = $this->wpdb->prepare("INSERT INTO `$this->table` (`link`, `signature`) VALUES(%s, %d)", md5($signed_url), $expires);

            $this->wpdb->query($query);

            return $signed_url;

        }

        public function invalidate(string $url)
        {


            $hash = md5($url);

            $this->wpdb->delete($this->table, ['link' => $hash], ['%s']);


        }

        public function gc() : bool
        {

            $must_be_newer_than = $this->currentTime();

            $query = $this->wpdb->prepare("DELETE FROM $this->table WHERE `expires` <= %d", $must_be_newer_than);

            return $this->wpdb->query($query) !== false;

        }


    }