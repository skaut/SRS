<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Enums\TroopApplicationState;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\ConfirmTroop;
use App\Model\User\Queries\TroopByLeaderQuery;
use App\Model\User\Queries\UserByIdQuery;
use App\Model\User\Troop;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Komponenta s formulářem pro potvrzení registrace oddílu.
 */
class TroopConfirmForm extends UI\Control
{
    private Troop $troop;

    /**
     * Událost při úspěšném odeslání formuláře.
     *
     * @var callable[]
     */
    public array $onSave = [];

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private QueryBus $queryBus,
        private CommandBus $commandBus,
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/troop_confirm_form.latte');

        $this->resolveTroop();

        $this->template->troop     = $this->troop;
        $this->template->agreement = $this->queryBus->handle(new SettingStringValueQuery(Settings::APPLICATION_AGREEMENT));

        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm(): Form
    {
        $this->resolveTroop();

        $form = $this->baseFormFactory->create();

        $pairedTroopCodeText = $form->addText('pairedTroopCode')
            ->setDefaultValue($this->troop->getPairedTroopCode());

        $agreementCheckbox = $form->addCheckbox('agreement', 'Souhlasím s podmínkami akce.')
            ->addRule(Form::FILLED, 'Musíš souhlasit s podmínkami akce.');

        $submit = $form->addSubmit('submit', 'Závazně registrovat');

        if ($this->troop->getState() !== TroopApplicationState::DRAFT) {
            $pairedTroopCodeText->setHtmlAttribute('readonly');
            $agreementCheckbox->setDisabled();
            $submit->setDisabled();
        }

        $form->setAction($this->getPresenter()->link('this'));

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $this->commandBus->handle(new ConfirmTroop($this->troop->getId(), $values->pairedTroopCode));

        $this->onSave();
    }

    private function resolveTroop(): void
    {
        $user        = $this->queryBus->handle(new UserByIdQuery($this->presenter->user->getId()));
        $this->troop = $this->queryBus->handle(new TroopByLeaderQuery($user->getId()));
    }
}
