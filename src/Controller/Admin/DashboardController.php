<?php

namespace App\Controller\Admin;

use App\Auth\Domain\Entity\User;
use App\Billing\Domain\Entity\BillingParameter;
use App\Billing\Domain\Entity\Meter;
use App\Billing\Domain\Entity\MeterReading;
use App\Billing\Domain\Entity\TariffPeriod;
use App\Property\Domain\Entity\Invitation;
use App\Property\Domain\Entity\Property;
use App\Property\Domain\Entity\PropertyMember;
use App\Rent\Domain\Entity\RentTerms;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // EasyAdmin 5's parent::index() only renders the default welcome page; send admins to a real CRUD.
        $adminUrlGenerator = $this->container->get(AdminUrlGeneratorInterface::class);

        return $this->redirect(
            $adminUrlGenerator
                ->setDashboard(self::class)
                ->setController(UserCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Rentlog Admin')
            ->setFaviconPath('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>🏠</text></svg>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Главная', 'fa fa-home');

        yield MenuItem::section('Учётные записи');
        yield MenuItem::linkToCrud('Пользователи', 'fa fa-users', User::class);

        yield MenuItem::section('Объекты и участники');
        yield MenuItem::linkToCrud('Объекты', 'fa fa-building', Property::class);
        yield MenuItem::linkToCrud('Участники объектов', 'fa fa-user-friends', PropertyMember::class);
        yield MenuItem::linkToCrud('Приглашения', 'fa fa-envelope', Invitation::class);

        yield MenuItem::section('Аренда и биллинг');
        yield MenuItem::linkToCrud('Условия аренды', 'fa fa-file-contract', RentTerms::class);
        yield MenuItem::linkToCrud('Счётчики', 'fa fa-tachometer-alt', Meter::class);
        yield MenuItem::linkToCrud('Показания', 'fa fa-chart-line', MeterReading::class);
        yield MenuItem::linkToCrud('Параметры начислений', 'fa fa-sliders-h', BillingParameter::class);
        yield MenuItem::linkToCrud('Тарифные периоды', 'fa fa-calendar-alt', TariffPeriod::class);
    }
}
