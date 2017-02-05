<?php

namespace App\Model\CMS;


use Kdyby\Doctrine\EntityRepository;

class NewsRepository extends EntityRepository
{
    public function findNewsById($id) {
        return $this->find($id);
    }

    public function addNews($text, $published) {
        $news = new News();

        $news->setText($text);
        $news->setPublished($published);

        $this->_em->persist($news);
        $this->_em->flush();

        return $news;
    }

    public function editNews($id, $text, $published) {
        $news = $this->find($id);

        $news->setText($text);
        $news->setPublished($published);

        $this->_em->flush();

        return $news;
    }

    public function removeNews($id)
    {
        $news = $this->find($id);
        $this->_em->remove($news);
        $this->_em->flush();
    }
}