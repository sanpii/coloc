<?php

namespace Model\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class ExpenseMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'Model\Expense';
        $this->object_name  =  'public.expense';

        $this->addField('id', 'int4');
        $this->addField('person_id', 'int4');
        $this->addField('price', 'float4');
        $this->addField('created', 'timestamp');
        $this->addField('shop', 'varchar');
        $this->addField('description', 'varchar');
        $this->addField('payment_id', 'int4');

        $this->pk_fields = array('id');
    }
}
