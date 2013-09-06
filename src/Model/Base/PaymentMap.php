<?php

namespace Model\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class PaymentMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'Model\Payment';
        $this->object_name  =  'public.payment';

        $this->addField('id', 'int4');
        $this->addField('done', 'bool');
        $this->addField('created', 'timestamp');

        $this->pk_fields = array('id');
    }
}
