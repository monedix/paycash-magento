<?php

namespace Openpay\Stores\Model\Source;

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
            array('value' => 'PE', 'label' => 'Perú')
        );     
    }
}
