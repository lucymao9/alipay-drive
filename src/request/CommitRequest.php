<?php

namespace Lucymao9\AlipayDrive\request;

class CommitRequest extends PlanetRequest
{
    public function getApiMethodName(){
        return "alipay.planet.solrideplatformtob.api.order.commit";
    }

}