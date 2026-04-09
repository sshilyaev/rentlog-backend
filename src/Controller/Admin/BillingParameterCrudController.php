<?php

namespace App\Controller\Admin;

use App\Billing\Domain\Entity\BillingParameter;
use App\Billing\Domain\Enum\BillingCategory;
use App\Billing\Domain\Enum\BillingParameterSourceType;
use App\Property\Domain\Entity\Property;
use App\Property\Infrastructure\Persistence\Doctrine\PropertyRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'billing-parameters', name: 'billing_parameter')]
final class BillingParameterCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return BillingParameter::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Параметр начисления')
            ->setEntityLabelInPlural('Параметры начислений')
            ->setSearchFields(['code', 'title']);
    }

    public function createEntity(string $entityFqcn): BillingParameter
    {
        $property = $this->propertyRepository->findOneBy([], ['createdAt' => 'ASC']);
        if (!$property instanceof Property) {
            throw new \RuntimeException('Сначала создайте объект.');
        }

        return new BillingParameter(
            property: $property,
            meter: null,
            code: 'param-'.substr(uniqid(), -6),
            title: 'Новый параметр',
            category: BillingCategory::Utility,
            sourceType: BillingParameterSourceType::Fixed,
            unit: null
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('property', 'Объект');
        yield AssociationField::new('meter', 'Счётчик');
        yield TextField::new('code', 'Код');
        yield TextField::new('title', 'Название');
        yield ChoiceField::new('category', 'Категория')->setChoices($this->enumChoices(BillingCategory::cases()));
        yield ChoiceField::new('sourceType', 'Источник')->setChoices($this->enumChoices(BillingParameterSourceType::cases()));
        yield TextField::new('unit', 'Единица');
        yield BooleanField::new('isActive', 'Активен');
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
