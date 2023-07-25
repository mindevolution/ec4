<?php

namespace Plugin\AdminSecurity4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_admin_record_config")
 * @ORM\Entity(repositoryClass="Plugin\AdminSecurity4\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * @var string
     *
     * @ORM\Column(name="admin_deny_hosts", type="text")
     */
    private $admin_deny_hosts;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAdminDenyHosts()
    {
        return $this->admin_deny_hosts;
    }

    /**
     * @param string $admin_deny_hosts
     * @return Config
     */
    public function setAdminDenyHosts($admin_deny_hosts)
    {
        $this->admin_deny_hosts = $admin_deny_hosts;
        return $this;
    }



}
