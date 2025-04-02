<?php

namespace Lucymao9\AlipayDrive\request;

class QueryRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.api.order.query";
    }

}