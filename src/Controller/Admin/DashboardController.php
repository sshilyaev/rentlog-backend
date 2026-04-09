<?php

namespace App\Controller\Admin;

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
        yield MenuItem::linkTo(UserCrudController::class, 'Пользователи', 'fa fa-users');

        yield MenuItem::section('Объекты и участники');
        yield MenuItem::linkTo(PropertyCrudController::class, 'Объекты', 'fa fa-building');
        yield MenuItem::linkTo(PropertyMemberCrudController::class, 'Участники объектов', 'fa fa-user-friends');
        yield MenuItem::linkTo(InvitationCrudController::class, 'Приглашения', 'fa fa-envelope');

        yield MenuItem::section('Аренда и биллинг');
        yield MenuItem::linkTo(RentTermsCrudController::class, 'Условия аренды', 'fa fa-file-contract');
        yield MenuItem::linkTo(MeterCrudController::class, 'Счётчики', 'fa fa-tachometer-alt');
        yield MenuItem::linkTo(MeterReadingCrudController::class, 'Показания', 'fa fa-chart-line');
        yield MenuItem::linkTo(BillingParameterCrudController::class, 'Параметры начислений', 'fa fa-sliders-h');
        yield MenuItem::linkTo(TariffPeriodCrudController::class, 'Тарифные периоды', 'fa fa-calendar-alt');
    }
}
