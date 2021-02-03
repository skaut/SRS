<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\ImageContentDto;
use App\Model\Cms\Dto\SlideshowContentDto;
use App\Services\FilesService;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nette\Utils\UnknownImageFileException;
use phpDocumentor\Reflection\Types\Array_;
use stdClass;

use function file_exists;

use const UPLOAD_ERR_OK;

/**
 * Entita obsahu se slideshow.
 *
 * @ORM\Entity
 * @ORM\Table(name="slideshow_content")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SlideshowContent extends Content implements IContent
{
    protected string $type = Content::IMAGE;

    /**
     * Adresy obrázků.
     *
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var string[]
     */
    protected ?array $images;

    private FilesService $filesService;

    public function injectFilesService(FilesService $filesService): void
    {
        $this->filesService = $filesService;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): void
    {
        $this->images = $images;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        /** @var Container $formContainer */
        $formContainer = $form[$this->getContentFormName()];

//        $formContainer->addText('currentImage', 'admin.cms.pages_content_image_current_file')
//            ->setHtmlAttribute('data-type', 'image')
//            ->setHtmlAttribute('data-image', $this->image);

        $formContainer->addMultiUpload('image', 'admin.cms.pages_content_image_new_file')
//            ->setHtmlAttribute('accept', 'image/*')
            ->setHtmlAttribute('data-show-preview', 'true');
//            ->addCondition(Form::FILLED)
//            ->addRule(Form::IMAGE, 'admin.cms.pages_content_image_new_file_format');

//        $formContainer->setDefaults([
//            'align' => $this->align,
//            'width' => $this->width,
//            'height' => $this->height,
//        ]);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     *
     * @throws UnknownImageFileException
     * @throws ImageException
     */
    public function contentFormSucceeded(Form $form, stdClass $values): void
    {
        parent::contentFormSucceeded($form, $values);

        $this->images = ['aa'];
//        $formName = $this->getContentFormName();
//        $values   = $values->$formName;
//        /** @var FileUpload $file */
//        $file   = $values->image;
//        $width  = $values->width !== '' ? $values->width : null;
//        $height = $values->height !== '' ? $values->height : null;
//
//        $image = null;
//
//        $exists = false;
//
//        if ($file->getError() == UPLOAD_ERR_OK) {
//            $path        = $this->generatePath($file);
//            $this->image = $path;
//            $this->filesService->save($file, $path);
//            $image  = $file->toImage();
//            $exists = true;
//        } elseif ($this->image) {
//            $path   = $this->filesService->getDir() . $this->image;
//            $exists = file_exists($path);
//            if ($exists) {
//                $image = Image::fromFile($path);
//            }
//        }
//
//        if ($exists) {
//            if ($width && $height) {
//                $this->width  = $width;
//                $this->height = $height;
//            } elseif (! $width && ! $height) {
//                $this->width  = $image->getWidth();
//                $this->height = $image->getHeight();
//            } elseif ($width) {
//                $this->width  = $width;
//                $this->height = (int) ($image->getHeight() * $width / $image->getWidth());
//            } else {
//                $this->width  = (int) ($image->getWidth() * $height / $image->getHeight());
//                $this->height = $height;
//            }
//        } else {
//            $this->width  = $width;
//            $this->height = $height;
//        }
//
//        $this->align = $values->align;
    }

    /**
     * Vygeneruje cestu pro uložení obrázku.
     */
    private function generatePath(FileUpload $file): string
    {
        return '/images/' . Random::generate(5) . '/' . Strings::webalize($file->name, '.');
    }

    public function convertToDto(): ContentDto
    {
        return new SlideshowContentDto($this->getComponentName(), $this->heading, $this->images);
    }
}
