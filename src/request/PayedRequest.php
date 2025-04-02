<?php

namespace Lucymao9\AlipayDrive\request;

class PayedRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.api.order.payed";
    }

}