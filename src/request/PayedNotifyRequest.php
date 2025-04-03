<?php

namespace Lucymao9\AlipayDrive\request;

class PayedNotifyRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.spi.payed.notify";
    }

}