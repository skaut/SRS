<?php
/**
 * Date: 3.12.12
 * Time: 20:58
 * Author: Michal Májský
 */

namespace SRS\Model;
use Doctrine\ORM\Mapping as ORM,
    JMS\Serializer\Annotation as JMS;

/**
 * @property-read int $id
 */
abstract class BaseEntity extends \Nette\Object
{
    /**
     * @JMS\Type("integer")
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     *
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
     * @param array $values
     * @param \Doctrine\ORM\EntityManager $em
     * @throws \Exception pak je nejspise chyba v metode a je treba ji opravit
     */
    public function setProperties($values = array(), $em)
    {
        $associtaionPosibilities = array('ORM\ManyToMany', 'ORM\ManyToOne', 'ORM\OneToMany');
        $reflection = new \Nette\Reflection\ClassType($this);

        foreach ($values as $key => $value) {
            //pokud vubec existuje property s timto jmenem
            if ($reflection->hasProperty($key)) {
                $propertyReflection = $reflection->getProperty($key);

                //obsluhujeme vazby
                if ($propertyReflection->hasAnnotation('ORM\ManyToMany') ||
                    $propertyReflection->hasAnnotation('ORM\ManyToOne') ||
                    $propertyReflection->hasAnnotation('ORM\OneToMany')
                ) {

                    $association = null;

                    foreach ($associtaionPosibilities as $possibility) {
                        $association = $propertyReflection->getAnnotation($possibility);
                        $targetEntity = $association['targetEntity'];
                        if ($association != null) break;
                    }
                    if ($association == null) {
                        throw new \Exception('Problem v prirazeni asociaci v BaseEntity->setProperties');
                    }

                    if (is_array($value)) { //vazba oneToMany nebo ManyToMany
                        $newData = new \Doctrine\Common\Collections\ArrayCollection();
                        foreach ($value as $itemId) {
                            $newData->add($em->getReference($targetEntity, $itemId));
                        }
                        $value = $newData;
                    } else { //vazba ManyToOne
                        if ($value != null) {
                            $value = $em->getReference($targetEntity, $value);
                        }
                    }
                }
                //method_exists(get_class(),"set$key")
                if ($key != 'id') {
                    $columnAnnotation = $propertyReflection->getAnnotation('ORM\Column');
                    if (isset($columnAnnotation['type']) && $columnAnnotation['type'] == 'date') {
                        $value = \DateTime::createFromFormat("Y-m-d", $value);
                    }
                    if (isset($columnAnnotation['type']) && $columnAnnotation['type'] == 'datetime') {
                        $value = BaseEntity::normalizeDateTime($value);
                        $date = $value;
                        $value = \DateTime::createFromFormat("Y-m-d H:i:s", $value);
                        if ($value == false) {
                            $value = \DateTime::createFromFormat("Y-n-j G:i:s", $date);
                        }
                        if ($value == false) {
                            throw new \Exception('Nepodařilo se naparsovat datum ' . $date);
                        }
                    }

                    $this->{"set$key"}($value);
                }
            }
        }

    }

    /**
     * Pretransformuje datetime vraceny v javascriptu do tvaru stravitelneho doctrine
     * @param string $datetime
     * @return string
     */
    public static function normalizeDateTime($datetime)
    {
        if (strpos($datetime, 'T') !== false) {
            $datetime = str_replace('T', ' ', $datetime);
            $deletePosition = strpos($datetime, '.');
            $result = str_split($datetime, $deletePosition);
            return $result[0];
        }
        return $datetime;
    }


}
