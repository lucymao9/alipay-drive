<?php

namespace Lucymao9\AlipayDrive\request;

class TrackQueryRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.api.order.tracequery";
    }

}