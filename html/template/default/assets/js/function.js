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

$(function() {

    $('.pagetop').hide();

    $(window).on('scroll', function() {
        // ページトップフェードイン
        if ($(this).scrollTop() > 300) {
            $('.pagetop').fadeIn();
        } else {
            $('.pagetop').fadeOut();
        }
        //edit by gzy
        var height = $('#page_shopping .ec-orderAccount').height()+$('#page_shopping .ec-orderDelivery').height()+$('#page_shopping .ec-orderPayment').height()+$('#page_shopping .ec-orderConfirm').height()+200;


        // PC表示の時のみに適用
        if (window.innerWidth > 767) {

            if ($('.ec-orderRole').length) {

                var side = $(".ec-orderRole__summary"),
                    wrap = $(".ec-orderRole").first(),
                    min_move = wrap.offset().top,
                    max_move = wrap.height(),
                    margin_bottom = max_move - min_move;

                var scrollTop = $(window).scrollTop();
                //edit by gzy
                if(scrollTop > height){
                    return false;
                }
                if (scrollTop > min_move && scrollTop < max_move) {
                    var margin_top = scrollTop - min_move;
                    side.css({"margin-top": margin_top});
                } else if (scrollTop < min_move) {
                    side.css({"margin-top": 0});
                } else if (scrollTop > max_move) {
                    side.css({"margin-top": margin_bottom});
                }

            }
        }
        return false;
    });


    $('.ec-headerNavSP').on('click', function() {
        $('.ec-layoutRole').toggleClass('is_active');
        $('.ec-drawerRole').toggleClass('is_active');
        $('.ec-drawerRoleClose').toggleClass('is_active');
        $('body').toggleClass('have_curtain');
    });

    $('.ec-overlayRole').on('click', function() {
        $('body').removeClass('have_curtain');
        $('.ec-layoutRole').removeClass('is_active');
        $('.ec-drawerRole').removeClass('is_active');
        $('.ec-drawerRoleClose').removeClass('is_active');
    });

    $('.ec-drawerRoleClose').on('click', function() {
        $('body').removeClass('have_curtain');
        $('.ec-layoutRole').removeClass('is_active');
        $('.ec-drawerRole').removeClass('is_active');
        $('.ec-drawerRoleClose').removeClass('is_active');
    });

    // TODO: カート展開時のアイコン変更処理
    $('.ec-headerRole__cart').on('click', '.ec-cartNavi', function() {
        // $('.ec-cartNavi').toggleClass('is-active');
        $('.ec-cartNaviIsset').toggleClass('is-active');
        $('.ec-cartNaviNull').toggleClass('is-active')
    });

    $('.ec-headerRole__cart').on('click', '.ec-cartNavi--cancel', function() {
        // $('.ec-cartNavi').toggleClass('is-active');
        $('.ec-cartNaviIsset').toggleClass('is-active');
        $('.ec-cartNaviNull').toggleClass('is-active')
    });

    $('.ec-orderMail__link').on('click', function() {
        $(this).siblings('.ec-orderMail__body').slideToggle();
    });

    $('.ec-orderMail__close').on('click', function() {
        $(this).parent().slideToggle();
    });

    $('.is_inDrawer').each(function() {
        var html = $(this).html();
        $(html).appendTo('.ec-drawerRole');
    });

    $('.ec-blockTopBtn').on('click', function() {
        $('html,body').animate({'scrollTop': 0}, 500);
    });

    // スマホのドロワーメニュー内の下層カテゴリ表示
    // TODO FIXME スマホのカテゴリ表示方法
    $('.ec-itemNav ul a').click(function() {
        var child = $(this).siblings();
        if (child.length > 0) {
            if (child.is(':visible')) {
                return true;
            } else {
                child.slideToggle();
                return false;
            }
        }
    });

    // イベント実行時のオーバーレイ処理
    // classに「load-overlay」が記述されていると画面がオーバーレイされる
    $('.load-overlay').on({
        click: function() {
            loadingOverlay();
        },
        change: function() {
            loadingOverlay();
        }
    });

    // submit処理についてはオーバーレイ処理を行う
    $(document).on('click', 'input[type="submit"], button[type="submit"]', function() {

        // html5 validate対応
        var valid = true;
        var form = getAncestorOfTagType(this, 'FORM');

        if (typeof form !== 'undefined' && !form.hasAttribute('novalidate')) {
            // form validation
            if (typeof form.checkValidity === 'function') {
                valid = form.checkValidity();
            }
        }

        if (valid) {
            loadingOverlay();
        }
    });
});

$(window).on('pageshow', function() {
    loadingOverlay('hide');
});

/**
 * オーバーレイ処理を行う関数
 */
function loadingOverlay(action) {

    if (action == 'hide') {
        $('.bg-load-overlay').remove();
    } else {
        $overlay = $('<div class="bg-load-overlay">');
        $('body').append($overlay);
    }
}

/**
 *  要素FORMチェック
 */
function getAncestorOfTagType(elem, type) {

    while (elem.parentNode && elem.tagName !== type) {
        elem = elem.parentNode;
    }

    return (type === elem.tagName) ? elem : undefined;
}

