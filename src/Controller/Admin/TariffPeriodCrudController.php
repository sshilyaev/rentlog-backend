<?php

namespace App\Controller\Admin;

use App\Billing\Domain\Entity\BillingParameter;
use App\Billing\Domain\Entity\TariffPeriod;
use App\Billing\Domain\Enum\TariffPricingType;
use App\Billing\Infrastructure\Persistence\Doctrine\BillingParameterRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'tariff-periods', name: 'tariff_period')]
final class TariffPeriodCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly BillingParameterRepository $billingParameterRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return TariffPeriod::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Тарифный период')
            ->setEntityLabelInPlural('Тарифные периоды');
    }

    public function createEntity(string $entityFqcn): TariffPeriod
    {
        $param = $this->billingParameterRepository->findOneBy([], ['createdAt' => 'ASC']);
        if (!$param instanceof BillingParameter) {
            throw new \RuntimeException('Сначала создайте параметр начисления.');
        }

        return new TariffPeriod(
            billingParameter: $param,
            pricingType: TariffPricingType::PerUnit,
            price: '0.00',
            currency: 'RUB',
            effectiveFrom: new \DateTimeImmutable('first day of January this year'),
            effectiveTo: null
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('billingParameter', 'Параметр');
        yield ChoiceField::new('pricingType', 'Тип цены')->setChoices($this->enumChoices(TariffPricingType::cases()));
        yield TextField::new('price', 'Цена');
        yield TextField::new('currency', 'Валюта');
        yield DateField::new('effectiveFrom', 'С даты');
        yield DateField::new('effectiveTo', 'По дату');
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
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
