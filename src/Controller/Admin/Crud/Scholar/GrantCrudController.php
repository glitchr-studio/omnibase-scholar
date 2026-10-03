<?php

namespace Base\Scholar\Controller\Admin\Crud\Scholar;

use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\IntegerField;
use Base\Field\TextField;
use Base\Field\TextareaField;
use Base\Scholar\Entity\Cv\Grant;
use Base\Scholar\Repository\ScholarRepository;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * A line of the CV: a grant or a funded project - its funder, reference, role, years and amount.
 */
class GrantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Grant::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-hand-holding-dollar';
    }

    private ?ScholarRepository $scholars = null;

    #[Required]
    public function setScholarRepository(ScholarRepository $scholars): void
    {
        $this->scholars = $scholars;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('visible')->add('origin');
    }

    public function createEntity(string $entityFqcn): object
    {
        return new $entityFqcn($this->scholars?->findMain());
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', '@scholar.admin.cv.grant_title')->setColumns(6);
        yield TextField::new('organization', '@scholar.admin.cv.funder')->setColumns(6)->setRequired(false);
        yield TextField::new('reference', '@scholar.admin.cv.reference')->setColumns(4)->setRequired(false)->hideOnIndex();
        yield TextField::new('role', '@scholar.admin.cv.role')->setColumns(4)->setRequired(false)->hideOnIndex();
        yield TextField::new('amount', '@scholar.admin.cv.amount')->setColumns(4)->setRequired(false)->hideOnIndex();
        yield TextField::new('start', '@scholar.admin.cv.start')->setColumns(3)->setRequired(false)->setHelp('@scholar.admin.cv.date_help');
        yield TextField::new('end', '@scholar.admin.cv.end')->setColumns(3)->setRequired(false)->hideOnIndex();
        yield TextField::new('city', '@scholar.admin.cv.city')->setColumns(3)->setRequired(false)->hideOnIndex();
        yield TextField::new('country', '@scholar.admin.cv.country')->setColumns(3)->setRequired(false)->hideOnIndex()->setHelp('@scholar.admin.cv.country_help');
        yield TextareaField::new('description', '@scholar.admin.cv.description')->setRequired(false)->hideOnIndex();
        yield TextField::new('url', '@scholar.admin.cv.url')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield IntegerField::new('position', '@scholar.admin.cv.position')->setColumns(2)->hideOnIndex();
        yield BooleanField::new('visible', '@scholar.admin.cv.visible')->setColumns(2)->setHelp('@scholar.admin.cv.visible_help');
        yield TextField::new('origin', '@scholar.admin.cv.origin')->onlyOnIndex();
    }
}
