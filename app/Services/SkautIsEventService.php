<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Nette;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use stdClass;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Služba pro správu skautIS akce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class SkautIsEventService
{
    use Nette\SmartObject;

    protected Skautis $skautIs;

    public function __construct(Skautis $skautIs)
    {
        $this->skautIs = $skautIs;
    }

    /**
     * Vrací true, pokud je akce neuzavřená.
     */
    abstract public function isEventDraft(int $eventId): bool;

    /**
     * Vloží účastníky do skautIS.
     *
     * @param Collection<User> $users
     * @param bool             $accept Přijetí účastníků (pouze u vzdělávacích akcí).
     */
    abstract public function insertParticipants(int $eventId, Collection $users, bool $accept = false): bool;

    /**
     * Vrací údaje o akci.
     */
    abstract protected function getEventDetail(int $eventId): stdClass;

    /**
     * Vrací seznam neuzavřených akcí.
     *
     * @return stdClass[]
     */
    abstract protected function getDraftEvents(): array;

    /**
     * Vrací název akce.
     */
    public function getEventDisplayName(int $eventId): string
    {
        return $this->getEventDetail($eventId)->DisplayName;
    }

    /**
     * Vrací seznam neuzavřených akcí pro select.
     *
     * @return string[]
     */
    public function getEventsOptions(): array
    {
        $options = [];
        try {
            foreach ($this->getDraftEvents() as $event) {
                $options[$event->ID] = $event->DisplayName;
            }
        } catch (WsdlException $ex) {
            Debugger::log($ex, ILogger::WARNING);
        }

        return $options;
    }
}
