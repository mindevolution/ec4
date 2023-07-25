// Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
//
// For the full copyright and license information, please view the LICENSE
// file that was distributed with this source code.


$(function(){
	// 高さ合わせ
	if ($(".tabacms_height_match")[0]) {
		$(".tabacms_height_match [class^='col-']").matchHeight();
	}
});