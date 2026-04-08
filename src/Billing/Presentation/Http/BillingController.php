<?php

namespace App\Billing\Presentation\Http;

use App\Auth\Domain\Entity\User;
use App\Billing\Application\Dto\CreateBillingParameterRequestDto;
use App\Billing\Application\Dto\CreateMeterReadingRequestDto;
use App\Billing\Application\Dto\CreateMeterRequestDto;
use App\Billing\Application\Dto\CreateTariffPeriodRequestDto;
use App\Billing\Application\Service\CreateBillingParameterService;
use App\Billing\Application\Service\CreateMeterService;
use App\Billing\Application\Service\CreateTariffPeriodService;
use App\Billing\Application\Service\RecordMeterReadingService;
use App\Billing\Domain\Entity\BillingParameter;
use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Entity\MeterReading;
use App\Billing\Domain\Entity\TariffPeriod;
use App\Billing\Infrastructure\Persistence\Doctrine\BillingParameterRepository;
use App\Billing\Infrastructure\Persistence\Doctrine\MeterReadingRepository;
use App\Billing\Infrastructure\Persistence\Doctrine\MeterRepository;
use App\Billing\Infrastructure\Persistence\Doctrine\TariffPeriodRepository;
use App\Property\Application\Service\PropertyAccessService;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Exception\PropertyAccessDeniedException;
use App\Property\Domain\Exception\PropertyNotFoundException;
use App\Shared\Presentation\Http\ApiJsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/properties/{propertyId}', name: 'api_v1_billing_', requirements: ['propertyId' => '[0-9a-fA-F-]{36}'])]
final class BillingController
{
    #[Route('/meters', name: 'meters_list', methods: ['GET'])]
    public function meters(
        string $propertyId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        MeterRepository $meterRepository
    ): Response {
        $property = $this->requireAccessibleProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        return ApiJsonResponse::success([
            'items' => array_map(fn (Meter $meter): array => $this->meterData($meter), $meterRepository->findByProperty($property)),
        ]);
    }

