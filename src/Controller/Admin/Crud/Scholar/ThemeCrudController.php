<?php

namespace Base\Scholar\Controller\Admin\Crud\Scholar;

use Base\Admin\Attribute\OpenToAdmins;
use Base\Admin\Controller\AbstractCrudController;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\SlugField;
use Base\Field\TextareaField;
use Base\Field\TextField;
use Base\Scholar\Entity\Theme;

/**
 * The research themes and the projects: a page each (/research/{slug}),
 * the publications filed under it (chosen on each publication).
 */
#[OpenToAdmins]
class ThemeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Theme::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-flask';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield SelectField::new('kind', '@scholar.admin.theme.kind')->setColumns(3); // the enum's cases, each naming itself
        yield TextField::new('title', '@scholar.admin.theme.title')->setColumns(9);
        yield SlugField::new('slug')->setColumns(6)->hideOnIndex();
        yield TextareaField::new('summary', '@scholar.admin.theme.summary')->setRequired(false)->hideOnIndex();
        yield TextareaField::new('description', '@scholar.admin.theme.description')->setNumOfRows(12)->setRequired(false)->hideOnIndex();
        yield ImageField::new('image', '@scholar.admin.theme.image')->setColumns(6)->hideOnIndex();
        yield IntegerField::new('startYear', '@scholar.admin.theme.start_year')->setColumns(2)->setRequired(false)->hideOnIndex();
        yield IntegerField::new('endYear', '@scholar.admin.theme.end_year')->setColumns(2)->setRequired(false)->hideOnIndex();
        yield TextField::new('funder', '@scholar.admin.theme.funder')->setColumns(4)->setRequired(false)->hideOnIndex();
        yield TextField::new('url', '@scholar.admin.theme.url')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield IntegerField::new('position', '@scholar.admin.theme.position')->setColumns(2);
        yield BooleanField::new('visible', '@scholar.admin.theme.visible')->setColumns(2);
    }
}
