<?php

namespace CommonGateway\PDDBundle\Command;

use App\Entity\Action;
use CommonGateway\PDDBundle\Service\SyncCasesService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This class handles the command for the synchronization of a xxllnc case to a woo object.
 *
 * This Command executes the syncCaseService->syncCasesHandler.
 *
 * @author  Conduction BV <info@conduction.nl>, Barry Brands <barry@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @category Command
 */
class SyncCasesCommand extends Command
{

    /**
     * The actual command.
     *
     * @var static
     */
    protected static $defaultName = 'pdd:case:synchronize';

    /**
     * The case service.
     *
     * @var SyncCasesService
     */
    private SyncCasesService $syncCaseService;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;


    /**
     * Class constructor.
     *
     * @param SyncCasesService $syncCaseService The case service
     */
    public function __construct(SyncCasesService $syncCaseService, EntityManagerInterface $entityManager)
    {
        $this->syncCaseService   = $syncCaseService;
        $this->entityManager = $entityManager;
        parent::__construct();

    }//end __construct()


    /**
     * Configures this command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('This command triggers SyncCasesService')
            ->setHelp('This command triggers SyncCasesService')
            ->addArgument(
                'id',
                InputArgument::OPTIONAL,
                'Case id to fetch from xxllnc'
            );

    }//end configure()


    /**
     * Executes syncCaseService->syncCasesHandler or syncCaseService->getCase if a id is given.
     *
     * @param InputInterface  Handles input from cli
     * @param OutputInterface Handles output from cli
     *
     * @return int 0 for failure, 1 for success
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $this->syncCaseService->setStyle($style);
        $caseId = $input->getArgument('id');

        $action = $this->entityManager->getRepository('App:Action')->findOneBy(['reference' => 'https://commongateway.nl/pdd.SyncCasesAction.action.json']);
        if ($action instanceof Action === null) {
            $style->error('Action with reference https://commongateway.nl/pdd.SyncCasesAction.action.json not found');

            return Command::FAILURE;
        }

        if (isset($caseId) === true
            && Uuid::isValid($caseId) === true
        ) {
            // if ($this->syncCaseService->getZaak($action->getConfiguration(), $caseId) === true) {
            //     return Command::FAILURE;
            // }

            isset($style) === true && $style->info("Succesfully synced and created a WOO object from xxllnc case: $caseId.");

            return Command::SUCCESS;
        }//end if

        if ($this->syncCaseService->syncCasesHandler([], $action->getConfiguration()) === null) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;

    }//end execute()


}//end class
