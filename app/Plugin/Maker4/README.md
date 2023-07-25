# メーカー管理プラグイン

[![Build Status](https://travis-ci.org/EC-CUBE/maker-plugin.svg?branch=master)](https://travis-ci.org/EC-CUBE/maker-plugin)
[![Build status](https://ci.appveyor.com/api/projects/status/1b1670q628q3h2vo?svg=true)](https://ci.appveyor.com/project/ECCUBE/maker-plugin)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/36f4dd74-3e50-48a2-8b47-de7ec9762333/mini.png)](https://insight.sensiolabs.com/projects/36f4dd74-3e50-48a2-8b47-de7ec9762333)
[![Coverage Status](https://coveralls.io/repos/github/EC-CUBE/maker-plugin/badge.svg?branch=master)](https://coveralls.io/github/EC-CUBE/maker-plugin?branch=master)

## 概要
商品詳細ページに、メーカー情報を表示できるようになるプラグイン。

## フロント
### 商品詳細ページに、メーカー情報を表示できる
- メーカー名を表示する。
- メーカーにURLが登録されている場合は、メーカー名からリンクする。
- メーカー情報の登録が無い場合は、何も表示しない。

## 管理画面
### メーカーを登録することができる。
- 商品管理>メーカー管理画面で、メーカーを登録、削除することができる。

### 商品ごとに、メーカーを関連付けをすることができる。
- メーカー管理で登録したメーカーから選択することができる。
- 商品ごとに、メーカーのURLを設定することができる。
