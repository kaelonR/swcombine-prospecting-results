<?php
namespace SWCPR\Repositories;

use PDO;
use SWCPR\Models\TerrainType;

class TerrainTypeRepository extends RepositoryBase {
    public function initDB(): void {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS TerrainTypes (uid nvarchar(5) NOT NULL, name nvarchar(255) NOT NULL, img_url nvarchar(255) NOT NULL, PRIMARY KEY (uid))");
        $stmt = $this->pdo->prepare("INSERT INTO TerrainTypes (uid, name, img_url) VALUES (:uid, :name, :img_url) ON DUPLICATE KEY UPDATE name = :name, img_url = :img_url");
        $stmt->execute(['uid' => '24:17', 'name' => 'Black Hole Terrain', 'img_url' => '/assets/img/terrain/black%20hole%20terrain.gif']);
        $stmt->execute(['uid' => '24:13', 'name' => 'Cave', 'img_url' => '/assets/img/terrain/cave.gif']);
        $stmt->execute(['uid' => '24:8', 'name' => 'Crater', 'img_url' => '/assets/img/terrain/crater.gif']);
        $stmt->execute(['uid' => '24:1', 'name' => 'Desert', 'img_url' => '/assets/img/terrain/desert.gif']);
        $stmt->execute(['uid' => '24:2', 'name' => 'Forest', 'img_url' => '/assets/img/terrain/forest.jpg']);
        $stmt->execute(['uid' => '24:15', 'name' => 'Gas Giant', 'img_url' => '/assets/img/terrain/gas%20giant.gif']);
        $stmt->execute(['uid' => '24:10', 'name' => 'Glacier', 'img_url' => '/assets/img/terrain/glacier.jpg']);
        $stmt->execute(['uid' => '24:5', 'name' => 'Grassland', 'img_url' => '/assets/img/terrain/grassland.gif']);
        $stmt->execute(['uid' => '24:3', 'name' => 'Jungle', 'img_url' => '/assets/img/terrain/jungle.gif']);
        $stmt->execute(['uid' => '24:11', 'name' => 'Mountain', 'img_url' => '/assets/img/terrain/mountain.gif']);
        $stmt->execute(['uid' => '24:6', 'name' => 'Ocean', 'img_url' => '/assets/img/terrain/ocean.gif']);
        $stmt->execute(['uid' => '24:7', 'name' => 'River', 'img_url' => '/assets/img/terrain/river.gif']);
        $stmt->execute(['uid' => '24:9', 'name' => 'Rock', 'img_url' => '/assets/img/terrain/rock.gif']);
        $stmt->execute(['uid' => '24:16', 'name' => 'Sun Terrain', 'img_url' => '/assets/img/terrain/sun%20terrain.gif']);
        $stmt->execute(['uid' => '24:4', 'name' => 'Swamp', 'img_url' => '/assets/img/terrain/swamp.gif']);
        $stmt->execute(['uid' => '24:12', 'name' => 'Volcanic', 'img_url' => '/assets/img/terrain/volcanic.gif']);
    }

    /**
     * @return TerrainType[];
     */
    public function list(): array {
        $result = $this->pdo->query("SELECT * FROM TerrainTypes")->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new TerrainType($row['uid'], $row['name'], $row['img_url']), $result);
    }

    public function getByUid(string $uid): ?TerrainType {
        $stmt = $this->pdo->prepare("SELECT * FROM TerrainTypes WHERE uid = :uid");
        $stmt->execute(['uid' => $uid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($result))
            return null;

        return new TerrainType($result['uid'], $result['name'], $result['img_url']);
    }
}