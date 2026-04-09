<?php

namespace App\Controller\Admin;

use App\Property\Domain\Entity\Property;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;
use App\Rent\Domain\Entity\RentTerms;
use App\Rent\Domain\Enum\RentTermsStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'rent-terms', name: 'rent_terms')]
final class RentTermsCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RentTerms::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Условия аренды')
            ->setEntityLabelInPlural('Условия аренды');
    }

    public function createEntity(string $entityFqcn): RentTerms
    {
        $property = $this->propertyRepository->findOneBy([], ['createdAt' => 'ASC']);
        if (!$property instanceof Property) {
            throw new \RuntimeException('Сначала создайте объект.');
        }

        return new RentTerms(
            property: $property,
            propertyMember: null,
            baseRentAmount: '0.00',
            currency: 'RUB',
            billingDay: 5,
            startsAt: new \DateTimeImmutable('first day of this month'),
            endsAt: null,
            notes: null,
            status: RentTermsStatus::Active
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('property', 'Объект');
        yield AssociationField::new('propertyMember', 'Участник (персонально)');
        yield TextField::new('baseRentAmount', 'Аренда');
        yield TextField::new('currency', 'Валюта');
        yield NumberField::new('billingDay', 'Число оплаты');
        yield DateField::new('startsAt', 'Начало');
        yield DateField::new('endsAt', 'Окончание');
        yield TextareaField::new('notes', 'Заметки');
        yield ChoiceField::new('status', 'Статус')->setChoices($this->enumChoices(RentTermsStatus::cases()));
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
