<?php

namespace Lucymao9\AlipayDrive;

use Lucymao9\AlipayDrive\aop\AlipayConfig;
use Lucymao9\AlipayDrive\aop\AopClient;
use Lucymao9\AlipayDrive\request\CancelFeeQueryRequest;
use Lucymao9\AlipayDrive\request\CancelRequest;
use Lucymao9\AlipayDrive\request\ChangePriceNotifyRequest;
use Lucymao9\AlipayDrive\request\CommitRequest;
use Lucymao9\AlipayDrive\request\EstimatePricesRequest;
use Lucymao9\AlipayDrive\request\FeeDetailQueryRequest;
use Lucymao9\AlipayDrive\request\ModifyDestinationRequest;
use Lucymao9\AlipayDrive\request\NearbyDriversRequest;
use Lucymao9\AlipayDrive\request\NotifyRequest;
use Lucymao9\AlipayDrive\request\PayedNotifyRequest;
use Lucymao9\AlipayDrive\request\PlanetRequest;
use Lucymao9\AlipayDrive\request\QueryRequest;
use Lucymao9\AlipayDrive\request\TraceQueryRequest;

class Drive
{
    private string $env;

    private $alipayClient;

    public function __construct($params)
    {
        $privateKey = $params['private_key'] ?? '';
        $alipayPublicKey = $params['alipay_public_key'] ?? '';
        $isv_app_id = $params['isv_app_id'] ?? '';
        $charset = $params['charset'] ?? 'UTF-8';
        $sign_type = $params['sign_type'] ?? 'RSA2';
        $sign = $params['sign'] ?? '';
        $this->env = $params['env'] ?? 'prod';
        $url = $this->env == 'prod' ? 'https://apigw.alipay-eco.com' : 'https://dev-apigw.alipay-eco.com';
//        $url = 'https://icgwcoreproxy2.dl.alipaydev.com';
        if ($params['url']) {
            $url = $params['url'];
        }
        $alipayConfig = new AlipayConfig();
        //行业云接口地址
        $alipayConfig->setServerUrl($url);
        //行业云上的服务商isv_app_id
        $alipayConfig->setAppId($isv_app_id);
        $alipayConfig->setPrivateKey($privateKey);
        $alipayConfig->setFormat("json");
        $alipayConfig->setAlipayPublicKey($alipayPublicKey);
        $alipayConfig->setCharset($charset);
        $alipayConfig->setSignType($sign_type);
        $alipayConfig->setIsLog($params['is_log'] ?? false);
//        $alipayConfig->setSkipSign(false);
        $this->alipayClient = new AopClient($alipayConfig);
        $this->alipayClient->encryptKey = $sign;
        $this->alipayClient->encryptType = "AES";
    }

    public function getResponse(PlanetRequest $request, string $objName = '')
    {
        $responseResult = $this->alipayClient->execute($request);
//        echo json_encode($responseResult);
        $responseApiName = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $response = $responseResult->$responseApiName;

//        return json_decode(json_encode($response),true);
        if (!empty($response->code) && $response->code == 10000) {
            $obj = $objName ? $response->$objName : $response;
            return ['result' => true, 'data' => json_decode(json_encode($obj), true)];
        } else {
            return ['result' => false, 'code' => $response->code, 'msg' => $response->msg, 'sub_code' => $response->sub_code, 'sub_msg' => $response->sub_msg];
        }
    }

