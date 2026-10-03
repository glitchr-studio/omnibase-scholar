<?php

namespace Base\Scholar\Controller\Admin\Crud\Scholar;

use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\TextField;
use Base\Field\TextareaField;
use Base\Scholar\Entity\Cv\Supervision;
use Base\Scholar\Enum\SupervisionLevel;
use Base\Scholar\Repository\ScholarRepository;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A supervision: the student, the level, the subject, the co-supervisors, what became of it.
 */
class SupervisionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Supervision::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-people-arrows';
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

    private ?TranslatorInterface $translator = null;

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /** @return array<string, string> */
    private function levels(): array
    {
        $levels = [];
        foreach (SupervisionLevel::cases() as $level) {
            $levels[$this->translator?->trans($level->label(), [], 'scholar') ?? $level->value] = $level->value;
        }

        return $levels;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', '@scholar.admin.cv.student')->setColumns(6);
        yield SelectField::new('levelValue', '@scholar.admin.cv.supervision_level')->setChoices($this->levels())->setColumns(3);
        yield TextField::new('organization', '@scholar.admin.cv.organization')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield TextareaField::new('subject', '@scholar.admin.cv.subject')->setRequired(false)->hideOnIndex();
        yield TextField::new('coSupervisors', '@scholar.admin.cv.co_supervisors')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield TextField::new('outcome', '@scholar.admin.cv.outcome')->setColumns(6)->setRequired(false)->hideOnIndex();
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
