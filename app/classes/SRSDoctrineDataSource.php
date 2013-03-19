<?php
/**
 * Date: 18.11.12
 * Time: 18:04
 * Author: Michal Májský
 */

namespace SRS;

class SRSDoctrineDataSource implements \NiftyGrid\DataSource\IDataSource
{
    private $qb;

    private $primary;

    public function __construct($qb, $primary)
    {
        // Query builder
        $this->qb = $qb;

        // Primary id
        $this->primary = $primary;
    }

    public function getQuery()
    {
        return $this->qb->getQuery();
    }

    public function getData()
    {
        $result = array();
        foreach ($this->getQuery()->getArrayResult() as $item) {
            $primaryKey = $this->primary;
            $id = $item[$primaryKey];
            $result[$id]['id'] = $item[$primaryKey];

            foreach ($item as $column => $value) {
                $result[$id][$column] = $value;
            }
        }

        return $result;
    }

    public function getPrimaryKey()
    {
        return $this->primary;
    }

    public function getCount($column = "*")
    {
        return $this->getSelectedRowsCount();
    }

    public function getSelectedRowsCount()
    {
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($this->getQuery());

        return $paginator->count();
    }

    public function orderData($by, $way)
    {
        $this->qb->orderBy($this->columnName($by), $way);
    }

    public function limitData($limit, $offset)
    {
        $this->qb->setFirstResult($offset)
            ->setMaxResults($limit);
    }

    public function filterData(array $filters)
    {
        foreach ($filters as $filter) {
            if ($filter["type"] == \NiftyGrid\FilterCondition::WHERE) {

                $column = $this->columnName($filter['column']);

                $value = $filter["value"];
                $expr = $this->qb->expr();
                $cond = false;

                switch ($filter['cond']) {
                    case ' LIKE ?':
                        $cond = $expr->like($column, $expr->literal($value));
                        break;

                    case ' = ?':
                        $cond = $expr->eq($column, $expr->literal($value));
                        break;

                    case ' > ?':
                        $cond = $expr->gt($column, $expr->literal($value));
                        break;

                    case ' >= ?':
                        $cond = $expr->gte($column, $expr->literal($value));
                        break;

                    case ' < ?':
                        $cond = $expr->lt($column, $expr->literal($value));
                        break;

                    case ' <= ?':
                        $cond = $expr->lte($column, $expr->literal($value));
                        break;

                    case ' <> ?':
                        $cond = $expr->neq($column, $expr->literal($value));
                        break;
                }

                if (!$cond) {
                    try {
                        $datetime = new \DateTime($value);
                        $value = $datetime->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                    }

                    if (isset($datetime)) {
                        switch ($filter['cond']) {
                            /** Dates */
                            case ' = ':
                                $cond = $expr->like($column, $expr->literal($datetime->format('Y-m-d') . '%'));
                                break;

                            case ' > ':
                                $cond = $expr->gt($column, $expr->literal($value));
                                break;

                            case ' >= ':
                                $cond = $expr->gte($column, $expr->literal($value));
                                break;

                            case ' < ':
                                $cond = $expr->lt($column, $expr->literal($value));
                                break;

                            case ' <= ':
                                $cond = $expr->lte($column, $expr->literal($value));
                                break;

                            case ' <> ':
                                $cond = $expr->neq($column, $expr->literal($value));
                                break;
                        }
                    }
                }

                if ($cond) {
                    $this->qb->andWhere($cond);
                }

            }
        }
    }

    private function columnName($full)
    {
        $name = explode("_", $full);
        $entity = $name[0];
        unset($name[0]);
        $part = $this->qb->getDQLPart('from');
        $fromAlias = $part[0]->getAlias();
        $column = $entity . "." . implode("_", $name);
        $column = str_replace('.', '', $column);
        $column = $fromAlias . '.' . $column;

        return $column;
    }

}
