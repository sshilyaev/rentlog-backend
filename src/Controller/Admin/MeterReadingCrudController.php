<?php

namespace App\Controller\Admin;

use App\Auth\Infrastructure\Persistence\Doctrine\UserRepository;
use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Entity\MeterReading;
use App\Billing\Domain\Enum\MeterReadingType;
use App\Billing\Infrastructure\Persistence\Doctrine\MeterRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'meter-readings', name: 'meter_reading')]
final class MeterReadingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly MeterRepository $meterRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return MeterReading::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Показание')
            ->setEntityLabelInPlural('Показания');
    }

    public function createEntity(string $entityFqcn): MeterReading
    {
        $meter = $this->meterRepository->findOneBy([]);
        $user = $this->userRepository->findOneBy([]);

        if (!$meter instanceof Meter || null === $user) {
            throw new \RuntimeException('Нужны хотя бы один счётчик и один пользователь.');
        }

        return new MeterReading(
            meter: $meter,
            recordedByUser: $user,
            type: MeterReadingType::Monthly,
            value: '0',
            billingYear: (int) date('Y'),
            billingMonth: (int) date('n'),
            comment: null
        );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('meter', 'Счётчик');
        yield AssociationField::new('recordedByUser', 'Кто внёс')->autocomplete();
        yield ChoiceField::new('type', 'Тип')->setChoices($this->enumChoices(MeterReadingType::cases()));
        yield NumberField::new('billingYear', 'Год');
        yield NumberField::new('billingMonth', 'Месяц');
        yield TextField::new('value', 'Значение');
        yield TextareaField::new('comment', 'Комментарий');
        yield DateTimeField::new('recordedAt', 'Записано');
        yield DateTimeField::new('createdAt', 'Создано')->hideOnForm();
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
