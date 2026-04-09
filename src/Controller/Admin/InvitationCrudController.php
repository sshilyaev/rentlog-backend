<?php

namespace App\Controller\Admin;

use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use App\Property\Domain\Entity\Invitation;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyMemberRepository;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'invitations', name: 'invitation')]
final class InvitationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyMemberRepository $propertyMemberRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Invitation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Приглашение')
            ->setEntityLabelInPlural('Приглашения')
            ->setSearchFields(['code', 'targetEmail']);
    }

    public function createEntity(string $entityFqcn): Invitation
    {
        $property = $this->propertyRepository->findOneBy([], ['createdAt' => 'ASC']);
        $creator = $this->userRepository->findOneBy([]);

        if (!$property instanceof Property || null === $creator) {
            throw new \RuntimeException('Нужны хотя бы один объект и один пользователь в системе.');
        }

        $members = $this->propertyMemberRepository->findByProperty($property);
        $member = $members[0] ?? null;
        if (!$member instanceof PropertyMember) {
            throw new \RuntimeException('У выбранного объекта нет участников — добавьте участника.');
        }

        return new Invitation(
            property: $property,
            propertyMember: $member,
            createdBy: $creator,
            targetEmail: null,
            expiresAt: new \DateTimeImmutable('+30 days')
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('code', 'Код')->setFormTypeOption('attr', ['readonly' => true]);
        yield AssociationField::new('property', 'Объект');
        yield AssociationField::new('propertyMember', 'Участник');
        yield AssociationField::new('createdBy', 'Кем создано')->autocomplete();
        yield TextField::new('targetEmail', 'Email приглашения');
        yield DateTimeField::new('expiresAt', 'Истекает');
        yield DateTimeField::new('claimedAt', 'Принято');
        yield DateTimeField::new('createdAt', 'Создано')->hideOnForm();
    }
}
