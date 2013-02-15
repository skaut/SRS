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

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $align;


    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $width;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $height;


    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $align
     */
    public function setAlign($align)
    {
        $this->align = $align;
    }

    /**
     * @return string
     */
    public function getAlign()
    {
        return $this->align;
    }

    /**
     * @param string $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }



    public function addFormItems(\Nette\Application\UI\Form $form) {
        $posOptions = array(
            'left' => 'Vlevo',
            'right' => 'Vpravo',
            'center' => 'Uprostřed (bez obtékání)'
        );
        parent::addFormItems($form);
        $formContainer = $form[$this->getFormIdentificator()];
        $formContainer->addUpload('image', 'Obrázek')
                        ->addCondition(\Nette\Application\UI\Form::FILLED)
                        ->addRule(\Nette\Application\UI\Form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF.');
        $formContainer->addSelect('align', 'Pozice')->setItems($posOptions)->setDefaultValue($this->align);
        $formContainer->addText('width', 'Šířka')->setDefaultValue($this->width);
        $formContainer->addText('height', 'Výška')->setDefaultValue($this->height);
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

            if ($values['width'] == null) {
                $this->width = $image->toImage()->getWidth();
            }
            else {
                $this->width = $values['width'];
            }
            if ($values['height'] == null) {
                $this->height = $image->toImage()->getHeight();
            }
            else {
                $this->height = $values['height'];
            }

        }
        else {
            $this->width = $values['width'];
            $this->height = $values['height'];
        }

        $this->align = $values['align'];

    }

    public function getContentName() {
        return $this->contentName;
    }





}
