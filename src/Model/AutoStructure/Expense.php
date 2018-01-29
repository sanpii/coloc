<?php
/**
 * This file has been automatically generated by Pomm's generator.
 * You MIGHT NOT edit this file as your changes will be lost at next
 * generation.
 */

namespace App\Model\AutoStructure;

use PommProject\ModelManager\Model\RowStructure;

/**
 * Expense
 *
 * Structure class for relation public.expense.
 *
 * Class and fields comments are inspected from table and fields comments.
 * Just add comments in your database and they will appear here.
 * @see http://www.postgresql.org/docs/9.0/static/sql-comment.html
 *
 *
 *
 * @see RowStructure
 */
class Expense extends RowStructure
{
    /**
     * __construct
     *
     * Structure definition.
     *
     * @access public
     */
    public function __construct()
    {
        $this
            ->setRelation('public.expense')
            ->setPrimaryKey(['id'])
            ->addField('id', 'int4')
            ->addField('person_id', 'int4')
            ->addField('price', 'numeric')
            ->addField('created', 'timestamp')
            ->addField('shop', 'varchar')
            ->addField('description', 'varchar')
            ->addField('payment_id', 'int4')
            ->addField('tr', 'int4')
            ;
    }
}
