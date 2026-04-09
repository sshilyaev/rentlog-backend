<?php

namespace App\Controller\Admin;

use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Property\Domain\Enum\PropertyMemberRole;
use App\Property\Domain\Enum\PropertyMemberStatus;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'property-members', name: 'property_member')]
final class PropertyMemberCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PropertyMember::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Участник объекта')
            ->setEntityLabelInPlural('Участники объектов')
            ->setSearchFields(['fullName', 'email', 'phone']);
    }

    public function createEntity(string $entityFqcn): PropertyMember
    {
        $property = $this->propertyRepository->findOneBy([], ['createdAt' => 'ASC']);
        if (!$property instanceof Property) {
            throw new \RuntimeException('Сначала создайте хотя бы один объект (Объекты).');
        }

        return new PropertyMember(
            property: $property,
            user: null,
            role: PropertyMemberRole::Tenant,
            status: PropertyMemberStatus::Placeholder,
            fullName: 'Новый участник',
            email: null,
            phone: null
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('property', 'Объект');
        yield AssociationField::new('user', 'Пользователь')->autocomplete();
        yield ChoiceField::new('role', 'Роль')->setChoices($this->enumChoices(PropertyMemberRole::cases()));
        yield ChoiceField::new('status', 'Статус')->setChoices($this->enumChoices(PropertyMemberStatus::cases()));
        yield TextField::new('fullName', 'ФИО');
        yield TextField::new('email', 'Email');
        yield TextField::new('phone', 'Телефон');
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('updatedAt')->hideOnForm();
    }

    
    private function enumChoices(array $cases): array
    {
        $out = [];
        foreach ($cases as $case) {
            if (is_object($case) && property_exists($case, 'name')) {
                $out[$case->name] = $case;
            }
        }

        return $out;
    }
}