    #[Route('/meters', name: 'meters_create', methods: ['POST'])]
    public function createMeter(
        string $propertyId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        CreateMeterService $createMeterService
    ): Response {
        $property = $this->requireLandlordProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        try {
            /** @var CreateMeterRequestDto $dto */
            $dto = $serializer->deserialize($request->getContent(), CreateMeterRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error('invalid_payload', 'Некорректный формат данных запроса.', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error('validation_failed', 'Некорректные данные для счетчика.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $meter = $createMeterService->handle($property, $dto);
        } catch (\InvalidArgumentException $exception) {
            return ApiJsonResponse::error('meter_invalid', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiJsonResponse::success([
            'meter' => $this->meterData($meter),
        ], Response::HTTP_CREATED);
    }

    #[Route('/meters/{meterId}/initial-reading', name: 'meters_initial_reading', requirements: ['meterId' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function initialReading(
        string $propertyId,
        string $meterId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        MeterRepository $meterRepository,
        RecordMeterReadingService $recordMeterReadingService
    ): Response {
        $property = $this->requireLandlordProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        $meter = $meterRepository->findOneByIdAndProperty($meterId, $property);

        if (!$meter instanceof Meter) {
            return ApiJsonResponse::error('meter_not_found', 'Счетчик не найден.', Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var CreateMeterReadingRequestDto $dto */
            $dto = $serializer->deserialize($request->getContent(), CreateMeterReadingRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error('invalid_payload', 'Некорректный формат данных запроса.', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error('validation_failed', 'Некорректные данные для стартовых показаний.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $reading = $recordMeterReadingService->createInitial($meter, $user, $dto);
        } catch (\InvalidArgumentException $exception) {
            return ApiJsonResponse::error('meter_reading_invalid', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiJsonResponse::success([
            'reading' => $this->readingData($reading),
        ], Response::HTTP_CREATED);
    }

    #[Route('/meters/{meterId}/readings', name: 'meters_readings_list', requirements: ['meterId' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function readings(
        string $propertyId,
        string $meterId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        MeterRepository $meterRepository,
        MeterReadingRepository $meterReadingRepository
    ): Response {
        $property = $this->requireAccessibleProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        $meter = $meterRepository->findOneByIdAndProperty($meterId, $property);

        if (!$meter instanceof Meter) {
            return ApiJsonResponse::error('meter_not_found', 'Счетчик не найден.', Response::HTTP_NOT_FOUND);
        }

        return ApiJsonResponse::success([
            'items' => array_map(fn (MeterReading $reading): array => $this->readingData($reading), $meterReadingRepository->findByMeter($meter)),
        ]);
    }

    #[Route('/meters/{meterId}/readings', name: 'meters_readings_create', requirements: ['meterId' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function createReading(
        string $propertyId,
        string $meterId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        MeterRepository $meterRepository,
        RecordMeterReadingService $recordMeterReadingService
    ): Response {
        $property = $this->requireAccessibleProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        $meter = $meterRepository->findOneByIdAndProperty($meterId, $property);

        if (!$meter instanceof Meter) {
            return ApiJsonResponse::error('meter_not_found', 'Счетчик не найден.', Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var CreateMeterReadingRequestDto $dto */
            $dto = $serializer->deserialize($request->getContent(), CreateMeterReadingRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error('invalid_payload', 'Некорректный формат данных запроса.', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error('validation_failed', 'Некорректные данные для ежемесячных показаний.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $reading = $recordMeterReadingService->createMonthly($meter, $user, $dto);
        } catch (\InvalidArgumentException $exception) {
            return ApiJsonResponse::error('meter_reading_invalid', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiJsonResponse::success([
            'reading' => $this->readingData($reading),
        ], Response::HTTP_CREATED);
    }

    #[Route('/billing/parameters', name: 'parameters_list', methods: ['GET'])]
    public function parameters(
        string $propertyId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        BillingParameterRepository $billingParameterRepository
    ): Response {
        $property = $this->requireAccessibleProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        return ApiJsonResponse::success([
            'items' => array_map(
                fn (BillingParameter $parameter): array => $this->parameterData($parameter),
                $billingParameterRepository->findByProperty($property)
            ),
        ]);
    }

    #[Route('/billing/parameters', name: 'parameters_create', methods: ['POST'])]
    public function createParameter(
        string $propertyId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        MeterRepository $meterRepository,
        CreateBillingParameterService $createBillingParameterService
    ): Response {
        $property = $this->requireLandlordProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        try {
            /** @var CreateBillingParameterRequestDto $dto */
            $dto = $serializer->deserialize($request->getContent(), CreateBillingParameterRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error('invalid_payload', 'Некорректный формат данных запроса.', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error('validation_failed', 'Некорректные данные для параметра начисления.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $meter = null;

        if ($dto->meterId !== null) {
            $meter = $meterRepository->findOneByIdAndProperty($dto->meterId, $property);

            if (!$meter instanceof Meter) {
                return ApiJsonResponse::error('meter_not_found', 'Связанный счетчик не найден.', Response::HTTP_NOT_FOUND);
            }
        }

        try {
            $parameter = $createBillingParameterService->handle($property, $meter, $dto);
        } catch (\InvalidArgumentException $exception) {
            return ApiJsonResponse::error('billing_parameter_invalid', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiJsonResponse::success([
            'parameter' => $this->parameterData($parameter),
        ], Response::HTTP_CREATED);
    }

    #[Route('/billing/parameters/{parameterId}/tariffs', name: 'tariffs_list', requirements: ['parameterId' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function tariffs(
        string $propertyId,
        string $parameterId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        BillingParameterRepository $billingParameterRepository,
        TariffPeriodRepository $tariffPeriodRepository
    ): Response {
        $property = $this->requireAccessibleProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        $parameter = $billingParameterRepository->findOneByIdAndProperty($parameterId, $property);

        if (!$parameter instanceof BillingParameter) {
            return ApiJsonResponse::error('billing_parameter_not_found', 'Параметр начисления не найден.', Response::HTTP_NOT_FOUND);
        }

        return ApiJsonResponse::success([
            'items' => array_map(
                fn (TariffPeriod $tariff): array => $this->tariffData($tariff),
                $tariffPeriodRepository->findByParameter($parameter)
            ),
        ]);
    }

    #[Route('/billing/parameters/{parameterId}/tariffs', name: 'tariffs_create', requirements: ['parameterId' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function createTariff(
        string $propertyId,
        string $parameterId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        BillingParameterRepository $billingParameterRepository,
        CreateTariffPeriodService $createTariffPeriodService
    ): Response {
        $property = $this->requireLandlordProperty($propertyId, $security, $propertyAccessService);

        if (!$property instanceof Property) {
            return $property;
        }

        $parameter = $billingParameterRepository->findOneByIdAndProperty($parameterId, $property);

        if (!$parameter instanceof BillingParameter) {
            return ApiJsonResponse::error('billing_parameter_not_found', 'Параметр начисления не найден.', Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var CreateTariffPeriodRequestDto $dto */
            $dto = $serializer->deserialize($request->getContent(), CreateTariffPeriodRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error('invalid_payload', 'Некорректный формат данных запроса.', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error('validation_failed', 'Некорректные данные для тарифа.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tariff = $createTariffPeriodService->handle($parameter, $dto);
        } catch (\InvalidArgumentException $exception) {
            return ApiJsonResponse::error('tariff_invalid', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiJsonResponse::success([
            'tariff' => $this->tariffData($tariff),
        ], Response::HTTP_CREATED);
    }

    private function requireAccessibleProperty(
        string $propertyId,
        Security $security,
        PropertyAccessService $propertyAccessService
    ): Property|Response {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        try {
            return $propertyAccessService->getAccessibleProperty($propertyId, $user);
        } catch (PropertyNotFoundException $exception) {
            return ApiJsonResponse::error('property_not_found', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (PropertyAccessDeniedException $exception) {
            return ApiJsonResponse::error('property_forbidden', $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }
    }

    private function requireLandlordProperty(
        string $propertyId,
        Security $security,
        PropertyAccessService $propertyAccessService
    ): Property|Response {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        try {
            $property = $propertyAccessService->getAccessibleProperty($propertyId, $user);
            $propertyAccessService->assertLandlord($property, $user);

            return $property;
        } catch (PropertyNotFoundException $exception) {
            return ApiJsonResponse::error('property_not_found', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (PropertyAccessDeniedException $exception) {
            return ApiJsonResponse::error('property_forbidden', $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }
    }

    private function requireUser(Security $security): User|Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return ApiJsonResponse::error('unauthenticated', 'Пользователь не аутентифицирован.', Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }

    private function meterData(Meter $meter): array
    {
        return [
            'id' => $meter->getId(),
            'code' => $meter->getCode(),
            'title' => $meter->getTitle(),
            'unit' => $meter->getUnit(),
            'isActive' => $meter->isActive(),
            'createdAt' => $meter->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $meter->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function readingData(MeterReading $reading): array
    {
        return [
            'id' => $reading->getId(),
            'type' => $reading->getType()->value,
            'billingYear' => $reading->getBillingYear(),
            'billingMonth' => $reading->getBillingMonth(),
            'value' => $reading->getValue(),
            'comment' => $reading->getComment(),
            'recordedByUserId' => $reading->getRecordedByUser()->getId(),
            'recordedAt' => $reading->getRecordedAt()->format(DATE_ATOM),
        ];
    }

    private function parameterData(BillingParameter $parameter): array
    {
        return [
            'id' => $parameter->getId(),
            'code' => $parameter->getCode(),
            'title' => $parameter->getTitle(),
            'category' => $parameter->getCategory()->value,
            'sourceType' => $parameter->getSourceType()->value,
            'meterId' => $parameter->getMeter()?->getId(),
            'unit' => $parameter->getUnit(),
            'isActive' => $parameter->isActive(),
            'createdAt' => $parameter->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $parameter->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function tariffData(TariffPeriod $tariff): array
    {
        return [
            'id' => $tariff->getId(),
            'pricingType' => $tariff->getPricingType()->value,
            'price' => $tariff->getPrice(),
            'currency' => $tariff->getCurrency(),
            'effectiveFrom' => $tariff->getEffectiveFrom()->format('Y-m-d'),
            'effectiveTo' => $tariff->getEffectiveTo()?->format('Y-m-d'),
            'createdAt' => $tariff->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
