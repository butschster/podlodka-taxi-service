<?php

declare(strict_types=1);

namespace App\Application\Bootloader;

use App\Infrastructure\CycleORM\Repository\DriverLocationRepository;
use App\Infrastructure\CycleORM\Repository\DriverRepository;
use App\Infrastructure\CycleORM\Repository\RatingRepository;
use App\Infrastructure\CycleORM\Repository\StatusRepository;
use App\Infrastructure\CycleORM\Repository\TaxiRequestRepository;
use App\Infrastructure\CycleORM\Repository\TripRepository;
use App\Infrastructure\CycleORM\Repository\UserRepository;
use App\Infrastructure\CycleORM\Repository\VehicleRepository;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;
use Spiral\Boot\Bootloader\Bootloader;
use Taxi\Driver;
use Taxi\Rating;
use Taxi\Repository\DriverLocationRepositoryInterface;
use Taxi\Repository\DriverRepositoryInterface;
use Taxi\Repository\RatingRepositoryInterface;
use Taxi\Repository\StatusRepositoryInterface;
use Taxi\Repository\TaxiRequestRepositoryInterface;
use Taxi\Repository\TripRepositoryInterface;
use Taxi\Repository\UserRepositoryInterface;
use Taxi\Repository\VehicleRepositoryInterface;
use Taxi\TaxiRequest;
use Taxi\Trip;
use Taxi\User;
use Taxi\Vehicle;

final class PersistenceBootloader extends Bootloader
{
    public function defineSingletons(): array
    {
        return [
            DriverLocationRepositoryInterface::class => DriverLocationRepository::class,

            // Repositories
            TripRepositoryInterface::class => static fn(
                ORMInterface $orm,
                EntityManagerInterface $em,
            ) => new TripRepository(new Select($orm, Trip::class), $em),

            TaxiRequestRepositoryInterface::class => static fn(
                ORMInterface $orm,
                EntityManagerInterface $em,
            ) => new TaxiRequestRepository(new Select($orm, TaxiRequest::class), $em),

            VehicleRepositoryInterface::class => static fn(
                ORMInterface $orm,
                EntityManagerInterface $em,
            ) => new VehicleRepository(new Select($orm, Vehicle::class), $em),

            DriverRepositoryInterface::class => static fn(
                ORMInterface $orm,
                EntityManagerInterface $em,
            ) => new DriverRepository(new Select($orm, Driver::class), $em),

            UserRepositoryInterface::class => static fn(
                ORMInterface $orm,
                EntityManagerInterface $em,
            ) => new UserRepository(new Select($orm, User::class), $em),

            RatingRepositoryInterface::class => static fn(
                ORMInterface $orm,
                EntityManagerInterface $em,
            ) => new RatingRepository(new Select($orm, Rating::class), $em),

            StatusRepositoryInterface::class => static fn(
                ORMInterface $orm,
                EntityManagerInterface $em,
            ) => new StatusRepository(new Select($orm, TaxiRequest\Status::class), $em),
        ];
    }
}
