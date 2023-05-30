<?php
namespace SWCPR\Repositories;

use PDO;

abstract class RepositoryBase {
    protected readonly PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    abstract function initDB();
}