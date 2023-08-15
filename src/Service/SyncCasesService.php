<?php

namespace CommonGateway\PDDBundle\Service;

use App\Service\SynchronizationService;
use CommonGateway\CoreBundle\Service\CallService;
use CommonGateway\CoreBundle\Service\GatewayResourceService;
use CommonGateway\CoreBundle\Service\MappingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class SyncCasesService
{

    private GatewayResourceService $resourceService;

    private CallService $callService;

    private SynchronizationService $syncService;

    private array $data;

    private array $configuration;


    public function __construct(
        EntityManagerInterface $entityManager,
        GatewayResourceService $resourceService,
        CallService $callService,
        SynchronizationService $syncService,
        MappingService $mappingService
    ) {
        $this->entityManager   = $entityManager;
        $this->resourceService = $resourceService;
        $this->callService     = $callService;
        $this->syncService     = $syncService;
        $this->mappingService  = $mappingService;

    }//end __construct()


    /**
     * Handles the synchronization of xxllnc cases.
     *
     * @param array $data
     * @param array $configuration
     *
     * @throws CacheException|InvalidArgumentException
     *
     * @return array
     */
    public function syncCasesHandler(array $data, array $configuration): array
    {
        $this->data          = $data;
        $this->configuration = $configuration;

        $source  = $this->resourceService->getSource('https://commongateway.woo.nl/source/noordwijk.zaaksysteem.source.json', 'common-gateway/pdd-bundle');
        $schema  = $this->resourceService->getSchema('https://commongateway.nl/mapping/pdd.openWOO.schema.json', 'common-gateway/pdd-bundle');
        $mapping = $this->resourceService->getMapping('https://commongateway.nl/pdd.xxllncCaseToWoo.schema.json', 'common-gateway/pdd-bundle');

        $sourceConfig = $source->getConfiguration();

        $response = $this->callService->getAllResults(
            $source,
            '/cases',
            $sourceConfig
        );

        $responseItems = [];
        foreach ($response as $result) {
            $synchronization = $this->syncService->findSyncBySource($source, $schema, $result['id']);
            $synchronization->setMapping($mapping);
            $synchronization = $this->syncService->synchronize($synchronization, $result);

            $responseItems[] = $synchronization->getObject()->toArray();
        }

        $this->data['response'] = new Response(json_encode($responseItems), 200);

        return $this->data;

    }//end syncCasesHandler()


}//end class
