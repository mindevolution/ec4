<?php

namespace Plugin\RakutenCard4\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Entity\Rc4CustomerToken;
use Plugin\RakutenCard4\Form\Type\CardDeleteType;
use Plugin\RakutenCard4\Repository\Rc4CustomerTokenRepository;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\RouteService;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * カード登録処理
 */
class MypageRegisterCardController extends AbstractController
{
    /** @var CardService */
    protected $cardService;
    /** @var Rc4CustomerTokenRepository */
    protected $customerTokenRepository;
    /** @var RouteService */
    protected $routeService;
    /** @var EccubeConfigEx */
    protected $configEx;

    /**
     * MypageRegisterCardController constructor.
     *
     * @param CardService $cardService
     * @param Rc4CustomerTokenRepository $customerTokenRepository
     * @param EntityManagerInterface $entityManager
     * @param RouteService $routeService
     * @param EccubeConfigEx $configEx
     */
    public function __construct(
        CardService $cardService
        , Rc4CustomerTokenRepository $customerTokenRepository
        , EntityManagerInterface $entityManager
        , RouteService $routeService
        , EccubeConfigEx $configEx
    )
    {
        $this->cardService = $cardService;
        $this->customerTokenRepository = $customerTokenRepository;
        $this->entityManager = $entityManager;
        $this->routeService = $routeService;
        $this->configEx = $configEx;
    }

    /**
     * カード登録画面
     *
     * @Route("/mypage/rakuten_card4", name="rakuten_card4_mypage")
     * @Template("@RakutenCard4/mypage_register_card.twig")
     */
    public function index(Request $request)
    {
        $common_message = '----------MypageRegisterCardController::index ----------';
        Rc4LogUtil::info($common_message . 'start');

        /** @var Customer $Customer */
        $Customer = $this->getUser();
        $CustomerTokens = $this->customerTokenRepository->getRegisterCardList($Customer);

        $DeleteForm = $this->createForm(CardDeleteType::class);

        Rc4LogUtil::info($common_message . '完了');

        return [
            'CustomerTokens' => $CustomerTokens,
            'token_url' => $this->cardService->getPayVaultTokenUrl(),
            'service_id' => $this->cardService->getServiceId(),
            'deleteForm' => $DeleteForm->createView(),
            'ableRegisterCard' => $this->cardService->ableRegisterCard($Customer),
        ];
    }

    /**
     * クレジットカード登録
     *
     * @Route("/mypage/rakuten_card4/register", name="rakuten_card4_mypage_register")
     */
    public function register(Request $request)
    {
        $common_message = '----------MypageRegisterCardController::register ----------';
        Rc4LogUtil::info($common_message . 'start');

        // いずれにしてもカード編集画面に戻る
        $response = $this->redirectToRoute('rakuten_card4_mypage');

        if ($request->getMethod() !== 'POST') {
            $this->addDanger(trans('rakuten_card4.front.mypage.card.register.failure'));
            Rc4LogUtil::info($common_message . 'POSTではないアクセス');
            return $response;
        }

        /** @var Customer $Customer */
        $Customer = $this->getUser();
        if (!$this->cardService->ableRegisterCard($Customer)){
            $this->addDanger(trans('rakuten_card4.front.mypage.card.register.max_count'));
            Rc4LogUtil::info($common_message . 'カードの登録が上限に達しました。');
            return $response;
        }

        // トークンチェック
        $cardForm = $this->cardService->getCardForm();
        $cardForm->handleRequest($request);
        $token_data = $cardForm->getData();

        // シグネチャーチェック
        $isSuccess = $this->cardService->checkTokenSignature($token_data);
        if ($this->cardService->isResultFailure()){
            $display_error = $this->cardService->getDisplayErrorMsg();
            $this->addDanger($display_error);
            $this->cardService->writeLogResponseFailure($common_message . ' payvaultからのfailure');
            return $response;
        }

        if (!$isSuccess){
            // シグネチャー
            $this->addDanger(trans('rakuten_card4.front.mypage.card.register.failure'));
            Rc4LogUtil::info($common_message . 'シグネチャーの不一致');
            return $response;
        }

        // テーブルへの登録
        $CustomerToken = new Rc4CustomerToken();
        $CustomerToken->setCustomer($Customer);
        $this->cardService->setTokenEntityCommon($CustomerToken, null, $Customer);
        $this->cardService->registerTokenInfo($CustomerToken, $token_data);
        $this->entityManager->persist($CustomerToken);
        $Customer->addRc4CustomerToken($CustomerToken);
        $this->entityManager->flush();

        // リクエストデータ作成
        $this->cardService->setAuthModeOnRegisterCard();
        $request_data = $this->cardService->getAuthorizeCommonData($CustomerToken);
        // コールバックURLの設定
        $request_data['callbackUrl'] = $this->generateUrl('rakuten_card4_authorize_html_receive_register_card', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $token_data = $this->cardService->getTokenCommonParam($CustomerToken, 0, CardService::CARD_TOKEN_VERSION_FOR_AUTH_HTML);
        $request_data['cardToken'] = $token_data;
        // 3Dセキュア マイページで使う場合に設定する
        if ($this->configEx->card_mypage_3d_use()){
            $this->cardService->set3dSecureData($request_data, $CustomerToken);
        }
        $sendData = $this->cardService->getAuthHtmlPostData($request_data);

        // レスポンスデータ作成
        $contents = $this->cardService->getContentsAuthHtml($sendData, $this->cardService->getAuthorizeHtmlUrl());
        $response = Response::create($contents);

        Rc4LogUtil::info($common_message . '完了');
        return $response;
    }

    /**
     * クレジットカード削除
     *
     * @Route("/mypage/rakuten_card4/delete", name="rakuten_card4_mypage_delete")
     */
    public function delete(Request $request)
    {
        $common_message = '----------MypageRegisterCardController::index ----------';
        Rc4LogUtil::info($common_message . 'start');

        $form = $this->createForm(CardDeleteType::class);
        $form->handleRequest($request);

        // いずれにしてもカード編集画面に戻る
        $response = $this->redirectToRoute('rakuten_card4_mypage');

        // 削除処理
        $isValid = $form->isSubmitted() && $form->isValid();
        if (!$isValid) {
            Rc4LogUtil::info($common_message . 'Form　エラー');
            $this->addDanger(trans('rakuten_card4.front.mypage.card.delete.failure'));
            return $response;
        }

        // データチェック
        $data = $form->getData();
        /** @var Rc4CustomerToken[] $CustomerTokens */
        $CustomerTokens = $data['CustomerToken'];
        $count = count($CustomerTokens);
        if (count($CustomerTokens) == 0){
            $this->addDanger(trans('rakuten_card4.front.mypage.card.delete.none'));
            Rc4LogUtil::info($common_message . '削除対象なし');
            return $response;
        }

        Rc4LogUtil::info($common_message . 'エラーなし、削除開始', ['count' => $count]);
        // 削除処理
        foreach ($CustomerTokens as $customerToken){
            $log = [
                'id' => $customerToken->getId(),
                'transaction_id' => $customerToken->getTransactionId(),
            ];
            Rc4LogUtil::info($common_message . '登録カードを削除します', $log);
            $this->entityManager->remove($customerToken);
            $this->entityManager->flush();
            Rc4LogUtil::info($common_message . '登録カードを削除しました', $log);
        }

        $this->addSuccess(trans('rakuten_card4.front.mypage.card.delete.success'));

        Rc4LogUtil::info($common_message . '完了');
        return $response;
    }
}
