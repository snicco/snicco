<?php

declare(strict_types=1);

namespace Snicco\Http;

use wpdb;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;

use function wp_cache_add;
use function wp_cache_get;

class DatabaseMagicLink extends MagicLink
{
    
    private string $table;
    private wpdb   $wpdb;
    
    public function __construct(string $table, array $lottery = [4, 100])
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix.$table;
        $this->lottery = $lottery;
    }
    
    public function notUsed(Request $request) :bool
    {
        $hash = md5($request->query('signature', ''));
        
        $query = $this->wpdb->prepare(
            "SELECT EXISTS(SELECT 1 FROM $this->table WHERE signature = %s LIMIT 1)",
            $hash
        );
        
        $exists = $this->wpdb->get_var($query);
        
        return (is_string($exists) && $exists === '1');
    }
    
    public function gc() :bool
    {
        $must_be_newer_than = $this->currentTime();
        
        $query = $this->wpdb->prepare(
            "DELETE FROM $this->table WHERE `expires` <= %d",
            $must_be_newer_than
        );
        
        return $this->wpdb->query($query) !== false;
    }
    
    public function destroy($signature)
    {
        $hash = md5($signature);
        
        $this->wpdb->delete($this->table, ['signature' => $hash], ['%s']);
    }
    
    public function store(string $signature, int $expires) :bool
    {
        $cached = wp_cache_get($signature, 'magic_links');
        
        if ($cached !== false) {
            return true;
        }
        
        $query = $this->wpdb->prepare(
            "INSERT INTO `$this->table` (`signature`, `expires`) VALUES(%s, %d)",
            md5($signature),
            $expires
        );
        
        wp_cache_add($signature, $signature, 'magic_links', $expires);
        
        $result = $this->wpdb->query($query);
        
        return $result !== false && $result !== 0;
    }
    
}