<?php

namespace Plugin\RakutenCard4\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Repository\QueryKey;
use Eccube\Repository\TagRepository;

class SearchOrderQueryCustomizer implements QueryCustomizer
{

    /** @var TagRepository $tagRepository */
    protected $tagRepository;

    /**
     * SearchProductQueryCustomizer constructor.
     * @param TagRepository $tagRepository
     * @param EntityManager $entityManager
     */
    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    public function getQueryKey()
    {
        return QueryKey::ORDER_SEARCH_ADMIN;
    }

    public function customize(QueryBuilder $builder, $params, $queryKey)
    {
        if (!empty($params['rakuten_payment_status']) && $params['rakuten_payment_status']) {
            $payments = [];
            foreach ($params['rakuten_payment_status'] as $rakuten_payment_status) {
                $payments[] = $rakuten_payment_status;
            }
            $builder
                ->leftJoin('o.Rc4OrderPayment', 'r4o')
                ->andWhere($builder->expr()->in('r4o.payment_status', ':payment_status'))
                ->setParameter('payment_status', $payments);

        }
    }
}