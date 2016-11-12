<?php

namespace Model;

use PommProject\ModelManager\Model\Model;
use PommProject\ModelManager\Model\Projection;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

use PommProject\Foundation\Where;

use Model\AutoStructure\Person as PersonStructure;
use Model\Person;

/**
 * PersonModel
 *
 * Model class for table person.
 *
 * @see Model
 */
class PersonModel extends Model
{
    use WriteQueries;

    /**
     * __construct()
     *
     * Model constructor
     *
     * @access public
     */
    public function __construct()
    {
        $this->structure = new PersonStructure;
        $this->flexible_entity_class = '\Model\Person';
    }
}
