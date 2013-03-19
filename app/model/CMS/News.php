<?php
/**
 * Date: 15.11.12
 * Time: 13:27
 * Author: Michal Májský
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="\SRS\Model\CMS\NewsRepository")
 * @property string $text
 * @property \DateTime $published
 * @property \DateTime $valid_from
 * @property \DateTime $valid_to
 */
class News extends \SRS\Model\BaseEntity
{


    /**
     * @ORM\Column(type="text")
     */
    protected $text;

    /**
     * @ORM\Column(type="date")
     */
    protected $published;

//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    protected $valid_from;
//
//    /**
//     * @ORM\Column(type="date", nullable=true)
//     */
//    protected $valid_to;

    /**
     * @ORM\ManyToOne(targetEntity="\SRS\model\User")
     */
    protected $author;


    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setPublished($published)
    {
        $this->published = $published;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

}

class NewsRepository extends \Doctrine\ORM\EntityRepository
{
    public $entity = '\SRS\Model\CMS\News';


    public function findAllOrderedByDate($limit = null)
    {
        $query = "SELECT item FROM {$this->entity} item ORDER BY item.published DESC";
        if ($limit === null) {

            $result = $this->_em->createQuery($query)->getResult();
        } else {
            $result = $this->_em->createQuery($query)->setMaxResults($limit)->getResult();
        }
        return $result;
    }


}
