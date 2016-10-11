<?php
namespace SRS\Components;
use SRS\Model\Acl\Resource;
use SRS\Model\Acl\Permission;

/**
 * Komponenta slouzici pro zobrazeni informaci o blocich
 */
class BlockBox extends \Nette\Application\UI\Control
{
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $this->template->categories = $this->presenter->context->database->getRepository('\SRS\Model\Program\Category')->findBy(array(), array('name' => 'ASC'));
        $this->template->uncategorized = count($this->presenter->context->database->getRepository('\SRS\Model\Program\Block')->findBy(array('category' => null)));
        $this->template->blocks = $this->presenter->context->database->getRepository('\SRS\Model\Program\Block')->findBy(array(), array('name' => 'ASC'));
        $this->template->basicBlockDuration = $this->presenter->dbsettings->get('basic_block_duration');

        $blockId = $this->presenter->getParameter('blockId');
        if ($blockId != null) {
            $selectedBlock = $this->presenter->context->database->getRepository('\SRS\Model\Program\Block')->find($blockId);
            $this->template->selectedBlock = $selectedBlock;
            if ($selectedBlock->category != null)
                $this->template->selectedCategoryId = $selectedBlock->category->id;
            else
                $this->template->selectedCategoryId = "unc";
        }
        else {
            $this->template->selectedCategoryId = null;
        }

        $template->render();
    }
}
