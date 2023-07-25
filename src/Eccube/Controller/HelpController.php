<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class HelpController extends AbstractController
{
    /**
     * HelpController constructor.
     */
    public function __construct()
    {
    }

    /**
     * 特定商取引法.
     *
     * @Route("/help/tradelaw", name="help_tradelaw")
     * @Template("Help/tradelaw.twig")
     */
    public function tradelaw()
    {
        return [];
    }

    /**
     * ご利用ガイド.
     *
     * @Route("/help/guide", name="help_guide")
     * @Template("Help/guide.twig")
     */
    public function guide()
    {
        return [];
    }

    /**
     * 当サイトについて.
     *
     * @Route("/help/about", name="help_about")
     * @Template("Help/about.twig")
     */
    public function about()
    {
        return [];
    }

    /**
     * プライバシーポリシー.
     *
     * @Route("/help/privacy", name="help_privacy")
     * @Template("Help/privacy.twig")
     */
    public function privacy()
    {
        return [];
    }

    /**
     * 利用規約.
     *
     * @Route("/help/agreement", name="help_agreement")
     * @Template("Help/agreement.twig")
     */
    public function agreement()
    {
        return [];
    }

    /**
     * プロ会員 利用規約.
     *
     * @Route("/help/pro_agreement", name="help_pro_agreement")
     * @Template("Help/pro_agreement.twig")
     */
    public function pro_agreement()
    {
        return [];
    }


    /**
     * FAQ.
     *
     * @Route("/help/faq", name="help_faq")
     * @Template("Help/faq.twig")
     */
    public function faq()
    {
        return [];
    }

    /**
     * 会員サービス.
     *
     * @Route("/help/member", name="help_member")
     * @Template("Help/member.twig")
     */
    public function member()
    {
        return [];
    }

    /**
     * プロ会員サービス.
     *
     * @Route("/help/pro_member", name="help_pro_member")
     * @Template("Help/pro_member.twig")
     */
    public function pro_member()
    {
        return [];
    }


	/**
     * 利用規約.
     *
     * @Route("/help/shopagreement", name="help_shop_agreement")
     * @Template("Help/shopagreement.twig")
     */
    public function shopagreement()
    {
        return [];
    }

















}
