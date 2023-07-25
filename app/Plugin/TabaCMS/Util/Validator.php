<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Util;

use Eccube\Application;

use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Validator
{

    private static function getForm(ExecutionContextInterface $context,$error_target = null) {
        $form = $context->getRoot();
        $error_form = null;
        if ($error_target != null) {
            $error_form = $form->get($error_target);
        } else {
            $matches = [];
            preg_match('/.+\[(.+)\].+/', $context->getPropertyPath(), $matches);
            if ($matches && count($matches) >= 2) {
                $error_target = $matches[1];
                $error_form = $form->get($error_target);
            } else {
                log_error("フォーム名の取得に失敗しました。");
                return null;
            }
        }
        return $error_form;
    }

    /**
     * 値が重複していないかチェックします。
     *
     * 記述例
     * use Plugin\PLUGIN_CODE\Util\Validator;
     * new Assert\Callback([
     *     'callback' => array(Validator::class,'unique'),
     *     'payload' => [
     *         'entity' => YourEntity::class,
     *         'column' => 'yourId',
     *         'group_columns' => array('targetEntity','columnName')
     *     ]
     * ]),
     *
     * payload
     * entity                 : [必須] Entityクラス名
     * column              : [必須] ユニークをチェックするカラム名
     * group_columns : [任意] グループカラム名 (グループ内でのユニークチェックを行うため）
     * error_message  : [任意] エラーメッセージ
     * error_target      : [任意] エラーを関連付ける(エラーメッセージを出す)フォーム名
     *
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    public static function unique($value, ExecutionContextInterface $context)
    {
        if (!$value) return;

        $payload = $context->getConstraint()->payload; // 設定情報
        $form = $context->getRoot();
        $data = $form->getData();
        $app = Application::getInstance();
        if (empty($app) || !isset($payload['entity']) || !isset($payload['column'])) return;

        // エラーメッセージを表示するフォーム名を取得します。
        if (isset($payload['error_target'])) {
            $error_form = self::getForm($context,$payload['error_target']);
        } else {
            $error_form = self::getForm($context);
        }

        // ユニークチェック
        $data = $context->getRoot()->getData();
        $qb = $app['orm.em']->createQueryBuilder()
            ->select('T')
            ->from($payload['entity'], 'T')
            ->where('T.' . $payload['column'] . ' = :value')
            ->setParameter('value', $value);
        // グループ内でのユニークのチェック
        if (isset($payload['group_columns'])) {
            if (is_array($payload['group_columns'])) {
                foreach ($payload['group_columns'] as $group_column) {
                    if (! isset($data[$group_column])) {
                        continue;
                    }
                    $qb->andWhere('T.' . $group_column . ' = :value_' . $group_column)->setParameter('value_' . $group_column, $data[$group_column]);
                }
            } else if (isset($data[$payload['group_columns']])) {
                $qb->andWhere('T.' . $payload['group_columns'] . ' = :value_' . $payload['group_columns'])->setParameter('value_' . $payload['group_columns'], $data[$payload['group_columns']]);
            }
        }
        $query = $qb->getQuery();
        $res = $query->getResult();
        if (count($res) === 0) {
            return;
        }
        if (count($res) === 1) {
            // すでにデータがある場合には、自身のデータを除くために、Entityから主キーを取得し自身のデータであるかをチェックします
            $meta = $app['orm.em']->getClassMetadata($payload['entity']);
            $ids = $meta->getIdentifierFieldNames();
            $is_match = true;
            if ($ids) {
                foreach ($ids as $id) {
                    // 一致しないデータの場合は、自身のデータではない
                    if ($data[$id] !== $res[0][$id]) {
                        $is_match = false;
                        break;
                    }
                }
                if ($is_match) return;
            }
        }

        $message = "既に使用しています。";
        if (!empty($payload['error_message'])) $message = $payload['error_message'];

        if ($error_form) $error_form->addError(new FormError($message));
    }

    /**
     * データキーの文字列として正しいかチェックします。
     *
     * ・「a-z , 0-9 , - , _」の文字列であること。
     * ・1文字目に - , _ は使用できません。
     *
     * @param string $value
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public static function validDataKey($value, \Symfony\Component\Validator\Context\ExecutionContextInterface $context)
    {
        $payload = $context->getConstraint()->payload; // 設定情報

        // エラーメッセージを表示するフォーム名を取得します。
        if (isset($payload['error_target'])) {
            $error_form = self::getForm($context,$payload['error_target']);
        } else {
            $error_form = self::getForm($context);
        }

        if (preg_match('/[^a-z0-9\-\_]+/', $value)) {
            $error_form->addError(new FormError("小文字アルファベット(a-z)、ハイフン(-)、アンダーバー(_)で入力してください。"));
        }
        if (preg_match('/^[\-\_0-9]/', $value)) {
            $error_form->addError(new FormError("1文字目に記号と数字は使用できません。"));
        }
    }

    /**
     * 文字列の開始に指定された文字が含まれていないかチェックします。
     *
     * @param string $value
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public static function validStartsWith($value, \Symfony\Component\Validator\Context\ExecutionContextInterface $context)
    {
        $payload = $context->getConstraint()->payload; // 設定情報

        // エラーメッセージを表示するフォーム名を取得します。
        if (isset($payload['error_target'])) {
            $error_form = self::getForm($context,$payload['error_target']);
        } else {
            $error_form = self::getForm($context);
        }

        if (! empty($payload['valid_string'])) {
            if (is_array($payload['valid_string'])) {
                foreach ($payload['valid_string'] as $str) {
                    if (strpos($value, $str) === 0) {
                        $error_form->addError(new FormError($str . " を文字列の先頭に指定することはできません。"));
                    }
                }
            } else if (strpos($value, $payload['valid_string']) === 0) {
                $error_form->addError(new FormError($payload['valid_string'] . " を文字列の先頭に指定することはできません。"));
            }
        }
    }
}
