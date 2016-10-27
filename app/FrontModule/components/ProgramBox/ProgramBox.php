<?php
/**
 * Date: 4.2.13
 * Time: 15:15
 * Author: Michal Májský
 */

namespace SRS\Components;
use SRS\Model\Acl\Resource;
use SRS\Model\Acl\Permission;

/**
 * Komponenta slouzici pro vyber programu na frontendu
 */
class ProgramBox extends \Nette\Application\UI\Control
{
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $template->translator = $this->presenter->translator;

        $template->backlink = $this->presenter->context->httpRequest->url->path;
        $dbsettings = $this->presenter->context->database->getRepository('\SRS\Model\Settings');

        $template->isAllowedLogInPrograms = ((bool)$dbsettings->get('is_allowed_log_in_programs')) &&
            \DateTime::createFromFormat("d.m.Y H:i", $dbsettings->get('log_in_programs_from')) <= new \DateTime('now') &&
            \DateTime::createFromFormat("d.m.Y H:i", $dbsettings->get('log_in_programs_to')) >= new \DateTime('now');

        $template->userHasPermission = $this->presenter->user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS);

        $template->render();
    }

}
