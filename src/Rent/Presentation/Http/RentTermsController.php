<?php

namespace App\Rent\Presentation\Http;

use App\Auth\Domain\Entity\User;
use App\Property\Application\Service\PropertyAccessService;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Domain\Exception\PropertyAccessDeniedException;
use App\Property\Domain\Exception\PropertyNotFoundException;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyMemberRepository;
use App\Rent\Application\Dto\UpsertRentTermsRequestDto;
use App\Rent\Application\Service\UpsertRentTermsService;
use App\Rent\Domain\Entity\RentTerms;
use App\Rent\Infrastructure\Persistence\Doctrine\RentTermsRepository;
use App\Shared\Presentation\Http\ApiJsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/properties/{propertyId}', name: 'api_v1_rent_terms_', requirements: ['propertyId' => '[0-9a-fA-F-]{36}'])]
final class RentTermsController
{
    #[Route('/rent-terms', name: 'show_base', methods: ['GET'])]
    public function showBase(
        string $propertyId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        RentTermsRepository $rentTermsRepository
    ): Response {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        try {
            $property = $propertyAccessService->getAccessibleProperty($propertyId, $user);
        } catch (PropertyNotFoundException $exception) {
            return ApiJsonResponse::error('property_not_found', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (PropertyAccessDeniedException $exception) {
            return ApiJsonResponse::error('property_forbidden', $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        $terms = $rentTermsRepository->findBaseByProperty($property);

        return ApiJsonResponse::success([
            'rentTerms' => $terms instanceof RentTerms ? $this->rentTermsData($terms) : null,
        ]);
    }

    #[Route('/rent-terms', name: 'upsert_base', methods: ['PUT'])]
    public function upsertBase(
        string $propertyId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        UpsertRentTermsService $upsertRentTermsService
    ): Response {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        try {
            $property = $propertyAccessService->getAccessibleProperty($propertyId, $user);
            $propertyAccessService->assertLandlord($property, $user);
        } catch (PropertyNotFoundException $exception) {
            return ApiJsonResponse::error('property_not_found', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (PropertyAccessDeniedException $exception) {
            return ApiJsonResponse::error('property_forbidden', $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        try {
            /** @var UpsertRentTermsRequestDto $dto */
            $dto = $serializer->deserialize($request->getContent(), UpsertRentTermsRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error('invalid_payload', 'Некорректный формат данных запроса.', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error('validation_failed', 'Некорректные данные для условий аренды.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $terms = $upsertRentTermsService->handle($property, null, $dto);
        } catch (\InvalidArgumentException $exception) {
            return ApiJsonResponse::error('rent_terms_invalid', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiJsonResponse::success([
            'rentTerms' => $this->rentTermsData($terms),
        ]);
    }

    #[Route('/members/{memberId}/rent-terms', name: 'show_member', requirements: ['memberId' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function showMember(
        string $propertyId,
        string $memberId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        PropertyMemberRepository $propertyMemberRepository,
        RentTermsRepository $rentTermsRepository
    ): Response {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        try {
            $property = $propertyAccessService->getAccessibleProperty($propertyId, $user);
        } catch (PropertyNotFoundException $exception) {
            return ApiJsonResponse::error('property_not_found', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (PropertyAccessDeniedException $exception) {
            return ApiJsonResponse::error('property_forbidden', $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        $member = $propertyMemberRepository->findOneByIdAndProperty($memberId, $property);

        if (!$member instanceof PropertyMember) {
            return ApiJsonResponse::error('property_member_not_found', 'Участник объекта не найден.', Response::HTTP_NOT_FOUND);
        }

        $baseTerms = $rentTermsRepository->findBaseByProperty($property);
        $memberTerms = $rentTermsRepository->findMemberTerms($property, $member);
        $effectiveTerms = $memberTerms ?? $baseTerms;

        return ApiJsonResponse::success([
            'baseRentTerms' => $baseTerms instanceof RentTerms ? $this->rentTermsData($baseTerms) : null,
            'memberRentTerms' => $memberTerms instanceof RentTerms ? $this->rentTermsData($memberTerms) : null,
            'effectiveRentTerms' => $effectiveTerms instanceof RentTerms ? $this->rentTermsData($effectiveTerms) : null,
        ]);
    }

    #[Route('/members/{memberId}/rent-terms', name: 'upsert_member', requirements: ['memberId' => '[0-9a-fA-F-]{36}'], methods: ['PUT'])]
    public function upsertMember(
        string $propertyId,
        string $memberId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        PropertyMemberRepository $propertyMemberRepository,
        UpsertRentTermsService $upsertRentTermsService
    ): Response {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        try {
            $property = $propertyAccessService->getAccessibleProperty($propertyId, $user);
            $propertyAccessService->assertLandlord($property, $user);
        } catch (PropertyNotFoundException $exception) {
            return ApiJsonResponse::error('property_not_found', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (PropertyAccessDeniedException $exception) {
            return ApiJsonResponse::error('property_forbidden', $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        $member = $propertyMemberRepository->findOneByIdAndProperty($memberId, $property);

        if (!$member instanceof PropertyMember) {
            return ApiJsonResponse::error('property_member_not_found', 'Участник объекта не найден.', Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var UpsertRentTermsRequestDto $dto */
            $dto = $serializer->deserialize($request->getContent(), UpsertRentTermsRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error('invalid_payload', 'Некорректный формат данных запроса.', Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error('validation_failed', 'Некорректные данные для условий аренды.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $terms = $upsertRentTermsService->handle($property, $member, $dto);
        } catch (\InvalidArgumentException $exception) {
            return ApiJsonResponse::error('rent_terms_invalid', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiJsonResponse::success([
            'rentTerms' => $this->rentTermsData($terms),
        ]);
    }

    private function requireUser(Security $security): User|Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return ApiJsonResponse::error('unauthenticated', 'Пользователь не аутентифицирован.', Response::HTTP_UNAUTHORIZED);
        }

        return $user;
    }

    private function rentTermsData(RentTerms $rentTerms): array
    {
        return [
            'id' => $rentTerms->getId(),
            'propertyMemberId' => $rentTerms->getPropertyMember()?->getId(),
            'baseRentAmount' => $rentTerms->getBaseRentAmount(),
            'currency' => $rentTerms->getCurrency(),
            'billingDay' => $rentTerms->getBillingDay(),
            'startsAt' => $rentTerms->getStartsAt()->format('Y-m-d'),
            'endsAt' => $rentTerms->getEndsAt()?->format('Y-m-d'),
            'notes' => $rentTerms->getNotes(),
            'status' => $rentTerms->getStatus()->value,
            'createdAt' => $rentTerms->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $rentTerms->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
