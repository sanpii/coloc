<?php

namespace Model;

use PommProject\ModelManager\Model\Model;
use PommProject\ModelManager\Model\Projection;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

use PommProject\Foundation\Where;

use Model\AutoStructure\Payment as PaymentStructure;
use Model\Payment;

/**
 * PaymentModel
 *
 * Model class for table payment.
 *
 * @see Model
 */
class PaymentModel extends Model
{
    use WriteQueries;

    /**
     * __construct()
     *
     * Model constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->structure = new PaymentStructure;
        $this->flexible_entity_class = '\Model\Payment';
    }
}
