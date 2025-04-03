<?php

namespace Lucymao9\AlipayDrive\request;

class CancelNotifyRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.spi.cancel.notify";
    }

}