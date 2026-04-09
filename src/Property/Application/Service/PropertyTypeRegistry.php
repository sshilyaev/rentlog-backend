<?php

namespace App\Property\Application\Service;

use App\Property\Domain\Enum\PropertyType;

final class PropertyTypeRegistry
{
    
    public function all(): array
    {
        return [
            [
                'code' => PropertyType::Apartment->value,
                'label' => 'Квартира',
                'notes' => 'Базовый тип для жилой аренды в многоквартирных домах.',
            ],
            [
                'code' => PropertyType::House->value,
                'label' => 'Дом',
                'notes' => 'Подходит для частных домов и коттеджей.',
            ],
            [
                'code' => PropertyType::LandPlot->value,
                'label' => 'Участок',
                'notes' => 'Подходит для земли и площадок без типовой жилой структуры.',
            ],
            [
                'code' => PropertyType::Garage->value,
                'label' => 'Гараж',
                'notes' => 'В будущем может иметь отдельные правила документов и параметров.',
            ],
            [
                'code' => PropertyType::Office->value,
                'label' => 'Офис',
                'notes' => 'В будущем может иметь отдельные виды оплат и документов.',
            ],
            [
                'code' => PropertyType::Other->value,
                'label' => 'Другое',
                'notes' => 'Резервный тип для нетиповых объектов.',
            ],
        ];
    }
}
