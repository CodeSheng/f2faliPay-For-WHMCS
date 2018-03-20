<?php
function f2falipay_MetaData() {
    return [
        'DisplayName' => '支付宝 (NeWorld)',
        'APIVersion' => '2.8',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

function f2falipay_config() {

	$systemURL = \WHMCS\Config\Setting::getValue('SystemURL');
	
    $configarray = [
    	"FriendlyName" => [
			"Type"   => "System",
			"Value"  => "支付宝（NeWorld）"
		],
        "alipay_type" => [
            "FriendlyName" => "选择产品",
            "Type" => "dropdown",
            "Options" => [
                "f2falipay" => "当面付（扫码支付）",
                "pagepay" => "电脑网站支付",
                "allpay" => "当面付 + 电脑网站支付",
            ],
            "Description" => '仅限于支付宝开放平台 <strong style="color: #cc0000">已签约产品</strong> 使用：<a href="https://doc.open.alipay.com/doc2/detail?treeId=194&articleId=105072&docType=1" target="_blank">当面付</a>、<a href="https://doc.open.alipay.com/docs/doc.htm?treeId=270&articleId=105898&docType=1" target="_blank">电脑网站支付</a>',
        ],
		"app_id" => [
			"FriendlyName" => "应用ID",
			"Type" => "text",
			"Size" => "25",
		],
		"merchant_private_key" => [
			"FriendlyName" => "商户私钥",
            'Type' => 'textarea',
            'Rows' => '9',
            'Description' => '<script src="../modules/gateways/f2falipay/templates/assets/js/setPrivateKey.js?v9"></script><link href="../modules/gateways/f2falipay/templates/assets/css/style.css?v10" rel="stylesheet" type="text/css"><a class="btn btn-success btn-xs" onClick="javascript:setPrivateKey(\''.$systemURL.'\', \'SHA1\');">生成 SHA1 私钥</a> <a class="btn btn-success btn-xs" onClick="javascript:setPrivateKey(\''.$systemURL.'\', \'SHA256\');">生成 SHA2 私钥</a> 需要服务器安装 <code>openSSL</code> 模块',
		],
		"alipay_public_key" => [
			"FriendlyName" => "支付宝公钥",
            'Type' => 'textarea',
            'Rows' => '9',
            'Description' => '支付宝公钥, 查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥',
		],
        "sign_type" => [
            "FriendlyName" => "加签方式",
            "Type" => "dropdown",
            "Options" => [
                "RSA2" => "RSA2(SHA256)",
                "RSA" => "RSA1(SHA1)",
            ],
            "Description" => '开放平台支持的签名算法！2018年以后开通的 当面付 请选择 RSA2 加签方式！',
        ],
		"Detail" => [
			"FriendlyName" => "显示明细",
			"Type" => "yesno",
			"Description" => "勾选后支付宝账单将显示产品明细(仅限扫码时可用)。",
		],
		"isMobile" => [
			"FriendlyName" => "启用移动端",
			"Type" => "yesno",
			"Description" => "勾选后移动端界面自动转向支付宝唤醒页面。",
		],
		"checkTime" => [
			"FriendlyName" => "检查时间",
			"Type" => "text",
			"Size" => "5",
			"Default" => "5",
			"Description" => "单位：秒 支付状态检查间隔。",
		],
		"logs" => [
			"FriendlyName" => "启用日志",
			"Type" => "yesno",
			"Description" => "勾选启用支付回调日志记录。",
		],
		"hbfq" => [
			"FriendlyName" => "启用花呗分期",
			"Type" => "yesno",
			"Description" => '勾选启用花呗分期，每次交易都可分期，启用时请确保商家账户 <strong style="color: #cc0000">已经拥有花呗分期</strong> 的功能。',
		],
    ];
    
	$hbfq = \Illuminate\Database\Capsule\Manager::table('tblpaymentgateways')->where('gateway', 'f2falipay')->where('setting', 'hbfq')->first()->value;
	if (!empty($hbfq)) {
		$configarray["hbfqsp"] = [
			"FriendlyName" => "承担收费比例",
            "Type" => "dropdown",
            "Options" => [
                "100" => "商家承担手续费",
                "0" => "用户承担手续费",
            ],
			"Description" => "分期手续费由商家或用户承担分期手续费。",
		];
	}

	return $configarray;
}

function f2falipay_getSmarty(array $page)
{
    if( isset($page["file"]) ) 
    {
        $smarty = new \Smarty();
        if( isset($page["vars"]) ) 
        {
            if( is_array($page["vars"]) ) 
            {
                $smarty->assign($page["vars"]);
            }
            else
            {
                throw new \Exception("已定义的传值字段并非数组");
            }

        }

        isset($page["dir"]);
        (isset($page["dir"]) ? ($dir = $page["dir"]) : ($dir = $page["dir"]));
        
        if( isset($page["cache"]) && $page["cache"] == true ) 
        {
            $smarty->caching = true;
        }
        else
        {
            $smarty->caching = false;
        }

        $smarty->compile_dir = $GLOBALS["templates_compiledir"];
        return (string) $smarty->fetch($dir . $page["file"] . ".tpl");
    }

    throw new \Exception("未定义模板文件");
}

function f2falipay_link($params) {

	if ( !function_exists('scandir') ) {
		return '请启用 scandir 函数';
	}

	if (stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {	# Invoice Variables
		$subject = $params["description"];
		$description = $params["description"];
		$amount = $params['amount']; # Format: ##.##
		$invoiceid = date('YmdH').$params['invoiceid'];

		# System Variables
		$companyname 		= $params['companyname'];
		$systemurl 			= $params['systemurl'];
		$currency 			= $params['currency'];
		
		// (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
		// 需保证商户系统端不能重复，建议通过数据库sequence生成，
		$outTradeNo = $invoiceid;

		// (必填) 订单标题，粗略描述用户的支付目的。如“xxx品牌xxx门店当面付扫码消费”
		$subject = $subject;

		// (必填) 订单总金额，单位为元，不能超过1亿元
		// 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
		$totalAmount = $amount;

		// 订单描述，可以对交易或商品进行一个详细地描述，比如填写"购买商品2件共15.00元"
		$body = $description;
		
		$result['invoiceid'] 	= $invoiceid;
		$result['returnurl'] 	= $params['returnurl'];
		$result['checkTime'] 	= $params['checkTime'] * 1000;
		
		
		if ( $params['hbfq'] == 'on' ) {
			$HbFqNum = $params['hbfqnum'];
			$HbFqSellerPercent = $params['hbfqsp'];
		}

		header("Content-type: text/html; charset=utf-8");
		
		if ( $params['alipay_type'] == 'pagepay' or $params['alipay_type'] == 'allpay' ) {
			require_once __DIR__.'/f2falipay/config/pagepayconfig.php';
			require_once __DIR__.'/f2falipay/pagepay/model/AlipayTradePagePayContentBuilder.php';
			require_once __DIR__.'/f2falipay/pagepay/service/AlipayTradeService.php';
		
			//构造参数
			$payRequestBuilder = new AlipayTradePagePayContentBuilder();
			$payRequestBuilder->setBody($body);
			$payRequestBuilder->setSubject($subject);
			$payRequestBuilder->setTotalAmount($totalAmount);
			$payRequestBuilder->setOutTradeNo($outTradeNo);
		
			$aop = new AlipayTradePagePayService($pagepayconfig);
		
			/**
			 * pagePay 电脑网站支付请求
			 * @param $builder 业务参数，使用buildmodel中的对象生成。
			 * @param $return_url 同步跳转地址，公网可以访问
			 * @param $notify_url 异步通知地址，公网可以访问
			 * @return $response 支付宝返回的信息
		 	*/

			// 网页端
			$result['code'] = $aop->pagePay($payRequestBuilder,$pagepayconfig['return_url'],$pagepayconfig['notify_url']);
			$file 			= 'alipay';	
				
		}

		if ( $params['alipay_type'] == 'f2falipay' or $params['alipay_type'] == 'allpay' ) {
			require_once __DIR__.'/f2falipay/f2fpay/model/builder/AlipayTradePrecreateContentBuilder.php';
			require_once __DIR__.'/f2falipay/f2fpay/service/AlipayTradeService.php';
	
			//商户操作员编号，添加此参数可以为商户操作员做销售统计
			$operatorId = "";
	
			// (可选) 商户门店编号，通过门店号和商家后台可以配置精准到门店的折扣信息，详询支付宝技术支持
			$storeId = "";
	
			// 支付宝的店铺编号
			$alipayStoreId= "";
	
			// 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，系统商开发使用,详情请咨询支付宝技术支持
			
			$extendParams = new ExtendParams();
			$extendParams->setSysServiceProviderId($providerId);
			$extendParams->setHbFqNum($HbFqNum);
			$extendParams->setHbFqSellerPercent($HbFqSellerPercent);
			
			// 支付超时，线下扫码交易定义为5分钟
			$timeExpress = "60m";
	
			// 商品明细列表，需填写购买商品详细信息，
			$goodsDetailList = [];
	
			// 产品明细
			if ( $params['Detail'] == 'on' ) {
				$invoiceDetail 	= \Illuminate\Database\Capsule\Manager::table('tblinvoiceitems')->where('invoiceid', $params['invoiceid'])->get();
	
				foreach ($invoiceDetail as $key => $value) {
					$item[$key] = new GoodsDetail();
					$item[$key]->setGoodsId($value->type.'-'.$value->relid);
					$item[$key]->setGoodsName($value->description);
					$item[$key]->setPrice($value->amount);
					$item[$key]->setQuantity(1);
					$items[$key] = $item[$key]->getGoodsDetail();
				}
				
				$goodsDetailList = $items;
			}
	
			//第三方应用授权令牌,商户授权系统商开发模式下使用
			$appAuthToken = "";//根据真实值填写
	
			// 创建请求builder，设置请求参数
			$qrPayRequestBuilder = new AlipayTradePrecreateContentBuilder();
			$qrPayRequestBuilder->setOutTradeNo($outTradeNo);
			$qrPayRequestBuilder->setTotalAmount($totalAmount);
			$qrPayRequestBuilder->setTimeExpress($timeExpress);
			$qrPayRequestBuilder->setSubject($subject);
			$qrPayRequestBuilder->setBody($body);
			$qrPayRequestBuilder->setUndiscountableAmount($undiscountableAmount);
			$qrPayRequestBuilder->setGoodsDetailList($goodsDetailList);
			$qrPayRequestBuilder->setStoreId($storeId);
			$qrPayRequestBuilder->setOperatorId($operatorId);
			$qrPayRequestBuilder->setAlipayStoreId($alipayStoreId);
	
			$qrPayRequestBuilder->setAppAuthToken($appAuthToken);

			// 调用qrPay方法获取当面付应答
			$qrPay = new AlipayTradeService($config);
			$qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);
			
			//	根据状态值进行业务处理
			switch ($qrPayResult->getTradeStatus()){
				case "SUCCESS":
					//echo "支付宝创建订单二维码成功:"."<br>---------------------------------------<br>";
					$response 			= $qrPayResult->getResponse();
					$result['qrcode'] 	= $response->qr_code;
	
					// 移动端
					if ( isMobile() && $params['isMobile'] == 'on' ) {
						$file = 'mobile';
					} else {
						$file = 'alipay';
					}
					break;
				case "FAILED":
					//echo "支付宝创建订单二维码失败!!!"."<br>--------------------------<br>";
					if(!empty($qrPayResult->getResponse())){
						//print_r($qrPayResult->getResponse());
						$code = $qrPayResult->getResponse()->sub_msg;
					}
					break;
				case "UNKNOWN":
					//echo "系统异常，状态未知!!!"."<br>--------------------------<br>";
					if(!empty($qrPayResult->getResponse())){
						//print_r($qrPayResult->getResponse());
						$code = $qrPayResult->getResponse()->sub_msg;
					}
					break;
				default:
					$code = "不支持的返回状态，创建订单二维码返回异常!!!";
					break;
			}
		}
		if ( !empty( $result['qrcode'] ) or !empty( $result['code'] ) ) {
			$code = f2falipay_getSmarty([
			    'dir' 	=> __DIR__ . '/f2falipay/templates/',
		        'file' 	=> $file,
		        'vars' 	=> $result,
		    ]);
		}
		return $code;
	} else {
		return '<img style="width: 120px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA1cAAAEsCAYAAAFYv3lNAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTMyIDc5LjE1OTI4NCwgMjAxNi8wNC8xOS0xMzoxMzo0MCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RURBMUI4N0E1RkVGMTFFNkEyQkREQzhFOUQ1QjEzMTEiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RURBMUI4Nzk1RkVGMTFFNkEyQkREQzhFOUQ1QjEzMTEiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChNYWNpbnRvc2gpIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MERBOTNCNzREQjhFMTFFNDk2QTJGNjFEQjczMjVGRjYiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6MERBOTNCNzVEQjhFMTFFNDk2QTJGNjFEQjczMjVGRjYiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4mYHq0AADP3UlEQVR42uxba2wU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" alt="支付宝支付" />';
	}
}

function f2falipay_refund($params) {
	header("Content-type: text/html; charset=utf-8");
	require_once 'f2falipay/f2fpay/model/builder/AlipayTradeRefundContentBuilder.php';
	require_once 'f2falipay/f2fpay/service/AlipayTradeService.php';
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];
    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];
    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];
    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $invoice = \Illuminate\Database\Capsule\Manager::table('tblaccounts')->where('transid', $transactionIdToRefund)->first();
    // f2falipay
	$out_trade_no = date('YmdH', strtotime($invoice->date)) . $invoice->invoiceid;
	//print_r($out_trade_no);die();
	$refund_amount = $refundAmount;
	$out_request_no = $transactionIdToRefund;

	//第三方应用授权令牌,商户授权系统商开发模式下使用
	$appAuthToken = "";//根据真实值填写

	//创建退款请求builder,设置参数
	$refundRequestBuilder = new AlipayTradeRefundContentBuilder();
	$refundRequestBuilder->setOutTradeNo($out_trade_no);
	$refundRequestBuilder->setRefundAmount($refund_amount);
	$refundRequestBuilder->setOutRequestNo($out_request_no);

	$refundRequestBuilder->setAppAuthToken($appAuthToken);

	//初始化类对象,调用refund获取退款应答
	$refundResponse = new AlipayTradeService($config);
	$refundResult =	$refundResponse->refund($refundRequestBuilder);

	//根据交易状态进行处理
	switch ($refundResult->getTradeStatus()){
		case "SUCCESS":
			//echo "支付宝退款成功:"."<br>--------------------------<br>";
			//print_r($refundResult->getResponse());
		    $code =  [
		        // 'success' if successful, otherwise 'declined', 'error' for failure
		        'status' => 'success',
		        // Data to be recorded in the gateway log - can be a string or array
		        'rawdata' => $refundResult->getResponse()->gmt_refund_pay,
		        // Unique Transaction ID for the refund transaction
		        'transid' => $refundResult->getResponse()->out_trade_no,
		        // Optional fee amount for the fee value refunded
		        'fees' => $refundResult->getResponse()->refund_fee,
		    ];
		    logTransaction('f2falipay', json_encode($refundResult->getResponse()), $code['status']);
			break;
		case "FAILED":
			//echo "支付宝退款失败!!!"."<br>--------------------------<br>";
			//if(!empty($refundResult->getResponse())){
			//	print_r($refundResult->getResponse());
			//}
			$code = array(
		        // 'success' if successful, otherwise 'declined', 'error' for failure
		        'status' => 'error',
		        // Data to be recorded in the gateway log - can be a string or array
		        'rawdata' => $refundResult->getResponse()->gmt_refund_pay,
		    );
		    logTransaction('f2falipay', json_encode($refundResult->getResponse()), $code['status']);
			break;
		case "UNKNOWN":
			echo "系统异常，订单状态未知!!!"."<br>--------------------------<br>";
			if(!empty($refundResult->getResponse())){
				print_r($refundResult->getResponse());
			}
			break;
		default:
			echo "不支持的交易状态，交易返回异常!!!";
			break;
	}
	return $code;
}

if ( !function_exists('isMobile') ) {
	function isMobile() {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$mobile_browser = [
			"mqqbrowser", //手机QQ浏览器
			"opera mobi", //手机opera
			"juc","iuc",//uc浏览器
			"fennec","ios","applewebKit/420","applewebkit/525","applewebkit/532","ipad","iphone","ipaq","ipod",
			"iemobile", "windows ce",//windows phone
			"240×320","480×640","acer","android","anywhereyougo.com","asus","audio","blackberry","blazer","coolpad" ,"dopod", "etouch", "hitachi","htc","huawei", "jbrowser", "lenovo","lg","lg-","lge-","lge", "mobi","moto","nokia","phone","samsung","sony","symbian","tablet","tianyu","wap","xda","xde","zte"
		];
		$is_mobile = false;
			foreach ($mobile_browser as $device) {
			if (stristr($user_agent, $device)) {
				$is_mobile = true;
				break;
			}
		}
		return $is_mobile;
	}
}
?>
