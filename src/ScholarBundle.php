<?php

namespace Base\Scholar;

use Base\Bundle\AbstractBaseBundle;
use Base\Traits\SingletonTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * A researcher's site: who they are (Scholar), what they published
 * (Publication: a glitchr/omnischolar Work kept here, with what the site
 * adds to it), their CV (Cv\Position, Degree, Award, Grant, Course,
 * Supervision) and their research themes and projects (Theme). The works
 * are read again each week by scholar:sync (the cron container); a new one
 * waits in the back office until it is validated. Talks are omnibase/agenda
 * Events, services omnibase/consulting Offerings.
 */
class ScholarBundle extends AbstractBaseBundle
{
    use SingletonTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /** Modern layout: the class lives in src/, the bundle root is the package root. */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->setMapping($this->getPath().'/src/Entity', 'Base\Scholar\Entity', 'App\Entity\Scholar');
        $this->setMapping($this->getPath().'/src/Repository', 'Base\Scholar\Repository', 'App\Repository\Scholar');
    }
}
