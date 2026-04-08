<?php

namespace App\Property\Infrastructure\Persistence\Doctrine;

use App\Property\Domain\Entity\Invitation;
use App\Property\Domain\Entity\PropertyMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    public function findOneByCode(string $code): ?Invitation
    {
        return $this->findOneBy(['code' => strtoupper($code)]);
    }

    public function findActiveForMember(PropertyMember $member): ?Invitation
    {
        return $this->createQueryBuilder('invitation')
            ->andWhere('invitation.propertyMember = :member')
            ->andWhere('invitation.claimedAt IS NULL')
            ->andWhere('invitation.expiresAt > :now')
            ->setParameter('member', $member)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('invitation.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
