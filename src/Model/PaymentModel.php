<?php

namespace App\Model;

use PommProject\ModelManager\Model\Model;
use PommProject\ModelManager\Model\Projection;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

use PommProject\Foundation\Where;

use App\Model\AutoStructure\Payment as PaymentStructure;
use App\Model\Payment;

/**
 * PaymentModel
 *
 * Model class for table payment.
 *
 * @see Model
 */
class PaymentModel extends Model
{
    use WriteQueries {
        deleteByPk as _deleteByPk;
    }

    /**
     * __construct()
     *
     * Model constructor
     *
     * @access public
     */
    public function __construct()
    {
        $this->structure = new PaymentStructure;
        $this->flexible_entity_class = '\App\Model\Payment';
    }

    public function deleteByPk($pk)
    {
        $map = $this->getSession()
            ->getModel('\App\Model\ExpenseModel');

        $sql = sprintf(
            'UPDATE %s SET payment_id = null WHERE payment_id = %d',
            $map->getStructure()->getRelation(), $pk['id']
        );
        $map->query($sql);

        $this->_deleteByPk($pk);
    }
}
