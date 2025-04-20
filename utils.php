<?php

/**
 * @Author       : 夜漫未央 <ocesux@gmail.com>
 * @Date         : 2025-04-19 21:05:14
 * @LastEditTime : 2025-04-20 16:17:06
 */

error_reporting( 0 );

/** 发送响应
 * @param int $statusCode 状态码
 * @param string $status 状态
 * @param string $message 消息
 * @param array $data 数据
 */
function sendResponse( $statusCode, $status, $message, $data = [] ) {
	http_response_code( $statusCode );
	header( 'Content-Type: application/json' );
	echo json_encode( [ 
		'code' => $statusCode,
		'status' => $status,
		'message' => $message,
		'data' => $data
	], JSON_UNESCAPED_UNICODE );
}


/**
 * 明文密码转键盘加密
 * @param string $password 明文密码
 * @param string $lowercase_map 小写字母键盘密码对照表
 * @param string $uppercase_map 大写字母键盘密码对照表
 * @param string $digits_map 数字键盘密码对照表
 * @param string $symbols_map 特殊符号键盘密码对照表
 * @param string $uuid 键盘加密的UUID
 */
function keyboardEncryption( $password, $lowercase_map, $uppercase_map, $digits_map, $symbols_map, $uuid ) {
	// 正常顺序
	$normal_order_lowercase = "qwertyuiopasdfghjklzxcvbnm";
	$normal_order_uppercase = "QWERTYUIOPASDFGHJKLZXCVBNM";
	$normal_order_digits = "0123456789";
	$normal_order_symbols = "*\>-]{/!,<?~}&@#[";

	// 词典映射
	$mapping_dict = array();
	for ( $i = 0; $i < strlen( $normal_order_lowercase ); $i++ ) {
		$mapping_dict[ $normal_order_lowercase[ $i ] ] = $lowercase_map[ $i ];
	}
	for ( $i = 0; $i < strlen( $normal_order_uppercase ); $i++ ) {
		$mapping_dict[ $normal_order_uppercase[ $i ] ] = $uppercase_map[ $i ];
	}
	for ( $i = 0; $i < strlen( $normal_order_digits ); $i++ ) {
		$mapping_dict[ $normal_order_digits[ $i ] ] = $digits_map[ $i ];
	}
	for ( $i = 0; $i < strlen( $normal_order_symbols ); $i++ ) {
		$mapping_dict[ $normal_order_symbols[ $i ] ] = $symbols_map[ $i ];
	}

	$decoded_password = "";
	for ( $i = 0; $i < strlen( $password ); $i++ ) {
		$decoded_password .= $mapping_dict[ $password[ $i ] ];
	}

	// 密码格式：键盘密文 + $1$ + UUID
	// *SSWShh*$1$9128cd2f-9d67-43f6-bbd7-21a05808f525
	return $decoded_password . "$1$" . $uuid;
}


/**
 * 判断字符串是否为JSON
 * @param string $string 字符串
 * @return bool 是否为JSON
 */
function is_valid_json( $string ) {
	if ( ! is_string( $string ) || trim( $string ) === "" ) {
		return false;
	}

	json_decode( $string );
	return json_last_error() === JSON_ERROR_NONE;
}


/**
 * AES 加密
 * @param string $data 明文
 * @param string $key 密钥
 */
function AESEncrypt( $data ) {
	// Base64 解码密钥
	$key = base64_decode( "3n4DdO47LWH2Co/WfpbdyA==" );
	// AES-128-ECB + PKCS7 填充
	$encrypted = openssl_encrypt(
		$data,
		'aes-128-ecb',
		$key,
		OPENSSL_RAW_DATA // 原始二进制输出
	);
	// 转为 Base64 字符串
	return base64_encode( $encrypted );
}


/**
 * 加密各类信息
 * @param string $type
 * @param string $cardNum
 * @param int $classno
 * @return string
 */
function encryptInfo( $type, int $cardNum, int $classno = null ) {
	switch ( $type ) {
		case 'getWaterUsageToken':
			$data = [ 
				'userid' => $cardNum,
				'userpassword' => 'kv7XjPzrDNJY0pdZ#',
				'time' => getNowFormatDate()
			];
			break;
		case 'getBathList':
			$data = [ 
				'ano' => $cardNum
			];
			break;
		case 'getReservationDetails':
			$data = [ 
				'classno' => $classno,  // int
				'ano' => $cardNum,      //String
			];
			break;
	}
	$data = json_encode( $data );
	$response = AESEncrypt( $data );
	return $response;
}


/**
 * 获取当前时间
 * @return string
 */
function getNowFormatDate() {
	$now = new DateTime();
	$year = $now->format( 'Y' );
	$month = $now->format( 'm' );
	$day = $now->format( 'd' );
	$hour = $now->format( 'H' );
	$minute = $now->format( 'i' );
	$second = $now->format( 's' );

	// 刻意保留原 JS 代码的错误逻辑：分钟用小时补零
	$minuteInt = intval( $minute );
	if ( $minuteInt < 10 ) {
		$minute = '0' . $hour; // 错误逻辑：这里应该用 $minute 却用了 $hour
	}

	return $year . $month . $day . $hour . $minute . $second;
}

/**
 * 发送请求
 * @param string $url 请求地址
 * @param string $type 请求类型
 * @param string $data 请求数据
 * @param string $access_token 访问令牌
 * @return string 响应数据
 */
function sendRequest( $url, $type = null, $data = null, $access_token = null ) {
	try {
		$headers = [];
		$curl = curl_init( $url );

		if ( $type == "getUserTokenReq" ) {
			$headers = [ 
				"Authorization: Basic bW9iaWxlX3NlcnZpY2VfcGxhdGZvcm06bW9iaWxlX3NlcnZpY2VfcGxhdGZvcm1fc2VjcmV0",
			];
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
		} else if ( $type == "getdetailedInfoReq" ) {
			$headers = [ 
				'Synjones-Auth: bearer ' . $access_token,
			];
		}

		curl_setopt( $curl, CURLOPT_ENCODING, "" );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );

		$response = curl_exec( $curl );
		// echo $response;
		return $response;
	} catch (Exception $e) {
		return $response;
	} finally {
		curl_close( $curl );
	}
}