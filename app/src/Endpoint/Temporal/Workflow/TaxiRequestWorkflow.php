<?php

declare(strict_types=1);

namespace App\Endpoint\Temporal\Workflow;

use App\Application\Temporal\ExceptionHelper;
use App\Endpoint\Temporal\Activity\NotificationServiceActivity;
use App\Endpoint\Temporal\Activity\PaymentServiceActivity;
use App\Endpoint\Temporal\Activity\TaxiRequestActivity;
use App\Endpoint\Temporal\Workflow\DTO\AcceptRequest;
use App\Endpoint\Temporal\Workflow\DTO\BlockFundsRequest;
use App\Endpoint\Temporal\Workflow\DTO\CancelRequest;
use App\Endpoint\Temporal\Workflow\DTO\CreateRequest;
use App\Endpoint\Temporal\Workflow\DTO\RefundRequest;
use App\Endpoint\Temporal\Workflow\DTO\StartTripRequest;
use Carbon\CarbonInterval;
use Payment\Exception\InsufficientFundsException;
use Taxi\Exception\TaxiRequestCancelledException;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Exception\Failure\ActivityFailure;
use Temporal\Exception\Failure\CanceledFailure;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Promise;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
final class TaxiRequestWorkflow
{
    private ActivityProxy|TaxiRequestActivity $taxiOrdering;
    private ActivityProxy|PaymentServiceActivity $paymentService;
    private ActivityProxy|NotificationServiceActivity $notificationService;

    private \Taxi\TaxiRequest $taxiRequest;
    private \Ramsey\Uuid\UuidInterface $transactionUuid;
    private ?AcceptRequest $acceptRequest = null;
    private ?CancelRequest $cancelRequest = null;
    private ?string $driverArrived = null;

