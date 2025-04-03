<?php

namespace Lucymao9\AlipayDrive\request;

class TraceQueryRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.api.order.tracequery";
    }

}