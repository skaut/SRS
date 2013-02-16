<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 7.1.13
 * Time: 16:55
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="\SRS\Model\SettingsRepository")
 * @property string $item
 * @property string $value
 * @property string $description

 */
class Settings extends \SRS\Model\BaseEntity
{

    /**
     * @ORM\Column(unique=true)
     * @var string
     */
    protected $item;


    /**
     * @ORM\Column(nullable=true)
     * @var string
     *
     */
    protected $value;
    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $description;


    public function __construct($item, $description = null, $value = null)
    {
        $this->item = $item;
        $this->value = $value;
        $this->description = $description;
    }


    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setItem($item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

}

class SettingsRepository extends \Doctrine\ORM\EntityRepository
{
    public $entity = '\SRS\Model\Settings';

    /**
     * Vrati rovnou zadanou hodnotu v konfigu
     * @param string $item
     * @return $string
     */
    public function get($item) {
        $result = $this->_em->getRepository($this->entity)->findByItem($item);
        if ($result == null) throw new SettingsException("Položka {$item} v Settings není");
        $value = $result[0]->value;
        return $value;
    }

    public function set($item, $value) {
        $result= $this->_em->getRepository($this->entity)->findByItem($item);
        if ($result == null) throw new SettingsException("Položka {$item} v Settings není");
        $item = $result[0];
        $item->value = $value;
        $this->_em->flush();
    }

    public function toArray() {

        $result = $this->_em->getRepository($this->entity)->findAll();
        \Nette\Diagnostics\Debugger::dump($result);
        throw new \Nette\NotImplementedException();
        //TODO
    }
}

class SettingsException extends \Exception { }
