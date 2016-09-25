<?php
/**
 * Date: 30.10.12
 * Time: 21:16
 * Author: Michal Májský
 */
namespace BackModule\CMSModule;
use Nette\Application\UI\Form;

/**
 * Obsluhuje sekci dokumenty
 */
class DocumentPresenter extends \BackModule\BasePresenter
{
    protected $resource = \SRS\Model\Acl\Resource::CMS;

    const DOC_REPO = '\SRS\Model\CMS\Documents\Document';
    const TAG_REPO = '\SRS\Model\CMS\Documents\Tag';

    public function startup()
    {
        parent::startup();
        $this->checkPermissions(\SRS\Model\Acl\Permission::MANAGE);
    }


    public function renderDocuments()
    {
        $documents = $this->context->database->getRepository(self::DOC_REPO)->findAll();
        $this->template->documents = $documents;
    }

    public function renderDocument($docId = null)
    {
        if (count($this->context->database->getRepository(self::TAG_REPO)->findAll()) == 0) {
            $this->flashMessage('Nejdříve vytvořte štítek');
            $this->redirect('tags');
        }
        if ($docId != null) {
            $doc = $this->context->database->getRepository(self::DOC_REPO)->find($docId);
            if ($doc == null) throw new \Nette\Application\BadRequestException('Dokument s tímto id neexistuje', 404);
            $form = $this->getComponent('documentForm');
            $form->bindEntity($doc);

            $this->template->document = $doc;
        } else {
            //do nothing
        }
    }

    public function handleDeleteDocument($docId)
    {
        $doc = $this->context->database->getRepository(self::DOC_REPO)->find($docId);
        if ($doc == null) throw new \Nette\Application\BadRequestException('Dokument s tímto id neexistuje', 404);
        unlink(WWW_DIR . $doc->file);
        $this->context->database->remove($doc);
        $this->context->database->flush();
        $this->flashMessage('Dokument smazán', 'success');
        $this->redirect(':Back:CMS:Document:documents');
    }

    public function renderTags()
    {
        $tags = $this->context->database->getRepository(self::TAG_REPO)->findAll();
        $this->template->tags = $tags;
    }

    public function renderTag($tagId = null)
    {
        if ($tagId != null) {
            $tag = $this->context->database->getRepository(self::TAG_REPO)->find($tagId);
            if ($tag == null) throw new \Nette\Application\BadRequestException('Tag s tímto id neexistuje', 404);
            $form = $this->getComponent('tagForm');
            $form->bindEntity($tag);

            $this->template->tag = $tag;
        } else {
            //do nothing
        }
    }

    public function handleDeleteTag($tagId)
    {
        $tag = $this->context->database->getRepository(self::TAG_REPO)->find($tagId);
        if ($tag == null) throw new \Nette\Application\BadRequestException('Tag s tímto id neexistuje', 404);
        $this->context->database->remove($tag);
        $this->context->database->flush();
        $this->flashMessage('Tag smazán', 'success');
        $this->redirect(':Back:CMS:Document:Tags');
    }


    public function renderHeaderFooter()
    {
        $this->template->logo = $this->dbsettings->get('logo');
    }


    protected function createComponentDocumentForm($name)
    {
        $tagChoices = array();
        $tags = $this->presenter->context->database->getRepository(self::TAG_REPO)->findAll();
        $tagChoices = \SRS\Form\EntityForm::getFormChoices($tags);
        $form = new \SRS\Form\CMS\Documents\DocumentForm(null, null, $tagChoices);
        return $form;
    }

    protected function createComponentTagForm($name)
    {
        $roles = $this->presenter->context->database->getRepository('SRS\Model\Acl\Role')->findAll();
        $form = new \SRS\Form\CMS\Documents\TagForm(null, null, \SRS\Form\EntityForm::getFormChoices($roles));
        return $form;
    }

    protected function createComponentHeaderFooterForm()
    {
        $form = new \SRS\Form\CMS\HeaderFooterForm(null, null, $this->dbsettings);
        return $form;
    }


}
