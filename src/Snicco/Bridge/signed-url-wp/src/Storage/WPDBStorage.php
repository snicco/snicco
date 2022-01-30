<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlWP\Storage;

use wpdb;
use RuntimeException;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Contracts\SignedUrlClock;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\SignedUrlClockUsingDateTimeImmutable;

use function str_replace;

use const ARRAY_N;

final class WPDBStorage implements SignedUrlStorage
{
    
    /**
     * @var wpdb
     */
    private $db;
    
    /**
     * @var string
     */
    private $table;
    
    /**
     * @var SignedUrlClock
     */
    private $clock;
    
    public function __construct(string $table, SignedUrlClock $clock = null)
    {
        global $wpdb;
        $this->db = $wpdb;
        $prefix = $this->db->prefix;
        $this->table = $prefix.str_replace($prefix, '', $table);
        $this->clock = $clock ?? new SignedUrlClockUsingDateTimeImmutable();
    }
    
    public function store(SignedUrl $signed_url) :void
    {
        $query = "insert into $this->table(id,expires,left_usages,protects) VALUES (%s,%d,%d,%s) ";
        
        $payload = [
            $signed_url->identifier(),
            $signed_url->expiresAt(),
            $signed_url->maxUsage(),
            $signed_url->protects(),
        ];
        
        $query = $this->db->prepare($query, $payload);
        
        $affected = $this->db->query($query);
        
        if ($affected !== 1 || ! empty($this->db->last_error)) {
            throw new RuntimeException(
                "A signed url could not be stored for path [{$signed_url->protects()}].\n wpdb last error: {$this->db->last_error}"
            );
        }
    }
    
    public function consume(string $identifier) :void
    {
        $left_usages = $this->remainingUsage($identifier);
        
        if ($left_usages === 0) {
            throw BadIdentifier::for($identifier);
        }
        
        $new = $left_usages - 1;
        
        if ($new === 0) {
            $query = "delete from $this->table where id = %s";
            $query = $this->db->prepare($query, $identifier);
        }
        else {
            $query =
                "update $this->table set left_usages = %d where id = %s and left_usages > 0";
            $query = $this->db->prepare($query, $new, $identifier);
        }
        
        $res = $this->db->query($query);
        
        if ($res !== 1) {
            throw new RuntimeException(
                "signed url usage for id [$identifier] could not be decremented.\nwpdb error: {$this->db->last_error}."
            );
        }
    }
    
    public function remainingUsage(string $identifier) :int
    {
        $query = "select left_usages from $this->table where id = %s limit 1";
        $query = $this->db->prepare($query, $identifier);
        
        $val = $this->db->get_results($query, ARRAY_N);
        
        if ( ! empty($this->db->last_error)) {
            throw new RuntimeException($this->db->last_error);
        }
        
        if (empty($val)) {
            return 0;
        }
        
        return (int) $val[0][0];
    }
    
    public function gc() :void
    {
        $query = "delete from $this->table where expires < %d";
        $query = $this->db->prepare($query, $this->clock->currentTimestamp());
        $result = $this->db->query($query);
        
        if ($result === false || ! empty($this->db->last_error)) {
            throw new RuntimeException(
                "garbage collection of signed urls did not work.\n {$this->db->last_error}"
            );
        }
    }
    
}