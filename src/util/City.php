<?php

namespace Lucymao9\AlipayDrive\util;

class City
{
    public static function getCityCode($adCode){
        return substr_replace($adCode,'00',4,2);
    }
}