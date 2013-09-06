<?php

namespace Model\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class PersonMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'Model\Person';
        $this->object_name  =  'public.person';

        $this->addField('id', 'int4');
        $this->addField('name', 'varchar');
        $this->addField('password', 'varchar');
        $this->addField('email', 'varchar');

        $this->pk_fields = array('id');
    }
}
