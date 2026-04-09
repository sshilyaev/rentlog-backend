<?php

namespace App\Controller\Admin;

use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Enum\MeterUnit;
use App\Property\Domain\Entity\Property;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'meters', name: 'meter')]
final class MeterCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Meter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Счётчик')
            ->setEntityLabelInPlural('Счётчики')
            ->setSearchFields(['code', 'title']);
    }

    public function createEntity(string $entityFqcn): Meter
    {
        $property = $this->propertyRepository->findOneBy([], ['createdAt' => 'ASC']);
        if (!$property instanceof Property) {
            throw new \RuntimeException('Сначала создайте объект.');
        }

        return new Meter(
            property: $property,
            code: 'meter-'.substr(uniqid(), -6),
            title: 'Новый счётчик',
            unit: MeterUnit::CubicMeter
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('property', 'Объект');
        yield TextField::new('code', 'Код');
        yield TextField::new('title', 'Название');
        yield ChoiceField::new('unit', 'Единица измерения')->setChoices(MeterUnit::choicesForForms());
        yield BooleanField::new('isActive', 'Активен');
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('updatedAt')->hideOnForm();
    }
}