// anchorをクリックした時にformを裏で作って指定のメソッドでリクエストを飛ばす
// Twigには以下のように埋め込む
// <a href="PATH" {{ csrf_token_for_anchor() }} data-method="(put/delete/postのうちいずれか)" data-confirm="xxxx" data-message="xxxx">
//
// オプション要素
// data-confirm : falseを定義すると確認ダイアログを出さない。デフォルトはダイアログを出す
// data-message : 確認ダイアログを出す際のメッセージをデフォルトから変更する
//
$(function() {
    var createForm = function(action, data) {
        var $form = $('<form action="' + action + '" method="post"></form>');
        for (input in data) {
            if (data.hasOwnProperty(input)) {
                $form.append('<input name="' + input + '" value="' + data[input] + '">');
            }
        }
        return $form;
    };

    $('a[token-for-anchor]').click(function(e) {
        e.preventDefault();
        var $this = $(this);
        var data = $this.data();
        if (data.confirm != false) {
            if (!confirm(data.message ? data.message : eccube_lang['common.delete_confirm'] )) {
                return false;
            }
        }

        // 削除時はオーバーレイ処理を入れる
        loadingOverlay();

        var $form = createForm($this.attr('href'), {
            _token: $this.attr('token-for-anchor'),
            _method: data.method
        }).hide();

        $('body').append($form); // Firefox requires form to be on the page to allow submission
        $form.submit();
    });
});
















$(function() {
    var width = document.body.clientWidth;
    if(width > 768){
        $('.ec-headerNaviRole__left').addClass('dis_none');

        $('.custom_search_btn').click(function(){
            if($('.ec-headerNaviRole__left').hasClass('dis_none')){
                $('.ec-headerNaviRole__left').removeClass('dis_none');
            }
            else{
                $('.ec-headerNaviRole__left').addClass('dis_none');
            }
        })
    }
    else{
        $('.ec-headerNaviRole .ec-headerNaviRole__search').addClass('dis_none');

        $('.custom_search_btn').click(function(){
            if($('.ec-headerNaviRole .ec-headerNaviRole__search').hasClass('dis_none')){
                $('.ec-headerNaviRole .ec-headerNaviRole__search').removeClass('dis_none');
            }
            else{
                $('.ec-headerNaviRole .ec-headerNaviRole__search').addClass('dis_none');
            }
        })
        
    }

    if(width <= 768){
        
        var custom_header_logo_nav_ul = $('.custom_header_logo_nav_ul').html();
        var str_custom_header_logo_nav_ul = '<div class="custom_header_logo_nav_ul">' + custom_header_logo_nav_ul + '</div>';

        $('.ec-drawerRole').append(str_custom_header_logo_nav_ul);
    }
    
    










    var $slideshow = $('.ec-newsRole__news');

    if ($slideshow.length) {
        

        $slideshow.slick({
            slidesToShow: 1,
            infinite: true,
            arrows: true,
            dots: false,
            // fade: true,
            // cssEase: 'linear',
            // autoplay: true,
            // autoplaySpeed: 5000,
            // pauseOnFocus: false,
            // pauseOnHover: false,
            // appendArrows: '.c-new-slideshow__arrow'
        });
    }







    var cat = $('.ec-headerCategoryArea').html();
    var str = '<div class="custom_cat">' + cat + '</div>';

    $('#page_product_list .ec-layoutRole__main > .ec-shelfRole').prepend(str);

    $('.custom_cat .ec-itemNav__nav').removeClass('ec-itemNav__nav');


    // if($('.ec-drawerRole .ec-headerCategoryArea .ec-itemNav__nav > li > ul').length > 0){

    //     $('.ec-drawerRole .ec-headerCategoryArea .ec-itemNav__nav > li > a').append('<span class="custom_nav_li_a"></span>');
    // }
    

    $('.ec-drawerRole .ec-headerCategoryArea .ec-itemNav__nav').children('li').each(function(){
        if($(this).children('ul').length > 0){
             $(this).prepend('<span class="custom_nav_li_jia"></span>');
        }
    });
    $('.ec-drawerRole .ec-headerCategoryArea .ec-itemNav__nav > li > ul').children('li').each(function(){
        if($(this).children('ul').length > 0){
             $(this).prepend('<span class="custom_nav_li_jia"></span>');
        }
    });


   

    $('.ec-drawerRole .ec-headerCategoryArea .ec-itemNav__nav li span').click(function() { 
            $(this).parent().children('ul').slideToggle();

            if($(this).hasClass('custom_nav_li_jian')){
                $(this).addClass('custom_nav_li_jia');
                $(this).removeClass('custom_nav_li_jian');
                
            }
            else{
                $(this).addClass('custom_nav_li_jian');
                $(this).removeClass('custom_nav_li_jia');
            }
            
            
        }); 


    $('.custom_cat .ec-itemNav > ul > li > ul').children('li').each(function(){
        if($(this).children('ul').length > 0){
             $(this).prepend('<span class="custom_nav_li_jia"></span>');
        }
    });


    $('.custom_cat .ec-itemNav > ul li span').click(function() { 
        $(this).parent().children('ul').slideToggle();

        if($(this).hasClass('custom_nav_li_jian')){
            $(this).addClass('custom_nav_li_jia');
            $(this).removeClass('custom_nav_li_jian');
            
        }
        else{
            $(this).addClass('custom_nav_li_jian');
            $(this).removeClass('custom_nav_li_jia');
        }
        
        
    }); 









});












