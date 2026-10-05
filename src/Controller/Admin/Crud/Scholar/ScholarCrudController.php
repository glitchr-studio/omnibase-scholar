<?php

namespace Base\Scholar\Controller\Admin\Crud\Scholar;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Attribute\OpenToAdmins;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\TextareaField;
use Base\Field\TextField;
use Base\Scholar\Entity\Scholar;
use Base\Scholar\Service\Synchronizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Who the site is about: the name and the title, the affiliation, the
 * identifiers, where the works are read from (one "source: author" a
 * line - "openalex: A5108007452", "hal: Keitaro Nakatani"), the pages that
 * are theirs elsewhere, the portrait and the biography. "Sync now" reads
 * the sources at once, as the weekly scholar:sync does.
 */
#[OpenToAdmins(actions: ['sync'])]
class ScholarCrudController extends AbstractCrudController
{
    private Synchronizer $synchronizer;
    private TranslatorInterface $translator;

    #[Required]
    public function setScholarServices(Synchronizer $synchronizer, TranslatorInterface $translator): void
    {
        $this->synchronizer = $synchronizer;
        $this->translator = $translator;
    }

    public static function getEntityFqcn(): string
    {
        return Scholar::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-user-graduate';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', '@scholar.admin.scholar.name')->setColumns(6);
        yield TextField::new('honorific', '@scholar.admin.scholar.honorific')->setColumns(2)->setRequired(false)->hideOnIndex();
        yield TextField::new('givenName', '@scholar.admin.scholar.given_name')->setColumns(2)->setRequired(false)->hideOnIndex();
        yield TextField::new('familyName', '@scholar.admin.scholar.family_name')->setColumns(2)->setRequired(false)->hideOnIndex();
        yield TextField::new('jobTitle', '@scholar.admin.scholar.job_title')->setColumns(6)->setRequired(false);
        yield TextField::new('affiliation', '@scholar.admin.scholar.affiliation')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield TextField::new('affiliationUrl', '@scholar.admin.scholar.affiliation_url')->setColumns(6)->setRequired(false)->hideOnIndex();
        yield TextField::new('orcid', '@scholar.admin.scholar.orcid')->setColumns(3)->setRequired(false);
        yield IntegerField::new('fromYear', '@scholar.admin.scholar.from_year')->setColumns(3)->setRequired(false)->hideOnIndex()->setHelp('@scholar.admin.scholar.from_year_help');
        yield TextareaField::new('profilesText', '@scholar.admin.scholar.profiles')->setRequired(false)->hideOnIndex()->setHelp('@scholar.admin.scholar.profiles_help');
        yield TextareaField::new('sameAsText', '@scholar.admin.scholar.same_as')->setRequired(false)->hideOnIndex()->setHelp('@scholar.admin.scholar.same_as_help');
        yield TextField::new('keywordsText', '@scholar.admin.scholar.keywords')->setRequired(false)->hideOnIndex()->setHelp('@scholar.admin.scholar.keywords_help');
        yield ImageField::new('portrait', '@scholar.admin.scholar.portrait')->setColumns(6)->hideOnIndex();
        yield TextareaField::new('biography', '@scholar.admin.scholar.biography')->setNumOfRows(14)->setRequired(false)->hideOnIndex()->setHelp('@scholar.admin.scholar.biography_help');
        yield BooleanField::new('autoApprove', '@scholar.admin.scholar.auto_approve')->setColumns(6)->setHelp('@scholar.admin.scholar.auto_approve_help');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        foreach ([Actions::PAGE_INDEX, Actions::PAGE_DETAIL] as $page) {
            $actions->add($page, Action::new('sync', '@scholar.admin.scholar.action.sync', 'fa-solid fa-rotate')
                ->linkToCrudAction('sync')
                ->askConfirmation('@scholar.admin.scholar.action.sync_confirm'));
        }

        return $actions;
    }

    /** The scholar's sources read now, as scholar:sync does each week. */
    #[AdminAction('/{entityId}/sync')]
    public function sync(string $entityId): Response
    {
        /** @var Scholar $scholar */
        $scholar = $this->findEntity($entityId);
        $summary = $this->synchronizer->sync($scholar);
        foreach ($summary->errors as $error) {
            $this->addFlash('danger', $error);
        }
        foreach ($summary->incomplete as $source => $why) {
            $this->addFlash('warning', $this->translator->trans('admin.sync.incomplete', ['source' => $source, 'why' => mb_strimwidth($why, 0, 160, '…')], 'scholar'));
        }
        $this->addFlash('success', $this->translator->trans('admin.sync.done', $summary->toArray(), 'scholar'));

        return $this->redirectToIndex();
    }
}
