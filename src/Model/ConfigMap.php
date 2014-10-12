<?php

namespace Model;

use Model\Config;
use Model\Base\ConfigMap as BaseConfigMap;

class ConfigMap extends BaseConfigMap
{
    public function get($key, $default = null)
    {
        $row = $this->findByPk(compact('key'));

        return ($row !== null) ? $row->getValue() : $default;
    }
}
