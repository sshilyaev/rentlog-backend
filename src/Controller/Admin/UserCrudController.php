<?php

namespace App\Controller\Admin;

use App\Auth\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[\EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute(path: 'users', name: 'user')]
final class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Пользователь')
            ->setEntityLabelInPlural('Пользователи')
            ->setSearchFields(['email', 'fullName']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email');
        yield TextField::new('fullName', 'ФИО');
        yield ArrayField::new('roles', 'Роли');
        yield TextField::new('plainPassword', 'Пароль')
            ->setFormType(PasswordType::class)
            ->onlyOnForms()
            ->setRequired(Crud::PAGE_NEW === $pageName)
            ->hideOnIndex();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->applyPasswordHash($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->applyPasswordHash($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function applyPasswordHash(object $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        if ($entityInstance->plainPassword !== null && $entityInstance->plainPassword !== '') {
            $entityInstance->updatePassword(
                $this->passwordHasher->hashPassword($entityInstance, $entityInstance->plainPassword)
            );
        }

        $entityInstance->plainPassword = null;
    }
}
