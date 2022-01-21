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
            array('value' => 'MX', 'label' => 'México'),
            array('value' => 'CO', 'label' => 'Colombia'),
            array('value' => 'EC', 'label' => 'Ecuador'),
            array('value' => 'PE', 'label' => 'Perú')
        );     
    }
}