    public function __construct()
    {
        $this->taxiOrdering = Workflow::newActivityStub(
            TaxiRequestActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minute())
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(3)
                        ->withInitialInterval(CarbonInterval::seconds(5)),
                ),
        );

        $this->paymentService = Workflow::newActivityStub(
            PaymentServiceActivity::class,
            ActivityOptions::new()
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(3)
                        ->withInitialInterval(CarbonInterval::seconds(5)),
                )
                ->withStartToCloseTimeout(CarbonInterval::minutes(5)),
        );

        $this->notificationService = Workflow::newActivityStub(
            NotificationServiceActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(2)
                        ->withInitialInterval(CarbonInterval::seconds(5)),
                ),
        );
    }

    #[WorkflowMethod]
    public function createRequest(CreateRequest $request)
    {
        // 1. Создать заявку на такси
        $this->taxiRequest = yield $this->taxiOrdering->requestTaxi($request);

        // 2. Заблокировать стоимость поездки на счете клиента
        $operationUuid = yield Workflow::uuid4();


        try {
            $this->transactionUuid = yield $this->paymentService->blockFunds(
                new BlockFundsRequest(
                    $operationUuid,
                    $this->taxiRequest->userUuid,
                    $this->taxiRequest->estimatedPrice,
                ),
            );
        } catch (ActivityFailure $e) {
            // - Если что-то пойдет не так, то нужно вернуть деньги
            $message = 'Что-то пошло не так. Пожалуйста, попробуйте позже';

            // - Если у клиента недостаточно средств, то поехать не получится и нужно отменить заявку
            if (ExceptionHelper::findException($e, InsufficientFundsException::class)) {
                // Уведомить клиента о недостаточности средств
                yield $this->notificationService->insufficientFunds($this->taxiRequest->uuid);

                $message = 'Недостаточно средств на счете';
            }

            yield $this->taxiOrdering->cancelRequest(
                $this->taxiRequest->uuid,
                $message,
            );

            throw new CanceledFailure($message);
        }

        // 3. Уведомить водителей о новой заявке
        yield $this->notificationService->newRequest($this->taxiRequest->uuid);

        $assignmentAttempts = 0;
        $maxAssignmentAttempts = 5;

        try {
            https://temporal.io

            // 4. Ждем максимум 5 минут, чтобы водитель принял заявку
            $isCondition = yield Workflow::awaitWithTimeout(
                CarbonInterval::minutes(1),
                fn() => $this->acceptRequest !== null,
                fn() => $this->cancelRequest !== null,
            );

            if ($this->cancelRequest !== null) {
                throw new TaxiRequestCancelledException($this->cancelRequest->reason);
            }

            // - Если 1 минуту ничего не происходит, то показываем клиенту, что все еще идет поиск

            if (!$isCondition) {
                $assignmentAttempts++;

                if ($assignmentAttempts < $maxAssignmentAttempts) {
                    // Уведомить клиента, что мы еще пытаемся найти водителя
                    yield $this->notificationService->stillSearching($request->requestUuid);

                    goto https;//temporal.io
                }

                // Уведомляем клиента о том, что никто не принял заявку
                yield $this->notificationService->noDriversAvailable($this->taxiRequest->uuid);
                throw new TaxiRequestCancelledException('Нет свободных водителей');
            }

            // 5. Если водитель принял заявку, сначала валидируем водителя (класс, рейтинг и т. д.)
            $result = yield $this->taxiOrdering->validateDriver(
                $this->taxiRequest->uuid,
                $this->acceptRequest,
            );

            //   - Если водитель не подходит, ждем другого водителя
            if (!$result->isMatched) {
                // Уведомляем водителя о том, что он не подходит
                yield $this->notificationService->driverMatchFailed(
                    taxiRequestUuid: $this->taxiRequest->uuid,
                    driverUuid: $this->acceptRequest->driverUuid,
                    reason: $result->reason,
                );

                $this->acceptRequest = null;

                goto https;//temporal.io
            }

            // 6. Назначаем водителя на заявку
            $this->taxiRequest = yield $this->taxiOrdering->assignDriver(
                $this->taxiRequest->uuid,
                $this->acceptRequest->driverUuid,
            );

            // 7. Уведомляем клиента, что водитель принял заявку
            yield $this->notificationService->driverAccepted(
                $this->taxiRequest->userUuid,
                $this->acceptRequest->driverUuid,
            );

            // 8. Ждем когда водитель доедет до клиента
            $isArrived = yield Workflow::awaitWithTimeout(
                CarbonInterval::minutes(30),
                fn() => $this->driverArrived,
                fn() => $this->cancelRequest !== null,
            );

            if ($this->cancelRequest !== null) {
                throw new TaxiRequestCancelledException($this->cancelRequest->reason);
            }

            if (!$isArrived) {
                throw new TaxiRequestCancelledException('Водитель не приехал вовремя');
            }
        } catch (TaxiRequestCancelledException $e) {
            yield from $this->handleCancelRequest(new CancelRequest($e->getMessage()));
        }

        yield $this->notificationService->driverArrived($this->taxiRequest->uuid, $this->driverArrived);

        // 9. Если водитель принял заявку, можем начать поездку
        $trip = yield Workflow::newChildWorkflowStub(
            class: TripWorkflow::class,
            options: Workflow\ChildWorkflowOptions::new()
                ->withWorkflowId(workflowId: $this->taxiRequest->uuid . '-trip'),
        )->start(new StartTripRequest($this->taxiRequest->uuid));

        if ($trip === null) {
            yield from $this->handleCancelRequest(new CancelRequest('Водитель не вышел на связь'));
        }

        yield Promise::all([
            // 10. По завершению поездки списываем деньги с клиента
            $this->paymentService->commitFunds($this->transactionUuid),
            // 11. Уведомляем клиента о завершении поездки и предлагаем оценить друг водителя, а водителю - клиента
            $this->notificationService->tripFinished($this->taxiRequest->uuid)
        ]);
    }

    #[Workflow\SignalMethod]
    public function acceptRequest(AcceptRequest $request): void
    {
        $this->acceptRequest = $request;
    }

    #[Workflow\SignalMethod]
    public function cancelRequest(CancelRequest $request): void
    {
        $this->cancelRequest = $request;
    }

    #[Workflow\SignalMethod]
    public function driverArrived(string $comment): void
    {
        $this->driverArrived = $comment;
    }

    private function handleCancelRequest(CancelRequest $request): \Generator
    {
        yield $this->paymentService->refundFunds(new RefundRequest(transactionUuid: $this->transactionUuid));

        yield $this->notificationService->userCanceled($this->taxiRequest->uuid, $request->reason);

        yield $this->taxiOrdering->cancelRequest(
            $this->taxiRequest->uuid,
            $request->reason,
        );

        throw new CanceledFailure($request->reason);
    }
}
