<?php

declare(strict_types=1);

namespace App\Service;

use Throwable;

final class EventDispatcher
{
    /** @var EventListenerInterface[] */
    private array $listeners = [];

    /** @var array<int, array{event: ReservationAllocatedEvent, error: string}> */
    private array $failedEvents = [];

    public function subscribe(EventListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
    }

    public function dispatch(ReservationAllocatedEvent $event): void
    {
        foreach ($this->listeners as $listener) {
            try {
                $listener->handle($event);
            } catch (Throwable $e) {
                // Bildirim hatası iade ve ayırma işlemini geri almaz; yeniden deneme için saklanır.
                $this->failedEvents[] = [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * @return array<int, array{event: ReservationAllocatedEvent, error: string}>
     */
    public function getFailedEvents(): array
    {
        return $this->failedEvents;
    }
}