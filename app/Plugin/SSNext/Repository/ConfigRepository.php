<?php

namespace Plugin\SSNext\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\SSNext\Entity\Config;

/**
 * Class ConfigRepository
 * @package Plugin\SSNext\Repository
 */
class ConfigRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param int $id
     * @return Config
     */
    public function get($id = 1)
    {
        if ($this->config) {
            return $this->config;
        }

        $config = $this->find($id);

        if ($config) {
            $this->config = $config;
        } else {
            $config = new Config();
        }

        return $config;
    }
}