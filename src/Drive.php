<?php

namespace Lucymao9\AlipayDrive;

use Lucymao9\AlipayDrive\aop\AlipayConfig;
use Lucymao9\AlipayDrive\aop\AopClient;
use Lucymao9\AlipayDrive\request\CancelFeeQueryRequest;
use Lucymao9\AlipayDrive\request\CancelRequest;
use Lucymao9\AlipayDrive\request\CommitRequest;
use Lucymao9\AlipayDrive\request\EstimatePricesRequest;
use Lucymao9\AlipayDrive\request\FeeDetailQueryRequest;
use Lucymao9\AlipayDrive\request\NearbyDriversRequest;
use Lucymao9\AlipayDrive\request\PlanetRequest;
use Lucymao9\AlipayDrive\request\QueryRequest;
use Lucymao9\AlipayDrive\request\TrackQueryRequest;

class Drive
{
    private string $env;

    private $alipayClient ;

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
        $url = 'https://icgwcoreproxy2.dl.alipaydev.com';
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
        $this->alipayClient = new AopClient($alipayConfig);
        $this->alipayClient->encryptKey = $sign;
        $this->alipayClient->encryptType = "AES";
    }

    public function getResponse(PlanetRequest $request)
    {
        $responseResult = $this->alipayClient->execute($request);
//        echo json_encode($responseResult);
        $responseApiName = str_replace(".","_",$request->getApiMethodName())."_response";
        $response = $responseResult->$responseApiName;

//        return json_decode(json_encode($response),true);
        if(!empty($response->code)&&$response->code==10000){
            return ['result'=>true,'data'=>$response->data];
        }
        else{
            return ['result'=>false,'code'=>$response->code,'msg'=>$response->msg,'sub_code'=>$response->sub_code,'sub_msg'=>$response->sub_msg];
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
//        print_r($response);
        return $response;
    }

    /**
     * 代驾预估价格列表
     * customCode	String	客户编号	必填	代驾SaaS内部定义的客户ID，
     * 获取见业务预入驻信息
     * cityCode	String	城市码	必填	下单所在城市城市码，下单派单时筛选指定城市代驾商户需要；
     * 见省市区划分城市码
     * phone	String	下单用户手机号	必填	下单用户手机号，必填
     * startLongitude	String	下单起始经度	必填	下单起始经度，必填https://lbs.amap.com/tools/picker
     * startLatitude	String	下单起始纬度	必填	下单起始纬度，必填
     * endLongitude	String	下单目的地经度	必填	下单目的地经度，必填
     * endLatitude	String	下单目的地纬度	必填	下单目的地纬度，必填
     * callLongDistanceDriver	Boolean	是否呼叫远程司机	必填	是否呼叫远程司机，是-返回的预估价格可能包含远程司机的订单预估价格；
     * driveIsvCodeList	List<String>	代驾商家isvCode集合	非必填	若指定代驾isv信息，则返回指定isv的预估报价和里程及用户等信息列表。否则以权益客户&下单城市维度绑定的所有isv进行返回
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 代驾用户下单接口
     * vehiclePlateNumber	String	用户代驾车辆车牌号	非必填	用户代驾车辆车牌号，新车未上牌校验看车架号；
     * vehicleIdentifyNumber	String	用户代驾车辆车架号	非必填	新车未上牌校验看车架号
     * vehicleNumberPreVerificate	String	是否进行车牌号/车架号车辆信息质检校验	非必填 YES - 进行车牌号车辆信息校验，司机已就位状态同步时，要求代驾商户回传相关车辆照片信息；NO - 不进行车牌号车辆信息校验，司机已就位状态同步时，代驾商户回传相关车辆照片信息可能为空；
     * userId	String	用户ID	非必填	用户唯一id，权益服务商有则传
     * phone	String	下单用户手机号	必填	下单用户手机号，必填
     * cityCode	String	城市码	必填	下单所在城市城市码，下单派单时筛选指定城市代驾商户需要；
     * startAddress	String	下单起始地址	必填	下单起始地址，必填https://lbs.amap.com/tools/picker
     * startLongitude	String	下单起始经度	必填	下单起始经度，必填
     * startLatitude	String	下单起始纬度	必填	下单起始纬度，必填
     * endAddress	String	下单目的地地址	必填	下单目的地地址，必填
     * endLongitude	String	下单目的地经度	必填	下单目的地经度，必填
     * endLatitude	String	下单目的地纬度	必填	下单目的地纬度，必填
     * equityTypeCode	String	权益类型编码	非必填	枚举类型：LCDK-里程抵扣；默认单位KM;    JEDK-金额抵扣；默认单位元;    LCMD-里程抵扣(KM)+免等时长(分钟) equityTypeCode、equityTypeValue、equityId 必须同时非空或者同时都传；
     * equityTypeValue	String	权益类型面值	非必填	权益券面值，与equityTypeCode对应。例如当 equityTypeCode 是 LCDK，equityTypeValue 是 10，则表示10 公里里程券。equityTypeCode 是 JEDK ，equityTypeValue 是 8.8，则表示8.8元金额抵扣券；对于 LCMD-里程抵扣+免等时长,使用“_”作为分隔符，例如,equityTypeCode：LCMD,equityTypeValue：10_30,equityId：axnshfad11a3awsa,表示该权益ID表示“10公里里程抵扣 + 30分钟免等权益”
     * equityId	String	权益ID（券Id）	非必填	客户透传权益券ID，用于权益客户与代驾商家核销；
     * customCode	String	客户编号	必填	代驾SaaS内部定义的客户ID，获取见业务预入驻信息
     * secondCustomCode	String	二级客户编号	必填	代驾SaaS内部定义的二级客户id（银行、保司等），分配给客户调用接口时传，获取见业务预入驻信息
     * customOrderNo	String	外部订单号	必填	外部客户系统的订单号，必传。用作幂等控制，若没有订单号也必须传一个唯一的幂等字段；
     * driveIsvCodeList	List<String>	代驾商家isvCode集合	非必填	若不为空表示客户指定代驾服务商进行派单。
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 用户取消订单接口
     * orderNo	String	代驾SaaS平台订单号	必填	代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone	String 	下单用户手机号	必填	用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * reason	String	用户取消原因	非必填	用户取消原因，有则传
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 订单详情查询接口
     * orderNo	String	代驾SaaS订单号	必填	代驾SaaS系统下单成功的订单号，后续所有订单功能都传此订单号
     * phone	String 	下单用户手机号	必填	用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 订单轨迹查询接口
     * orderNo	String	代驾SaaS订单号	必填	代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone	String 	用户手机号码	必填	用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * startTimestamp	String	轨迹查询时间戳	非必填	Unix时间戳，到秒，（返回该时间点后的路径数据，用于增量获取
     * @param $params
     * @return array
     */
    public function trackQuery($params)
    {
        $request = new TrackQueryRequest();
        //报文内容为json格式
        $request->setBizContent(json_encode([
            'orderNo' => $params['orderNo'] ?? '',
            'phone' => $params['phone'] ?? '',
            'startTimestamp' => $params['startTimestamp'] ?? '',
        ]));
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 费用明细查询接口
     * orderNo	String	代驾SaaS订单号	必填	代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone	String 	用户手机号码	必填	用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 取消费用查询接口
     * orderNo	String	代驾SaaS订单号	必填	代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone	String 	用户手机号码	必填	用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

    /**
     * 线上支付成功通知
     * orderNo	String	代驾SaaS订单号	必填	代驾SaaS系统下单成功的订单号，后续所有的订单功能都传此订单号
     * phone	String 	用户手机号码	必填	用户手机号，应与订单下单时传入的一致，代驾SaaS会进行校验
     * totalFee	Integer	订单总金额	必填	订单总金额。单位：分
     * payFee	Integer	用户支付金额	必填	用户实际支付金额。单位：分
     * payTime	String	用户支付时间	必填	用户支付时间。格式：yyyy-MM-dd HH:mm:ss
     * tradeNo	String	交易号	必填	超出权益抵扣的金额部分，再拉起收银台后的支付交易单号
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
        //加密开关，仅在接口内容需要加密时生效
        $request->setNeedEncrypt(true);
        $response = $this->getResponse($request);
        return $response;
    }

}