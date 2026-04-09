<?php

namespace App\Property\Presentation\Http;

use App\Auth\Domain\Entity\User;
use App\Property\Application\Dto\ClaimInvitationRequestDto;
use App\Property\Application\Service\ClaimInvitationService;
use App\Property\Domain\Entity\Invitation;
use App\Property\Domain\Exception\InvitationAlreadyClaimedException;
use App\Property\Domain\Exception\InvitationEmailMismatchException;
use App\Property\Domain\Exception\InvitationExpiredException;
use App\Property\Domain\Exception\InvitationMemberAlreadyLinkedException;
use App\Property\Domain\Exception\InvitationNotFoundException;
use App\Shared\Presentation\Http\ApiJsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/invitations', name: 'api_v1_invitations_')]
final class InvitationController
{
    #[Route('/claim', name: 'claim', methods: ['POST'])]
    public function claim(
        Request $request,
        Security $security,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ClaimInvitationService $claimInvitationService
    ): Response {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return ApiJsonResponse::error(
                'unauthenticated',
                'Пользователь не аутентифицирован.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            
            $dto = $serializer->deserialize($request->getContent(), ClaimInvitationRequestDto::class, 'json');
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
                'Некорректные данные для принятия приглашения.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $invitation = $claimInvitationService->handle($dto->code, $user);
        } catch (InvitationNotFoundException $exception) {
            return ApiJsonResponse::error('invitation_not_found', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvitationAlreadyClaimedException $exception) {
            return ApiJsonResponse::error('invitation_already_claimed', $exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (InvitationExpiredException $exception) {
            return ApiJsonResponse::error('invitation_expired', $exception->getMessage(), Response::HTTP_GONE);
        } catch (InvitationEmailMismatchException $exception) {
            return ApiJsonResponse::error('invitation_email_mismatch', $exception->getMessage(), Response::HTTP_FORBIDDEN);
        } catch (InvitationMemberAlreadyLinkedException $exception) {
            return ApiJsonResponse::error('invitation_member_already_linked', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiJsonResponse::success([
            'message' => 'Приглашение принято.',
            'invitation' => $this->invitationData($invitation),
            'propertyId' => $invitation->getProperty()->getId(),
            'propertyMemberId' => $invitation->getPropertyMember()->getId(),
        ]);
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
