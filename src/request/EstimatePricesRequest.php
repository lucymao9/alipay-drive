<?php

namespace Lucymao9\AlipayDrive\request;

class EstimatePricesRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.api.order.estimatePrices";
    }

}