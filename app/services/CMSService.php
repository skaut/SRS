<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\ContentDTO;
use App\Model\CMS\Page;
use App\Model\CMS\PageDTO;
use App\Model\Payment\PaymentRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use FioApi;
use Nette;

/**
 * Služba pro správu plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CMSService
{
    use Nette\SmartObject;

    /** @var RoleRepository */
    private $roleRepository;


    public function __construct(
        RoleRepository $roleRepository,
        SettingsRepository $settingsRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->roleRepository = $roleRepository;
    }

    /**
     * @param Page $page
     * @return PageDTO
     * @throws \App\Model\Page\PageException
     */
    public function convertPageToPageDTO(Page $page) : PageDTO
    {
        $name = $page->getName();
        $allowedRoles = $this->roleRepository->findRolesIds($page->getRoles());

        $mainContents = [];
        foreach ($page->getContents(Content::MAIN)->toArray() as $content) {
            $mainContents[] = $this->convertContentToContentDTO($content);
        }

        $sidebarContents = [];
        foreach ($page->getContents(Content::SIDEBAR)->toArray() as $content) {
            $sidebarContents[] = $this->convertContentToContentDTO($content);
        }

        return new PageDTO($name, $allowedRoles, $mainContents, $sidebarContents);
    }

    public function convertContentToContentDTO(Content $content) : ContentDTO
    {

    }
}
