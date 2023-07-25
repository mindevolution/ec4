<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Common;

abstract class AbstractConstants
{

    /**
     * @var string プラグインコード
     */
    const PLUGIN_CODE = "TabaCMS";

    /**
     * @var string プラグインコード(小文字)
     */
    const PLUGIN_CODE_LC = "tabacms";

    /**
     * @var string コンテナに登録するキー値
     */
    const CONTAINER_KEY_NAME = "spreadworks.taba";

    /**
     * @var string プラグインカテゴリーID
     */
    const PLUGIN_CATEGORY_ID = "taba-app";

    /**
     * @var string プラグインカテゴリー名
     */
    const PLUGIN_CATEGORY_NAME = "taba&trade; app";

    /**
     *
     * @var string 管理画面用ルーティング接頭詞
     */
    const ADMIN_BIND_PREFIX = 'admin_plugin_tabacms';

    /**
     *
     * @var string 管理画面用URI接頭詞
     */
    const ADMIN_URI_PREFIX = '/%eccube_admin_route%/plugin/taba-app/tabacms';

    /**
     *
     * @var string 管理画面用コントローラー
     */
    const ADMIN_CONTROLLER = "Plugin\\TabaCMS\\Controller\\AdminController";

    /**
     *
     * @var string フロント用ルーティング接頭詞
     */
    const FRONT_BIND_PREFIX = 'plugin_tabacms';

    /**
     *
     * @var string フロント用URI接頭詞
     */
    const FRONT_URI_PREFIX = '/plugin/tabacms';

    /**
     *
     * @var string フロント用コントローラー
     */
    const FRONT_CONTROLLER = "Plugin\\TabaCMS\\Controller\\FrontController";

    /**
     *
     * @var string テンプレート設置パス
     */
    const TEMPLATE_PATH = 'TabaCMS/Resource/template';

    /**
     *  キャッシュヘッダーを出力有無設定をコンテナに保存する値のキーです。
     * 
     * @var string
     */
    const HTTP_CACHE_STATUS = self::PLUGIN_CODE .  "_HTTP_CACHE_STATUS";

    /**
     *
     * @var string プラグイン用ロガー
     */
    const LOGGER = 'monolog.logger.tabacms';

    /**
     *
     * @var array Application に登録する Repository のキー値
     */
    public static $REPO = array(
        'CATEGORY' => 'eccube.repository.tabacms.category',
        'TYPE' => 'eccube.repository.tabacms.type',
        'POST' => 'eccube.repository.tabacms.post'
    );

    /**
     *
     * @var array Application に登録する Entity のキー値
     */
    public static $ENTITY = array(
        'CATEGORY' => "Plugin\\TabaCMS\\Entity\\Category",
        'TYPE' => "Plugin\\TabaCMS\\Entity\\Type",
        'POST' => "Plugin\\TabaCMS\\Entity\\Post"
    );

    /**
     *
     * @var string 管理画面で使用するページタイトル名
     */
    const PAGE_TITLE = 'taba&trade; app CMS';

    /**
     * ウィジット
     */
    public static $WIDGETS = array(
        'category'
    );

    const DEFAULT_PAGE_COUNT = 15;

    const DATA_HOLDER_KEY_PAGE = "STATUS_PAGE";

    const PAGE_POST = "post";

    const PAGE_LIST = "list";

    const PAGE_UNKNOWN = "unknown";

    const DATA_HOLDER_KEY_TYPE_DK = "TYPE_DATA_KEY";

    const DATA_HOLDER_KEY_POST_DK = "POST_DATA_KEY";

    /**
     * @var string 設定ファイル
     */
    const USER_CONFIG_FILE = "user_config.yml";

    /**
     * @var string プラグインで使用するデータを保管するディレクトリ
     */
    const PLUGIN_DATA_DIR = DIRECTORY_SEPARATOR . 'app' .  DIRECTORY_SEPARATOR . 'PluginData' . DIRECTORY_SEPARATOR . Constants::PLUGIN_CODE;
}
