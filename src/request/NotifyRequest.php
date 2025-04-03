<?php

namespace Lucymao9\AlipayDrive\request;

class NotifyRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.spi.order.notify";
    }

}