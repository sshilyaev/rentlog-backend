<?php

namespace App\Property\Presentation\Http;

use App\Auth\Domain\Entity\User;
use App\Property\Application\Dto\CreateInvitationRequestDto;
use App\Property\Application\Dto\CreatePropertyMemberRequestDto;
use App\Property\Application\Dto\CreatePropertyRequestDto;
use App\Property\Application\Service\CreateInvitationService;
use App\Property\Application\Service\CreatePropertyMemberService;
use App\Property\Application\Service\CreatePropertyService;
use App\Property\Application\Service\PropertyAccessService;
use App\Property\Application\Service\PropertyTypeRegistry;
use App\Property\Domain\Entity\Invitation;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Domain\Exception\PropertyAccessDeniedException;
use App\Property\Domain\Exception\PropertyMemberAlreadyExistsException;
use App\Property\Domain\Exception\PropertyNotFoundException;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyMemberRepository;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;
use App\Shared\Presentation\Http\ApiJsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/properties', name: 'api_v1_properties_')]
final class PropertyController
{
    #[Route('/types', name: 'types', methods: ['GET'])]
    public function types(PropertyTypeRegistry $registry): Response
    {
        return ApiJsonResponse::success([
            'items' => $registry->all(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        CreatePropertyService $createPropertyService
    ): Response {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        try {
            
            $dto = $serializer->deserialize($request->getContent(), CreatePropertyRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error(
                'invalid_payload',
                'Некорректный формат данных запроса.',
                Response::HTTP_BAD_REQUEST
            );
        }
        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error(
                'validation_failed',
                'Некорректные данные для создания объекта.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $property = $createPropertyService->handle($user, $dto);

        return ApiJsonResponse::success([
            'property' => $this->propertyData($property),
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Security $security, PropertyRepository $propertyRepository): Response
    {
        $user = $this->requireUser($security);

        if (!$user instanceof User) {
            return $user;
        }

        $properties = array_map(
            fn (Property $property): array => $this->propertyData($property),
            $propertyRepository->findByUser($user)
        );

        return ApiJsonResponse::success([
            'items' => $properties,
        ]);
    }

    #[Route('/{propertyId}', name: 'show', requirements: ['propertyId' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function show(
        string $propertyId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        PropertyMemberRepository $propertyMemberRepository
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

        $currentMember = $propertyMemberRepository->findOneByPropertyAndUser($property, $user);

        return ApiJsonResponse::success([
            'property' => $this->propertyData($property),
            'currentMember' => $currentMember instanceof PropertyMember ? $this->memberData($currentMember) : null,
        ]);
    }

    #[Route('/{propertyId}/members', name: 'members_list', requirements: ['propertyId' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function members(
        string $propertyId,
        Security $security,
        PropertyAccessService $propertyAccessService,
        PropertyMemberRepository $propertyMemberRepository
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

        $members = array_map(
            fn (PropertyMember $member): array => $this->memberData($member),
            $propertyMemberRepository->findByProperty($property)
        );

        return ApiJsonResponse::success([
            'items' => $members,
        ]);
    }

    #[Route('/{propertyId}/members', name: 'members_create', requirements: ['propertyId' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function createMember(
        string $propertyId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        CreatePropertyMemberService $createPropertyMemberService
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
            
            $dto = $serializer->deserialize($request->getContent(), CreatePropertyMemberRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            return ApiJsonResponse::error(
                'invalid_payload',
                'Некорректный формат данных запроса.',
                Response::HTTP_BAD_REQUEST
            );
        }
        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error(
                'validation_failed',
                'Некорректные данные для участника объекта.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $member = $createPropertyMemberService->handle($property, $dto);
        } catch (PropertyMemberAlreadyExistsException $exception) {
            return ApiJsonResponse::error(
                'property_member_already_exists',
                $exception->getMessage(),
                Response::HTTP_CONFLICT
            );
        }

        return ApiJsonResponse::success([
            'member' => $this->memberData($member),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{propertyId}/members/{memberId}/invite', name: 'members_invite', requirements: ['propertyId' => '[0-9a-fA-F-]{36}', 'memberId' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function inviteMember(
        string $propertyId,
        string $memberId,
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyAccessService $propertyAccessService,
        PropertyMemberRepository $propertyMemberRepository,
        CreateInvitationService $createInvitationService
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
            return ApiJsonResponse::error(
                'property_member_not_found',
                'Участник объекта не найден.',
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            
            $dto = $serializer->deserialize($request->getContent(), CreateInvitationRequestDto::class, 'json');
        } catch (SerializerExceptionInterface) {
            $dto = new CreateInvitationRequestDto();
        }

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return ApiJsonResponse::error(
                'validation_failed',
                'Некорректные данные для приглашения.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $invitation = $createInvitationService->handle($property, $member, $user, $dto);

        return ApiJsonResponse::success([
            'invitation' => $this->invitationData($invitation),
        ], Response::HTTP_CREATED);
    }

    private function requireUser(Security $security): User|Response
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return ApiJsonResponse::error(
                'unauthenticated',
                'Пользователь не аутентифицирован.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $user;
    }

    private function propertyData(Property $property): array
    {
        return [
            'id' => $property->getId(),
            'title' => $property->getTitle(),
            'typeCode' => $property->getTypeCode()->value,
            'status' => $property->getStatus()->value,
            'address' => $property->getAddress(),
            'description' => $property->getDescription(),
            'metadata' => $property->getMetadata(),
            'createdAt' => $property->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $property->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function memberData(PropertyMember $member): array
    {
        return [
            'id' => $member->getId(),
            'role' => $member->getRole()->value,
            'status' => $member->getStatus()->value,
            'fullName' => $member->getFullName(),
            'email' => $member->getEmail(),
            'phone' => $member->getPhone(),
            'linkedUserId' => $member->getUser()?->getId(),
        ];
    }

    private function invitationData(Invitation $invitation): array
    {
        return [
            'id' => $invitation->getId(),
            'code' => $invitation->getCode(),
            'targetEmail' => $invitation->getTargetEmail(),
            'expiresAt' => $invitation->getExpiresAt()->format(DATE_ATOM),
            'claimedAt' => $invitation->getClaimedAt()?->format(DATE_ATOM),
        ];
    }
}