    /**
     * 附近可用司机列表
     * customCode    String    客户编号    必填    代驾SaaS内部定义的客户ID，获取见业务预入驻信息
     * cityCode    String    城市码    必填    下单所在城市城市码，下单派单时筛选指定城市代驾商户需要；见省市区划分城市码
     * phone    String    下单用户手机号    必填    下单用户手机号，必填
     * startLongitude    String    下单起始经度    必填    下单起始经度，必填https://lbs.amap.com/tools/picker
     * startLatitude    String    下单起始纬度    必填    下单起始纬度，必填
     * driveIsvCodeList    List<String>    代驾商家isvCode集合    非必填    若指定代驾isv信息，则返回指定isv的附近司机信息列表。否则以权益客户&下单城市维度绑定的所有isv进行返回
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function nearbyDrivers($params)
    {
        $request = new NearbyDriversRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'customCode' => $params['customCode'] ?? '',//客户编号 代驾SaaS内部定义的客户ID，获取见业务预入驻信息
            'cityCode' => $params['cityCode'] ?? '',//城市码
            'phone' => $params['phone'] ?? '',
            'startLongitude' => $params['startLongitude'] ?? '',
            'startLatitude' => $params['startLatitude'] ?? '',
            'driveIsvCodeList' => $params['driveIsvCodeList'] ?? [],
        ]));
        $response = $this->getResponse($request, 'nearbyDriverQueryItemList');
//        print_r($response);
        return $response;
    }

    /**
     * 代驾预估价格列表
     * customCode    String    客户编号    必填    代驾SaaS内部定义的客户ID，
     * 获取见业务预入驻信息
     * cityCode    String    城市码    必填    下单所在城市城市码，下单派单时筛选指定城市代驾商户需要；
     * 见省市区划分城市码
     * phone    String    下单用户手机号    必填    下单用户手机号，必填
     * startLongitude    String    下单起始经度    必填    下单起始经度，必填https://lbs.amap.com/tools/picker
     * startLatitude    String    下单起始纬度    必填    下单起始纬度，必填
     * endLongitude    String    下单目的地经度    必填    下单目的地经度，必填
     * endLatitude    String    下单目的地纬度    必填    下单目的地纬度，必填
     * callLongDistanceDriver    Boolean    是否呼叫远程司机    必填    是否呼叫远程司机，是-返回的预估价格可能包含远程司机的订单预估价格；
     * driveIsvCodeList    List<String>    代驾商家isvCode集合    非必填    若指定代驾isv信息，则返回指定isv的预估报价和里程及用户等信息列表。否则以权益客户&下单城市维度绑定的所有isv进行返回
     * @param $params
     * @return array
     */
    public function estimatePrices($params)
    {
        $request = new EstimatePricesRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'customCode' => $params['customCode'] ?? '',//客户编号 代驾SaaS内部定义的客户ID，获取见业务预入驻信息
            'cityCode' => $params['cityCode'] ?? '',//城市码
            'phone' => $params['phone'] ?? '',
            'startLongitude' => $params['startLongitude'] ?? '',
            'startLatitude' => $params['startLatitude'] ?? '',
            'endLongitude' => $params['endLongitude'] ?? '',
            'endLatitude' => $params['endLatitude'] ?? '',
            'callLongDistanceDriver' => $params['callLongDistanceDriver'] ?? false,
            'driveIsvCodeList' => $params['driveIsvCodeList'] ?? [],
        ]));
        $response = $this->getResponse($request, 'estimatePriceItems');
        return $response;
    }

    /**
     * 代驾用户下单接口
     * vehiclePlateNumber    String    用户代驾车辆车牌号    非必填    用户代驾车辆车牌号，新车未上牌校验看车架号；
     * vehicleIdentifyNumber    String    用户代驾车辆车架号    非必填    新车未上牌校验看车架号
     * vehicleNumberPreVerificate    String    是否进行车牌号/车架号车辆信息质检校验    非必填 YES - 进行车牌号车辆信息校验，司机已就位状态同步时，要求代驾商户回传相关车辆照片信息；NO - 不进行车牌号车辆信息校验，司机已就位状态同步时，代驾商户回传相关车辆照片信息可能为空；
     * userId    String    用户ID    非必填    用户唯一id，权益服务商有则传
     * phone    String    下单用户手机号    必填    下单用户手机号，必填
     * cityCode    String    城市码    必填    下单所在城市城市码，下单派单时筛选指定城市代驾商户需要；
     * startAddress    String    下单起始地址    必填    下单起始地址，必填https://lbs.amap.com/tools/picker
     * startLongitude    String    下单起始经度    必填    下单起始经度，必填
     * startLatitude    String    下单起始纬度    必填    下单起始纬度，必填
     * endAddress    String    下单目的地地址    必填    下单目的地地址，必填
     * endLongitude    String    下单目的地经度    必填    下单目的地经度，必填
     * endLatitude    String    下单目的地纬度    必填    下单目的地纬度，必填
     * equityTypeCode    String    权益类型编码    非必填    枚举类型：LCDK-里程抵扣；默认单位KM;    JEDK-金额抵扣；默认单位元;    LCMD-里程抵扣(KM)+免等时长(分钟) equityTypeCode、equityTypeValue、equityId 必须同时非空或者同时都传；
     * equityTypeValue    String    权益类型面值    非必填    权益券面值，与equityTypeCode对应。例如当 equityTypeCode 是 LCDK，equityTypeValue 是 10，则表示10 公里里程券。equityTypeCode 是 JEDK ，equityTypeValue 是 8.8，则表示8.8元金额抵扣券；对于 LCMD-里程抵扣+免等时长,使用“_”作为分隔符，例如,equityTypeCode：LCMD,equityTypeValue：10_30,equityId：axnshfad11a3awsa,表示该权益ID表示“10公里里程抵扣 + 30分钟免等权益”
     * equityId    String    权益ID（券Id）    非必填    客户透传权益券ID，用于权益客户与代驾商家核销；
     * customCode    String    客户编号    必填    代驾SaaS内部定义的客户ID，获取见业务预入驻信息
     * secondCustomCode    String    二级客户编号    必填    代驾SaaS内部定义的二级客户id（银行、保司等），分配给客户调用接口时传，获取见业务预入驻信息
     * customOrderNo    String    外部订单号    必填    外部客户系统的订单号，必传。用作幂等控制，若没有订单号也必须传一个唯一的幂等字段；
     * driveIsvCodeList    List<String>    代驾商家isvCode集合    非必填    若不为空表示客户指定代驾服务商进行派单。
     * @param $params
     * @return array
     */
    public function commit($params)
    {
        $request = new CommitRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'vehiclePlateNumber' => $params['vehiclePlateNumber'] ?? '',
            'vehicleIdentifyNumber' => $params['vehicleIdentifyNumber'] ?? '',
            'vehicleNumberPreVerificate' => $params['vehicleNumberPreVerificate'] ?? '',
            'userId' => $params['userId'] ?? '',
            'phone' => $params['phone'] ?? '',
            'cityCode' => $params['cityCode'] ?? '',
            'startAddress' => $params['startAddress'] ?? '',
            'startLongitude' => $params['startLongitude'] ?? '',
            'startLatitude' => $params['startLatitude'] ?? '',
            'endAddress' => $params['endAddress'] ?? '',
            'endLongitude' => $params['endLongitude'] ?? '',
            'endLatitude' => $params['endLatitude'] ?? '',
            'equityTypeCode' => $params['equityTypeCode'] ?? '',
            'equityTypeValue' => $params['equityTypeValue'] ?? '',
            'equityId' => $params['equityId'] ?? '',
            'customCode' => $params['customCode'] ?? '',
            'secondCustomCode' => $params['secondCustomCode'] ?? '',
            'customOrderNo' => $params['customOrderNo'] ?? '',
            'driveIsvCodeList' => $params['driveIsvCodeList'] ?? [],
        ]));
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 用户取消订单接口
     * orderNo    String    代驾SaaS平台订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone    String    下单用户手机号    必填    用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * reason    String    用户取消原因    非必填    用户取消原因，有则传
     * @param $params
     * @return array
     */
    public function cancel($params)
    {
        $request = new CancelRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'orderNo' => $params['orderNo'] ?? '',
            'phone' => $params['phone'] ?? '',
            'reason' => $params['reason'] ?? '',
        ]));
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 订单详情查询接口
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有订单功能都传此订单号
     * phone    String    下单用户手机号    必填    用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * @param $params
     * @return array
     */
    public function query($params)
    {
        $request = new QueryRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'orderNo' => $params['orderNo'] ?? '',
            'phone' => $params['phone'] ?? '',
        ]));
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 订单轨迹查询接口
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone    String    用户手机号码    必填    用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * startTimestamp    String    轨迹查询时间戳    非必填    Unix时间戳，到秒，（返回该时间点后的路径数据，用于增量获取
     * @param $params
     * @return array
     */
    public function traceQuery($params)
    {
        $request = new TraceQueryRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'orderNo' => $params['orderNo'] ?? '',
            'phone' => $params['phone'] ?? '',
            'startTimestamp' => $params['startTimestamp'] ?? '',
        ]));
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 费用明细查询接口
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone    String    用户手机号码    必填    用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * @param $params
     * @return array
     */
    public function feeDetailQuery($params)
    {
        $request = new FeeDetailQueryRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'orderNo' => $params['orderNo'] ?? '',
            'phone' => $params['phone'] ?? '',
        ]));
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 取消费用查询接口
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone    String    用户手机号码    必填    用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * @param $params
     * @return array
     */
    public function cancelFeeQuery($params)
    {
        $request = new CancelFeeQueryRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'orderNo' => $params['orderNo'] ?? '',
            'phone' => $params['phone'] ?? '',
        ]));
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 线上支付成功通知
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone    String    用户手机号码    必填    用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * totalFee    Integer    订单总金额    必填    订单总金额。单位：分
     * payFee    Integer    用户支付金额    必填    用户实际支付金额。单位：分
     * payTime    String    用户支付时间    必填    用户支付时间。格式：yyyy-MM-dd HH:mm:ss
     * tradeNo    String    交易号    必填    超出权益抵扣的金额部分，再拉起收银台后的支付交易单号
     * @param $params
     * @return array
     */
    public function payed($params)
    {
        $request = new CancelFeeQueryRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'orderNo' => $params['orderNo'] ?? '',
            'phone' => $params['phone'] ?? '',
            'totalFee' => $params['totalFee'] ?? 0,
            'payFee' => $params['payFee'] ?? 0,
            'payTime' => $params['payTime'] ?? '',
            'tradeNo' => $params['tradeNo'] ?? '',
        ]));
        $response = $this->getResponse($request);
        return $response;
    }


    /**
     * 订单正向状态同步SPI
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * customOrderNo    String    客户订单号    必填    客户订单号，仅透传回去
     * orderStatus    String    订单状态    必填    订单状态，详情见文档【订单状态机】、【订单状态枚举表】
     * driverAcceptTime    String    司机接单时间    非必填    司机接单时间，司机接单后的状态必传；该状态表示商户侧的业务时间
     * orderChangeTime    String    订单状态改变时间    必填    订单状态改变时间；代驾商家传
     * totalAmount    String    订单应付金额    非必填    订单应付金额，服务完成后进入到待支付状态，或取消产生取消费进入到取消待支付状态时必传，单位：元
     * waitFreeMinute    String    免费等候时长    非必填    免费等候时长，司机已就位时必传；单位：分钟。如果服务商没有产生取消费的场景，即在进入到开始服务（开车）之前都可以免责取消，此字段可以传0，请在对接前和解决方案以及技术同学做确认
     * vehicleNumberValidate    String    车牌号/车架号校验是否通过    非必填    车牌号/车架号校验是否通过，二级客户配置了需要进行车牌号校验，则该字段必传。若字段为空则，理论上不进行校验。若二级客户维度配置需要进行车牌号校验，但是下游未传该结果字段，支付宝侧进行报错。
     * 枚举字典值：
     * "SUCCESS"-校验通过；
     * "FAILED"-校验失败；
     * driverCarPhotoList    List<String>    司机就位时拍摄的代驾车辆照片信息    非必填    二级客户维度配置代驾车辆预校验，并且订单同步状态为司机已就位，该字段必传。
     * 司机就位时拍摄的代驾车辆照片信息，照片至少需要清晰包含车牌号等关键信息；
     * 具体拍摄照片要求，例如具体张数、拍摄角度，由上游权益客户与代驾商户确定；
     * driverCarPlateNumber    String    需代驾车辆车牌号    非必填    下游不一定有该字段
     * @param $params
     * @return mixed
     */
    public function notify($params)
    {
        $request = new NotifyRequest();
        return $this->alipayClient->parseResponse($request, $params);
    }

    /**
     * 代驾商家订单取消SPI
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * customOrderNo    String    客户订单号    必填    客户订单号，仅透传回去
     * orderChangeTime    String    订单状态改变时间    必填    订单状态改变时间
     * cancelReason    String    取消订单原因    非必填    代驾司机主动取消订单原因，有则传
     * @param $params
     * @return mixed
     */
    public function cancelNotify($params)
    {
        $request = new CancelRequest();
        return $this->alipayClient->parseResponse($request, $params);
    }

    /**
     * 代驾商家改价通知SPI
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * customOrderNo    String    客户订单号    必填    客户订单号，仅透传回去
     * phone    String    乘车人手机号    必填    乘车人手机号，必填非空
     * totalAmount    String    改价之后的金额    必填    改价之后的金额，必填非空；单位：元；精度：两位小数；例：8.08
     * @param $params
     * @return void
     */
    public function changePriceNotify($params)
    {
        $request = new ChangePriceNotifyRequest();
        return $this->alipayClient->parseResponse($request, $params);
    }

    /**
     * 代驾商家收款成功SPI
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * customOrderNo    String    客户订单号    必填    客户订单号，仅透传回去
     * phone    String    用户手机号    必填    乘车人手机号，必填非空
     * totalAmount    String    订单总金额    必填    单位：元；
     * 精度：两位小数；
     * 例：8.08；
     * 订单总费用，是代驾商户测与乘客协调一致的代驾总费用。支付宝侧以该金额作为订单总金额。
     * payAmount    String    支付金额    必填    单位：元；
     * 精度：两位小数；
     * 例：8.08；
     * 一、对于代驾商户侧进行线下支付模式，用户待支付金额等与订单总金额：payAmount=totalAmount；例如司机出示收款码用户扫码支付的线下支付方式，支付宝不考虑代驾商户是否使用了权益或优惠信息，仅仅同步用户线下支付的金额。
     * 例如：
     * 1.代驾商户支持新用户首单减免10元的新客券，代驾总费用是100元，新用户下单后使用券码只需要线下支付90元，那么同步的订单总金额和用户待支付金额就是90元；
     * 2.代驾商户支持10公里免费权益，某权益用户一笔订单行驶了13公里，超过权益券的3公里费用是12.88元，用户线下支付该金额后，代驾SaaS同步给客户的订单总金额和用户待支付金额都是12.88元；
     * 二、对于客户的线上支付模式，应该由客户调用代驾SaaS实现的支付成功通知API，由客户通知代驾SaaS支付成功；线上支付本期暂且不做，未提供API；
     * @param $params
     * @return mixed
     */
    public function payedNotify($params)
    {
        $request = new PayedNotifyRequest();
        return $this->alipayClient->parseResponse($request, $params);
    }

    /**
     * 代驾商家修改终点SPI
     * orderNo    String    代驾SaaS订单号    必填    代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * customOrderNo    String    客户订单号    必填    客户订单号，仅透传回去
     * phone    String    乘车人手机号    必填    乘车人手机号，必填非空
     * address    String    修改后代驾目的地详细地址    必填    修改后代驾目的地详细地址
     * longitude    String    修改后代驾目的地经度    必填    修改后代驾目的地经度
     * latitude    String    修改后代驾目的地纬度    必填    修改后代驾目的地纬度
     * @param $params
     * @return mixed
     */
    public function modifyDestination($params)
    {
        $request = new ModifyDestinationRequest();
        return $this->alipayClient->parseResponse($request, $params);
    }

}