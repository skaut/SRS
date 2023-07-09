<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\SlideshowContentDto;
use App\Services\FilesService;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use stdClass;

use function array_map;
use function assert;
use function basename;
use function json_encode;

use const JSON_THROW_ON_ERROR;
use const UPLOAD_ERR_OK;

/**
 * Entita obsahu se slideshow.
 */
#[ORM\Entity]
#[ORM\Table(name: 'slideshow_content')]
class SlideshowContent extends Content implements IContent
{
    protected string $type = Content::SLIDESHOW;

    /**
     * Adresy obrázků.
     *
     * @var string[]|null.
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    protected array|null $images = null;

    private FilesService $filesService;

    public function injectFilesService(FilesService $filesService): void
    {
        $this->filesService = $filesService;
    }

    /** @return string[]|null */
    public function getImages(): array|null
    {
        return $this->images;
    }

    /** @param string[]|null $images */
    public function setImages(array|null $images): void
    {
        $this->images = $images;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];
        assert($formContainer instanceof Container);

        $formContainer->addMultiUpload('images', 'admin.cms.pages.content.form.slideshow_images')
            ->setHtmlAttribute('accept', 'image/*')
            ->setHtmlAttribute('data-show-preview', 'true')
            ->setHtmlAttribute('data-initial-preview', json_encode($this->images, JSON_THROW_ON_ERROR))
            ->setHtmlAttribute('data-initial-preview-config', json_encode(array_map(static fn (string $image) => ['caption' => basename($image)], $this->images), JSON_THROW_ON_ERROR))
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.cms.pages.content.form.slideshow_images_format');

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values): void
    {
        parent::contentFormSucceeded($form, $values);

        $formName = $this->getContentFormName();
        $values   = $values->$formName;

        if (! empty($values->images)) {
            foreach ($this->images as $image) {
                $this->filesService->delete($image);
            }

            $this->images = [];

            foreach ($values->images as $image) {
                assert($image instanceof FileUpload);
                if ($image->getError() === UPLOAD_ERR_OK) {
                    $this->images[] = $this->filesService->save($image, 'images', true, $image->name);
                }
            }
        }
    }

    public function convertToDto(): ContentDto
    {
        return new SlideshowContentDto($this->getComponentName(), $this->heading, $this->images);
    }
}
