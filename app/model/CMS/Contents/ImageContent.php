<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:27
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\CMS;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 *
 * @property string $image
 */
class ImageContent extends \SRS\Model\CMS\Content implements IContent
{
    protected $contentType = 'imagecontent';
    protected $contentName = 'Obrázek';

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $image;


    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }


    public function addFormItems(\Nette\Application\UI\Form $form) {
        parent::addFormItems($form);
        $formContainer = $form[$this->getFormIdentificator()];
        $formContainer->addUpload('image', 'Obrázek')
                        ->addCondition(\Nette\Application\UI\Form::FILLED)
                        ->addRule(\Nette\Application\UI\Form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF.');
        $formContainer->addHidden('template', 'contents/'.$this->contentType.'.html');
        $formContainer->addHidden('curImage', $this->image);

        return $form;
    }

    public function setValuesFromPageForm(\Nette\Application\UI\Form $form) {
        parent::setValuesFromPageForm($form);
        $values = $form->getValues();
        $values = $values[$this->getFormIdentificator()];

        $image = $values['image'];

        if ($image->size > 0) {

            $imagePath = '/files/images/'. \Nette\Utils\Strings::random(5) . '_' . \Nette\Utils\Strings::webalize($image->getName(), '.');
            $image->move( WWW_DIR . $imagePath);
            $this->image = $imagePath;
        }
//        else {
//            $this->image = '';
//        }
    }

    public function getContentName() {
        return $this->contentName;
    }





}
