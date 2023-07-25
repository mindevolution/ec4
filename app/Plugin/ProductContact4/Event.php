<?php

namespace Plugin\ProductContact4;

use Doctrine\ORM\EntityManager;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\ProductRepository;
use Eccube\Util\StringUtil;
use Plugin\ProductContact4\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ProductRepository
     */
    protected $productRepository;


    /**
     * @var ConfigRepository
     */
    protected $configRepository;


    public function __construct(
        ConfigRepository $configRepository,
        EntityManager $entityManager = null,
        ProductRepository $productRepository = null
    )
    {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Product/detail.twig' => 'onRenderProductDetail',
             EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE => 'onFrontContactIndexInitialize',
             'plugin.contact.index.complete' => 'onFrontContactIndexComplete',
        ];
    }


    public function onRenderProductDetail(TemplateEvent $event)
    {
        $label = 'この商品を問い合わせる';
        $Config = $this->configRepository->get();

        if ($Config && StringUtil::isNotBlank($Config->getName())) {
            $label = $Config->getName();
        }

        $event->setParameter('contact_button_label', $label);
        $event->addSnippet('@ProductContact4/Product/contact_button.twig');
    }

    public function onFrontContactIndexInitialize(EventArgs $event)
    {
        $request = $event->getRequest();
        $builder = $event->getArgument('builder');

        if ($request->query->get('product'))
        {
           $Product = $this->productRepository->find($request->query->get('product'));

           if ($Product) {
               $data = $builder->getData();
               $data['Product'] = $Product;
               $builder->setData($data);
           }
        }
    }


    public function onFrontContactIndexComplete(EventArgs $event) {

        $Contact = $event->getArgument('Contact');
        $data = $event->getArgument('data');
        // エンティティを更新
        $Contact
            ->setProduct($data['Product']);

        // DB更新
        $this->entityManager->persist($Contact);
        $this->entityManager->flush($Contact);
    }
}
