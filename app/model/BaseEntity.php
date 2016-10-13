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
 * Zakladni entita pro cely projekt
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
                        if ($value == "") {
                            $value = null;
                        }
                        else {
                            $date = $value;
                            $value = \DateTime::createFromFormat("d.m.Y", $date);

                            if ($value == false) {
                                $value = \DateTime::createFromFormat("Y-m-d", $value);
                            }

                            if ($value == false) {
                                throw new \Exception('Nepodařilo se naparsovat datum ' . $date);
                            }
                        }
                    }
                    if (isset($columnAnnotation['type']) && $columnAnnotation['type'] == 'datetime') {
                        if ($value == "") {
                            $value = null;
                        }
                        else {
                            $date = $value;
                            $value = \DateTime::createFromFormat("d.m.Y H:i", $date);

                            if ($value == false) {
                                $value = \DateTime::createFromFormat("d.m.Y H:i", $date . " 00:00");
                            }

                            if ($value == false) {
                                $value = \DateTime::createFromFormat("Y-n-j H:i:s", $date);
                            }

                            if ($value == false) {
                                $value = \DateTime::createFromFormat("Y-n-j G:i:s", $date);
                            }

                            if ($value == false) {
                                throw new \Exception('Nepodařilo se naparsovat datum a čas ' . $date);
                            }
                        }
                    }

                    $this->{"set$key"}($value);
                }
            }
        }

    }
}
