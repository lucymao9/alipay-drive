<?php

namespace Lucymao9\AlipayDrive\request;

class CancelRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.api.order.cancel";
    }

}