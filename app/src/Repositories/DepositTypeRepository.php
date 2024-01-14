<?php
namespace SWCPR\Repositories;

use PDO;
use SWCPR\Models\DepositType;

class DepositTypeRepository extends RepositoryBase {
    public function initDB(): void {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS DepositTypes (uid nvarchar(5) NOT NULL, name nvarchar(255) NOT NULL, img_url nvarchar(255) NOT NULL, PRIMARY KEY (uid))");
        $stmt = $this->pdo->prepare("INSERT INTO DepositTypes (uid, name, img_url) VALUES (:uid, :name, :img_url) ON DUPLICATE KEY UPDATE name = :name, img_url = :img_url");
        $stmt->execute(['uid' => '16:1', 'name' => 'Quantum', 'img_url' => '/assets/img/deposit/quantum.gif']);
        $stmt->execute(['uid' => '16:2', 'name' => 'Meleenium', 'img_url' => '/assets/img/deposit/meleenium.gif']);
        $stmt->execute(['uid' => '16:3', 'name' => 'Ardanium', 'img_url' => '/assets/img/deposit/ardanium.png']);
        $stmt->execute(['uid' => '16:4', 'name' => 'Rudic', 'img_url' => '/assets/img/deposit/rudic.gif']);
        $stmt->execute(['uid' => '16:5', 'name' => 'Ryll', 'img_url' => '/assets/img/deposit/ryll.png']);
        $stmt->execute(['uid' => '16:6', 'name' => 'Duracrete', 'img_url' => '/assets/img/deposit/duracrete.png']);
        $stmt->execute(['uid' => '16:7', 'name' => 'Alazhi', 'img_url' => '/assets/img/deposit/alazhi.png']);
        $stmt->execute(['uid' => '16:8', 'name' => 'Laboi', 'img_url' => '/assets/img/deposit/laboi.gif']);
        $stmt->execute(['uid' => '16:9', 'name' => 'Adegan', 'img_url' => '/assets/img/deposit/adegan.png']);
        $stmt->execute(['uid' => '16:10', 'name' => 'Rockivory', 'img_url' => '/assets/img/deposit/rockivory.gif']);
        $stmt->execute(['uid' => '16:11', 'name' => 'Tibannagas', 'img_url' => '/assets/img/deposit/tibannagas.png']);
        $stmt->execute(['uid' => '16:12', 'name' => 'Nova', 'img_url' => '/assets/img/deposit/nova.gif']);
        $stmt->execute(['uid' => '16:13', 'name' => 'Varium', 'img_url' => '/assets/img/deposit/varium.png']);
        $stmt->execute(['uid' => '16:14', 'name' => 'Varmigio', 'img_url' => '/assets/img/deposit/varmigio.gif']);
        $stmt->execute(['uid' => '16:15', 'name' => 'Lommite', 'img_url' => '/assets/img/deposit/lommite.gif']);
        $stmt->execute(['uid' => '16:16', 'name' => 'Hibridium', 'img_url' => '/assets/img/deposit/hibridium.gif']);
        $stmt->execute(['uid' => '16:17', 'name' => 'Durelium', 'img_url' => '/assets/img/deposit/durelium.png']);
        $stmt->execute(['uid' => '16:18', 'name' => 'Lowickan', 'img_url' => '/assets/img/deposit/lowickan.png']);
        $stmt->execute(['uid' => '16:19', 'name' => 'Vertex', 'img_url' => '/assets/img/deposit/vertex.png']);
        $stmt->execute(['uid' => '16:20', 'name' => 'Berubian', 'img_url' => '/assets/img/deposit/berubian.gif']);
        $stmt->execute(['uid' => '16:21', 'name' => 'Bacta', 'img_url' => '/assets/img/deposit/bacta.png']);
    }

    /**
     * @return DepositType[];
     */
    public function list(): array {
        $result = $this->pdo->query("SELECT * FROM DepositTypes")->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new DepositType($row['uid'], $row['name'], $row['img_url']), $result);
    }

    public function getByUid(string $uid): ?DepositType {
        $stmt = $this->pdo->prepare("SELECT * FROM DepositTypes WHERE uid = :uid");
        $stmt->execute(['uid' => $uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($result))
            return null;

        return new DepositType($result['uid'], $result['name'], $result['img_url']);
    }
}