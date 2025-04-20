<?php

/**
 * @Author       : 夜漫未央 <ocesux@gmail.com>
 * @Date         : 2025-04-19 21:05:14
 * @LastEditTime : 2025-04-20 16:17:06
 * 
 * 一卡通学生信息界面接口：
 * 获取UserToken：getToken
 * 获取学生详细信息：getDetailInfo
 * 获取院系信息：getDepartmentInfo
 * 
 * 浴室预约界面接口：
 * 获取浴室列表：getBathList
 * 获取预约码：getReservationCode
 * 取消预约：cancelReservation 
 * 
 * 设备码用水以及扫一扫的接口没抓，感兴趣可以自行研究PR...
 */


error_reporting( 0 );
require_once __DIR__ . '/utils.php';
header( "Content-Type: application/json; charset=utf-8" );


/**
 * @param string $type 操作类型
 */
switch ( $_GET["type"] ) {
	case "getToken":
		getToken();
		break;
	case "getDetailInfo":
		getDetailInfo();
		break;
	case "getDepartmentInfo":
		getDepartmentInfo();
		break;
	case "getBathList":
		getBathList();
		break;
	case "getReservationCode":
		getReservationCode();
		break;
	case "cancelReservation":
		cancelReservation();
		break;
	default:
		sendResponse( 400, "error", "缺少必要参数" );
}


/**
 * 获取Token
 * @param string $studentId 学号
 * @param string $password 密码
 */
function getToken() {
	try {
		$studentId = $_GET["studentId"];
		$password = $_GET["password"];

		if ( empty( $studentId ) || empty( $password ) ) {
			throw new Exception( "缺少必要参数" );
		}

		// 获取键盘加密对照表
		$keyboardUrl = "https://ecard.tmucmc.edu.cn/berserker-secure/keyboard?type=Standard&order=0&synAccessSource=h5";
		$keyboardData = sendRequest( $keyboardUrl );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $keyboardData ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$keyboardData = json_decode( $keyboardData, true )["data"];

		// 转换取得加密后密码
		$password = keyboardEncryption(
			$password,
			$keyboardData["lowerLetterKeyboard"],
			$keyboardData["upperLetterKeyboard"],
			$keyboardData["numberKeyboard"],
			$keyboardData["symbolKeyboard"],
			$keyboardData["uuid"],
		);
		// 拼接POST数据
		$postData = "username=$studentId&password=$password&grant_type=password&scope=all&loginFrom=h5&logintype=snoNew&device_token=h5&synAccessSource=h5";
		// 获取Token
		$tokenUrl = "https://ecard.tmucmc.edu.cn/berserker-auth/oauth/token";
		$userToken = sendRequest( $tokenUrl, "getUserTokenReq", $postData );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $userToken ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$tokenData = json_decode( $userToken, true );

		if ( isset( $tokenData["access_token"] ) ) {
			sendResponse( 200, "success", "获取Token成功", $tokenData );
		} elseif ( $tokenData["status"] == 400 ) {
			sendResponse( 400, "error", "用户名或密码错误" );
		} elseif ( $tokenData["status"] == 500 ) {
			sendResponse( 500, "error", "请求出错" );
		} else {
			throw new Exception( "未知错误" );
		}
	} catch (Exception $e) {
		// 返回错误响应
		sendResponse( 400, "error", $e->getMessage() );
	}
}

/**
 * 获取学生详细信息
 * @param string $token Token
 */
function getDetailInfo() {
	try {
		$token = $_GET["token"];

		if ( empty( $token ) ) {
			throw new Exception( "缺少必要参数" );
		}

		$queryCardUrl = "https://ecard.tmucmc.edu.cn/berserker-app/ykt/tsm/queryCard?synAccessSource=h5";
		$detailInfo = sendRequest( $queryCardUrl, "getdetailedInfoReq", null, $token );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $detailInfo ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$detailInfo = json_decode( $detailInfo, true );

		if ( $detailInfo["code"] == 200 ) {
			sendResponse( 200, "success", "获取详细信息成功", $detailInfo["data"]["card"] );
		} elseif ( $detailInfo["code"] == 401 ) {
			sendResponse( 401, "error", $detailInfo["message"] );
		} elseif ( $detailInfo["code"] == 500 ) {
			sendResponse( 500, "error", "请求出错" );
		} else {
			throw new Exception( "未知错误" );
		}
	} catch (Exception $e) {
		// 返回错误响应
		sendResponse( 400, "error", $e->getMessage() );
	}
}

/**
 * 获取院系信息
 * @param string $cardNum 卡号
 * @param string $token Token
 */
function getDepartmentInfo() {
	try {
		$cardNum = $_GET["cardNum"];
		$token = $_GET["token"];

		if ( empty( $cardNum ) || empty( $token ) ) {
			throw new Exception( "缺少必要参数" );
		}

		$departmentUrl = "https://ecard.tmucmc.edu.cn/berserker-app/cardStyle?synAccessSource=h5";
		$departmentInfo = sendRequest( $departmentUrl, "getdetailedInfoReq", null, $token );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $departmentInfo ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$departmentInfo = json_decode( $departmentInfo, true );

		if ( $departmentInfo["code"] == 200 ) {
			$pattern = '/<div class="card-info-custom" style="position: absolute;color:#000000;top:54.436797752808985%;left:53.75%;"><span class="" style="font-size:14px;color:#000000;">(.*?)<\/span><\/div>/';
			$content = $departmentInfo['data'][ $cardNum ]['model'];
			if ( preg_match( $pattern, $content, $matches ) ) {
				$content = $matches[1];
				sendResponse( 200, "success", "获取院系信息成功", $content );
			} else {
				throw new Exception( "匹配院系失败" );
			}
		} elseif ( $departmentInfo["code"] == 401 ) {
			sendResponse( 401, "error", $departmentInfo["message"] );
		} elseif ( $departmentInfo["code"] == 500 ) {
			sendResponse( 500, "error", "请求出错" );
		} else {
			throw new Exception( "未知错误" );
		}
	} catch (Exception $e) {
		// 返回错误响应
		sendResponse( 400, "error", $e->getMessage() );
	}
}

