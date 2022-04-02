<?php

namespace Paycash\Pay\Model\Source;

class Countries
{
    /**
     * @return array
     */
    public function getOptions()
    {
        return array(
            array('value' => 'MEX', 'label' => 'México'),
            array('value' => 'COL', 'label' => 'Colombia'),
            array('value' => 'ECU', 'label' => 'Ecuador'),
            array('value' => 'PER', 'label' => 'Perú'),
            array('value' => 'CRI', 'label' => 'Costa Rica')
        );     
    }
}
