<?php

namespace Base\Scholar\Controller\Admin\Crud\Scholar;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Attribute\OpenToAdmins;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Field\AssociationField;
use Base\Field\BooleanField;
use Base\Field\FileField;
use Base\Field\IdField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\TextareaField;
use Base\Field\TextField;
use Base\Scholar\Entity\Publication;
use Base\Scholar\Repository\ScholarRepository;
use Omnischolar\Model\WorkType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The publications: what the sources gave (read only, refreshed at each
 * sync) and what the site adds (validated or not, hidden, pinned, in the
 * selection, a PDF, links to buy it or to the publisher, a cover, a note,
 * themes). "Validate" and "Reject" on each one - the dashboard's
 * "Publications to validate" has the same two buttons. A publication typed
 * by hand ("New") is the site's: its title, authors, year and venue are
 * the record.
 */
#[OpenToAdmins(actions: ['approve', 'reject'])]
class PublicationCrudController extends AbstractCrudController
{
    private TranslatorInterface $translator;
    private ScholarRepository $scholars;

    #[Required]
    public function setScholarServices(TranslatorInterface $translator, ScholarRepository $scholars): void
    {
        $this->translator = $translator;
        $this->scholars = $scholars;
    }

    public static function getEntityFqcn(): string
    {
        return Publication::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-book-open';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->setDefaultSort(['year' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('status')->add('type')->add('year')->add('hidden')->add('selected')->add('manual');
    }

    public function configureFields(string $pageName): iterable
    {
        $publication = $this->adminContext->getEntity();
        $manual = !$publication instanceof Publication || $publication->isManual() || null === $publication->getId();
        $types = [];
        foreach (WorkType::cases() as $type) {
            $types[$this->translator->trans('publication.type.'.$type->value, ['count' => 1], 'scholar')] = $type->value;
        }

        yield IdField::new('id')->onlyOnIndex();
        yield SelectField::new('status', '@scholar.admin.publication.status')->setColumns(3); // the enum's cases, each naming itself
        yield IntegerField::new('year', '@scholar.admin.publication.year')->setColumns(2)->setDisabled(!$manual);
        yield SelectField::new('type', '@scholar.admin.publication.type')->setChoices($types)->setColumns(3)->setDisabled(!$manual);
        yield TextField::new('title', '@scholar.admin.publication.title')->setColumns(12)->setDisabled(!$manual);
        yield TextField::new('authors', '@scholar.admin.publication.authors')->setColumns(12)->hideOnIndex()->setRequired(false)->setDisabled(!$manual)->setHelp('@scholar.admin.publication.authors_help');
        yield TextField::new('venue', '@scholar.admin.publication.venue')->setColumns(8)->setRequired(false)->setDisabled(!$manual);
        yield TextField::new('doi', '@scholar.admin.publication.doi')->setColumns(4)->hideOnIndex()->setRequired(false)->setDisabled(!$manual);
        yield IntegerField::new('citations', '@scholar.admin.publication.citations')->onlyOnIndex();

        yield BooleanField::new('hidden', '@scholar.admin.publication.hidden')->setColumns(3);
        yield BooleanField::new('pinned', '@scholar.admin.publication.pinned')->setColumns(3)->hideOnIndex();
        yield BooleanField::new('selected', '@scholar.admin.publication.selected')->setColumns(3);
        yield AssociationField::new('themes', '@scholar.admin.publication.themes')->setColumns(12)->hideOnIndex()->setRequired(false);
        yield FileField::new('pdf', '@scholar.admin.publication.pdf')->setColumns(6)->hideOnIndex()->setHelp('@scholar.admin.publication.pdf_help');
        yield TextField::new('pdfUrl', '@scholar.admin.publication.pdf_url')->setColumns(6)->hideOnIndex()->setRequired(false);
        yield TextField::new('publisherUrl', '@scholar.admin.publication.publisher_url')->setColumns(6)->hideOnIndex()->setRequired(false);
        yield TextField::new('buyUrl', '@scholar.admin.publication.buy_url')->setColumns(6)->hideOnIndex()->setRequired(false);
        yield ImageField::new('cover', '@scholar.admin.publication.cover')->setColumns(6)->hideOnIndex()->setHelp('@scholar.admin.publication.cover_help');
        yield TextareaField::new('note', '@scholar.admin.publication.note')->hideOnIndex()->setRequired(false)->setHelp('@scholar.admin.publication.note_help');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        foreach ([Actions::PAGE_INDEX, Actions::PAGE_DETAIL, Actions::PAGE_EDIT] as $page) {
            $actions->add($page, Action::new('approve', '@scholar.admin.publication.action.approve', 'fa-solid fa-check')->linkToCrudAction('approve'));
            $actions->add($page, Action::new('reject', '@scholar.admin.publication.action.reject', 'fa-solid fa-ban')->linkToCrudAction('reject'));
        }

        return $actions;
    }

    public function createEntity(string $entityFqcn): object
    {
        // Typed by hand: the site's own record, online once saved.
        return (new Publication($this->scholars->findMain()))->setManual(true)->approve();
    }

    #[AdminAction('/{entityId}/approve')]
    public function approve(Request $request, string $entityId): Response
    {
        return $this->decide($request, $entityId, true);
    }

    #[AdminAction('/{entityId}/reject')]
    public function reject(Request $request, string $entityId): Response
    {
        return $this->decide($request, $entityId, false);
    }

    private function decide(Request $request, string $entityId, bool $approve): Response
    {
        /** @var Publication $publication */
        $publication = $this->findEntity($entityId);
        $approve ? $publication->approve() : $publication->reject();
        $this->entityManager->flush();
        $this->addFlash('success', $this->translator->trans($approve ? 'admin.publication.flash.approved' : 'admin.publication.flash.rejected', ['title' => mb_strimwidth((string) $publication->getTitle(), 0, 80, '…')], 'scholar'));

        // Back where the button was: the dashboard's widget, or the list.
        $back = (string) $request->headers->get('referer');
        if ('' !== $back && parse_url($back, \PHP_URL_HOST) === $request->getHost()) {
            return $this->redirect($back);
        }

        return $this->redirectToIndex();
    }
}
