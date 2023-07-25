<?php

namespace Plugin\AdminSecurity4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LoginRecord
 *
 * @ORM\Table(name="plg_admin_record")
 * @ORM\Entity(repositoryClass="Plugin\AdminSecurity4\Repository\LoginRecordRepository")
 */
class LoginRecord extends \Eccube\Entity\AbstractEntity
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
     * @ORM\Column( type="text",nullable=true)
     */
    private $user_name;

    /**
     * @var integer
     * @ORM\Column( type="smallint",nullable=true)
     */
    private $success_flg;

    /**
     * @var string
     * @ORM\Column( type="text",nullable=true)
     */
    private $client_ip;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @var \Eccube\Entity\Member
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="member_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    private $LoginUser;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user_name
     *
     * @param string $userName
     * @return LoginRecord
     */
    public function setUserName($userName)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get user_name
     *
     * @return string 
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set success_flg
     *
     * @param integer $successFlg
     * @return LoginRecord
     */
    public function setSuccessFlg($successFlg)
    {
        $this->success_flg = $successFlg;

        return $this;
    }

    /**
     * Get success_flg
     *
     * @return integer 
     */
    public function getSuccessFlg()
    {
        return $this->success_flg;
    }

    /**
     * Set client_ip
     *
     * @param string $clientIp
     * @return LoginRecord
     */
    public function setClientIp($clientIp)
    {
        $this->client_ip = $clientIp;

        return $this;
    }

    /**
     * Get client_ip
     *
     * @return string 
     */
    public function getClientIp()
    {
        return $this->client_ip;
    }

    /**
     * Set create_date
     *
     * @param \DateTime $createDate
     * @return LoginRecord
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get create_date
     *
     * @return \DateTime 
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set update_date
     *
     * @param \DateTime $updateDate
     * @return LoginRecord
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get update_date
     *
     * @return \DateTime 
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }


    /**
     * Set LoginUser
     *
     * @param \Eccube\Entity\Member $loginUser
     * @return LoginRecord
     */
    public function setLoginUser(\Eccube\Entity\Member $loginUser = null)
    {
        $this->LoginUser = $loginUser;

        return $this;
    }

    /**
     * Get LoginUser
     *
     * @return \Eccube\Entity\Member 
     */
    public function getLoginUser()
    {
        return $this->LoginUser;
    }
}
