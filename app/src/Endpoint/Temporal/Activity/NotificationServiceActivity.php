<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Activity;

use Ramsey\Uuid\UuidInterface;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: "notification-request.")]
final readonly class NotificationServiceActivity
{
    #[ActivityMethod]
    public function newRequest(UuidInterface $taxiRequestUuid): void
    {
        dump('Отправка push-уведомления водителям о новом заказе');
    }

    #[ActivityMethod]
    public function stillSearching(UuidInterface $taxiRequestUuid): void
    {
        dump('Мы все еще ищем водителя. Пожалуйста, подождите');
    }

    #[ActivityMethod]
    public function noDriverAvailable(UuidInterface $taxiRequestUuid): void
    {
        dump('Извините, в данный момент нет доступных водителей');
    }

    #[ActivityMethod]
    public function driverMatchFailed(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid, string $reason): void
    {
        dump(\sprintf('Водитель %s не был выбран. Причина: %s', $driverUuid, $reason));
    }

    #[ActivityMethod]
    public function driverAccepted(UuidInterface $taxiRequestUuid, UuidInterface $driverUuid): void
    {
        dump('Водитель принял ваш заказ');
    }

    #[ActivityMethod]
    public function userCanceled(UuidInterface $taxiRequestUuid, string $reason): void
    {
        dump(\sprintf('Заказ был отменен. Причина: %s', $reason));
    }

    #[ActivityMethod]
    public function driverNotResponding(UuidInterface $taxiRequestUuid): void
    {
        dump('Водитель не выходит на связь и от него не приходит ответ. Пожалуйста, свяжитесь с водителем');
    }

    #[ActivityMethod]
    public function tripFinished(UuidInterface $taxiRequestUuid): void
    {
        dump('Поездка завершена. Спасибо за использование нашего сервиса. Пожалуйста, оцените поездку');
    }

    #[ActivityMethod]
    public function driverArrived(UuidInterface $taxiRequestUuid, string $comment): void
    {
        dump(\sprintf('Водитель прибыл и ожидает вас на месте. Пожалуйста, подойдите к машине. %s', $comment));
    }

    #[ActivityMethod]
    public function insufficientFunds(UuidInterface $taxiRequestUuid): void
    {
        dump('Недостаточно средств на счете. Пожалуйста, пополните баланс');
    }
}