/**
 * 获取用水Token
 * @param string $cardNum 卡号
 */
function getWaterUsageToken( $cardNum ) {
	try {
		$bathTokenUrl = "http://payment.tmucmc.edu.cn:5001/waterapi/api/GetToken?info=" . rawurlencode( encryptInfo( "getWaterUsageToken", $cardNum ) );
		$waterUsageToken = sendRequest( $bathTokenUrl );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $waterUsageToken ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$detailInfo = json_decode( $waterUsageToken, true );
		if ( $detailInfo["RetNo"] == 0 ) {
			return $detailInfo["Token"];
		} else {
			throw new Exception( "未知错误" );
		}
	} catch (Exception $e) {
		return Null;
	}
}

/**
 * 获取浴室列表
 * @param string $cardNum 卡号
 */
function getBathList() {
	try {
		$cardNum = $_GET["cardNum"];

		if ( empty( $cardNum ) ) {
			throw new Exception( "缺少必要参数" );
		}

		$encryptInfo = rawurlencode( encryptInfo( "getBathList", $cardNum ) );
		$waterUsageToken = getWaterUsageToken( $cardNum );
		if ( $waterUsageToken == null ) {
			throw new Exception( "获取用水Token失败" );
		}

		$bathListUrl = "http://payment.tmucmc.edu.cn:5001/waterapi/api/AccUseHzWatch?info=$encryptInfo&token=$waterUsageToken";
		$bathData = sendRequest( $bathListUrl );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $bathData ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$bathData = json_decode( $bathData, true );

		if ( $bathData["RetNo"] == 0 ) {
			sendResponse( 200, "success", "获取浴室列表成功", $bathData["List"] );
		} else {
			throw new Exception( $bathData["RetDsp"] );
		}
	} catch (Exception $e) {
		// 返回错误响应
		sendResponse( 400, "error", $e->getMessage() );
	}
}

/**
 * 获取预约码
 * @param string $cardNum 卡号
 * @param string $classno 浴室编号
 */
function getReservationCode() {
	try {
		$cardNum = $_GET["cardNum"];
		$classno = $_GET["classno"];

		if ( empty( $cardNum ) || empty( $classno ) ) {
			throw new Exception( "缺少必要参数" );
		}

		$encryptInfo = rawurlencode( encryptInfo( "getReservationDetails", $cardNum, $classno ) );
		$waterUsageToken = getWaterUsageToken( $cardNum );
		if ( $waterUsageToken == null ) {
			throw new Exception( "获取用水Token失败" );
		}


		$getReservationCodeUrl = "http://payment.tmucmc.edu.cn:5001/waterapi/api/BookCodeReq?info=$encryptInfo&token=$waterUsageToken";
		$ReservationCodeData = sendRequest( $getReservationCodeUrl );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $ReservationCodeData ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$ReservationCodeData = json_decode( $ReservationCodeData, true );

		if ( isset( $ReservationCodeData["RetNo"] ) ) {
			// $bookData["RetNo"] == 0 || 
			if ( $ReservationCodeData["RetNo"] == 0 ) {
				sendResponse( 200, "success", "获取预约码成功", $ReservationCodeData["BookCode"] );
			} elseif ( $ReservationCodeData["RetNo"] == -24 ) {
				throw new Exception( "重复预约" );
			} else {
				throw new Exception( $ReservationCodeData["RetDsp"] );
			}
		} else {
			throw new Exception( "未知错误" );
		}
	} catch (Exception $e) {
		// 返回错误响应
		sendResponse( 400, "error", $e->getMessage() );
	}
}

/**
 * 取消预约码
 * @param string $cardNum 卡号
 * @param string $classno 浴室编号
 */
function cancelReservation() {
	try {
		$cardNum = $_GET["cardNum"];
		$classno = $_GET["classno"];

		if ( empty( $cardNum ) || empty( $classno ) ) {
			throw new Exception( "缺少必要参数" );
		}

		$encryptInfo = rawurlencode( encryptInfo( "getReservationDetails", $cardNum, $classno ) );
		$waterUsageToken = getWaterUsageToken( $cardNum );
		if ( $waterUsageToken == null ) {
			throw new Exception( "获取用水Token失败" );
		}


		$cancelReservationUrl = "http://payment.tmucmc.edu.cn:5001/waterapi/api/BookCodeReqCancel?info=$encryptInfo&token=$waterUsageToken";
		$cancelReservationData = sendRequest( $cancelReservationUrl );

		// 返接口返回值非JSON
		if ( ! is_valid_json( $cancelReservationData ) ) {
			throw new Exception( "服务器返回数据错误" );
		}

		$cancelReservationData = json_decode( $cancelReservationData, true );

		if ( isset( $cancelReservationData["RetNo"] ) ) {
			// $bookData["RetNo"] == 0 || 
			if ( $cancelReservationData["RetNo"] == 0 ) {
				sendResponse( 200, "success", "取消预约码成功", $cancelReservationData["BookCode"] );
			} elseif ( $cancelReservationData["RetNo"] == -34 ) {
				throw new Exception( "重复取消" );
			} else {
				throw new Exception( $cancelReservationData["RetDsp"] );
			}
		} else {
			throw new Exception( "未知错误" );
		}
	} catch (Exception $e) {
		// 返回错误响应
		sendResponse( 400, "error", $e->getMessage() );
	}
}