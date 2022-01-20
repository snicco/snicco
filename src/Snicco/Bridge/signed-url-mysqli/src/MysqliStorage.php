<?php

declare(strict_types=1);

namespace Snicco\Bridge\SingedUrlMysqli;

use mysqli;
use RuntimeException;
use mysqli_sql_exception;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Contracts\SignedUrlClock;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\SignedUrlClockUsingDateTimeImmutable;

use function intval;

final class MysqliStorage implements SignedUrlStorage
{
    
    /**
     * @var mysqli
     */
    private $mysqli;
    
    /**
     * @var string
     */
    private $table;
    
    /**
     * @var SignedUrlClock
     */
    private $clock;
    
    /**
     * @param  mysqli  $mysqli
     * Error reporting should be configured to throw exceptions on errors. {@see mysqli_report()}
     */
    public function __construct(mysqli $mysqli, string $table_name, SignedUrlClock $clock = null)
    {
        $this->mysqli = $mysqli;
        $this->table = $table_name;
        $this->clock = $clock ?? new SignedUrlClockUsingDateTimeImmutable();
    }
    
    public function consume(string $identifier) :void
    {
        $left_usages = $this->remainingUsage($identifier);
        
        if ($left_usages === 0) {
            throw BadIdentifier::for($identifier);
        }
        
        $new = $left_usages - 1;
        
        if ($new === 0) {
            $query = "delete from $this->table where id = ?";
            $statement = $this->mysqli->prepare($query);
            $statement->bind_param('s', $identifier);
        }
        else {
            $query = "update $this->table set left_usages = ? where id = ? and left_usages > 0";
            $statement = $this->mysqli->prepare($query);
            $statement->bind_param('is', $new, $identifier);
        }
        
        $statement->execute();
        
        if ($statement->affected_rows !== 1) {
            throw new RuntimeException(
                "Usage for id [$identifier] could not be decremented.\nError: {$this->mysqli->error}"
            );
        }
    }
    
    public function gc() :void
    {
        $query = "delete from $this->table where expires < ?";
        $statement = $this->mysqli->prepare($query);
        $ts = $this->clock->currentTimestamp();
        $statement->bind_param('i', $ts);
        $statement->execute();
    }
    
    public function remainingUsage(string $identifier) :int
    {
        $query = "select left_usages from $this->table where id = ? limit 1";
        
        $statement = $this->mysqli->prepare($query);
        
        $statement->bind_param('s', $identifier);
        $statement->execute();
        
        $result = $statement->get_result();
        $row = $result->fetch_row();
        
        return intval($row[0] ?? 0);
    }
    
    public function store(SignedUrl $signed_url) :void
    {
        $query = "insert into $this->table(id,expires,left_usages,protects) values (?,?,?,?) ";
        
        $payload = [
            $signed_url->identifier(),
            $signed_url->expiresAt(),
            $signed_url->maxUsage(),
            $signed_url->protects(),
        ];
        
        try {
            $stmt = $this->mysqli->prepare($query);
            
            $stmt->bind_param('siis', ...$payload);
            
            $stmt->execute();
            
            if ($stmt->affected_rows !== 1) {
                throw new RuntimeException(
                    "Failed to store signed url for path [{$signed_url->protects()}].\nMysqli error: {$this->mysqli->error}."
                );
            }
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException(
                "Failed to store signed url for protected path [{$signed_url->protects()}].\n{$e->getMessage()}",
                $e->getCode(),
                $e->getPrevious()
            );
        }
    }
    
}