<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 3.12.12
 * Time: 20:58
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Model;
use Doctrine\ORM\Mapping as ORM;


/**
 * @property-read int $id
 */
abstract class BaseEntity extends \Nette\Object
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    public function setProperties($values = array())
    {
        foreach($values as $key => $value){
            if ($key != 'id') {
                $this->{"set$key"}($value);
            }
        }


    }

}
