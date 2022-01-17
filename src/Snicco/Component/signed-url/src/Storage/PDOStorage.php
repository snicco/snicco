<?php

declare(strict_types=1);

namespace Snicco\SignedUrl\Storage;

use PDO;
use PDOException;
use RuntimeException;
use Snicco\SignedUrl\SignedUrl;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Exceptions\BadIdentifier;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;

use function is_int;

final class PDOStorage implements SignedUrlStorage
{
    
    /**
     * @var PDO
     */
    private $pdo;
    
    /**
     * @var string
     */
    private $table;
    
    /**
     * @var SignedUrlClock
     */
    private $clock;
    
    /**
     * @param  PDO  $pdo  An initialized PDO instance {@see PDO::ERRMODE_EXCEPTION} should be used.
     */
    public function __construct(PDO $pdo, string $table, SignedUrlClock $clock = null)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->clock = $clock;
    }
    
    public function decrementUsage(string $identifier) :void
    {
        $usage = $this->remainingUsage($identifier);
        
        if ($usage === 0) {
            throw BadIdentifier::for($identifier);
        }
        
        $new_usage = $usage - 1;
        
        if ($new_usage === 0) {
            $stmt = $this->pdo->prepare("delete from $this->table where id = ?");
            $stmt->execute([$identifier]);
            return;
        }
        
        $query = "update $this->table set left_usages = ? where id = ? and left_usages > 0";
        $statement = $this->pdo->prepare($query);
        $statement->execute([$new_usage, $identifier]);
    }
    
    public function remainingUsage(string $identifier) :int
    {
        $query = "select left_usages from $this->table where id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$identifier]);
        
        $usage = $stmt->fetchColumn();
        return is_int($usage) ? $usage : 0;
    }
    
    public function store(SignedUrl $signed_url) :void
    {
        $query = "insert into $this->table(id,expires,left_usages,protects) values (?,?,?,?)";
        
        try {
            $stmt = $this->pdo->prepare($query);
            
            $stmt->execute([
                $signed_url->identifier(),
                $signed_url->expiresAt(),
                $signed_url->maxUsage(),
                $signed_url->protects(),
            ]);
            
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    "Failed to store signed url for protected path [{$signed_url->protects()}]."
                );
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to store signed url for protected path [{$signed_url->protects()}].\n{$e->getMessage()}",
                $e->getCode(),
                $e->getPrevious()
            );
        }
    }
    
    public function gc() :void
    {
        $stmt = $this->pdo->prepare("delete from $this->table where expires < ?");
        $stmt->execute([$this->clock->currentTimestamp()]);
    }
    
}