<?php

namespace App\Tests\Service;

use App\Entity\Animal;
use App\Entity\MedicalEvent;
use App\Entity\Reminder;
use App\Entity\User;
use App\Repository\MedicalEventRepository;
use App\Service\ReminderScheduler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ReminderSchedulerTest extends TestCase
{
    private ReminderScheduler $scheduler;

    // Rejouée avant chaque test, sur une instance neuve de la classe : seul ce
    // qui vaut pour tous les tests a sa place ici, jamais l'état d'un scénario.
    protected function setUp(): void
    {
        // Deux stubs inertes : sync() ne touche ni au repository ni à
        // l'EntityManager, ils ne sont là que pour le constructeur.
        $this->scheduler = new ReminderScheduler(
            $this->createStub(MedicalEventRepository::class),
            $this->createStub(EntityManagerInterface::class),
        );
    }

    public function testCreatesOneReminderPerOffset(): void
    {
        // --- Arrange : on met en place l'état de départ ---
        $event = $this->makeEvent('2026-09-20');

        // --- Act : l'action qu'on teste, une seule ---
        $report = $this->scheduler->sync($event);

        // --- Assert : ce qu'on affirme sur le résultat ---
        $this->assertSame(2, $report['created']);
        $this->assertCount(2, $event->getReminders());
    }

    public function testSecondSyncChangesNothing(): void
    {
        $event = $this->makeEvent('2026-09-20');
        $this->scheduler->sync($event);

        $report = $this->scheduler->sync($event);

        $this->assertSame(['created' => 0, 'removed' => 0], $report);
        $this->assertCount(2, $event->getReminders());
    }

    public function testDoneEventLosesItsReminders(): void
    {
        $event = $this->makeEvent('2026-09-20');
        $this->scheduler->sync($event);
        $event->setIsDone(true);

        $report = $this->scheduler->sync($event);

        $this->assertSame(['created' => 0, 'removed' => 2], $report);
        $this->assertCount(0, $event->getReminders());
    }

    public function testDateChangeReplacesReminders(): void
    {
        $event = $this->makeEvent('2026-09-20');
        $this->scheduler->sync($event);
        $event->setDate(new \DateTime('2026-09-27'));

        $report = $this->scheduler->sync($event);

        $this->assertSame(['created' => 2, 'removed' => 2], $report);

        // Les compteurs seuls laisseraient passer deux rappels recréés à
        // l'ancienne date : on vérifie aussi où ils tombent.
        $this->assertSame(
            ['2026-09-20 08:00', '2026-09-26 08:00'],
            $this->scheduledDates($event),
        );
    }

    public function testSentReminderSurvivesDateChange(): void
    {
        $event = $this->makeEvent('2026-09-20');
        $this->scheduler->sync($event);
        $this->markSent($event, '2026-09-13 08:00');
        $event->setDate(new \DateTime('2026-09-27'));

        $report = $this->scheduler->sync($event);

        // Seul l'ancien J-1, encore en attente, disparaît.
        $this->assertSame(['created' => 2, 'removed' => 1], $report);
        $this->assertSame(
            ['2026-09-13 08:00', '2026-09-20 08:00', '2026-09-26 08:00'],
            $this->scheduledDates($event),
        );
    }

    public function testSentReminderIsNotRecreated(): void
    {
        $event = $this->makeEvent('2026-09-20');
        $this->scheduler->sync($event);
        $this->markSent($event, '2026-09-13 08:00');

        $report = $this->scheduler->sync($event);

        $this->assertSame(['created' => 0, 'removed' => 0], $report);
        $this->assertCount(2, $event->getReminders());
    }

    private function makeEvent(string $date): MedicalEvent
    {
        return (new MedicalEvent())
            ->setDate(new \DateTime($date))
            ->setIsDone(false)
            ->setAnimal((new Animal())->setOwner(new User()));
    }

    /**
     * Simule le passage de ReminderSender sur le rappel prévu à cette date.
     */
    private function markSent(MedicalEvent $event, string $scheduledAt): void
    {
        foreach ($event->getReminders() as $reminder) {
            if ($reminder->getScheduledAt()->format('Y-m-d H:i') === $scheduledAt) {
                $reminder->setSentAt(new \DateTimeImmutable($scheduledAt));

                return;
            }
        }

        // Sans ça, une date mal recopiée laisserait le test tourner sur un
        // état qui n'est pas celui qu'il prétend décrire.
        $this->fail(sprintf('Aucun rappel prévu le %s.', $scheduledAt));
    }

    /**
     * Dates d'envoi triées : l'ordre de la collection n'est pas une promesse
     * du service, le test ne doit pas en dépendre.
     *
     * @return string[]
     */
    private function scheduledDates(MedicalEvent $event): array
    {
        $dates = $event->getReminders()
            ->map(fn (Reminder $reminder): string => $reminder->getScheduledAt()->format('Y-m-d H:i'))
            ->toArray();
        sort($dates);

        return $dates;
    }
}
