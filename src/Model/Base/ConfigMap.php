<?php

namespace Model\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class ConfigMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'Model\Config';
        $this->object_name  =  'public.config';

        $this->addField('key', 'varchar');
        $this->addField('value', 'varchar');

        $this->pk_fields = array('key');
    }
}
