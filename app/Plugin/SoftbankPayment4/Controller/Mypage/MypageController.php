<?php

namespace Plugin\SoftbankPayment4\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Plugin\SoftbankPayment4\Client\CreditCardInfoClient;
use Plugin\SoftbankPayment4\Exception\SbpsException;
use Plugin\SoftbankPayment4\Form\Type\CreditApiType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerPointRepository;
use Eccube\Repository\CustomerLevelRepository;

class MypageController extends AbstractController
{
    /**
     * @var CreditCardInfoClient
     */
    private $creditCardInfoClient;
	
	/**
     * @var CustomerShopRepository
     */
    protected $customerShopRepository;

    /**
     * @var CustomerLevelRepository
     */
    protected $customerLevelRepository;

    /**
     * @var CustomerPointRepository
     */
    protected $customerPointRepository;

    public function __construct(
		CreditCardInfoClient $creditCardInfoClient,
		CustomerLevelRepository $customerLevelRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerPointRepository $customerPointRepository
	) {
        $this->creditCardInfoClient = $creditCardInfoClient;
		$this->customerLevelRepository = $customerLevelRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->customerPointRepository = $customerPointRepository;
    }

    /**
     * @Route("/mypage/sbps/credit/index", name="sbps_credit_list")
     * @Template("@SoftbankPayment4/default/mypage/credit/index.twig")
     */
    public function index()
    {
		//edit by gzy 计算积分等级过期时间到页面显示
        $isCustomerShop = "N";
        $level4Shop = "";
        $max4Shop = 0;
        $money4Shop = 0;
        $expTime4Shop = "";

        $CustomerPoints = $this->customerPointRepository->getCustomerPointByCustomer($this->getUser());

        $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($this->getUser());
        if($CustomerShop && $CustomerShop->getStatus() == "Y"){
            $isCustomerShop = "Y";
            $level4Shop = $CustomerShop->getCustomerShopLevel()->getLevel();
            $max4Shop = $CustomerShop->getCustomerShopLevel()->getMax();
            $money4Shop = intval($max4Shop) + 1 - intval($CustomerShop->getMoney());
            $expTime4Shop = date( 'Y-m-d', strtotime($CustomerShop->getExpTime()));
        }


        $CustomerLevel = $this->customerLevelRepository->getCustomerLevelByCustomer($this->getUser());
        $level = $CustomerLevel->getCustomerLevelDetail()->getLevel();
        $max = $CustomerLevel->getCustomerLevelDetail()->getMax();
        $money = intval($max) + 1 - intval($CustomerLevel->getMoney());
        $expTime = date( 'Y-m-d', strtotime($CustomerLevel->getExpTime()));

        $month = date( 'Y-m-d', strtotime($now . "+1 month"));
        $expPoints = 0;
         
        foreach ($CustomerPoints as $CustomerPoint) {
            if($CustomerPoint->getStatus() == "Y"){
                $expDate = $CustomerPoint->getExpTime();
                
                if($month >= $expDate){
                    $expPoints += $CustomerPoint->getPoint();
                }
            }
        }
		
		
		
		
        try {
            $response = $this->creditCardInfoClient
                ->setCustomer($this->getUser())
                ->implement();
        } catch (SbpsException $e) {
            $message = 'カード情報の取得に失敗しました。';
            $message.= '(エラーコード: ' . $e->getCode() . ')';
            $this->addError($message);
            return $this->redirectToRoute('mypage');
        } catch (\Exception $e) {
            $this->addError('システム上で予期しないエラーが発生しました。');
            return $this->redirectToRoute('mypage');
        }

        if ($response === null) {
            $arrCardInfo['result'] = 'NG';
        } else {
            $arrNum = str_split($response['cc_number'], 4);
            $arrExpiration = str_split($response['cc_expiration'], 4);

            $arrCardInfo = [
                'result' => 'OK',
                'card_num_1' => $arrNum[0],
                'card_num_2' => $arrNum[1],
                'card_num_3' => $arrNum[2],
                'card_num_4' => $arrNum[3],
                'expiration_year' => $arrExpiration[0],
                'expiration_month' => $arrExpiration[1],
            ];
        }

        return [
            'cardInfo' => $arrCardInfo,
            'isCustomerShop' => $isCustomerShop,
            'level' => $level,
            'money' => $money,
            'expPoints' => $expPoints,
            'expTime' => $expTime,
            'level4Shop' => $level4Shop,
            'money4Shop' => $money4Shop,
            'expTime4Shop' => $expTime4Shop
        ];
    }

    /**
     * @Route("/mypage/sbps/credit/new", name="sbps_credit_store")
     * @Template("@SoftbankPayment4/default/mypage/credit/edit.twig")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function store(Request $request)
    {
        $form = $this->createForm(CreditApiType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->creditCardInfoClient
                    ->setCustomer($this->getUser())
                    ->store($request->request->get('credit_api'));
            } catch (SbpsException $e) {
                $message = 'カード情報の登録に失敗しました。';
                $message.= '(エラーコード: ' . $e->getCode() . ')';
                $this->addError($message);
            } catch (\Exception $e) {
                $this->addError('システム上で予期しないエラーが発生しました。');
            }

            return $this->redirectToRoute('sbps_credit_list');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/mypage/sbps/credit/edit", name="sbps_credit_update")
     * @Template("@SoftbankPayment4/default/mypage/credit/edit.twig")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function update(Request $request)
    {
        $form = $this->createForm(CreditApiType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->creditCardInfoClient
                    ->setCustomer($this->getUser())
                    ->update($request->request->get('credit_api'));
            } catch (SbpsException $e) {
                $message = 'カード情報の更新に失敗しました。';
                $message.= '(エラーコード: ' . $e->getCode() . ')';
                $this->addError($message);
            } catch (\Exception $e) {
                $this->addError('システム上で予期しないエラーが発生しました。');
            }

            return $this->redirectToRoute('sbps_credit_list');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/mypage/sbps/credit/delete", name="sbps_credit_delete", methods={"DELETE"})
     */
    public function delete()
    {
        try {
            $this->creditCardInfoClient
                ->setCustomer($this->getUser())
                ->delete();
        } catch (SbpsException $e) {
            $message = 'カード情報の削除に失敗しました。';
            $message.= '(エラーコード: ' . $e->getCode() . ')';
            $this->addError($message);
        } catch (\Exception $e) {
            $this->addError('システム上で予期しないエラーが発生しました。');
        }

        return $this->redirectToRoute('sbps_credit_list');
    }
}