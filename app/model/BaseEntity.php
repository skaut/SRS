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

    /**
     * Slouzi pro vlozeni atributu z Nette Formulare
     *
     * @param array $values
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function setProperties($values = array(), $em = null)
    {
        $posibilities = array ('ORM\ManyToMany', 'ORM\ManyToOne', 'ORM\OneToMany');
        $reflection = new \Nette\Reflection\ClassType($this);

        foreach($values as $key => $value){
           $propertyReflection = $reflection->getProperty($key);

            //obsluhujeme vazby
            if ($propertyReflection->hasAnnotation('ORM\ManyToMany') ||
                $propertyReflection->hasAnnotation('ORM\ManyToOne') ||
                $propertyReflection->hasAnnotation('ORM\OneToMany'))
            {
                $association = null;
                while ($association == null) {
                    $association = $propertyReflection->getAnnotation('ORM\ManyToMany');
                    $targetEntity = $association['targetEntity'];
                }

                if (is_array($value)) { //vazba oneToMany nebo ManyToMany
                    $newData = new \Doctrine\Common\Collections\ArrayCollection();
                    foreach($value as $itemId) {
                        $newData->add($em->getReference($targetEntity, $itemId));
                    }
                    $value = $newData;
                }
                else { //vazba ManyToOne
                    $value = $em->getReference($targetEntity, $value);
                }
            }

            if ($key != 'id') {
                $this->{"set$key"}($value);
            }
        }

    }


}
