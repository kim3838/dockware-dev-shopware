<?php declare(strict_types=1);

namespace MyPlugin\Command;

use Symfony\Component\Console\Helper\Table;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'swag-commands:example',
    description: 'Add a short description for your command',
)]
class ExampleCommand extends Command
{
    private $productEntityRepository;

    public function __construct(EntityRepository $productEntityRepository)
    {
        parent::__construct(null);
        $this->productEntityRepository = $productEntityRepository;
    }

    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setDescription('Does something very special.');
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $searchResult = $this->productEntityRepository->search(new Criteria(), Context::createDefaultContext());

        $table = new Table($output);
        $table->setHeaders(['UUID','Name']);

        foreach ($searchResult->getEntities() as $entity) {
            $table->addRow([
                $entity->getId(),
                $entity->getName()
            ]);
        }

        $table->render();

        return 0;
    }
}
