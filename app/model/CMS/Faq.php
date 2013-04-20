<?php
/**
 * Date: 15.11.12
 * Time: 13:27
 * Author: Michal Májský
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;


/**
 * Entita reprezentujici FAQ
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\CMS\FaqRepository")
 * @property string $question
 * @property string $answer
 * @property integer $position
 * @property boolean $public
 */
class Faq extends \SRS\Model\BaseEntity
{

    /**
     * @ORM\Column(type="text")
     */
    protected $question;


    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $answer;


    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    protected $position = 0;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $public = false;

    /**
     * @param string $answer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;
    }

    /**
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param boolean $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return boolean
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @param string $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    public function getQuestion()
    {
        return $this->question;
    }

}

class FaqRepository extends \Doctrine\ORM\EntityRepository
{
    public $entity = '\SRS\Model\CMS\Faq';

    public function findAllOrdered()
    {
        $result = $this->_em->createQuery("SELECT faq FROM {$this->entity} faq ORDER BY faq.position ASC")->getResult();
        return $result;
    }

    public function findAllOrderedPublished()
    {
        $result = $this->_em->createQuery("SELECT faq FROM {$this->entity} faq WHERE faq.public=true ORDER BY faq.position ASC")->getResult();
        return $result;

    }
}
