<?php

namespace App\Model\CMS\Content;


use App\Services\FilesService;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Utils\Random;
use Nette\Utils\Strings;

/**
 * @ORM\Entity
 * @ORM\Table(name="image_content")
 */
class ImageContent extends Content implements IContent
{
    protected $type = Content::IMAGE;

    const LEFT = 'left';
    const RIGHT = 'right';
    const CENTER = 'center';

    public static $aligns = [
        self::LEFT,
        self::RIGHT,
        self::CENTER
    ];

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $image;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $align;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $width;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $height;

    /**
     * @var FilesService
     */
    private $filesService;

    /**
     * @param FilesService $filesService
     */
    public function injectRoleRepository(FilesService $filesService) {
        $this->filesService = $filesService;
    }

    /**
     * @return array
     */
    public static function getAligns()
    {
        return self::$aligns;
    }

    /**
     * @param array $aligns
     */
    public static function setAligns($aligns)
    {
        self::$aligns = $aligns;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getAlign()
    {
        return $this->align;
    }

    /**
     * @param string $align
     */
    public function setAlign($align)
    {
        $this->align = $align;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);
        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addHidden('currentImage')
            ->setDefaultValue($this->image);

        $formContainer->addUpload('image', 'admin.cms.pages_content_image_new_file')
            ->setAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.cms.pages_content_image_new_file_format');

        $formContainer->addSelect('align', 'admin.cms.pages_content_image_align', $this->prepareAlignOptions($form->getTranslator()))
            ->setDefaultValue($this->align);

        $formContainer->addText('width', 'admin.cms.pages_content_image_width')
            ->setDefaultValue($this->width)
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.cms.pages_content_image_width_format');

        $formContainer->addText('height', 'admin.cms.pages_content_image_height')
            ->setDefaultValue($this->height)
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.cms.pages_content_image_height_format');

        return $form;
    }

    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];

        $image = $values['image'];
        $width = $values['width'];
        $height = $values['height'];

        if ($image->size > 0) {
            $path = $this->generatePath($image);
            $this->image = $path;

            $this->filesService->save($image, $path);

            $this->width = $width ?: $this->image->toImage()->getWidth();
            $this->height = $height ?: $this->image->toImage()->getHeight();
        }
        else {
            $this->width = $width;
            $this->height = $height;
        }

        $this->align = $values['align'];
    }

    private function prepareAlignOptions($translator)
    {
        $options = [];
        foreach (ImageContent::$aligns as $align)
            $options[$align] = $translator->translate('common.align.' . $align);
        return $options;
    }

    private function generatePath($file) {
        return '/images/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }
}