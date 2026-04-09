<?php

namespace App\Controller\Admin;

use App\Property\Domain\Entity\Property;
use App\Property\Domain\Enum\PropertyStatus;
use App\Property\Domain\Enum\PropertyType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'properties', name: 'property')]
final class PropertyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Property::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Объект')
            ->setEntityLabelInPlural('Объекты')
            ->setSearchFields(['title', 'address']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Название');
        yield ChoiceField::new('typeCode', 'Тип')->setChoices($this->enumChoices(PropertyType::cases()));
        yield ChoiceField::new('status', 'Статус')->setChoices($this->enumChoices(PropertyStatus::cases()));
        yield TextField::new('address', 'Адрес');
        yield TextareaField::new('description', 'Описание');
        yield ArrayField::new('metadata', 'Метаданные (JSON)');
        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Обновлён')->hideOnForm();
    }

    
    private function enumChoices(array $cases): array
    {
        $out = [];
        foreach ($cases as $case) {
            if (is_object($case) && property_exists($case, 'value') && property_exists($case, 'name')) {
                $out[$case->name] = $case;
            }
        }

        return $out;
    }
}
